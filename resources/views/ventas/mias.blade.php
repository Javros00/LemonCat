@extends('layouts.app')
@section('titulo', 'Mis ventas de hoy')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Mis ventas de hoy</h1>
        <div class="muted">{{ today()->translatedFormat('l j \d\e F Y') }}</div>
    </div>
    <a class="btn" href="{{ route('ventas.nueva') }}">+ Nueva venta</a>
</div>

<div class="kpis">
    <div class="kpi destacado"><div class="etq">Total vendido</div><div class="val">@dinero($total)</div></div>
    <div class="kpi"><div class="etq">Ventas</div><div class="val">{{ $ventas->where('cancelada', false)->count() }}</div></div>
</div>

<div class="tarjeta">
    @if ($ventas->isEmpty())
        <p class="vacio">Todavía no registras ventas hoy.</p>
    @else
        <div class="tabla-env">
            <table>
                <thead><tr><th>Folio</th><th>Hora</th><th>Artículos</th><th>Pago</th><th class="num">Total</th><th></th></tr></thead>
                <tbody>
                @foreach ($ventas as $v)
                    <tr @class(['tachada' => $v->cancelada])>
                        <td>{{ $v->folio }}</td>
                        <td>{{ $v->fecha->format('H:i') }}</td>
                        <td>{{ $v->detalles->map(fn ($d) => rtrim(rtrim(number_format((float) $d->cantidad, 3), '0'), '.').' × '.$d->descripcion)->implode(', ') }}</td>
                        <td>{{ \App\Models\Venta::FORMAS_PAGO[$v->forma_pago] ?? $v->forma_pago }}</td>
                        <td class="num">@dinero($v->total)</td>
                        <td>
                            @if ($v->cancelada)<span class="etiqueta roja">Cancelada</span>
                            @else<a href="{{ route('ventas.ticket', $v) }}" target="_blank">Ticket</a>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
