@extends('layouts.app')
@section('titulo', 'Entrar')
@section('clase_main', 'login')

@section('contenido')
<div class="tarjeta">
    <h1>{{ config('ventas.negocio') }}</h1>
    <p class="muted" style="margin-top:0">Registro de ventas</p>

    <form method="POST" action="{{ url('/login') }}">
        @csrf
        <div class="campo">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" autofocus required>
        </div>
        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <div class="campo">
            <label class="check"><input type="checkbox" name="recordar" value="1"> Mantener sesión iniciada</label>
        </div>
        <button class="btn btn-grande" type="submit">Entrar</button>
    </form>
</div>
@endsection
