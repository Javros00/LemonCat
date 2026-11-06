<?php

/*
|--------------------------------------------------------------------------
| Configuración del sistema de ventas
|--------------------------------------------------------------------------
| Las cuentas de la póliza son ejemplos: pide a tu contador las cuentas
| reales de tu catálogo contable y cámbialas aquí (o en el archivo .env).
*/

return [

    'negocio' => env('VENTAS_NEGOCIO', 'Mi Negocio'),

    'poliza' => [
        'tipo' => env('POLIZA_TIPO', 'Diario'),
        'cuentas' => [
            'caja' => ['numero' => env('CTA_CAJA', '101.01'), 'nombre' => 'Caja'],
            'bancos' => ['numero' => env('CTA_BANCOS', '102.01'), 'nombre' => 'Bancos'],
            'ventas' => ['numero' => env('CTA_VENTAS', '401.01'), 'nombre' => 'Ventas'],
            'iva_trasladado' => ['numero' => env('CTA_IVA', '208.01'), 'nombre' => 'IVA trasladado cobrado'],
            'costo_ventas' => ['numero' => env('CTA_COSTO', '501.01'), 'nombre' => 'Costo de ventas'],
            'almacen' => ['numero' => env('CTA_ALMACEN', '115.01'), 'nombre' => 'Almacén'],
        ],
        // Formas de pago que van a la cuenta de caja; las demás van a bancos
        'pagos_a_caja' => ['efectivo'],
        // Incluir el asiento de costo de ventas contra almacén
        'incluir_costo' => env('POLIZA_INCLUIR_COSTO', true),
    ],

];
