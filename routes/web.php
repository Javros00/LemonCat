<?php

use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

// --- Inicio de sesión ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'formulario'])->name('login');
    Route::post('/login', [AuthController::class, 'entrar'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/salir', [AuthController::class, 'salir'])->name('salir');

    // Admin va al panel; vendedor va directo a capturar venta
    Route::get('/', function () {
        return auth()->user()->esAdmin()
            ? redirect()->route('panel')
            : redirect()->route('ventas.nueva');
    })->name('inicio');

    // --- Todos los usuarios: registrar ventas ---
    Route::get('/venta', [VentaController::class, 'nueva'])->name('ventas.nueva');
    Route::post('/venta', [VentaController::class, 'guardar'])->name('ventas.guardar');
    Route::get('/mis-ventas', [VentaController::class, 'misVentas'])->name('ventas.mias');
    Route::get('/ventas/{venta}/ticket', [VentaController::class, 'ticket'])->name('ventas.ticket');

    // --- Solo administrador ---
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/', [ReporteController::class, 'panel'])->name('panel');

        // Ventas
        Route::get('/ventas', [VentaController::class, 'lista'])->name('ventas.lista');
        Route::post('/ventas/{venta}/cancelar', [VentaController::class, 'cancelar'])->name('ventas.cancelar');

        // Catálogo y precios
        Route::get('/articulos', [ArticuloController::class, 'lista'])->name('articulos.lista');
        Route::get('/articulos/nuevo', [ArticuloController::class, 'nuevo'])->name('articulos.nuevo');
        Route::post('/articulos', [ArticuloController::class, 'guardar'])->name('articulos.guardar');
        Route::get('/articulos/{articulo}/editar', [ArticuloController::class, 'editar'])->name('articulos.editar');
        Route::put('/articulos/{articulo}', [ArticuloController::class, 'actualizar'])->name('articulos.actualizar');
        Route::post('/articulos/{articulo}/activo', [ArticuloController::class, 'cambiarActivo'])->name('articulos.activo');

        Route::get('/precios', [ArticuloController::class, 'precios'])->name('precios.editar');
        Route::post('/precios', [ArticuloController::class, 'guardarPrecios'])->name('precios.guardar');
        Route::get('/precios/historial', [ArticuloController::class, 'historial'])->name('precios.historial');
        Route::get('/catalogo/exportar', [ArticuloController::class, 'exportar'])->name('catalogo.exportar');
        Route::get('/catalogo/importar', [ArticuloController::class, 'formImportar'])->name('catalogo.importar');
        Route::post('/catalogo/importar', [ArticuloController::class, 'importar'])->name('catalogo.importar.subir');

        // Cortes, exportación y póliza
        Route::get('/corte-diario', [ReporteController::class, 'corteDiario'])->name('corte.diario');
        Route::get('/corte-mensual', [ReporteController::class, 'corteMensual'])->name('corte.mensual');
        Route::get('/poliza', [ReporteController::class, 'poliza'])->name('poliza');
        Route::get('/exportar/ventas', [ReporteController::class, 'exportarVentas'])->name('exportar.ventas');
        Route::get('/exportar/poliza', [ReporteController::class, 'exportarPoliza'])->name('exportar.poliza');

        // Usuarios
        Route::get('/usuarios', [UsuarioController::class, 'lista'])->name('usuarios.lista');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'actualizar'])->name('usuarios.actualizar');
    });
});
