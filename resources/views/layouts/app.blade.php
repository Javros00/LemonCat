<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Ventas') · {{ config('ventas.negocio') }}</title>
    <link rel="stylesheet" href="{{ asset('css/ventas.css') }}">
    @stack('head')
</head>
<body>
@auth
    @php $u = auth()->user(); @endphp
    <header class="barra">
        <div class="barra-in">
            <a class="marca" href="{{ route('inicio') }}">{{ config('ventas.negocio') }}</a>
            <nav class="menu">
                <a href="{{ route('ventas.nueva') }}" @class(['activo' => request()->routeIs('ventas.nueva')])>Nueva venta</a>
                @if ($u->esAdmin())
                    <a href="{{ route('panel') }}" @class(['activo' => request()->routeIs('panel')])>Panel</a>
                    <a href="{{ route('ventas.lista') }}" @class(['activo' => request()->routeIs('ventas.lista')])>Ventas</a>
                    <a href="{{ route('corte.diario') }}" @class(['activo' => request()->routeIs('corte.diario')])>Corte diario</a>
                    <a href="{{ route('corte.mensual') }}" @class(['activo' => request()->routeIs('corte.mensual')])>Corte mensual</a>
                    <a href="{{ route('poliza') }}" @class(['activo' => request()->routeIs('poliza')])>Póliza</a>
                    <a href="{{ route('articulos.lista') }}" @class(['activo' => request()->routeIs('articulos.*', 'catalogo.*')])>Artículos</a>
                    <a href="{{ route('precios.editar') }}" @class(['activo' => request()->routeIs('precios.*')])>Precios</a>
                    <a href="{{ route('usuarios.lista') }}" @class(['activo' => request()->routeIs('usuarios.*')])>Usuarios</a>
                @else
                    <a href="{{ route('ventas.mias') }}" @class(['activo' => request()->routeIs('ventas.mias')])>Mis ventas de hoy</a>
                @endif
            </nav>
            <div class="usuario">
                <span>{{ $u->name }}</span>
                <form method="POST" action="{{ route('salir') }}">@csrf<button type="submit">Salir</button></form>
            </div>
        </div>
    </header>
@endauth

<main class="@yield('clase_main', 'contenido')">
    @if (session('ok'))
        <div class="aviso aviso-ok">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="aviso aviso-error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="aviso aviso-error">
            Revisa lo siguiente:
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @yield('contenido')
</main>
@stack('scripts')
</body>
</html>
