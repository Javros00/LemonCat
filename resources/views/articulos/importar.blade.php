@extends('layouts.app')
@section('titulo', 'Importar catálogo')

@section('contenido')
<div class="encabezado">
    <h1>Cargar catálogo o lista de precios desde Excel</h1>
    <a class="btn btn-sec" href="{{ route('articulos.lista') }}">‹ Artículos</a>
</div>

@if (session('errores_importacion'))
    <div class="aviso aviso-info">
        Algunas filas no se cargaron:
        <ul>@foreach (session('errores_importacion') as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="rejilla">
    <form class="tarjeta" method="POST" action="{{ route('catalogo.importar.subir') }}" enctype="multipart/form-data">
        @csrf
        <h2>Subir archivo</h2>
        <div class="campo">
            <label for="archivo">Archivo Excel (.xlsx, .xls) o CSV</label>
            <input type="file" id="archivo" name="archivo" accept=".xlsx,.xls,.csv" required>
        </div>
        <button class="btn" type="submit">Cargar</button>
    </form>

    <div class="tarjeta">
        <h2>Cómo debe venir el archivo</h2>
        <p>La primera fila lleva los encabezados. Usa los mismos de tu lista:</p>
        <table>
            <thead><tr><th>plu</th><th>producto</th><th>tipo</th><th>precio</th><th>costo</th></tr></thead>
            <tbody><tr><td>PLU0003</td><td>Muffin</td><td>Pan</td><td>30</td><td>15</td></tr></tbody>
        </table>
        <ul class="muted" style="font-size:.9rem;padding-left:18px">
            <li>Si la clave ya existe, se actualiza (precio, costo, descripción y tipo). Si no existe, se crea.</li>
            <li>Columnas opcionales: <strong>iva</strong> (0, 8 o 16) y <strong>activo</strong> (SI/NO).</li>
            <li>Una celda vacía deja ese dato como está.</li>
            <li>Para cambiar solo precios, descarga el catálogo, edítalo en Excel y vuélvelo a subir.</li>
        </ul>
        <a class="btn btn-sec" href="{{ route('catalogo.exportar') }}">Descargar catálogo actual</a>
    </div>
</div>
@endsection
