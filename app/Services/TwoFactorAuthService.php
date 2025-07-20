<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    protected int $window;

    protected int $backupCodesCount;

    public function __construct()
    {
        $this->window = config('security.2fa.window', 1);
        $this->backupCodesCount = config('security.2fa.backup_codes_count', 8);
    }

    /**
     * Generate a new secret key for the user.
     */
    public function generateSecretKey(): string
    {
        return $this->generateRandomSecret();
    }

    /**
     * Generate QR code URL for the secret.
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        $appName = config('app.name');
        $email = $user->email;

        $otpauth = "otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";

        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($otpauth);
    }

    /**
     * Verify TOTP code.
     */
    public function verifyTotpCode(string $secret, string $code): bool
    {
        $timeSlice = floor(time() / 30);

        // Check current time slice and adjacent ones (window)
        for ($i = -$this->window; $i <= $this->window; $i++) {
            if ($this->generateTotpCode($secret, $timeSlice + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate backup codes for the user.
     */
    public function generateBackupCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < $this->backupCodesCount; $i++) {
            $codes[] = $this->generateBackupCode();
        }

        return $codes;
    }

    /**
     * Verify backup code.
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        $backupCodes = $user->two_factor_backup_codes ?? [];

        foreach ($backupCodes as $index => $hashedCode) {
            if (hash_equals($hashedCode, hash('sha256', $code))) {
                // Remove used backup code
                unset($backupCodes[$index]);
                $user->two_factor_backup_codes = array_values($backupCodes);
                $user->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Enable 2FA for user.
     */
    public function enableTwoFactorAuth(User $user, string $secret): void
    {
        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_enabled = true;
        $user->two_factor_backup_codes = $this->hashBackupCodes($this->generateBackupCodes());
        $user->save();
    }

    /**
     * Disable 2FA for user.
     */
    public function disableTwoFactorAuth(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->two_factor_backup_codes = [];
        $user->save();
    }

    /**
     * Check if 2FA is enabled for user.
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_enabled && ! empty($user->two_factor_secret);
    }

    /**
     * Check if 2FA is required for user's role.
     */
    public function isRequiredForUser(User $user): bool
    {
        $requiredRoles = config('security.2fa.required_for_roles', []);

        if (empty($requiredRoles)) {
            return false;
        }

        return $user->hasAnyRole($requiredRoles);
    }

    /**
     * Generate a random secret for TOTP.
     */
    protected function generateRandomSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    /**
     * Generate TOTP code for given secret and time slice.
     */
    protected function generateTotpCode(string $secret, int $timeSlice): string
    {
        // Convert secret from base32
        $key = $this->base32Decode($secret);

        // Convert time slice to binary
        $time = pack('N*', 0, $timeSlice);

        // Generate hash
        $hash = hash_hmac('sha1', $time, $key, true);

        // Extract dynamic binary code
        $offset = ord($hash[19]) & 0xF;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, 6);

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a backup code.
     */
    protected function generateBackupCode(): string
    {
        return strtoupper(Str::random(8));
    }

    /**
     * Hash backup codes for storage.
     */
    protected function hashBackupCodes(array $codes): array
    {
        return array_map(function ($code) {
            return hash('sha256', $code);
        }, $codes);
    }

    /**
     * Decode base32 string.
     */
    protected function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $paddingChars = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];

        if (! in_array($paddingChars, $allowedValues)) {
            return '';
        }

        for ($i = 0; $i < 4; $i++) {
            if ($paddingChars == $allowedValues[$i] && substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return '';
            }
        }

        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';

        for ($i = 0; $i < count($secret); $i += 8) {
            $x = '';
            if (! in_array($secret[$i], str_split($base32chars))) {
                return '';
            }

            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(strpos($base32chars, $secret[$i + $j] ?? ''), 10, 2), 5, '0', STR_PAD_LEFT);
            }

            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }

        return $binaryString;
    }
}
