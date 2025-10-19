<x-mail::message>
# Nueva Propuesta Recibida

Hola,

Has recibido una nueva propuesta para tu solicitud de cotización **{{ $quotationRequest->title }}**.

## Detalles de la Propuesta

**Proveedor:** {{ $provider->name }}

**Precio Cotizado:** ${{ number_format($response->quoted_amount, 0, ',', '.') }} COP

@if($response->estimated_days)
**Tiempo Estimado:** {{ $response->estimated_days }} días
@endif

@if($response->proposal)
**Notas del Proveedor:**
{{ $response->proposal }}
@endif

@if($response->attachments)
**Documentos Adjuntos:** Sí (ver en la plataforma)
@endif

<x-mail::button :url="config('app.url') . '/quotation-requests/' . $quotationRequest->id . '/responses/' . $response->id">
Ver Propuesta Completa
</x-mail::button>

Puedes revisar todos los detalles de la propuesta, descargar los documentos adjuntos y aprobarla o rechazarla desde la plataforma.

Gracias,<br>
{{ $tenantName }}

---

<small>Este correo fue enviado automáticamente. Por favor no respondas a este mensaje.</small>
</x-mail::message>
