<?php

namespace Database\Seeders;

use App\Settings\EmailSettings;
use Illuminate\Database\Seeder;

class EmailSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emailSettings = app(EmailSettings::class);

        // Only initialize if not already configured
        if (! $emailSettings->isConfigured()) {
            // Set default SMTP settings for Mailpit (development)
            $emailSettings->smtp_host = 'localhost';
            $emailSettings->smtp_port = 1025;
            $emailSettings->smtp_username = '';
            $emailSettings->smtp_password = '';
            $emailSettings->smtp_encryption = 'none';
            $emailSettings->smtp_timeout = 10;

            // Default email addresses (should be configured by admin)
            $emailSettings->admin_email_address = '';
            $emailSettings->admin_email_name = 'Administración';
            $emailSettings->admin_email_signature = "Administración del Conjunto\n\nEste es un correo automático, por favor no responder.";

            $emailSettings->council_email_address = '';
            $emailSettings->council_email_name = 'Concejo de Administración';
            $emailSettings->council_email_signature = "Concejo de Administración\n\nEste correo es enviado por el concejo de administración del conjunto.";

            // General settings
            $emailSettings->default_reply_to = '';
            $emailSettings->default_bcc = '';
            $emailSettings->email_enabled = true;
            $emailSettings->email_queue_enabled = true;
            $emailSettings->email_retry_attempts = 3;
            $emailSettings->email_retry_delay = 300;

            // Template settings
            $emailSettings->use_html_templates = true;
            $emailSettings->email_header_logo = '';
            $emailSettings->email_footer_text = 'Habitta - Sistema de Gestión de Conjuntos Residenciales';
            $emailSettings->email_template_colors = [
                'primary' => '#3b82f6',
                'secondary' => '#6b7280',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'danger' => '#ef4444',
            ];

            // Mailpit settings (for development)
            $emailSettings->mailpit_url = 'http://localhost:8025';
            $emailSettings->mailpit_enabled = true;
            $emailSettings->use_mailpit_in_development = true;

            // Security settings
            $emailSettings->email_encryption_enabled = false;
            $emailSettings->email_spam_protection = true;
            $emailSettings->blocked_domains = [];
            $emailSettings->allowed_domains = [];
            $emailSettings->email_tracking_enabled = false;

            // Notifications
            $emailSettings->send_delivery_notifications = false;
            $emailSettings->send_read_receipts = false;
            $emailSettings->auto_reply_enabled = false;
            $emailSettings->auto_reply_message = '';

            // Backup & Archive
            $emailSettings->email_backup_enabled = true;
            $emailSettings->email_retention_days = 365;
            $emailSettings->email_archive_enabled = true;
            $emailSettings->email_archive_path = 'emails/archive';

            // Rate limiting
            $emailSettings->emails_per_hour_limit = 100;
            $emailSettings->emails_per_day_limit = 1000;
            $emailSettings->rate_limiting_enabled = true;

            $emailSettings->save();

            $this->command->info('Email settings initialized with default values.');
        } else {
            $this->command->info('Email settings already configured.');
        }
    }
}
