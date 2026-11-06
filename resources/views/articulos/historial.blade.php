@extends('layouts.app')
@section('titulo', 'Historial de precios')

@section('contenido')
<div class="encabezado">
    <h1>Historial de cambios de precio</h1>
    <a class="btn btn-sec" href="{{ route('precios.editar') }}">‹ Lista de precios</a>
</div>

<div class="tarjeta">
    @if ($cambios->isEmpty())
        <p class="vacio">Aún no hay cambios registrados.</p>
    @else
        <div class="tabla-env">
            <table>
                <thead><tr><th>Fecha</th><th>Artículo</th><th>Usuario</th><th>Origen</th><th class="num">Precio</th><th class="num">Costo</th></tr></thead>
                <tbody>
                @foreach ($cambios as $c)
                    <tr>
                        <td>{{ $c->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $c->articulo->clave ?? '' }} · {{ $c->articulo->descripcion ?? 'Artículo borrado' }}</td>
                        <td>{{ $c->usuario->name ?? '—' }}</td>
                        <td>{{ ucfirst($c->origen) }}</td>
                        <td class="num">@dinero($c->precio_anterior) → <strong>@dinero($c->precio_nuevo)</strong></td>
                        <td class="num">@dinero($c->costo_anterior) → <strong>@dinero($c->costo_nuevo)</strong></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $cambios->links() }}
    @endif
</div>
@endsection
