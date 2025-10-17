<x-mail::message>
# ¡Felicitaciones, {{ $registration->contact_name }}!

Nos complace informarte que tu solicitud de registro como proveedor ha sido **aprobada**.

## Detalles de tu registro:

- **Empresa:** {{ $registration->company_name }}
- **Categoría:** {{ $registration->service_type }}
- **Email de contacto:** {{ $registration->email }}
- **Teléfono:** {{ $registration->phone }}

Ya estás registrado en nuestra plataforma y podrás ser contactado por las administraciones de conjuntos residenciales que necesiten tus servicios.

@if($registration->admin_notes)
## Nota del administrador:

{{ $registration->admin_notes }}
@endif

## Próximos pasos:

1. Mantén actualizada tu información de contacto
2. Responde oportunamente a las solicitudes de los conjuntos
3. Brinda un servicio de calidad para mantener tu reputación

Si tienes alguna pregunta o necesitas actualizar tu información, no dudes en contactarnos.

Gracias por confiar en Tavira,<br>
{{ config('app.name') }}
</x-mail::message>
