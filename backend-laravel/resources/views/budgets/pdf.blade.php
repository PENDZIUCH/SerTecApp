<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Presupuesto #{{ $budget->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #333; }
        .info { margin-bottom: 20px; }
        .info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        .footer { margin-top: 40px; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PRESUPUESTO</h1>
        <p>N° {{ $budget->id }}</p>
    </div>

    <div class="info">
        <p><strong>Cliente:</strong> {{ $budget->customer->business_name ?: $budget->customer->name }}</p>
        <p><strong>Fecha:</strong> {{ $budget->created_at->format('d/m/Y') }}</p>
        @if($budget->valid_until)
        <p><strong>Válido hasta:</strong> {{ $budget->valid_until->format('d/m/Y') }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($budget->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">${{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">${{ number_format($budget->total_amount, 2) }} USD</td>
            </tr>
        </tbody>
    </table>

    @if($budget->notes)
    <div style="margin-top: 30px;">
        <p><strong>Notas:</strong></p>
        <p>{{ $budget->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Presupuesto generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
