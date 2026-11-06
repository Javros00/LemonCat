@extends('layouts.app')
@section('titulo', 'Lista de precios')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Lista de precios y costos</h1>
        <div class="muted">Cambia los valores que necesites y guarda. Cada cambio queda en el historial.</div>
    </div>
    <div class="acciones">
        <a class="btn btn-sec" href="{{ route('precios.historial') }}">Historial de cambios</a>
        <a class="btn btn-sec" href="{{ route('catalogo.importar') }}">Cargar desde Excel</a>
    </div>
</div>

<form class="tarjeta filtros" method="GET">
    <div class="campo">
        <label>Tipo o familia</label>
        <select name="tipo" onchange="this.form.submit()">
            <option value="">Todos</option>
            @foreach ($tipos as $t)<option @selected(request('tipo') === $t)>{{ $t }}</option>@endforeach
        </select>
    </div>
    <div class="campo">
        <label>Ajuste rápido a los precios mostrados</label>
        <div style="display:flex;gap:6px">
            <input type="number" step="0.01" id="ajuste" placeholder="Ej. 10" style="width:110px">
            <select id="ajuste-modo" style="width:auto">
                <option value="pct">% de aumento</option>
                <option value="monto">$ de aumento</option>
            </select>
            <button type="button" class="btn btn-sec" id="aplicar-ajuste">Aplicar</button>
        </div>
    </div>
    <p class="muted" style="margin:0;font-size:.85rem;flex-basis:100%">El ajuste solo cambia lo que ves en pantalla (no toca precios en 0). Revisa y luego presiona Guardar.</p>
</form>

<form method="POST" action="{{ route('precios.guardar') }}" class="tarjeta" id="form-precios">
    @csrf
    <div class="tabla-env">
        <table>
            <thead><tr><th>Clave</th><th>Descripción</th><th>Tipo</th><th style="width:130px">Precio</th><th style="width:130px">Costo</th><th class="num">Margen</th></tr></thead>
            <tbody>
            @forelse ($articulos as $a)
                <tr>
                    <td>{{ $a->clave }}</td>
                    <td>{{ $a->descripcion }}</td>
                    <td>{{ $a->tipo }}</td>
                    <td><input type="number" step="0.01" min="0" class="in-precio" name="precios[{{ $a->id }}][precio]" value="{{ number_format((float) $a->precio, 2, '.', '') }}" data-original="{{ number_format((float) $a->precio, 2, '.', '') }}"></td>
                    <td><input type="number" step="0.01" min="0" class="in-costo" name="precios[{{ $a->id }}][costo]" value="{{ number_format((float) $a->costo, 2, '.', '') }}" data-original="{{ number_format((float) $a->costo, 2, '.', '') }}"></td>
                    <td class="num margen">—</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">No hay artículos activos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="acciones" style="margin-top:14px;justify-content:space-between;align-items:center">
        <span class="muted" id="contador">Sin cambios</span>
        <button class="btn" type="submit">Guardar cambios</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const filas = [...document.querySelectorAll('#form-precios tbody tr')].filter(tr => tr.querySelector('.in-precio'));
    function actualizar() {
        let cambios = 0;
        filas.forEach(tr => {
            const p = tr.querySelector('.in-precio'), c = tr.querySelector('.in-costo');
            const vp = parseFloat(p.value) || 0, vc = parseFloat(c.value) || 0;
            tr.querySelector('.margen').textContent = vp > 0 ? Math.round((vp - vc) / vp * 100) + '%' : '—';
            const cambio = Math.abs(vp - parseFloat(p.dataset.original)) > 0.001 || Math.abs(vc - parseFloat(c.dataset.original)) > 0.001;
            tr.style.background = cambio ? 'var(--aviso-claro)' : '';
            if (cambio) cambios++;
        });
        document.getElementById('contador').textContent = cambios ? cambios + ' artículos con cambios sin guardar' : 'Sin cambios';
    }
    document.getElementById('form-precios').addEventListener('input', actualizar);
    document.getElementById('aplicar-ajuste').addEventListener('click', () => {
        const v = parseFloat(document.getElementById('ajuste').value);
        if (isNaN(v)) return;
        const pct = document.getElementById('ajuste-modo').value === 'pct';
        filas.forEach(tr => {
            const p = tr.querySelector('.in-precio');
            const actual = parseFloat(p.value) || 0;
            if (actual <= 0) return;
            const nuevo = pct ? actual * (1 + v / 100) : actual + v;
            p.value = Math.max(0, nuevo).toFixed(2);
        });
        actualizar();
    });
    let sucio = false;
    document.getElementById('form-precios').addEventListener('input', () => sucio = true);
    document.getElementById('form-precios').addEventListener('submit', () => sucio = false);
    window.addEventListener('beforeunload', e => { if (sucio) { e.preventDefault(); e.returnValue = ''; } });
    actualizar();
})();
</script>
@endpush
