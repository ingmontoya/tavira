<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EmailSettings extends Settings
{
    // SMTP Configuration
    public string $smtp_host = '';

    public int $smtp_port = 587;

    public string $smtp_username = '';

    public string $smtp_password = '';

    public string $smtp_encryption = 'none'; // tls, ssl, or none

    public int $smtp_timeout = 30;

    // Email Accounts Configuration
    public string $admin_email_address = '';

    public string $admin_email_name = 'Administración';

    public string $admin_email_signature = '';

    public string $council_email_address = '';

    public string $council_email_name = 'Concejo de Administración';

    public string $council_email_signature = '';

    // General Email Settings
    public string $default_reply_to = '';

    public string $default_bcc = '';

    public bool $email_enabled = true;

    public bool $email_queue_enabled = true;

    public int $email_retry_attempts = 3;

    public int $email_retry_delay = 300; // seconds

    // Email Templates
    public bool $use_html_templates = true;

    public string $email_header_logo = '';

    public string $email_footer_text = '';

    public array $email_template_colors = [
        'primary' => '#3b82f6',
        'secondary' => '#6b7280',
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
    ];

    // Mailpit Integration (for development)
    public string $mailpit_url = 'http://localhost:8025';

    public bool $mailpit_enabled = true;

    public bool $use_mailpit_in_development = true;

    // Security & Privacy
    public bool $email_encryption_enabled = false;

    public bool $email_spam_protection = true;

    public array $blocked_domains = [];

    public array $allowed_domains = [];

    public bool $email_tracking_enabled = false;

    // Notifications
    public bool $send_delivery_notifications = false;

    public bool $send_read_receipts = false;

    public bool $auto_reply_enabled = false;

    public string $auto_reply_message = '';

    // Backup & Archive
    public bool $email_backup_enabled = true;

    public int $email_retention_days = 365;

    public bool $email_archive_enabled = true;

    public string $email_archive_path = 'emails/archive';

    // Rate Limiting
    public int $emails_per_hour_limit = 100;

    public int $emails_per_day_limit = 1000;

    public bool $rate_limiting_enabled = true;

    public static function group(): string
    {
        return 'email';
    }

    /**
     * Get SMTP configuration as array
     */
    public function getSMTPConfig(): array
    {
        return [
            'transport' => 'smtp',
            'host' => $this->smtp_host,
            'port' => $this->smtp_port,
            'username' => $this->smtp_username,
            'password' => $this->smtp_password,
            'encryption' => ($this->smtp_encryption === 'none' || empty($this->smtp_encryption)) ? null : $this->smtp_encryption,
            'timeout' => $this->smtp_timeout,
        ];
    }

    /**
     * Get admin email configuration
     */
    public function getAdminEmailConfig(): array
    {
        return [
            'address' => $this->admin_email_address,
            'name' => $this->admin_email_name,
            'signature' => $this->admin_email_signature,
        ];
    }

    /**
     * Get council email configuration
     */
    public function getCouncilEmailConfig(): array
    {
        return [
            'address' => $this->council_email_address,
            'name' => $this->council_email_name,
            'signature' => $this->council_email_signature,
        ];
    }

    /**
     * Check if email system is properly configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->smtp_host) &&
               ! empty($this->smtp_username) &&
               ! empty($this->admin_email_address) &&
               ! empty($this->council_email_address);
    }

    /**
     * Check if Mailpit should be used
     */
    public function shouldUseMailpit(): bool
    {
        return $this->mailpit_enabled &&
               $this->use_mailpit_in_development &&
               app()->environment(['local', 'testing']);
    }

    /**
     * Get email template configuration
     */
    public function getTemplateConfig(): array
    {
        return [
            'use_html' => $this->use_html_templates,
            'header_logo' => $this->email_header_logo,
            'footer_text' => $this->email_footer_text,
            'colors' => $this->email_template_colors,
        ];
    }

    /**
     * Get rate limiting configuration
     */
    public function getRateLimitConfig(): array
    {
        return [
            'enabled' => $this->rate_limiting_enabled,
            'per_hour' => $this->emails_per_hour_limit,
            'per_day' => $this->emails_per_day_limit,
        ];
    }

    /**
     * Validate email address against allowed/blocked domains
     */
    public function isEmailAllowed(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);

        // Check blocked domains
        if (in_array($domain, $this->blocked_domains)) {
            return false;
        }

        // Check allowed domains (if specified, only allow these)
        if (! empty($this->allowed_domains)) {
            return in_array($domain, $this->allowed_domains);
        }

        return true;
    }

    /**
     * Get complete email configuration for Laravel mail config
     */
    public function toMailConfig(): array
    {
        return [
            'default' => $this->email_enabled ? 'smtp' : 'log',
            'mailers' => [
                'smtp' => $this->getSMTPConfig(),
            ],
            'from' => [
                'address' => $this->admin_email_address ?: config('mail.from.address'),
                'name' => $this->admin_email_name ?: config('mail.from.name'),
            ],
        ];
    }
}
