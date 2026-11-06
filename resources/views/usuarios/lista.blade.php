@extends('layouts.app')
@section('titulo', 'Usuarios')

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Usuarios</h1>
        <div class="muted">Cambia nombres y contraseñas. Deja la contraseña vacía para no cambiarla.</div>
    </div>
</div>

<div class="rejilla">
    @foreach ($usuarios as $u)
        <form class="tarjeta" method="POST" action="{{ route('usuarios.actualizar', $u) }}">
            @csrf @method('PUT')
            <h2>{{ $u->username }} <span class="etiqueta {{ $u->esAdmin() ? 'verde' : '' }}">{{ $u->esAdmin() ? 'Administrador' : 'Vendedor' }}</span></h2>
            <div class="campo">
                <label>Nombre</label>
                <input type="text" name="name" value="{{ $u->name }}" required maxlength="100">
            </div>
            <div class="campo">
                <label>Nueva contraseña</label>
                <input type="password" name="password" autocomplete="new-password" minlength="6" placeholder="Mínimo 6 caracteres">
            </div>
            @unless ($u->esAdmin())
                <div class="campo">
                    <label class="check"><input type="checkbox" name="activo" value="1" @checked($u->activo)> Puede entrar al sistema</label>
                </div>
            @endunless
            <button class="btn" type="submit">Guardar</button>
        </form>
    @endforeach
</div>
@endsection
