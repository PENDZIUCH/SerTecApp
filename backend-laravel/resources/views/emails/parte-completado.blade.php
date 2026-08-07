<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parte de Servicio Técnico</title>
<style>
  body { font-family: Arial, sans-serif; color: #333; max-width: 700px; margin: 0 auto; padding: 20px; }
  .header { background: #1a1a2e; color: white; padding: 20px 24px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
  .header h1 { margin: 0; font-size: 20px; }
  .header .order-num { font-size: 14px; opacity: 0.8; }
  .section { border: 1px solid #e0e0e0; border-radius: 4px; margin: 16px 0; overflow: hidden; }
  .section-title { background: #f5f5f5; padding: 10px 16px; font-weight: bold; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #555; border-bottom: 1px solid #e0e0e0; }
  .section-body { padding: 16px; }
  .row { display: flex; gap: 24px; margin-bottom: 12px; }
  .field { flex: 1; }
  .field label { display: block; font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
  .field span { font-size: 14px; color: #333; }
  .text-block { font-size: 14px; line-height: 1.6; white-space: pre-wrap; }
  .repuesto-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
  .firma-img { max-width: 300px; border: 1px solid #ddd; border-radius: 4px; background: white; padding: 8px; }
  .footer { text-align: center; color: #999; font-size: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #eee; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
  .badge-success { background: #d4edda; color: #155724; }
  @media print {
    body { max-width: 100%; }
    .header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>

<div class="header">
  <div>
    <h1>{{ config('app.name') }}</h1>
    <div class="order-num">Parte de Servicio Técnico</div>
  </div>
  <div style="text-align:right">
    <div style="font-size:22px; font-weight:bold">#{{ str_pad($parte->work_order_id, 4, '0', STR_PAD_LEFT) }}</div>
    <div style="font-size:12px; opacity:0.8">{{ $parte->created_at->format('d/m/Y H:i') }}</div>
  </div>
</div>

<div class="section">
  <div class="section-title">Datos del Cliente</div>
  <div class="section-body">
    <div class="row">
      <div class="field">
        <label>Cliente</label>
        <span>{{ $customer->business_name }}</span>
      </div>
      <div class="field">
        <label>Dirección</label>
        <span>{{ $customer->address ?? '—' }}</span>
      </div>
    </div>
    <div class="row">
      <div class="field">
        <label>Teléfono</label>
        <span>{{ $customer->phone ?? '—' }}</span>
      </div>
      <div class="field">
        <label>Email</label>
        <span>{{ $customer->email ?? '—' }}</span>
      </div>
    </div>
  </div>
</div>

<div class="section">
  <div class="section-title">Detalles del Servicio</div>
  <div class="section-body">
    <div class="row">
      <div class="field">
        <label>Técnico</label>
        <span>{{ $technician?->name ?? '—' }}</span>
      </div>
      <div class="field">
        <label>Estado</label>
        <span class="badge badge-success">Completado</span>
      </div>
    </div>
    <div style="margin-top:12px">
      <div class="field" style="margin-bottom:12px">
        <label>Diagnóstico</label>
        <p class="text-block">{{ $parte->diagnosis }}</p>
      </div>
      <div class="field">
        <label>Trabajo Realizado</label>
        <p class="text-block">{{ $parte->work_done }}</p>
      </div>
    </div>
  </div>
</div>

@if($parte->parts_used && count($parte->parts_used) > 0)
<div class="section">
  <div class="section-title">Repuestos Utilizados</div>
  <div class="section-body">
    @foreach($parte->parts_used as $repuesto)
    <div class="repuesto-item">
      <span>{{ $repuesto['nombre'] ?? $repuesto }}</span>
      <span>x{{ $repuesto['cantidad'] ?? 1 }}</span>
    </div>
    @endforeach
  </div>
</div>
@endif

@if($parte->signature)
<div class="section">
  <div class="section-title">Conformidad del Cliente</div>
  <div class="section-body">
    <p style="font-size:13px; color:#666; margin:0 0 12px">El cliente presta conformidad con el trabajo realizado mediante la firma a continuación:</p>
    <img src="{{ $parte->signature }}" alt="Firma del cliente" class="firma-img">
    <p style="font-size:12px; color:#999; margin:8px 0 0">Firmado el {{ $parte->created_at->format('d/m/Y') }} a las {{ $parte->created_at->format('H:i') }} hs.</p>
  </div>
</div>
@endif

<div class="footer">
  <p>Este documento fue generado automáticamente por {{ config('app.name') }}.</p>
  <p>{{ now()->format('d/m/Y H:i') }}</p>
</div>

</body>
</html>
