@extends('layouts.app')
@section('titulo', 'Corte mensual')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Corte mensual</h1>
        <div class="muted">{{ ucfirst($mes->translatedFormat('F Y')) }}</div>
    </div>
    <form class="filtros" method="GET">
        <div class="campo"><label>Mes</label><input type="month" name="mes" value="{{ $mes->format('Y-m') }}" max="{{ today()->format('Y-m') }}"></div>
        <button class="btn btn-sec" type="submit">Ver</button>
        <a class="btn" href="{{ route('poliza', ['mes' => $mes->format('Y-m')]) }}">Póliza del mes</a>
    </form>
</div>

<div class="kpis">
    <div class="kpi destacado"><div class="etq">Vendido en el mes</div><div class="val">@dinero($resumen['total'])</div></div>
    <div class="kpi"><div class="etq">Ventas</div><div class="val">{{ $resumen['ventas'] }}</div></div>
    <div class="kpi"><div class="etq">Costo de ventas</div><div class="val">@dinero($resumen['costo'])</div></div>
    <div class="kpi"><div class="etq">Utilidad bruta</div><div class="val">@dinero($resumen['utilidad'])</div></div>
    <div class="kpi"><div class="etq">Margen</div><div class="val">{{ $resumen['margen'] }}%</div></div>
</div>

@include('reportes._exportar', ['params' => ['periodo' => 'mes', 'mes' => $mes->format('Y-m')]])

<div class="tarjeta">
    <h2>Ventas por día</h2>
    <div class="tabla-env">
        <table>
            <thead><tr><th>Día</th><th class="num">Ventas</th><th class="num">Total</th><th class="num">Costo</th><th class="num">Utilidad</th><th class="no-imprimir"></th></tr></thead>
            <tbody>
            @forelse ($dias as $d)
                <tr>
                    <td>{{ ucfirst($d->dia->translatedFormat('D j')) }}</td>
                    <td class="num">{{ $d->ventas }}</td>
                    <td class="num">@dinero($d->total)</td>
                    <td class="num">@dinero($d->costo)</td>
                    <td class="num">@dinero($d->total - $d->costo)</td>
                    <td class="no-imprimir"><a href="{{ route('corte.diario', ['fecha' => $d->dia->format('Y-m-d')]) }}">Corte</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">Sin ventas en el mes.</td></tr>
            @endforelse
            </tbody>
            @if ($dias->isNotEmpty())
                <tfoot><tr><td>Total</td><td class="num">{{ $dias->sum('ventas') }}</td><td class="num">@dinero($dias->sum('total'))</td><td class="num">@dinero($dias->sum('costo'))</td><td class="num">@dinero($dias->sum('total') - $dias->sum('costo'))</td><td class="no-imprimir"></td></tr></tfoot>
            @endif
        </table>
    </div>
</div>

@include('reportes._tablas')
@endsection
