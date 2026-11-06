@extends('layouts.app')
@section('titulo', 'Corte diario')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Corte diario</h1>
        <div class="muted">{{ ucfirst($dia->translatedFormat('l j \d\e F Y')) }}</div>
    </div>
    <form class="filtros" method="GET">
        <div class="campo"><label>Día</label><input type="date" name="fecha" value="{{ $dia->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}"></div>
        <button class="btn btn-sec" type="submit">Ver</button>
        <a class="btn btn-sec" href="{{ route('corte.diario', ['fecha' => $dia->copy()->subDay()->format('Y-m-d')]) }}">‹ Día anterior</a>
    </form>
</div>

<div class="kpis">
    <div class="kpi destacado"><div class="etq">Total del día</div><div class="val">@dinero($resumen['total'])</div></div>
    <div class="kpi"><div class="etq">Ventas</div><div class="val">{{ $resumen['ventas'] }}</div></div>
    <div class="kpi"><div class="etq">Costo</div><div class="val">@dinero($resumen['costo'])</div></div>
    <div class="kpi"><div class="etq">Utilidad bruta</div><div class="val">@dinero($resumen['utilidad'])</div></div>
    <div class="kpi"><div class="etq">Canceladas</div><div class="val">{{ $resumen['canceladas'] }} · @dinero($resumen['total_canceladas'])</div></div>
</div>

@include('reportes._exportar', ['params' => ['periodo' => 'dia', 'fecha' => $dia->format('Y-m-d')]])
@include('reportes._tablas')

@if ($canceladas->isNotEmpty())
    <div class="tarjeta">
        <h2>Ventas canceladas (no se suman)</h2>
        <table>
            <thead><tr><th>Folio</th><th>Hora</th><th>Vendedor</th><th class="num">Total</th><th>Canceló</th><th>Motivo</th></tr></thead>
            <tbody>
            @foreach ($canceladas as $v)
                <tr>
                    <td><a href="{{ route('ventas.ticket', $v) }}" target="_blank">{{ $v->folio }}</a></td>
                    <td>{{ $v->fecha->format('H:i') }}</td>
                    <td>{{ $v->vendedor->name }}</td>
                    <td class="num">@dinero($v->total)</td>
                    <td>{{ $v->canceladaPor->name ?? '—' }}</td>
                    <td>{{ $v->motivo_cancelacion }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
