<?php

namespace App\Http\Controllers;

use App\Services\SessionSecurityService;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SecurityController extends Controller
{
    protected SessionSecurityService $sessionService;

    protected TwoFactorAuthService $twoFactorService;

    public function __construct(
        SessionSecurityService $sessionService,
        TwoFactorAuthService $twoFactorService
    ) {
        $this->sessionService = $sessionService;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Display security dashboard.
     */
    public function dashboard()
    {
        $user = auth()->user();

        $securityMetrics = [
            'active_sessions' => $this->sessionService->getActiveSessions($user),
            'concurrent_sessions' => $this->sessionService->hasConcurrentSessions($user),
            'session_time_remaining' => $this->sessionService->getTimeRemaining(),
            'two_factor_enabled' => $this->twoFactorService->isEnabled($user),
            'two_factor_required' => $this->twoFactorService->isRequiredForUser($user),
            'recent_logins' => $this->getRecentLogins($user),
            'security_events' => $this->getRecentSecurityEvents($user),
        ];

        return Inertia::render('Security/Dashboard', [
            'metrics' => $securityMetrics,
            'config' => [
                'session_timeout' => config('security.session.absolute_timeout', 480),
                'two_factor_qr_size' => config('security.2fa.qr_code_size', 200),
            ],
        ]);
    }

    /**
     * Enable two-factor authentication.
     */
    public function enableTwoFactor(Request $request)
    {
        $user = auth()->user();
        $secret = $request->input('secret');
        $code = $request->input('code');

        if (! $this->twoFactorService->verifyTotpCode($secret, $code)) {
            return back()->withErrors(['code' => 'Invalid verification code']);
        }

        $this->twoFactorService->enableTwoFactorAuth($user, $secret);

        Log::info('Two-factor authentication enabled', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);

        return back()->with('success', 'Two-factor authentication enabled successfully');
    }

    /**
     * Disable two-factor authentication.
     */
    public function disableTwoFactor(Request $request)
    {
        $user = auth()->user();
        $password = $request->input('password');

        if (! Hash::check($password, $user->password)) {
            return back()->withErrors(['password' => 'Invalid password']);
        }

        $this->twoFactorService->disableTwoFactorAuth($user);

        Log::info('Two-factor authentication disabled', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);

        return back()->with('success', 'Two-factor authentication disabled successfully');
    }

    /**
     * Generate new secret for 2FA setup.
     */
    public function generateTwoFactorSecret()
    {
        $user = auth()->user();
        $secret = $this->twoFactorService->generateSecretKey();
        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    /**
     * Get backup codes for 2FA.
     */
    public function getBackupCodes()
    {
        $user = auth()->user();

        if (! $this->twoFactorService->isEnabled($user)) {
            return response()->json(['error' => 'Two-factor authentication is not enabled'], 400);
        }

        $backupCodes = $this->twoFactorService->generateBackupCodes();

        // Store hashed backup codes
        $user->two_factor_backup_codes = array_map(function ($code) {
            return hash('sha256', $code);
        }, $backupCodes);
        $user->save();

        return response()->json(['backup_codes' => $backupCodes]);
    }

    /**
     * Invalidate specific session.
     */
    public function invalidateSession(Request $request)
    {
        $sessionId = $request->input('session_id');
        $user = auth()->user();

        // Check if session belongs to user
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        // Delete session
        DB::table('sessions')->where('id', $sessionId)->delete();

        Log::info('Session invalidated by user', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip' => request()->ip(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Invalidate all other sessions.
     */
    public function invalidateAllSessions()
    {
        $user = auth()->user();
        $currentSessionId = session()->getId();

        // Delete all sessions except current
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        Log::info('All other sessions invalidated by user', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get recent login attempts.
     */
    protected function getRecentLogins($user): array
    {
        // This would typically come from audit logs
        return [
            [
                'timestamp' => now()->subHours(2),
                'ip' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'success' => true,
            ],
            [
                'timestamp' => now()->subDays(1),
                'ip' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'success' => true,
            ],
        ];
    }

    /**
     * Get recent security events.
     */
    protected function getRecentSecurityEvents($user): array
    {
        // This would typically come from security logs
        return [
            [
                'timestamp' => now()->subHours(1),
                'event' => 'Session extended',
                'ip' => '192.168.1.100',
                'severity' => 'low',
            ],
            [
                'timestamp' => now()->subDays(1),
                'event' => 'Two-factor authentication enabled',
                'ip' => '192.168.1.100',
                'severity' => 'medium',
            ],
        ];
    }
}
