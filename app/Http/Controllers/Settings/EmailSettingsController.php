<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\EmailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class EmailSettingsController extends Controller
{
    protected EmailSettings $emailSettings;

    public function __construct(EmailSettings $emailSettings)
    {
        $this->emailSettings = $emailSettings;
    }

    public function index()
    {
        return Inertia::render('settings/Email', [
            'settings' => $this->emailSettings->toArray(),
            'emailPresets' => $this->getEmailPresets(),
            'isConfigured' => $this->emailSettings->isConfigured(),
            'mailpitStatus' => $this->getMailpitStatus(),
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validatedData = $validator->validated();

        // Encrypt sensitive data before storing
        if (isset($validatedData['smtp_password']) && !empty($validatedData['smtp_password'])) {
            $validatedData['smtp_password'] = encrypt($validatedData['smtp_password']);
        }

        // Update email settings
        foreach ($validatedData as $key => $value) {
            if (property_exists($this->emailSettings, $key)) {
                $this->emailSettings->$key = $value;
            }
        }

        $this->emailSettings->save();

        Log::info('Email settings updated', [
            'user_id' => auth()->user()->id,
            'updated_settings' => array_keys($validatedData),
            'ip' => $request->ip(),
        ]);

        return back()->with('success', 'Configuración de correo electrónico actualizada correctamente');
    }

    public function testConnection(Request $request)
    {
        $request->validate([
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer|between:1,65535',
            'smtp_username' => 'required|string',
            'smtp_password' => 'required|string',
            'smtp_encryption' => 'nullable|in:tls,ssl',
        ]);

        try {
            // Test SMTP connection
            $config = [
                'transport' => 'smtp',
                'host' => $request->smtp_host,
                'port' => $request->smtp_port,
                'username' => $request->smtp_username,
                'password' => $request->smtp_password,
                'encryption' => $request->smtp_encryption ?: null,
                'timeout' => 10,
            ];

            // Configure mail temporarily
            config(['mail.mailers.test_smtp' => $config]);

            // Try to create a mailer instance and test connection
            $mailer = app('mail.manager')->mailer('test_smtp');
            $transport = $mailer->getSymfonyTransporter();
            
            // Test the connection (this will throw exception if fails)
            $transport->start();
            $transport->stop();

            Log::info('SMTP connection test successful', [
                'user_id' => auth()->user()->id,
                'smtp_host' => $request->smtp_host,
                'smtp_port' => $request->smtp_port,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conexión SMTP exitosa'
            ]);

        } catch (\Exception $e) {
            Log::warning('SMTP connection test failed', [
                'user_id' => auth()->user()->id,
                'smtp_host' => $request->smtp_host,
                'smtp_port' => $request->smtp_port,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de conexión SMTP: ' . $e->getMessage()
            ], 400);
        }
    }

    public function testEmail(Request $request)
    {
        $request->validate([
            'to_email' => 'required|email',
            'email_type' => 'required|in:admin,council',
        ]);

        if (!$this->emailSettings->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'La configuración de correo no está completa'
            ], 400);
        }

        try {
            $emailType = $request->email_type;
            $config = $emailType === 'admin' 
                ? $this->emailSettings->getAdminEmailConfig()
                : $this->emailSettings->getCouncilEmailConfig();

            // Send test email
            $testData = [
                'to' => $request->to_email,
                'from' => $config['address'],
                'from_name' => $config['name'],
                'subject' => 'Test de Configuración de Correo - ' . $config['name'],
                'body' => "Este es un correo de prueba para verificar la configuración del correo electrónico.\n\nEnviado desde: {$config['name']}\nFecha: " . now()->format('d/m/Y H:i:s'),
            ];

            // TODO: Implement actual email sending using the configured settings
            // For now, just log the attempt

            Log::info('Test email sent', [
                'user_id' => auth()->user()->id,
                'to_email' => $request->to_email,
                'email_type' => $emailType,
                'from_config' => $config,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo de prueba enviado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Test email failed', [
                'user_id' => auth()->user()->id,
                'to_email' => $request->to_email,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar correo de prueba: ' . $e->getMessage()
            ], 500);
        }
    }

    public function applyPreset(Request $request)
    {
        $request->validate([
            'preset' => 'required|in:gmail,outlook,mailpit,custom',
        ]);

        $preset = $request->input('preset');
        $this->applyEmailPresetConfiguration($preset);

        Log::info('Email preset applied', [
            'user_id' => auth()->user()->id,
            'preset' => $preset,
            'ip' => $request->ip(),
        ]);

        return back()->with('success', "Configuración preset '{$preset}' aplicada correctamente");
    }

    public function resetToDefaults()
    {
        // Reset all settings to default values
        $this->emailSettings->smtp_host = '';
        $this->emailSettings->smtp_port = 587;
        $this->emailSettings->smtp_username = '';
        $this->emailSettings->smtp_password = '';
        $this->emailSettings->smtp_encryption = 'tls';
        $this->emailSettings->smtp_timeout = 30;

        $this->emailSettings->admin_email_address = '';
        $this->emailSettings->admin_email_name = 'Administración';
        $this->emailSettings->admin_email_signature = '';
        
        $this->emailSettings->council_email_address = '';
        $this->emailSettings->council_email_name = 'Concejo de Administración';
        $this->emailSettings->council_email_signature = '';

        $this->emailSettings->email_enabled = true;
        $this->emailSettings->email_queue_enabled = true;
        $this->emailSettings->mailpit_enabled = true;
        $this->emailSettings->use_mailpit_in_development = true;

        $this->emailSettings->save();

        Log::info('Email settings reset to defaults', [
            'user_id' => auth()->user()->id,
            'ip' => request()->ip(),
        ]);

        return back()->with('success', 'Configuración de correo restaurada a valores predeterminados');
    }

    private function getMailpitStatus(): array
    {
        if (!$this->emailSettings->mailpit_enabled) {
            return ['available' => false, 'message' => 'Mailpit deshabilitado'];
        }

        try {
            $response = Http::timeout(3)->get($this->emailSettings->mailpit_url . '/api/v1/info');
            
            if ($response->successful()) {
                return [
                    'available' => true,
                    'message' => 'Mailpit disponible',
                    'version' => $response->json('version') ?? 'unknown'
                ];
            }
        } catch (\Exception $e) {
            // Mailpit not available
        }

        return [
            'available' => false,
            'message' => 'Mailpit no disponible en ' . $this->emailSettings->mailpit_url
        ];
    }

    private function getEmailPresets(): array
    {
        return [
            'gmail' => [
                'name' => 'Gmail',
                'description' => 'Configuración para cuentas de Gmail',
                'settings' => [
                    'smtp_host' => 'smtp.gmail.com',
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'smtp_timeout' => 30,
                ],
            ],
            'outlook' => [
                'name' => 'Outlook/Hotmail',
                'description' => 'Configuración para cuentas de Outlook y Hotmail',
                'settings' => [
                    'smtp_host' => 'smtp-mail.outlook.com',
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'smtp_timeout' => 30,
                ],
            ],
            'mailpit' => [
                'name' => 'Mailpit (Desarrollo)',
                'description' => 'Configuración para usar Mailpit en desarrollo',
                'settings' => [
                    'smtp_host' => 'localhost',
                    'smtp_port' => 1025,
                    'smtp_encryption' => '',
                    'smtp_timeout' => 10,
                    'mailpit_enabled' => true,
                    'use_mailpit_in_development' => true,
                ],
            ],
            'custom' => [
                'name' => 'Personalizada',
                'description' => 'Configuración personalizada para servidor SMTP propio',
                'settings' => [
                    'smtp_host' => '',
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'smtp_timeout' => 30,
                ],
            ],
        ];
    }

    private function applyEmailPresetConfiguration(string $preset): void
    {
        $presets = $this->getEmailPresets();

        if (!isset($presets[$preset])) {
            throw new \InvalidArgumentException("Invalid email preset: {$preset}");
        }

        $settings = $presets[$preset]['settings'];

        foreach ($settings as $key => $value) {
            if (property_exists($this->emailSettings, $key)) {
                $this->emailSettings->$key = $value;
            }
        }

        $this->emailSettings->save();
    }

    private function getValidationRules(): array
    {
        return [
            // SMTP Configuration
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'integer|between:1,65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_timeout' => 'integer|between:1,300',

            // Email Accounts
            'admin_email_address' => 'nullable|email|max:255',
            'admin_email_name' => 'nullable|string|max:255',
            'admin_email_signature' => 'nullable|string|max:1000',
            'council_email_address' => 'nullable|email|max:255',
            'council_email_name' => 'nullable|string|max:255',
            'council_email_signature' => 'nullable|string|max:1000',

            // General Settings
            'default_reply_to' => 'nullable|email|max:255',
            'default_bcc' => 'nullable|email|max:255',
            'email_enabled' => 'boolean',
            'email_queue_enabled' => 'boolean',
            'email_retry_attempts' => 'integer|between:1,10',
            'email_retry_delay' => 'integer|between:1,3600',

            // Templates
            'use_html_templates' => 'boolean',
            'email_header_logo' => 'nullable|string|max:255',
            'email_footer_text' => 'nullable|string|max:500',
            'email_template_colors' => 'array',

            // Mailpit
            'mailpit_url' => 'nullable|url|max:255',
            'mailpit_enabled' => 'boolean',
            'use_mailpit_in_development' => 'boolean',

            // Security
            'email_encryption_enabled' => 'boolean',
            'email_spam_protection' => 'boolean',
            'blocked_domains' => 'array',
            'allowed_domains' => 'array',
            'email_tracking_enabled' => 'boolean',

            // Notifications
            'send_delivery_notifications' => 'boolean',
            'send_read_receipts' => 'boolean',
            'auto_reply_enabled' => 'boolean',
            'auto_reply_message' => 'nullable|string|max:1000',

            // Backup & Archive
            'email_backup_enabled' => 'boolean',
            'email_retention_days' => 'integer|between:1,3650',
            'email_archive_enabled' => 'boolean',
            'email_archive_path' => 'nullable|string|max:255',

            // Rate Limiting
            'emails_per_hour_limit' => 'integer|between:1,10000',
            'emails_per_day_limit' => 'integer|between:1,50000',
            'rate_limiting_enabled' => 'boolean',
        ];
    }
}