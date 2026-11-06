@extends('layouts.app')
@section('titulo', 'Nueva venta')

@section('contenido')
@if (session('ultima_venta'))
    <p class="no-imprimir" style="margin:-6px 0 14px">
        <a href="{{ route('ventas.ticket', session('ultima_venta')) }}" target="_blank">Ver / imprimir ticket de la última venta</a>
    </p>
@endif

<div class="pos">
    {{-- Catálogo --}}
    <section>
        <div class="pos-buscar">
            <input type="search" id="buscar" placeholder="Buscar por clave o nombre… (Enter agrega si la clave es exacta)" autocomplete="off">
            <div class="chips" id="chips">
                <button type="button" class="chip activo" data-tipo="">Todos</button>
                @foreach ($tipos as $t)
                    <button type="button" class="chip" data-tipo="{{ $t }}">{{ $t }}</button>
                @endforeach
            </div>
        </div>
        <div class="productos" id="productos"></div>
        <p class="vacio" id="sin-resultados" hidden>No hay artículos con esa búsqueda.</p>
    </section>

    {{-- Ticket --}}
    <aside class="tarjeta ticket">
        <h2>Venta actual</h2>
        <form method="POST" action="{{ route('ventas.guardar') }}" id="form-venta">
            @csrf
            <div class="ticket-lista" id="ticket-lista"></div>
            <p class="vacio" id="ticket-vacio">Toca un artículo para agregarlo.</p>
            <div id="items-ocultos"></div>

            <div class="ticket-total"><span>Total</span><span id="total">$0.00</span></div>

            <div class="pagos">
                @foreach ($formasPago as $clave => $nombre)
                    <label>
                        <input type="radio" name="forma_pago" value="{{ $clave }}" @checked(old('forma_pago', 'efectivo') === $clave)>
                        <span>{{ $nombre }}</span>
                    </label>
                @endforeach
            </div>

            <div id="bloque-efectivo">
                <div class="campo">
                    <label for="pago_con">Recibí en efectivo (opcional)</label>
                    <input type="number" step="0.01" min="0" inputmode="decimal" name="pago_con" id="pago_con" value="{{ old('pago_con') }}" placeholder="0.00">
                </div>
                <div class="cambio"><span>Cambio</span><span id="cambio">—</span></div>
            </div>

            <button type="submit" class="btn btn-grande" id="btn-cobrar" disabled>Cobrar</button>
            <button type="button" class="btn btn-sec" id="btn-limpiar" style="width:100%;margin-top:8px">Vaciar venta</button>
        </form>
    </aside>
</div>

{{-- En celular: total siempre visible y botón para ir a cobrar --}}
<div class="barra-movil" id="barra-movil">
    <div><div style="font-size:.8rem;color:#d8d0c5" id="bm-articulos">0 artículos</div><strong id="bm-total">$0.00</strong></div>
    <a class="btn" href="#form-venta" id="bm-ir">Cobrar ›</a>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const catalogo = @json($catalogo);
    const anterior = @json(old('items', []));
    const porId = new Map(catalogo.map(a => [a.id, a]));
    const porClave = new Map(catalogo.map(a => [a.clave.toUpperCase(), a]));
    const fmt = n => '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const $ = id => document.getElementById(id);
    const buscar = $('buscar'), productos = $('productos'), lista = $('ticket-lista');
    let tipoActivo = '';
    /** carrito: [{id, cantidad}] en orden de captura */
    let carrito = [];

    // Restaurar si hubo un error de validación
    anterior.forEach(it => {
        const a = porId.get(Number(it.articulo_id));
        if (a && a.precio > 0) carrito.push({ id: a.id, cantidad: Number(it.cantidad) || 1 });
    });

    function pintarCatalogo() {
        const q = buscar.value.trim().toLowerCase();
        const visibles = catalogo.filter(a =>
            (!tipoActivo || a.tipo === tipoActivo) &&
            (!q || a.clave.toLowerCase().includes(q) || a.descripcion.toLowerCase().includes(q)));
        productos.innerHTML = visibles.map(a => a.precio > 0
            ? `<button type="button" class="producto" data-id="${a.id}">
                   <span class="p-clave">${esc(a.clave)} · ${esc(a.tipo)}</span>
                   <span class="p-desc">${esc(a.descripcion)}</span>
                   <span class="p-precio">${fmt(a.precio)}</span></button>`
            : `<div class="producto sin-precio" title="El administrador debe asignarle precio">
                   <span class="p-clave">${esc(a.clave)} · ${esc(a.tipo)}</span>
                   <span class="p-desc">${esc(a.descripcion)}</span>
                   <span class="p-precio">Sin precio</span></div>`).join('');
        $('sin-resultados').hidden = visibles.length > 0;
    }

    function agregar(id, cantidad = 1) {
        const a = porId.get(id);
        if (!a || !(a.precio > 0)) return;
        const it = carrito.find(x => x.id === id);
        if (it) it.cantidad = +(it.cantidad + cantidad).toFixed(3);
        else carrito.push({ id, cantidad });
        pintarTicket();
    }

    function total() {
        return carrito.reduce((s, it) => s + Math.round(it.cantidad * porId.get(it.id).precio * 100) / 100, 0);
    }

    function pintarTicket() {
        carrito = carrito.filter(it => it.cantidad > 0);
        lista.innerHTML = carrito.map((it, i) => {
            const a = porId.get(it.id);
            return `<div class="ticket-item">
                <div><div class="ti-desc">${esc(a.descripcion)}</div><div class="muted" style="font-size:.8rem">${esc(a.clave)} · ${fmt(a.precio)} c/u</div></div>
                <div class="ti-imp">${fmt(it.cantidad * a.precio)}</div>
                <div class="ti-ctrl">
                    <button type="button" data-accion="menos" data-i="${i}" aria-label="Quitar uno">−</button>
                    <input type="number" min="0" step="1" inputmode="decimal" value="${it.cantidad}" data-i="${i}" aria-label="Cantidad">
                    <button type="button" data-accion="mas" data-i="${i}" aria-label="Agregar uno">+</button>
                </div>
                <div style="text-align:right"><button type="button" class="ti-quitar" data-accion="quitar" data-i="${i}">Quitar</button></div>
            </div>`;
        }).join('');
        $('ticket-vacio').hidden = carrito.length > 0;
        $('items-ocultos').innerHTML = carrito.map((it, i) =>
            `<input type="hidden" name="items[${i}][articulo_id]" value="${it.id}">
             <input type="hidden" name="items[${i}][cantidad]" value="${it.cantidad}">`).join('');
        $('total').textContent = fmt(total());
        $('bm-total').textContent = fmt(total());
        const piezas = carrito.reduce((s, it) => s + it.cantidad, 0);
        $('bm-articulos').textContent = piezas === 1 ? '1 artículo' : (+piezas.toFixed(3)) + ' artículos';
        $('btn-cobrar').disabled = carrito.length === 0;
        pintarCambio();
    }

    function pintarCambio() {
        const efectivo = document.querySelector('input[name=forma_pago]:checked')?.value === 'efectivo';
        $('bloque-efectivo').hidden = !efectivo;
        const recibido = parseFloat($('pago_con').value);
        if (!efectivo || isNaN(recibido)) { $('cambio').textContent = '—'; return; }
        const c = recibido - total();
        $('cambio').textContent = c < 0 ? 'Falta ' + fmt(-c) : fmt(c);
        $('cambio').style.color = c < 0 ? 'var(--error)' : '';
    }

    productos.addEventListener('click', e => {
        const b = e.target.closest('button.producto');
        if (b) agregar(Number(b.dataset.id));
    });

    lista.addEventListener('click', e => {
        const b = e.target.closest('button[data-accion]');
        if (!b) return;
        const it = carrito[Number(b.dataset.i)];
        if (b.dataset.accion === 'mas') it.cantidad = +(it.cantidad + 1).toFixed(3);
        if (b.dataset.accion === 'menos') it.cantidad = Math.max(0, +(it.cantidad - 1).toFixed(3));
        if (b.dataset.accion === 'quitar') it.cantidad = 0;
        pintarTicket();
    });

    lista.addEventListener('change', e => {
        if (e.target.matches('input[data-i]')) {
            const v = parseFloat(e.target.value);
            carrito[Number(e.target.dataset.i)].cantidad = isNaN(v) || v < 0 ? 0 : +v.toFixed(3);
            pintarTicket();
        }
    });

    // Enter en una cantidad solo confirma la cantidad, no cobra
    lista.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.matches('input[data-i]')) { e.preventDefault(); e.target.blur(); }
    });

    buscar.addEventListener('input', pintarCatalogo);
    buscar.addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const a = porClave.get(buscar.value.trim().toUpperCase());
        if (a && a.precio > 0) { agregar(a.id); buscar.value = ''; pintarCatalogo(); }
    });

    $('chips').addEventListener('click', e => {
        const c = e.target.closest('.chip');
        if (!c) return;
        tipoActivo = c.dataset.tipo;
        document.querySelectorAll('.chip').forEach(x => x.classList.toggle('activo', x === c));
        pintarCatalogo();
    });

    document.querySelectorAll('input[name=forma_pago]').forEach(r => r.addEventListener('change', pintarCambio));
    $('pago_con').addEventListener('input', pintarCambio);

    $('btn-limpiar').addEventListener('click', () => {
        if (carrito.length && !confirm('¿Vaciar la venta actual?')) return;
        carrito = []; $('pago_con').value = ''; pintarTicket();
    });

    $('form-venta').addEventListener('submit', e => {
        if (!carrito.length) { e.preventDefault(); return; }
        const efectivo = document.querySelector('input[name=forma_pago]:checked')?.value === 'efectivo';
        const recibido = parseFloat($('pago_con').value);
        if (efectivo && !isNaN(recibido) && recibido + 0.001 < total()) {
            e.preventDefault(); alert('El efectivo recibido es menor que el total.'); return;
        }
        if (!efectivo) $('pago_con').value = '';
        $('btn-cobrar').disabled = true;
        $('btn-cobrar').textContent = 'Guardando…';
    });

    document.querySelector('main').classList.add('con-barra-movil');
    $('bm-ir').addEventListener('click', e => { e.preventDefault(); document.querySelector('.ticket').scrollIntoView({ behavior: 'smooth' }); });

    pintarCatalogo();
    pintarTicket();
    if (window.matchMedia('(min-width: 901px)').matches) buscar.focus();
})();
</script>
@endpush
