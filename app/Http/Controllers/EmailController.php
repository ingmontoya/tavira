<?php

namespace App\Http\Controllers;

use App\Settings\EmailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class EmailController extends Controller
{
    protected EmailSettings $emailSettings;

    public function __construct(EmailSettings $emailSettings)
    {
        $this->emailSettings = $emailSettings;
    }
    /**
     * Display email inbox for admin
     */
    public function adminIndex(Request $request)
    {
        $emails = $this->getMailpitEmails();
        
        return Inertia::render('Email/Admin/Index', [
            'emails' => $emails,
            'view' => 'admin'
        ]);
    }

    /**
     * Display email inbox for concejo
     */
    public function concejoIndex(Request $request)
    {
        $emails = $this->getMailpitEmails();
        
        return Inertia::render('Email/Concejo/Index', [
            'emails' => $emails,
            'view' => 'concejo'
        ]);
    }

    /**
     * Show specific email for admin
     */
    public function adminShow($id)
    {
        $email = $this->getMailpitEmail($id);
        
        return Inertia::render('Email/Admin/Show', [
            'email' => $email,
            'view' => 'admin'
        ]);
    }

    /**
     * Show specific email for concejo
     */
    public function concejoShow($id)
    {
        $email = $this->getMailpitEmail($id);
        
        return Inertia::render('Email/Concejo/Show', [
            'email' => $email,
            'view' => 'concejo'
        ]);
    }

    /**
     * Compose new email for admin
     */
    public function adminCompose(Request $request)
    {
        return Inertia::render('Email/Admin/Compose', [
            'view' => 'admin'
        ]);
    }

    /**
     * Compose new email for concejo
     */
    public function concejoCompose(Request $request)
    {
        return Inertia::render('Email/Concejo/Compose', [
            'view' => 'concejo'
        ]);
    }

    /**
     * Send email
     */
    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'view' => 'required|in:admin,concejo'
        ]);

        // TODO: Implement actual email sending logic
        // For now, just return success response
        
        return redirect()->route("email.{$request->view}.index")
            ->with('success', 'Correo enviado exitosamente');
    }

    /**
     * Delete email
     */
    public function destroy($id, Request $request)
    {
        // TODO: Implement email deletion logic with Mailpit API
        
        $view = $request->query('view', 'admin');
        
        return redirect()->route("email.{$view}.index")
            ->with('success', 'Correo eliminado exitosamente');
    }

    /**
     * Mark email as read
     */
    public function markAsRead($id, Request $request)
    {
        // TODO: Implement mark as read logic with Mailpit API
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark email as unread
     */
    public function markAsUnread($id, Request $request)
    {
        // TODO: Implement mark as unread logic with Mailpit API
        
        return response()->json(['success' => true]);
    }

    /**
     * Get emails from Mailpit API
     */
    private function getMailpitEmails()
    {
        // Check if we should use Mailpit
        if (!$this->emailSettings->shouldUseMailpit()) {
            return $this->getMockEmails();
        }

        try {
            $mailpitUrl = $this->emailSettings->mailpit_url . '/api/v1/messages';
            $response = Http::timeout(5)->get($mailpitUrl);
            
            if ($response->successful()) {
                $data = $response->json();
                $messages = $data['messages'] ?? [];
                
                // If Mailpit has no messages, return mock data for demo purposes
                if (empty($messages)) {
                    return $this->getMockEmails();
                }
                
                return $messages;
            }
        } catch (\Exception $e) {
            // If Mailpit is not available, return mock data
            return $this->getMockEmails();
        }

        // Fallback to mock data
        return $this->getMockEmails();
    }

    /**
     * Get specific email from Mailpit API
     */
    private function getMailpitEmail($id)
    {
        // Check if we should use Mailpit
        if (!$this->emailSettings->shouldUseMailpit()) {
            return $this->getMockEmail($id);
        }

        try {
            $mailpitUrl = $this->emailSettings->mailpit_url . "/api/v1/message/{$id}";
            $response = Http::timeout(5)->get($mailpitUrl);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // If Mailpit returns data, use it, otherwise return mock data
                if (!empty($data)) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // If Mailpit is not available, return mock data
            return $this->getMockEmail($id);
        }

        // Fallback to mock data
        return $this->getMockEmail($id);
    }

    /**
     * Mock email data when Mailpit is not available
     */
    private function getMockEmails()
    {
        $adminConfig = $this->emailSettings->getAdminEmailConfig();
        $councilConfig = $this->emailSettings->getCouncilEmailConfig();

        return [
            [
                'ID' => '1',
                'From' => [
                    'Address' => 'propietario.102@conjunto.com', 
                    'Name' => 'María González - Apt 102'
                ],
                'To' => [[
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ]],
                'Subject' => 'Solicitud de certificación de paz y salvo',
                'Date' => now()->subMinutes(30)->toISOString(),
                'Read' => false,
                'Size' => 1024,
                'Snippet' => 'Buenos días, necesito la certificación de paz y salvo para...'
            ],
            [
                'ID' => '2',
                'From' => [
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ],
                'To' => [[
                    'Address' => $councilConfig['address'] ?: 'concejo@habitta.com', 
                    'Name' => $councilConfig['name'] ?: 'Concejo'
                ]],
                'Subject' => 'Informe mensual de administración - Enero 2025',
                'Date' => now()->subHours(2)->toISOString(),
                'Read' => false,
                'Size' => 2560,
                'Snippet' => 'Estimado concejo, adjunto el informe mensual de administración...'
            ],
            [
                'ID' => '3',
                'From' => [
                    'Address' => $councilConfig['address'] ?: 'concejo@habitta.com', 
                    'Name' => $councilConfig['name'] ?: 'Concejo'
                ],
                'To' => [[
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ]],
                'Subject' => 'Aprobación de presupuesto obras comunes',
                'Date' => now()->subHours(5)->toISOString(),
                'Read' => true,
                'Size' => 1890,
                'Snippet' => 'Se aprueba el presupuesto presentado para las obras de...'
            ],
            [
                'ID' => '4',
                'From' => [
                    'Address' => 'mantenimiento@empresaservicios.com', 
                    'Name' => 'Empresa de Mantenimiento'
                ],
                'To' => [[
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ]],
                'Subject' => 'Cotización mantenimiento ascensores',
                'Date' => now()->subDays(1)->toISOString(),
                'Read' => true,
                'Size' => 3200,
                'Snippet' => 'Estimados, adjuntamos la cotización solicitada para...'
            ],
            [
                'ID' => '5',
                'From' => [
                    'Address' => 'propietario.205@conjunto.com', 
                    'Name' => 'Carlos Rodríguez - Apt 205'
                ],
                'To' => [[
                    'Address' => $councilConfig['address'] ?: 'concejo@habitta.com', 
                    'Name' => $councilConfig['name'] ?: 'Concejo'
                ]],
                'Subject' => 'Queja por ruido en horas nocturnas',
                'Date' => now()->subDays(1)->subHours(3)->toISOString(),
                'Read' => false,
                'Size' => 1456,
                'Snippet' => 'Señores del concejo, me dirijo a ustedes para reportar...'
            ],
            [
                'ID' => '6',
                'From' => [
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ],
                'To' => [[
                    'Address' => 'propietarios@conjunto.com', 
                    'Name' => 'Todos los Propietarios'
                ]],
                'Subject' => 'Aviso: Corte de agua programado',
                'Date' => now()->subDays(2)->toISOString(),
                'Read' => true,
                'Size' => 890,
                'Snippet' => 'Estimados propietarios, informamos que el día viernes...'
            ],
            [
                'ID' => '7',
                'From' => [
                    'Address' => 'contador@contabilidadexterna.com', 
                    'Name' => 'Contadora Externa'
                ],
                'To' => [[
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ]],
                'Subject' => 'Estados financieros diciembre 2024',
                'Date' => now()->subDays(3)->toISOString(),
                'Read' => true,
                'Size' => 4567,
                'Snippet' => 'Adjunto los estados financieros correspondientes al mes...'
            ],
            [
                'ID' => '8',
                'From' => [
                    'Address' => $councilConfig['address'] ?: 'concejo@habitta.com', 
                    'Name' => $councilConfig['name'] ?: 'Concejo'
                ],
                'To' => [[
                    'Address' => 'propietarios@conjunto.com', 
                    'Name' => 'Todos los Propietarios'
                ]],
                'Subject' => 'Convocatoria Asamblea General Ordinaria',
                'Date' => now()->subDays(4)->toISOString(),
                'Read' => true,
                'Size' => 2890,
                'Snippet' => 'Por medio de la presente se convoca a todos los propietarios...'
            ],
            [
                'ID' => '9',
                'From' => [
                    'Address' => 'seguridad@empresaseguridad.com', 
                    'Name' => 'Empresa de Vigilancia'
                ],
                'To' => [[
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ]],
                'Subject' => 'Reporte semanal de seguridad',
                'Date' => now()->subDays(5)->toISOString(),
                'Read' => true,
                'Size' => 1789,
                'Snippet' => 'Reporte de novedades de seguridad de la semana del...'
            ],
            [
                'ID' => '10',
                'From' => [
                    'Address' => 'propietario.301@conjunto.com', 
                    'Name' => 'Ana Martínez - Apt 301'
                ],
                'To' => [[
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ]],
                'Subject' => 'Solicitud permiso para renovación apartamento',
                'Date' => now()->subWeek()->toISOString(),
                'Read' => true,
                'Size' => 1234,
                'Snippet' => 'Solicito permiso para realizar renovaciones en mi apartamento...'
            ]
        ];
    }

    /**
     * Mock specific email data
     */
    private function getMockEmail($id)
    {
        $adminConfig = $this->emailSettings->getAdminEmailConfig();
        $councilConfig = $this->emailSettings->getCouncilEmailConfig();

        $emails = [
            '1' => [
                'ID' => '1',
                'From' => [
                    'Address' => 'propietario.102@conjunto.com', 
                    'Name' => 'María González - Apt 102'
                ],
                'To' => [[
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ]],
                'Subject' => 'Solicitud de certificación de paz y salvo',
                'Date' => now()->subMinutes(30)->toISOString(),
                'Read' => false,
                'Size' => 1024,
                'HTML' => '<p>Buenos días,</p><p>Espero se encuentren bien. Me dirijo a ustedes para solicitar la certificación de paz y salvo de mi apartamento 102, ya que necesito realizar algunos trámites bancarios.</p><p>Mi número de cédula es 52.123.456 y llevo viviendo en el conjunto desde hace 3 años. Siempre he estado al día con mis pagos de administración.</p><p>Agradezco su pronta respuesta.</p><p>Cordialmente,<br><strong>María González</strong><br>Apartamento 102<br>Teléfono: 300-123-4567</p>',
                'Text' => 'Buenos días, Me dirijo a ustedes para solicitar la certificación de paz y salvo de mi apartamento 102. Cordialmente, María González - Apt 102'
            ],
            '2' => [
                'ID' => '2',
                'From' => [
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ],
                'To' => [[
                    'Address' => $councilConfig['address'] ?: 'concejo@habitta.com', 
                    'Name' => $councilConfig['name'] ?: 'Concejo'
                ]],
                'Subject' => 'Informe mensual de administración - Enero 2025',
                'Date' => now()->subHours(2)->toISOString(),
                'Read' => false,
                'Size' => 2560,
                'HTML' => '<h2>Informe Mensual de Administración</h2><h3>Enero 2025</h3><p><strong>Estimado Concejo de Administración,</strong></p><p>Adjunto el informe correspondiente al mes de enero 2025 con los siguientes puntos:</p><ul><li><strong>Ingresos totales:</strong> $45.280.000</li><li><strong>Gastos operacionales:</strong> $38.950.000</li><li><strong>Saldo disponible:</strong> $6.330.000</li></ul><h4>Actividades realizadas:</h4><ul><li>Mantenimiento preventivo de ascensores</li><li>Limpieza y desinfección de áreas comunes</li><li>Reparación de bomba de agua</li><li>Podas en zonas verdes</li></ul><h4>Observaciones:</h4><p>Se requiere aprobación para la renovación del contrato de vigilancia que vence el 28 de febrero.</p><p>Cordialmente,<br>' . ($adminConfig['signature'] ?: 'Administración del Conjunto') . '</p>',
                'Text' => 'Informe mensual de administración - Enero 2025. Ingresos: $45.280.000, Gastos: $38.950.000, Saldo: $6.330.000.'
            ],
            '3' => [
                'ID' => '3',
                'From' => [
                    'Address' => $councilConfig['address'] ?: 'concejo@habitta.com', 
                    'Name' => $councilConfig['name'] ?: 'Concejo'
                ],
                'To' => [[
                    'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                    'Name' => $adminConfig['name'] ?: 'Administración'
                ]],
                'Subject' => 'Aprobación de presupuesto obras comunes',
                'Date' => now()->subHours(5)->toISOString(),
                'Read' => true,
                'Size' => 1890,
                'HTML' => '<p><strong>Estimada Administración,</strong></p><p>En reunión del concejo celebrada el día de hoy, se tomaron las siguientes decisiones:</p><h4>APROBADO:</h4><ul><li>Presupuesto para reparación de fachada: $8.500.000</li><li>Renovación del contrato de vigilancia por 12 meses</li><li>Compra de nuevos equipos de jardinería: $2.300.000</li></ul><h4>PENDIENTE:</h4><ul><li>Cotización adicional para el sistema de cámaras</li><li>Evaluación del estado de la piscina</li></ul><p>Autorizo proceder con las actividades aprobadas.</p><p>Cordialmente,<br>' . ($councilConfig['signature'] ?: 'Concejo de Administración') . '</p>',
                'Text' => 'Se aprueba presupuesto para reparación de fachada por $8.500.000 y renovación contrato vigilancia.'
            ],
            '5' => [
                'ID' => '5',
                'From' => [
                    'Address' => 'propietario.205@conjunto.com', 
                    'Name' => 'Carlos Rodríguez - Apt 205'
                ],
                'To' => [[
                    'Address' => $councilConfig['address'] ?: 'concejo@habitta.com', 
                    'Name' => $councilConfig['name'] ?: 'Concejo'
                ]],
                'Subject' => 'Queja por ruido en horas nocturnas',
                'Date' => now()->subDays(1)->subHours(3)->toISOString(),
                'Read' => false,
                'Size' => 1456,
                'HTML' => '<p><strong>Señores del Concejo,</strong></p><p>Me dirijo a ustedes para presentar una queja formal por los constantes ruidos que se presentan en el apartamento 206, ubicado justo encima del mío.</p><h4>Detalles de la situación:</h4><ul><li><strong>Horarios:</strong> Entre las 11:00 PM y 2:00 AM</li><li><strong>Tipo de ruido:</strong> Música alta, televisor y conversaciones</li><li><strong>Frecuencia:</strong> Viernes y sábados principalmente</li><li><strong>Duración:</strong> Esta situación lleva presentándose por más de 2 meses</li></ul><p>He intentado hablar directamente con los vecinos pero no han mostrado disposición para solucionar el problema.</p><p>Solicito respetuosamente su intervención para que se haga cumplir el reglamento de propiedad horizontal.</p><p>Quedo atento a su respuesta.</p><p><strong>Carlos Rodríguez</strong><br>Apartamento 205<br>Cédula: 79.456.123<br>Teléfono: 301-987-6543</p>',
                'Text' => 'Queja formal por ruidos del apartamento 206 en horarios nocturnos. Solicito intervención del concejo.'
            ]
        ];

        return $emails[$id] ?? [
            'ID' => $id,
            'From' => [
                'Address' => $adminConfig['address'] ?: 'admin@habitta.com', 
                'Name' => $adminConfig['name'] ?: 'Administración'
            ],
            'To' => [[
                'Address' => $councilConfig['address'] ?: 'concejo@habitta.com', 
                'Name' => $councilConfig['name'] ?: 'Concejo'
            ]],
            'Subject' => 'Correo de ejemplo',
            'Date' => now()->subHours(1)->toISOString(),
            'Read' => false,
            'Size' => 1024,
            'HTML' => '<p>Este es un correo de ejemplo para demostrar la funcionalidad del sistema.</p><p>Saludos cordiales,<br>' . ($adminConfig['signature'] ?: 'Administración') . '</p>',
            'Text' => 'Este es un correo de ejemplo para demostrar la funcionalidad del sistema.'
        ];
    }
}