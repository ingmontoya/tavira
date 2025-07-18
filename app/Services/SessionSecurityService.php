<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SessionSecurityService
{
    protected int $absoluteTimeout;
    protected int $timeoutWarning;
    
    public function __construct()
    {
        $this->absoluteTimeout = config('security.session.absolute_timeout', 480) * 60; // Convert to seconds
        $this->timeoutWarning = config('security.session.timeout_warning', 5) * 60; // Convert to seconds
    }
    
    /**
     * Initialize secure session for user.
     */
    public function initializeSecureSession(User $user): void
    {
        // Regenerate session ID
        Session::regenerate();
        
        // Set session security data
        Session::put('user_id', $user->id);
        Session::put('user_ip', request()->ip());
        Session::put('user_agent', request()->userAgent());
        Session::put('login_time', now()->timestamp);
        Session::put('last_activity', now()->timestamp);
        
        // Set absolute timeout
        Session::put('absolute_timeout', now()->timestamp + $this->absoluteTimeout);
        
        // Log session creation
        Log::info('Secure session initialized', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => Session::getId(),
        ]);
    }
    
    /**
     * Update session activity.
     */
    public function updateActivity(): void
    {
        if (Session::has('user_id')) {
            Session::put('last_activity', now()->timestamp);
        }
    }
    
    /**
     * Check if session is valid.
     */
    public function isSessionValid(): bool
    {
        if (!Session::has('user_id')) {
            return false;
        }
        
        // Check absolute timeout
        if ($this->isAbsoluteTimeoutExpired()) {
            $this->invalidateSession('absolute_timeout');
            return false;
        }
        
        // Check IP address consistency
        if (!$this->isIpAddressConsistent()) {
            $this->invalidateSession('ip_change');
            return false;
        }
        
        // Check user agent consistency
        if (!$this->isUserAgentConsistent()) {
            $this->invalidateSession('user_agent_change');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if session is about to expire.
     */
    public function isAboutToExpire(): bool
    {
        if (!Session::has('absolute_timeout')) {
            return false;
        }
        
        $absoluteTimeout = Session::get('absolute_timeout');
        $currentTime = now()->timestamp;
        
        return ($absoluteTimeout - $currentTime) <= $this->timeoutWarning;
    }
    
    /**
     * Get time remaining until session expires.
     */
    public function getTimeRemaining(): int
    {
        if (!Session::has('absolute_timeout')) {
            return 0;
        }
        
        $absoluteTimeout = Session::get('absolute_timeout');
        $currentTime = now()->timestamp;
        
        return max(0, $absoluteTimeout - $currentTime);
    }
    
    /**
     * Extend session timeout.
     */
    public function extendSession(): void
    {
        if (Session::has('user_id')) {
            Session::put('absolute_timeout', now()->timestamp + $this->absoluteTimeout);
            Session::put('last_activity', now()->timestamp);
        }
    }
    
    /**
     * Invalidate session securely.
     */
    public function invalidateSession(string $reason = 'manual'): void
    {
        $userId = Session::get('user_id');
        $sessionId = Session::getId();
        
        // Log session invalidation
        Log::info('Session invalidated', [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'reason' => $reason,
            'ip' => request()->ip(),
        ]);
        
        // Clear session data
        Session::flush();
        Session::regenerate();
        
        // Clear user sessions from database
        if ($userId) {
            DB::table('sessions')
                ->where('user_id', $userId)
                ->delete();
        }
    }
    
    /**
     * Invalidate all sessions for user.
     */
    public function invalidateAllUserSessions(User $user): void
    {
        // Clear all database sessions
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();
        
        // Clear cache if used
        Cache::forget("user_sessions_{$user->id}");
        
        Log::info('All user sessions invalidated', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);
    }
    
    /**
     * Get active sessions for user.
     */
    public function getActiveSessions(User $user): array
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->select('id', 'ip_address', 'user_agent', 'last_activity')
            ->get();
        
        return $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $session->last_activity,
                'is_current' => $session->id === Session::getId(),
            ];
        })->toArray();
    }
    
    /**
     * Check if user has concurrent sessions.
     */
    public function hasConcurrentSessions(User $user): bool
    {
        $sessionCount = DB::table('sessions')
            ->where('user_id', $user->id)
            ->count();
        
        return $sessionCount > 1;
    }
    
    /**
     * Check if absolute timeout has expired.
     */
    protected function isAbsoluteTimeoutExpired(): bool
    {
        if (!Session::has('absolute_timeout')) {
            return false;
        }
        
        $absoluteTimeout = Session::get('absolute_timeout');
        $currentTime = now()->timestamp;
        
        return $currentTime >= $absoluteTimeout;
    }
    
    /**
     * Check if IP address is consistent.
     */
    protected function isIpAddressConsistent(): bool
    {
        if (!Session::has('user_ip')) {
            return false;
        }
        
        $sessionIp = Session::get('user_ip');
        $currentIp = request()->ip();
        
        return $sessionIp === $currentIp;
    }
    
    /**
     * Check if user agent is consistent.
     */
    protected function isUserAgentConsistent(): bool
    {
        if (!Session::has('user_agent')) {
            return false;
        }
        
        $sessionUserAgent = Session::get('user_agent');
        $currentUserAgent = request()->userAgent();
        
        return $sessionUserAgent === $currentUserAgent;
    }
}