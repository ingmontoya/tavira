<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Security personnel model for police, security companies, etc.
 * Stored in central database (not tenant-specific)
 *
 * Status flow:
 * 1. pending_email_verification - Just registered, needs to verify email
 * 2. pending_admin_approval - Email verified, waiting for admin approval
 * 3. active - Approved and can use the system
 * 4. rejected - Admin rejected the registration
 * 5. suspended - Admin suspended the account
 */
class SecurityPersonnel extends Authenticatable
{
    use HasUuids, HasApiTokens;

    /**
     * Use the central database connection.
     * SecurityPersonnel are stored centrally, not per tenant.
     */
    protected $connection = 'central';

    protected $table = 'security_personnel';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'organization_type',
        'organization_name',
        'id_number',
        'password',
        'status',
        'accept_terms',
        'accept_location_tracking',
        'email_verification_token',
        'email_verified_at',
        'admin_approved_at',
        'admin_approved_by',
        'rejection_reason',
    ];

    protected $hidden = [
        'password',
        'email_verification_token',
    ];

    protected $casts = [
        'accept_terms' => 'boolean',
        'accept_location_tracking' => 'boolean',
        'email_verified_at' => 'datetime',
        'admin_approved_at' => 'datetime',
    ];

    /**
     * Generate email verification token
     */
    public function generateEmailVerificationToken(): string
    {
        $this->email_verification_token = Str::random(64);
        $this->save();
        return $this->email_verification_token;
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): bool
    {
        if ($this->email_verification_token === $token) {
            $this->email_verified_at = now();
            $this->email_verification_token = null;
            $this->status = 'pending_admin_approval';
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Check if email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Approve by admin
     */
    public function approve(string $adminId): void
    {
        $this->admin_approved_at = now();
        $this->admin_approved_by = $adminId;
        $this->status = 'active';
        $this->rejection_reason = null;
        $this->save();
    }

    /**
     * Reject by admin
     */
    public function reject(string $adminId, string $reason): void
    {
        $this->admin_approved_by = $adminId;
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * Suspend account
     */
    public function suspend(string $adminId, string $reason): void
    {
        $this->admin_approved_by = $adminId;
        $this->status = 'suspended';
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * Check if the personnel is active (fully approved)
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the personnel is pending email verification
     */
    public function isPendingEmailVerification(): bool
    {
        return $this->status === 'pending_email_verification';
    }

    /**
     * Check if the personnel is pending admin approval
     */
    public function isPendingAdminApproval(): bool
    {
        return $this->status === 'pending_admin_approval';
    }

    /**
     * Check if the personnel was rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the personnel is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if can login (active only)
     */
    public function canLogin(): bool
    {
        return $this->isActive();
    }
}
