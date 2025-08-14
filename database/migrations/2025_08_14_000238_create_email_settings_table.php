<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The settings table should already exist from spatie/laravel-settings
        // We just need to insert the default email settings
        
        $emailSettings = [
            // SMTP Configuration
            ['group' => 'email', 'name' => 'smtp_host', 'payload' => json_encode('localhost'), 'locked' => false],
            ['group' => 'email', 'name' => 'smtp_port', 'payload' => json_encode(1025), 'locked' => false],
            ['group' => 'email', 'name' => 'smtp_username', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'smtp_password', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'smtp_encryption', 'payload' => json_encode('none'), 'locked' => false],
            ['group' => 'email', 'name' => 'smtp_timeout', 'payload' => json_encode(10), 'locked' => false],

            // Admin Email Configuration
            ['group' => 'email', 'name' => 'admin_email_address', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'admin_email_name', 'payload' => json_encode('Administración'), 'locked' => false],
            ['group' => 'email', 'name' => 'admin_email_signature', 'payload' => json_encode("Administración del Conjunto\n\nEste es un correo automático, por favor no responder."), 'locked' => false],

            // Council Email Configuration
            ['group' => 'email', 'name' => 'council_email_address', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'council_email_name', 'payload' => json_encode('Concejo de Administración'), 'locked' => false],
            ['group' => 'email', 'name' => 'council_email_signature', 'payload' => json_encode("Concejo de Administración\n\nEste correo es enviado por el concejo de administración del conjunto."), 'locked' => false],

            // General Email Settings
            ['group' => 'email', 'name' => 'default_reply_to', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'default_bcc', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'email_enabled', 'payload' => json_encode(true), 'locked' => false],
            ['group' => 'email', 'name' => 'email_queue_enabled', 'payload' => json_encode(true), 'locked' => false],
            ['group' => 'email', 'name' => 'email_retry_attempts', 'payload' => json_encode(3), 'locked' => false],
            ['group' => 'email', 'name' => 'email_retry_delay', 'payload' => json_encode(300), 'locked' => false],

            // Email Templates
            ['group' => 'email', 'name' => 'use_html_templates', 'payload' => json_encode(true), 'locked' => false],
            ['group' => 'email', 'name' => 'email_header_logo', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'email_footer_text', 'payload' => json_encode('Habitta - Sistema de Gestión de Conjuntos Residenciales'), 'locked' => false],
            ['group' => 'email', 'name' => 'email_template_colors', 'payload' => json_encode([
                'primary' => '#3b82f6',
                'secondary' => '#6b7280',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'danger' => '#ef4444',
            ]), 'locked' => false],

            // Mailpit Integration
            ['group' => 'email', 'name' => 'mailpit_url', 'payload' => json_encode('http://localhost:8025'), 'locked' => false],
            ['group' => 'email', 'name' => 'mailpit_enabled', 'payload' => json_encode(true), 'locked' => false],
            ['group' => 'email', 'name' => 'use_mailpit_in_development', 'payload' => json_encode(true), 'locked' => false],

            // Security & Privacy
            ['group' => 'email', 'name' => 'email_encryption_enabled', 'payload' => json_encode(false), 'locked' => false],
            ['group' => 'email', 'name' => 'email_spam_protection', 'payload' => json_encode(true), 'locked' => false],
            ['group' => 'email', 'name' => 'blocked_domains', 'payload' => json_encode([]), 'locked' => false],
            ['group' => 'email', 'name' => 'allowed_domains', 'payload' => json_encode([]), 'locked' => false],
            ['group' => 'email', 'name' => 'email_tracking_enabled', 'payload' => json_encode(false), 'locked' => false],

            // Notifications
            ['group' => 'email', 'name' => 'send_delivery_notifications', 'payload' => json_encode(false), 'locked' => false],
            ['group' => 'email', 'name' => 'send_read_receipts', 'payload' => json_encode(false), 'locked' => false],
            ['group' => 'email', 'name' => 'auto_reply_enabled', 'payload' => json_encode(false), 'locked' => false],
            ['group' => 'email', 'name' => 'auto_reply_message', 'payload' => json_encode(''), 'locked' => false],

            // Backup & Archive
            ['group' => 'email', 'name' => 'email_backup_enabled', 'payload' => json_encode(true), 'locked' => false],
            ['group' => 'email', 'name' => 'email_retention_days', 'payload' => json_encode(365), 'locked' => false],
            ['group' => 'email', 'name' => 'email_archive_enabled', 'payload' => json_encode(true), 'locked' => false],
            ['group' => 'email', 'name' => 'email_archive_path', 'payload' => json_encode('emails/archive'), 'locked' => false],

            // Rate Limiting
            ['group' => 'email', 'name' => 'emails_per_hour_limit', 'payload' => json_encode(100), 'locked' => false],
            ['group' => 'email', 'name' => 'emails_per_day_limit', 'payload' => json_encode(1000), 'locked' => false],
            ['group' => 'email', 'name' => 'rate_limiting_enabled', 'payload' => json_encode(true), 'locked' => false],
        ];

        foreach ($emailSettings as $setting) {
            // Only insert if it doesn't exist
            $exists = DB::table('settings')
                ->where('group', $setting['group'])
                ->where('name', $setting['name'])
                ->exists();

            if (!$exists) {
                DB::table('settings')->insert(array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all email settings
        DB::table('settings')->where('group', 'email')->delete();
    }
};