@component('mail::message')
# Trabajo de Servicio Completado

Estimado/a **{{ $customer->business_name }}**,

Le informamos que el trabajo técnico correspondiente a su orden fue completado.

@component('mail::panel')
**Orden #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}**  
**Técnico:** {{ $technician?->name ?? 'Sin asignar' }}  
**Diagnóstico:** {{ $parte->diagnosis }}  
**Trabajo realizado:** {{ $parte->work_done }}
@if($parte->parts_used && count($parte->parts_used) > 0)
**Repuestos utilizados:**
@foreach($parte->parts_used as $repuesto)
- {{ $repuesto['nombre'] ?? $repuesto }} x{{ $repuesto['cantidad'] ?? 1 }}
@endforeach
@endif
**Fecha:** {{ $parte->created_at->format('d/m/Y H:i') }}
@endcomponent

Si tiene alguna consulta sobre el trabajo realizado, no dude en contactarnos.

{{ config('app.name') }}
@endcomponent
