@extends('layouts.app')
@section('titulo', 'Ventas')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Ventas</h1>
        <div class="muted">{{ $totales->ventas }} ventas vigentes · @dinero($totales->total)</div>
    </div>
</div>

<form class="tarjeta filtros" method="GET">
    <div class="campo"><label>Desde</label><input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}"></div>
    <div class="campo"><label>Hasta</label><input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}"></div>
    <div class="campo">
        <label>Vendedor</label>
        <select name="vendedor">
            <option value="">Todos</option>
            @foreach ($vendedores as $v)
                <option value="{{ $v->id }}" @selected(request('vendedor') == $v->id)>{{ $v->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo">
        <label>Estado</label>
        <select name="estado">
            <option value="">Todas</option>
            <option value="vigentes" @selected(request('estado') === 'vigentes')>Vigentes</option>
            <option value="canceladas" @selected(request('estado') === 'canceladas')>Canceladas</option>
        </select>
    </div>
    <button class="btn" type="submit">Filtrar</button>
</form>

<div class="tarjeta">
    @if ($ventas->isEmpty())
        <p class="vacio">No hay ventas en este periodo.</p>
    @else
        <div class="tabla-env">
            <table>
                <thead><tr><th>Folio</th><th>Fecha</th><th>Vendedor</th><th>Pago</th><th class="num">Total</th><th class="num">Costo</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @foreach ($ventas as $v)
                    <tr @class(['tachada' => $v->cancelada])>
                        <td><a href="{{ route('ventas.ticket', $v) }}" target="_blank">{{ $v->folio }}</a></td>
                        <td>{{ $v->fecha->format('d/m/Y H:i') }}</td>
                        <td>{{ $v->vendedor->name }}</td>
                        <td>{{ \App\Models\Venta::FORMAS_PAGO[$v->forma_pago] ?? $v->forma_pago }}</td>
                        <td class="num">@dinero($v->total)</td>
                        <td class="num">@dinero($v->costo_total)</td>
                        <td>
                            @if ($v->cancelada)
                                <span class="etiqueta roja" title="{{ $v->motivo_cancelacion }}">Cancelada</span>
                            @else
                                <span class="etiqueta verde">Vigente</span>
                            @endif
                        </td>
                        <td>
                            @unless ($v->cancelada)
                                <form method="POST" action="{{ route('ventas.cancelar', $v) }}" class="form-cancelar">
                                    @csrf
                                    <input type="hidden" name="motivo">
                                    <button class="btn btn-peligro btn-chico" type="submit">Cancelar</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $ventas->links() }}
    @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.form-cancelar').forEach(f => f.addEventListener('submit', e => {
    const motivo = prompt('Motivo de la cancelación:');
    if (!motivo || !motivo.trim()) { e.preventDefault(); return; }
    f.querySelector('[name=motivo]').value = motivo.trim();
}));
</script>
@endpush
