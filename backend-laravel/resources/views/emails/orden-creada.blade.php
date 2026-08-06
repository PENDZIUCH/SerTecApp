@component('mail::message')
# Confirmación de Orden de Servicio

Estimado/a **{{ $customer->business_name }}**,

Le confirmamos que hemos recibido su solicitud de servicio técnico.

@component('mail::panel')
**Orden #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}**  
**Problema:** {{ $order->description }}  
**Prioridad:** {{ match($order->priority) { 'low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente', default => $order->priority } }}  
**Técnico asignado:** {{ $technician?->name ?? 'Por asignar' }}
@endcomponent

Nos pondremos en contacto a la brevedad para coordinar la visita.

Gracias por confiar en nuestro servicio.

{{ config('app.name') }}
@endcomponent
