@extends('layouts.app')
@section('titulo', $articulo->exists ? 'Editar artículo' : 'Nuevo artículo')

@section('contenido')
<div class="encabezado">
    <h1>{{ $articulo->exists ? 'Editar '.$articulo->clave : 'Nuevo artículo' }}</h1>
    <a class="btn btn-sec" href="{{ route('articulos.lista') }}">‹ Volver</a>
</div>

<form class="tarjeta" method="POST" action="{{ $articulo->exists ? route('articulos.actualizar', $articulo) : route('articulos.guardar') }}" style="max-width:720px">
    @csrf
    @if ($articulo->exists) @method('PUT') @endif

    <div class="fila-form">
        <div class="campo">
            <label for="clave">Clave</label>
            <input type="text" id="clave" name="clave" value="{{ old('clave', $articulo->clave) }}" required maxlength="30">
        </div>
        <div class="campo">
            <label for="tipo">Tipo o familia</label>
            <input type="text" id="tipo" name="tipo" list="lista-tipos" value="{{ old('tipo', $articulo->tipo) }}" required maxlength="60">
            <datalist id="lista-tipos">@foreach ($tipos as $t)<option value="{{ $t }}">@endforeach</datalist>
        </div>
    </div>

    <div class="campo">
        <label for="descripcion">Descripción</label>
        <input type="text" id="descripcion" name="descripcion" value="{{ old('descripcion', $articulo->descripcion) }}" required maxlength="255">
    </div>

    <div class="fila-form">
        <div class="campo">
            <label for="precio">Precio de venta (neto, con IVA)</label>
            <input type="number" step="0.01" min="0" id="precio" name="precio" value="{{ old('precio', $articulo->precio ?? 0) }}" required>
        </div>
        <div class="campo">
            <label for="costo">Costo</label>
            <input type="number" step="0.01" min="0" id="costo" name="costo" value="{{ old('costo', $articulo->costo ?? 0) }}" required>
        </div>
        <div class="campo">
            <label for="tasa_iva">IVA incluido en el precio</label>
            @php $iva = (string) (int) old('tasa_iva', $articulo->tasa_iva ?? 0); @endphp
            <select id="tasa_iva" name="tasa_iva">
                <option value="0" @selected($iva === '0')>0% (alimentos)</option>
                <option value="8" @selected($iva === '8')>8% (frontera)</option>
                <option value="16" @selected($iva === '16')>16%</option>
            </select>
        </div>
    </div>

    <div class="campo">
        <label class="check"><input type="checkbox" name="activo" value="1" @checked(old('activo', $articulo->activo))> Activo (aparece en la pantalla de venta)</label>
    </div>
    <p class="muted" style="font-size:.88rem">Si el precio es 0 el artículo se ve en la pantalla de venta pero no se puede vender.</p>

    <button class="btn" type="submit">Guardar</button>
</form>

@if (! empty($cambios) && $cambios->isNotEmpty())
    <div class="tarjeta" style="max-width:720px">
        <h2>Últimos cambios de precio</h2>
        <table>
            <thead><tr><th>Fecha</th><th>Usuario</th><th class="num">Precio</th><th class="num">Costo</th></tr></thead>
            <tbody>
            @foreach ($cambios as $c)
                <tr>
                    <td>{{ $c->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $c->usuario->name ?? '—' }}</td>
                    <td class="num">@dinero($c->precio_anterior) → @dinero($c->precio_nuevo)</td>
                    <td class="num">@dinero($c->costo_anterior) → @dinero($c->costo_nuevo)</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
