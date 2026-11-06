{{-- Tablas comunes de los cortes: vendedores, formas de pago, familias y artículos --}}
<div class="rejilla">
    <div class="tarjeta">
        <h2>Por vendedor</h2>
        <table>
            <thead><tr><th>Vendedor</th><th class="num">Ventas</th><th class="num">Total</th></tr></thead>
            <tbody>
            @forelse ($vendedores as $v)
                <tr><td>{{ $v->vendedor }}</td><td class="num">{{ $v->ventas }}</td><td class="num">@dinero($v->total)</td></tr>
            @empty
                <tr><td colspan="3" class="vacio">Sin ventas</td></tr>
            @endforelse
            </tbody>
            @if ($vendedores->isNotEmpty())
                <tfoot><tr><td>Total</td><td class="num">{{ $vendedores->sum('ventas') }}</td><td class="num">@dinero($vendedores->sum('total'))</td></tr></tfoot>
            @endif
        </table>
    </div>
    <div class="tarjeta">
        <h2>Por forma de pago</h2>
        <table>
            <thead><tr><th>Forma</th><th class="num">Ventas</th><th class="num">Total</th></tr></thead>
            <tbody>
            @forelse ($formas as $f)
                <tr><td>{{ $f->nombre }}</td><td class="num">{{ $f->ventas }}</td><td class="num">@dinero($f->total)</td></tr>
            @empty
                <tr><td colspan="3" class="vacio">Sin ventas</td></tr>
            @endforelse
            </tbody>
            @if ($formas->isNotEmpty())
                <tfoot><tr><td>Total</td><td class="num">{{ $formas->sum('ventas') }}</td><td class="num">@dinero($formas->sum('total'))</td></tr></tfoot>
            @endif
        </table>
    </div>
</div>

<div class="tarjeta">
    <h2>Por tipo o familia</h2>
    <div class="tabla-env">
        <table>
            <thead><tr><th>Tipo</th><th class="num">Cantidad</th><th class="num">Monto</th><th class="num">Costo</th><th class="num">Utilidad</th></tr></thead>
            <tbody>
            @forelse ($tipos as $t)
                <tr><td>{{ $t->tipo }}</td><td class="num">@cant($t->cantidad)</td><td class="num">@dinero($t->monto)</td><td class="num">@dinero($t->costo)</td><td class="num">@dinero($t->monto - $t->costo)</td></tr>
            @empty
                <tr><td colspan="5" class="vacio">Sin ventas</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="tarjeta">
    <h2>Por artículo</h2>
    <div class="tabla-env">
        <table>
            <thead><tr><th>Clave</th><th>Descripción</th><th class="num">Cantidad</th><th class="num">Monto</th><th class="num">Costo</th></tr></thead>
            <tbody>
            @forelse ($articulos as $a)
                <tr><td>{{ $a->clave }}</td><td>{{ $a->descripcion }}</td><td class="num">@cant($a->cantidad)</td><td class="num">@dinero($a->monto)</td><td class="num">@dinero($a->costo)</td></tr>
            @empty
                <tr><td colspan="5" class="vacio">Sin ventas</td></tr>
            @endforelse
            </tbody>
            @if ($articulos->isNotEmpty())
                <tfoot><tr><td colspan="2">Total</td><td class="num">@cant($articulos->sum('cantidad'))</td><td class="num">@dinero($articulos->sum('monto'))</td><td class="num">@dinero($articulos->sum('costo'))</td></tr></tfoot>
            @endif
        </table>
    </div>
</div>
