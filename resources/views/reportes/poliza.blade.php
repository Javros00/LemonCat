@extends('layouts.app')
@section('titulo', 'Póliza')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Póliza de ventas</h1>
        <div class="muted">{{ ucfirst($mes->translatedFormat('F Y')) }}</div>
    </div>
    <form class="filtros" method="GET">
        <div class="campo"><label>Mes</label><input type="month" name="mes" value="{{ $mes->format('Y-m') }}" max="{{ today()->format('Y-m') }}"></div>
        <button class="btn btn-sec" type="submit">Ver</button>
    </form>
</div>

<div class="tarjeta">
    <div class="poliza-cab">
        <div><span>Tipo</span>{{ $poliza['tipo'] }}</div>
        <div><span>Fecha</span>{{ $poliza['fecha']->format('d/m/Y') }}</div>
        <div><span>Concepto</span>{{ $poliza['concepto'] }}</div>
        <div><span>Estado</span>
            @if ($poliza['cuadra'])<span class="etiqueta verde">Cuadrada</span>@else<span class="etiqueta roja">No cuadra</span>@endif
        </div>
    </div>

    @if (empty($poliza['lineas']))
        <p class="vacio">No hay ventas en este mes.</p>
    @else
        <div class="tabla-env">
            <table>
                <thead><tr><th>Cuenta</th><th>Nombre</th><th>Concepto</th><th class="num">Cargo</th><th class="num">Abono</th></tr></thead>
                <tbody>
                @foreach ($poliza['lineas'] as $l)
                    <tr>
                        <td>{{ $l['cuenta'] }}</td>
                        <td>{{ $l['nombre'] }}</td>
                        <td>{{ $l['concepto'] }}</td>
                        <td class="num">{{ $l['cargo'] ? '$'.number_format($l['cargo'], 2) : '' }}</td>
                        <td class="num">{{ $l['abono'] ? '$'.number_format($l['abono'], 2) : '' }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr><td colspan="3">Totales</td><td class="num">@dinero($poliza['cargos'])</td><td class="num">@dinero($poliza['abonos'])</td></tr></tfoot>
            </table>
        </div>
        <div class="acciones no-imprimir" style="margin-top:14px">
            <a class="btn" href="{{ route('exportar.poliza', ['mes' => $mes->format('Y-m'), 'formato' => 'xlsx']) }}">Descargar Excel</a>
            <a class="btn btn-sec" href="{{ route('exportar.poliza', ['mes' => $mes->format('Y-m'), 'formato' => 'csv']) }}">CSV</a>
            <button type="button" class="btn btn-sec" onclick="window.print()">Imprimir</button>
        </div>
    @endif
</div>

<div class="kpis">
    <div class="kpi"><div class="etq">Total cobrado</div><div class="val">@dinero($poliza['total'])</div></div>
    <div class="kpi"><div class="etq">Ventas sin IVA</div><div class="val">@dinero($poliza['subtotal'])</div></div>
    <div class="kpi"><div class="etq">IVA trasladado</div><div class="val">@dinero($poliza['iva'])</div></div>
    <div class="kpi"><div class="etq">Costo de ventas</div><div class="val">@dinero($poliza['costo'])</div></div>
</div>

<p class="muted no-imprimir" style="font-size:.88rem">
    Las cuentas contables se configuran en el archivo <code>.env</code> (CTA_CAJA, CTA_VENTAS, etc.).
    El IVA se calcula con la tasa de cada artículo; los precios son netos, así que se desglosa del total.
</p>
@endsection
