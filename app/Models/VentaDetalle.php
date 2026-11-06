<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaDetalle extends Model
{
    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id', 'articulo_id', 'clave', 'descripcion', 'tipo', 'cantidad',
        'precio', 'costo', 'tasa_iva', 'importe', 'costo_importe',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'precio' => 'decimal:2',
            'costo' => 'decimal:2',
            'tasa_iva' => 'decimal:2',
            'importe' => 'decimal:2',
            'costo_importe' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }
}
