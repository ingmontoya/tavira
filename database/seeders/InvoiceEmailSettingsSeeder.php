<?php

namespace Database\Seeders;

use App\Models\InvoiceEmailSetting;
use Illuminate\Database\Seeder;

class InvoiceEmailSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'cost_per_email',
                'name' => 'Costo por Email',
                'description' => 'Costo en USD por cada email enviado',
                'category' => 'cost',
                'value' => '0.001',
                'type' => 'decimal',
                'default_value' => '0.001',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Costo estimado por email para cálculos de presupuesto',
                'placeholder' => '0.001',
                'sort_order' => 10,
            ],
            [
                'key' => 'monthly_budget_limit',
                'name' => 'Límite Presupuestal Mensual',
                'description' => 'Límite máximo de gasto mensual en emails (USD)',
                'category' => 'cost',
                'value' => '500',
                'type' => 'decimal',
                'default_value' => '500',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Límite máximo de gasto mensual permitido',
                'placeholder' => '500',
                'sort_order' => 20,
            ],
            [
                'key' => 'max_retries',
                'name' => 'Máximo Reintentos',
                'description' => 'Número máximo de reintentos para emails fallidos',
                'category' => 'delivery',
                'value' => '3',
                'type' => 'integer',
                'default_value' => '3',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Número de veces que se reintentará enviar un email fallido',
                'placeholder' => '3',
                'sort_order' => 30,
            ],
            [
                'key' => 'batch_size',
                'name' => 'Tamaño de Lote',
                'description' => 'Número de emails a procesar por lote',
                'category' => 'delivery',
                'value' => '50',
                'type' => 'integer',
                'default_value' => '50',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Cantidad de emails que se procesan en cada lote',
                'placeholder' => '50',
                'sort_order' => 40,
            ],
            [
                'key' => 'delay_between_emails',
                'name' => 'Retraso Entre Emails (ms)',
                'description' => 'Tiempo de espera en milisegundos entre cada email',
                'category' => 'delivery',
                'value' => '100',
                'type' => 'integer',
                'default_value' => '100',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Pausa entre emails para evitar sobrecarga del proveedor',
                'placeholder' => '100',
                'sort_order' => 50,
            ],

            // Template Settings
            [
                'key' => 'default_template',
                'name' => 'Plantilla por Defecto',
                'description' => 'Plantilla de email por defecto para facturas',
                'category' => 'templates',
                'value' => 'invoice',
                'type' => 'select',
                'options' => [
                    ['value' => 'invoice', 'label' => 'Factura Estándar'],
                    ['value' => 'reminder', 'label' => 'Recordatorio de Pago'],
                    ['value' => 'payment_plan', 'label' => 'Plan de Pagos'],
                ],
                'default_value' => 'invoice',
                'is_required' => true,
                'input_type' => 'select',
                'help_text' => 'Plantilla que se usará por defecto al crear nuevos lotes',
                'sort_order' => 60,
            ],
            [
                'key' => 'default_subject',
                'name' => 'Asunto por Defecto',
                'description' => 'Asunto por defecto para emails de facturas',
                'category' => 'templates',
                'value' => 'Factura de Administración - {{invoice_number}}',
                'type' => 'string',
                'default_value' => 'Factura de Administración - {{invoice_number}}',
                'is_required' => true,
                'input_type' => 'text',
                'help_text' => 'Usar {{variable}} para incluir datos dinámicos',
                'placeholder' => 'Factura de Administración - {{invoice_number}}',
                'sort_order' => 70,
            ],
            [
                'key' => 'sender_name',
                'name' => 'Nombre del Remitente',
                'description' => 'Nombre que aparecerá como remitente de los emails',
                'category' => 'templates',
                'value' => config('app.name', 'Tavira'),
                'type' => 'string',
                'default_value' => config('app.name', 'Tavira'),
                'is_required' => true,
                'input_type' => 'text',
                'help_text' => 'Nombre visible del remitente en los emails',
                'placeholder' => 'Mi Conjunto Residencial',
                'sort_order' => 80,
            ],
            [
                'key' => 'include_pdf_by_default',
                'name' => 'Incluir PDF por Defecto',
                'description' => 'Incluir automáticamente el PDF de la factura en los emails',
                'category' => 'templates',
                'value' => 'true',
                'type' => 'boolean',
                'default_value' => 'true',
                'is_required' => true,
                'input_type' => 'checkbox',
                'help_text' => 'Adjuntar automáticamente el PDF de la factura',
                'sort_order' => 90,
            ],

            // Security Settings
            [
                'key' => 'rate_limit_emails_per_hour',
                'name' => 'Límite de Emails por Hora',
                'description' => 'Número máximo de emails que se pueden enviar por hora',
                'category' => 'security',
                'value' => '1000',
                'type' => 'integer',
                'default_value' => '1000',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Límite para prevenir abuso del sistema',
                'placeholder' => '1000',
                'sort_order' => 100,
            ],
            [
                'key' => 'max_recipients_per_batch',
                'name' => 'Máximo Destinatarios por Lote',
                'description' => 'Número máximo de destinatarios permitidos en un solo lote',
                'category' => 'security',
                'value' => '500',
                'type' => 'integer',
                'default_value' => '500',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Límite para controlar el tamaño de los lotes',
                'placeholder' => '500',
                'sort_order' => 110,
            ],
            [
                'key' => 'require_valid_emails',
                'name' => 'Validar Emails',
                'description' => 'Verificar que los emails tengan formato válido antes de enviar',
                'category' => 'security',
                'value' => 'true',
                'type' => 'boolean',
                'default_value' => 'true',
                'is_required' => true,
                'input_type' => 'checkbox',
                'help_text' => 'Rechazar automáticamente emails con formato inválido',
                'sort_order' => 120,
            ],

            // Monitoring Settings
            [
                'key' => 'delivery_rate_threshold',
                'name' => 'Umbral de Tasa de Entrega',
                'description' => 'Tasa mínima de entrega para generar alertas (0.0 - 1.0)',
                'category' => 'notifications',
                'value' => '0.95',
                'type' => 'decimal',
                'default_value' => '0.95',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Alerta si la tasa de entrega está por debajo de este valor',
                'placeholder' => '0.95',
                'sort_order' => 130,
            ],
            [
                'key' => 'bounce_rate_threshold',
                'name' => 'Umbral de Tasa de Rebote',
                'description' => 'Tasa máxima de rebote antes de generar alertas (0.0 - 1.0)',
                'category' => 'notifications',
                'value' => '0.05',
                'type' => 'decimal',
                'default_value' => '0.05',
                'is_required' => true,
                'input_type' => 'number',
                'help_text' => 'Alerta si la tasa de rebote supera este valor',
                'placeholder' => '0.05',
                'sort_order' => 140,
            ],
        ];

        foreach ($settings as $setting) {
            InvoiceEmailSetting::updateOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'is_system' => true,
                    'is_visible' => true,
                    'is_editable' => true,
                ])
            );
        }

        $this->command->info('Invoice email settings seeded successfully.');
    }
}
