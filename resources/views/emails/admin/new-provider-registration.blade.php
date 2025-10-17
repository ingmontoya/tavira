<x-mail::message>
# Nueva Solicitud de Registro de Proveedor

Se ha recibido una nueva solicitud de registro de proveedor que requiere tu revisión y aprobación.

## Detalles del Proveedor:

- **Empresa:** {{ $registration->company_name }}
- **Persona de Contacto:** {{ $registration->contact_name }}
- **Email:** {{ $registration->email }}
- **Teléfono:** {{ $registration->phone }}
- **Categoría de Servicio:** {{ $registration->service_type }}

@if($registration->description)
## Descripción de Servicios:

{{ $registration->description }}
@endif

---

**Fecha de Solicitud:** {{ $registration->created_at->format('d/m/Y H:i') }}

<x-mail::button :url="url('/admin/provider-registrations/' . $registration->id)">
Ver Solicitud Completa
</x-mail::button>

Por favor, revisa esta solicitud y decide si aprobar o rechazar al proveedor desde el panel de administración.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
