@extends('layouts.app')
@section('titulo', 'Artículos')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Catálogo de artículos</h1>
        <div class="muted">{{ $articulos->count() }} artículos en la lista</div>
    </div>
    <div class="acciones">
        <a class="btn" href="{{ route('articulos.nuevo') }}">+ Nuevo artículo</a>
        <a class="btn btn-sec" href="{{ route('catalogo.importar') }}">Importar Excel</a>
        <a class="btn btn-sec" href="{{ route('catalogo.exportar') }}">Descargar Excel</a>
    </div>
</div>

@if ($sinPrecio > 0)
    <div class="aviso aviso-info">
        {{ $sinPrecio }} artículos activos no tienen precio y no se pueden vender.
        <a href="{{ route('articulos.lista', ['estado' => 'sin_precio']) }}">Ver cuáles</a> ·
        <a href="{{ route('precios.editar') }}">Asignar precios</a>
    </div>
@endif

<form class="tarjeta filtros" method="GET">
    <div class="campo" style="flex:1;min-width:200px"><label>Buscar</label><input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Clave o nombre"></div>
    <div class="campo">
        <label>Tipo o familia</label>
        <select name="tipo">
            <option value="">Todos</option>
            @foreach ($tipos as $t)<option @selected(request('tipo') === $t)>{{ $t }}</option>@endforeach
        </select>
    </div>
    <div class="campo">
        <label>Mostrar</label>
        <select name="estado">
            <option value="">Todos</option>
            <option value="sin_precio" @selected(request('estado') === 'sin_precio')>Sin precio</option>
            <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
        </select>
    </div>
    <button class="btn" type="submit">Filtrar</button>
</form>

<div class="tarjeta">
    <div class="tabla-env">
        <table>
            <thead><tr><th>Clave</th><th>Descripción</th><th>Tipo</th><th class="num">Precio</th><th class="num">Costo</th><th class="num">Margen</th><th>IVA</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse ($articulos as $a)
                @php $p = (float) $a->precio; $c = (float) $a->costo; @endphp
                <tr>
                    <td>{{ $a->clave }}</td>
                    <td>{{ $a->descripcion }}</td>
                    <td>{{ $a->tipo }}</td>
                    <td class="num">@if ($p > 0) @dinero($p) @else <span class="etiqueta roja">Sin precio</span> @endif</td>
                    <td class="num">@dinero($c)</td>
                    <td class="num">{{ $p > 0 ? number_format(($p - $c) / $p * 100, 0).'%' : '—' }}</td>
                    <td>{{ (int) $a->tasa_iva }}%</td>
                    <td>@if ($a->activo)<span class="etiqueta verde">Activo</span>@else<span class="etiqueta">Inactivo</span>@endif</td>
                    <td class="acciones">
                        <a class="btn btn-sec btn-chico" href="{{ route('articulos.editar', $a) }}">Editar</a>
                        <form method="POST" action="{{ route('articulos.activo', $a) }}">
                            @csrf
                            <button class="btn btn-sec btn-chico" type="submit">{{ $a->activo ? 'Desactivar' : 'Activar' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="vacio">No hay artículos con ese filtro.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
