@extends('layouts.app')
@section('titulo', 'Panel')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Panel</h1>
        <div class="muted">{{ today()->translatedFormat('l j \d\e F Y') }}</div>
    </div>
    <div class="acciones">
        <a class="btn" href="{{ route('corte.diario') }}">Corte del día</a>
        <a class="btn btn-sec" href="{{ route('corte.mensual') }}">Corte del mes</a>
    </div>
</div>

<h2>Hoy</h2>
<div class="kpis">
    <div class="kpi destacado"><div class="etq">Vendido hoy</div><div class="val">@dinero($hoy['total'])</div></div>
    <div class="kpi"><div class="etq">Ventas</div><div class="val">{{ $hoy['ventas'] }}</div></div>
    <div class="kpi"><div class="etq">Ticket promedio</div><div class="val">@dinero($hoy['ticket_promedio'])</div></div>
    <div class="kpi"><div class="etq">Utilidad bruta</div><div class="val">@dinero($hoy['utilidad'])</div></div>
</div>

<h2>{{ ucfirst(today()->translatedFormat('F Y')) }}</h2>
<div class="kpis">
    <div class="kpi destacado"><div class="etq">Vendido en el mes</div><div class="val">@dinero($mes['total'])</div></div>
    <div class="kpi"><div class="etq">Ventas</div><div class="val">{{ $mes['ventas'] }}</div></div>
    <div class="kpi"><div class="etq">Costo</div><div class="val">@dinero($mes['costo'])</div></div>
    <div class="kpi"><div class="etq">Margen</div><div class="val">{{ $mes['margen'] }}%</div></div>
</div>

<div class="rejilla">
    <div class="tarjeta">
        <h2>Vendedores hoy</h2>
        @if ($vendedoresHoy->isEmpty())
            <p class="vacio">Sin ventas hoy.</p>
        @else
            <table>
                <thead><tr><th>Vendedor</th><th class="num">Ventas</th><th class="num">Total</th></tr></thead>
                <tbody>
                @foreach ($vendedoresHoy as $v)
                    <tr><td>{{ $v->vendedor }}</td><td class="num">{{ $v->ventas }}</td><td class="num">@dinero($v->total)</td></tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="tarjeta">
        <h2>Últimas ventas</h2>
        @if ($ultimas->isEmpty())
            <p class="vacio">Aún no hay ventas.</p>
        @else
            <table>
                <thead><tr><th>Folio</th><th>Hora</th><th>Vendedor</th><th class="num">Total</th></tr></thead>
                <tbody>
                @foreach ($ultimas as $v)
                    <tr @class(['tachada' => $v->cancelada])>
                        <td><a href="{{ route('ventas.ticket', $v) }}" target="_blank">{{ $v->folio }}</a></td>
                        <td>{{ $v->fecha->format('d/m H:i') }}</td>
                        <td>{{ $v->vendedor->name }}</td>
                        <td class="num">@dinero($v->total)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p style="margin:10px 0 0"><a href="{{ route('ventas.lista') }}">Ver todas</a></p>
        @endif
    </div>
</div>
@endsection
