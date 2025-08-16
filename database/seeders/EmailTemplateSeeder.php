<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Factura Moderna',
                'description' => 'Plantilla moderna y atractiva para envío de facturas',
                'type' => 'invoice',
                'subject' => '🧾 Nueva Factura {{billing_period}} - {{apartment_address}}',
                'body' => $this->getModernInvoiceTemplate(),
                'design_config' => [
                    'primary_color' => '#2563eb',
                    'secondary_color' => '#1d4ed8',
                    'accent_color' => '#3b82f6',
                    'background_color' => '#f8fafc',
                    'text_color' => '#1e293b',
                    'font_family' => 'system-ui, -apple-system, sans-serif',
                    'header_style' => 'gradient',
                    'footer_style' => 'modern',
                    'button_style' => 'rounded',
                    'layout' => 'centered',
                    'max_width' => '600px',
                    'show_logo' => true,
                    'show_contact_info' => true,
                ],
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Factura Clásica',
                'description' => 'Plantilla clásica y profesional para envío de facturas',
                'type' => 'invoice',
                'subject' => 'Factura {{billing_period}} - {{apartment_address}}',
                'body' => $this->getClassicInvoiceTemplate(),
                'design_config' => [
                    'primary_color' => '#059669',
                    'secondary_color' => '#047857',
                    'accent_color' => '#10b981',
                    'background_color' => '#ffffff',
                    'text_color' => '#374151',
                    'font_family' => 'Georgia, serif',
                    'header_style' => 'classic',
                    'footer_style' => 'simple',
                    'button_style' => 'square',
                    'layout' => 'full-width',
                    'max_width' => '650px',
                    'show_logo' => true,
                    'show_contact_info' => true,
                ],
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Recibo de Pago Moderno',
                'description' => 'Plantilla moderna para confirmación de pagos recibidos',
                'type' => 'payment_receipt',
                'subject' => '✅ Pago Confirmado - {{apartment_address}}',
                'body' => $this->getPaymentReceiptTemplate(),
                'design_config' => [
                    'primary_color' => '#16a34a',
                    'secondary_color' => '#15803d',
                    'accent_color' => '#22c55e',
                    'background_color' => '#f0fdf4',
                    'text_color' => '#166534',
                    'font_family' => 'system-ui, -apple-system, sans-serif',
                    'header_style' => 'success',
                    'footer_style' => 'modern',
                    'button_style' => 'rounded',
                    'layout' => 'centered',
                    'max_width' => '600px',
                    'show_logo' => true,
                    'show_contact_info' => true,
                ],
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Recordatorio de Pago',
                'description' => 'Plantilla amigable para recordatorios de pago',
                'type' => 'payment_reminder',
                'subject' => '⏰ Pago Pendiente - {{apartment_address}}',
                'body' => $this->getPaymentReminderTemplate(),
                'design_config' => [
                    'primary_color' => '#f59e0b',
                    'secondary_color' => '#d97706',
                    'accent_color' => '#fbbf24',
                    'background_color' => '#fffbeb',
                    'text_color' => '#92400e',
                    'font_family' => 'system-ui, -apple-system, sans-serif',
                    'header_style' => 'warning',
                    'footer_style' => 'modern',
                    'button_style' => 'rounded',
                    'layout' => 'centered',
                    'max_width' => '600px',
                    'show_logo' => true,
                    'show_contact_info' => true,
                ],
                'is_default' => true,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::create($template);
        }
    }

    private function getModernInvoiceTemplate(): string
    {
        return '
        <div style="max-width: 500px; margin: 0 auto; font-family: Arial, sans-serif; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 24px; text-align: center; color: white;">
                <h1 style="margin: 0; font-size: 22px; font-weight: 700;">{{conjunto_name}}</h1>
                <p style="margin: 8px 0 0 0; opacity: 0.9;">Nueva Factura</p>
            </div>

            <!-- Amount -->
            <div style="padding: 20px; text-align: center; background: #f8fafc;">
                <div style="background: white; padding: 20px; border-radius: 8px; border: 2px solid #3b82f6;">
                    <p style="margin: 0 0 8px 0; color: #64748b; font-size: 14px;">Total a Pagar</p>
                    <p style="margin: 0; font-size: 28px; font-weight: 700; color: #1e293b;">${{balance_due}}</p>
                    <p style="margin: 8px 0 0 0; color: #dc2626; font-weight: 600;">Vence: {{due_date}}</p>
                </div>
            </div>

            <!-- Content -->
            <div style="padding: 20px;">
                <p style="margin: 0 0 16px 0; color: #374151;">Hola,</p>
                <p style="margin: 0 0 16px 0; color: #374151;">Te enviamos la factura de <strong>{{billing_period}}</strong> para {{apartment_address}}.</p>
                <p style="margin: 0 0 16px 0; color: #374151;">Encuentra el detalle completo en el PDF adjunto.</p>
                
                <!-- Info Box -->
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 16px 0; border-radius: 0 4px 4px 0;">
                    <p style="margin: 0; font-size: 13px; color: #92400e;">📎 <strong>Factura:</strong> {{invoice_number}} | <strong>Período:</strong> {{billing_period}}</p>
                </div>

                <p style="margin: 16px 0 0 0; color: #374151; font-size: 14px;">Gracias por tu pago puntual.</p>
            </div>

            <!-- Footer -->
            <div style="background: #f1f5f9; padding: 16px; text-align: center; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0; font-size: 12px; color: #64748b;">{{conjunto_name}} | Correo automático</p>
            </div>
        </div>';
    }

    private function getClassicInvoiceTemplate(): string
    {
        return '
        <div style="max-width: 500px; margin: 0 auto; font-family: Georgia, serif; background: #fff; border: 2px solid #059669; border-radius: 4px;">
            <!-- Header -->
            <div style="background: #059669; padding: 20px; text-align: center; color: white;">
                <h1 style="margin: 0; font-size: 20px; letter-spacing: 1px;">{{conjunto_name}}</h1>
                <div style="height: 1px; background: #10b981; margin: 12px auto; width: 80px;"></div>
                <p style="margin: 0; font-size: 14px;">FACTURA</p>
            </div>

            <!-- Details -->
            <div style="padding: 20px;">
                <div style="border: 1px solid #d1d5db; padding: 16px; margin-bottom: 16px; border-radius: 4px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 16px; color: #047857;">Detalles</h3>
                    
                    <p style="margin: 4px 0; font-size: 14px;"><strong>Factura:</strong> {{invoice_number}}</p>
                    <p style="margin: 4px 0; font-size: 14px;"><strong>Apartamento:</strong> {{apartment_address}}</p>
                    <p style="margin: 4px 0; font-size: 14px;"><strong>Período:</strong> {{billing_period}}</p>
                    <p style="margin: 4px 0; font-size: 14px;"><strong>Vencimiento:</strong> <span style="color: #dc2626;">{{due_date}}</span></p>
                </div>

                <!-- Amount -->
                <div style="background: #f0fdf4; border: 2px solid #059669; padding: 16px; text-align: center; margin-bottom: 16px; border-radius: 4px;">
                    <p style="margin: 0 0 4px 0; font-size: 12px; color: #047857;">TOTAL</p>
                    <p style="margin: 0; font-size: 24px; font-weight: bold; color: #047857;">${{balance_due}}</p>
                </div>

                <!-- Message -->
                <p style="margin: 16px 0; color: #374151; font-size: 14px; line-height: 1.5;">Estimado propietario, adjuntamos la factura de {{billing_period}} por ${{total_amount}}. Pago hasta {{due_date}}.</p>

                <div style="background: #fffbeb; border-left: 3px solid #f59e0b; padding: 12px; margin: 16px 0; border-radius: 0 4px 4px 0;">
                    <p style="margin: 0; font-size: 12px; color: #92400e;"><strong>PDF adjunto</strong> con el detalle completo.</p>
                </div>
            </div>

            <!-- Footer -->
            <div style="background: #f9fafb; padding: 12px; text-align: center; border-top: 1px solid #d1d5db;">
                <p style="margin: 0; font-size: 11px; color: #6b7280;">{{conjunto_name}} - No responder</p>
            </div>
        </div>';
    }

    private function getPaymentReceiptTemplate(): string
    {
        return '
        <div style="max-width: 450px; margin: 0 auto; font-family: Arial, sans-serif; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #16a34a, #15803d); padding: 24px; text-align: center; color: white;">
                <div style="background: white; color: #16a34a; width: 50px; height: 50px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; font-size: 24px;">✅</div>
                <h1 style="margin: 0; font-size: 20px; font-weight: 700;">¡Pago Confirmado!</h1>
                <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 14px;">Gracias por tu pago</p>
            </div>

            <!-- Amount -->
            <div style="padding: 20px; text-align: center; background: #f0fdf4;">
                <div style="background: white; padding: 20px; border-radius: 8px; border: 2px solid #22c55e;">
                    <p style="margin: 0 0 8px 0; color: #15803d; font-size: 14px;">Monto Pagado</p>
                    <p style="margin: 0; font-size: 28px; font-weight: 700; color: #166534;">${{payment_amount}}</p>
                    <p style="margin: 8px 0 0 0; color: #15803d; font-size: 14px;">{{payment_date}}</p>
                </div>
            </div>

            <!-- Details -->
            <div style="padding: 20px;">
                <p style="margin: 0 0 16px 0; color: #374151;">¡Hola!</p>
                <p style="margin: 0 0 16px 0; color: #374151;">Confirmamos tu pago de <strong>${{payment_amount}}</strong> para {{apartment_address}}.</p>
                
                <!-- Info Box -->
                <div style="background: #dcfce7; border-left: 4px solid #22c55e; padding: 12px; margin: 16px 0; border-radius: 0 4px 4px 0;">
                    <p style="margin: 0; font-size: 13px; color: #166534;">💰 <strong>Recibo:</strong> {{receipt_number}} | <strong>Método:</strong> {{payment_method}}</p>
                </div>

                <p style="margin: 16px 0 0 0; color: #374151; font-size: 14px;">Conserva este correo como comprobante.</p>
            </div>

            <!-- Footer -->
            <div style="background: #f0fdf4; padding: 16px; text-align: center; border-top: 1px solid #bbf7d0;">
                <p style="margin: 0; font-size: 12px; color: #15803d;">{{conjunto_name}} | ¡Gracias!</p>
            </div>
        </div>';
    }

    private function getPaymentReminderTemplate(): string
    {
        return '
        <div style="max-width: 450px; margin: 0 auto; font-family: Arial, sans-serif; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 24px; text-align: center; color: white;">
                <div style="background: white; color: #f59e0b; width: 50px; height: 50px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; font-size: 24px;">⏰</div>
                <h1 style="margin: 0; font-size: 20px; font-weight: 700;">Recordatorio de Pago</h1>
                <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 14px;">Tienes un pago pendiente</p>
            </div>

            <!-- Amount -->
            <div style="padding: 20px; text-align: center; background: #fffbeb;">
                <div style="background: white; padding: 20px; border-radius: 8px; border: 2px solid #f59e0b;">
                    <p style="margin: 0 0 8px 0; color: #d97706; font-size: 14px;">Monto Vencido</p>
                    <p style="margin: 0; font-size: 28px; font-weight: 700; color: #dc2626;">${{overdue_amount}}</p>
                    <p style="margin: 8px 0 0 0; color: #dc2626; font-weight: 600;">{{days_overdue}} días de atraso</p>
                </div>
            </div>

            <!-- Details -->
            <div style="padding: 20px;">
                <p style="margin: 0 0 16px 0; color: #374151;">Hola,</p>
                <p style="margin: 0 0 16px 0; color: #374151;">Tienes un pago pendiente de <strong>${{overdue_amount}}</strong> para {{apartment_address}}.</p>
                <p style="margin: 0 0 16px 0; color: #374151; font-size: 14px;">Venció el {{due_date}} hace {{days_overdue}} días.</p>
                
                <!-- Warning -->
                <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 12px; margin: 16px 0; border-radius: 0 4px 4px 0;">
                    <p style="margin: 0; font-size: 13px; color: #991b1b;">⚠️ <strong>Importante:</strong> Los pagos en mora generan intereses adicionales.</p>
                </div>

                <p style="margin: 16px 0 0 0; color: #374151; font-size: 14px;">Por favor realiza tu pago lo antes posible.</p>
            </div>

            <!-- Footer -->
            <div style="background: #fffbeb; padding: 16px; text-align: center; border-top: 1px solid #fde68a;">
                <p style="margin: 0; font-size: 12px; color: #d97706;">{{conjunto_name}} | Recordatorio automático</p>
            </div>
        </div>';
    }
}
