<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $venta->folio }}</title>
    <style>
        body { font: 13px/1.4 ui-monospace, Menlo, Consolas, monospace; color: #000; background: #fff; margin: 0; padding: 12px; }
        .t { width: 72mm; max-width: 100%; margin: 0 auto; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 4px; }
        .c { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        td { padding: 2px 0; vertical-align: top; }
        .r { text-align: right; white-space: nowrap; }
        .sep { border-top: 1px dashed #000; margin: 6px 0; }
        .tot td { font-weight: bold; font-size: 15px; }
        .cancel { border: 2px solid #000; text-align: center; font-weight: bold; padding: 4px; margin: 6px 0; }
        button { display: block; margin: 14px auto 0; padding: 8px 16px; font: inherit; cursor: pointer; }
        @media print { button { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="t">
    <h1>{{ config('ventas.negocio') }}</h1>
    <div class="c">Folio {{ $venta->folio }}<br>{{ $venta->fecha->format('d/m/Y H:i') }}<br>Atendió: {{ $venta->vendedor->name }}</div>
    @if ($venta->cancelada)<div class="cancel">VENTA CANCELADA</div>@endif
    <div class="sep"></div>
    <table>
        @foreach ($venta->detalles as $d)
            <tr><td colspan="2">{{ $d->descripcion }}</td></tr>
            <tr><td>&nbsp;@cant($d->cantidad) × @dinero($d->precio)</td><td class="r">@dinero($d->importe)</td></tr>
        @endforeach
    </table>
    <div class="sep"></div>
    <table>
        <tr class="tot"><td>TOTAL</td><td class="r">@dinero($venta->total)</td></tr>
        <tr><td>Pago</td><td class="r">{{ \App\Models\Venta::FORMAS_PAGO[$venta->forma_pago] ?? $venta->forma_pago }}</td></tr>
        @if ($venta->pago_con !== null)
            <tr><td>Recibido</td><td class="r">@dinero($venta->pago_con)</td></tr>
            <tr><td>Cambio</td><td class="r">@dinero($venta->cambio)</td></tr>
        @endif
    </table>
    <div class="sep"></div>
    <div class="c">Precios con IVA incluido<br>¡Gracias por su compra!</div>
    <button onclick="window.print()">Imprimir</button>
</div>
</body>
</html>
