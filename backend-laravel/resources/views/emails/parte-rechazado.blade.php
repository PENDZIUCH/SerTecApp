<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Actualización de servicio</title>
<style>
  body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
  .header { background: #1a1a2e; color: white; padding: 20px 24px; border-radius: 8px 8px 0 0; }
  .header h1 { margin: 0; font-size: 20px; }
  .body { border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px; padding: 24px; }
  .order-box { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 16px; margin: 16px 0; }
  .footer { text-align: center; color: #999; font-size: 12px; margin-top: 24px; }
</style>
</head>
<body>

<div class="header">
  <h1>{{ config('app.name') }}</h1>
</div>

<div class="body">
  <p>Estimado/a <strong>{{ $customer->business_name }}</strong>,</p>

  <p>Le informamos que el servicio técnico correspondiente a su orden está siendo revisado nuevamente por nuestro equipo.</p>

  <div class="order-box">
    <strong>Orden #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</strong><br>
    <span style="color:#666; font-size:14px;">{{ $order->description }}</span>
    @if($nuevoTecnico)
    <br><br>
    <span style="font-size:14px;">Técnico asignado: <strong>{{ $nuevoTecnico }}</strong></span>
    @endif
  </div>

  <p>Un técnico se va a contactar a la brevedad para coordinar la revisión.</p>

  <p>Disculpe los inconvenientes ocasionados.</p>

  <p>Saludos,<br><strong>{{ config('app.name') }}</strong></p>
</div>

<div class="footer">
  <p>Este mensaje fue generado automáticamente. Por favor no responda este email.</p>
</div>

</body>
</html>
