{{-- Botones de exportación para el sistema local. Recibe $params (periodo + fecha/mes) --}}
<div class="tarjeta no-imprimir">
    <h2>Exportar para el sistema local</h2>
    <p class="muted" style="margin-top:0">Agrupado por artículo: <strong>clave, cantidad, monto</strong> (más descripción como referencia).</p>
    <div class="acciones">
        <a class="btn" href="{{ route('exportar.ventas', $params + ['formato' => 'xlsx']) }}">Excel (.xlsx)</a>
        <a class="btn btn-sec" href="{{ route('exportar.ventas', $params + ['formato' => 'csv']) }}">CSV</a>
        <a class="btn btn-sec" href="{{ route('exportar.ventas', $params + ['formato' => 'xlsx', 'detalle' => 'renglones']) }}">Detalle venta por venta (.xlsx)</a>
        <button type="button" class="btn btn-sec" onclick="window.print()">Imprimir corte</button>
    </div>
</div>
