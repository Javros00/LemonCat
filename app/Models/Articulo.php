<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Articulo extends Model
{
    protected $table = 'articulos';

    protected $fillable = ['clave', 'descripcion', 'tipo', 'precio', 'costo', 'tasa_iva', 'activo'];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'costo' => 'decimal:2',
            'tasa_iva' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /** Solo se pueden vender artículos activos y con precio mayor a cero. */
    public function scopeVendibles(Builder $query): Builder
    {
        return $query->where('activo', true)->where('precio', '>', 0);
    }

    public function sePuedeVender(): bool
    {
        return $this->activo && (float) $this->precio > 0;
    }

    public function cambios(): HasMany
    {
        return $this->hasMany(CambioPrecio::class)->latest();
    }

    /**
     * Cambia precio y/o costo dejando registro en el historial.
     * Devuelve true si hubo cambio.
     */
    public function actualizarPrecioCosto(?float $precio, ?float $costo, ?int $userId, string $origen = 'manual'): bool
    {
        $precioAnt = (float) $this->precio;
        $costoAnt = (float) $this->costo;
        $precio = $precio === null ? $precioAnt : round($precio, 2);
        $costo = $costo === null ? $costoAnt : round($costo, 2);

        if (abs($precio - $precioAnt) < 0.001 && abs($costo - $costoAnt) < 0.001) {
            return false;
        }

        $this->precio = $precio;
        $this->costo = $costo;
        $this->save();

        CambioPrecio::create([
            'articulo_id' => $this->id,
            'user_id' => $userId,
            'precio_anterior' => $precioAnt,
            'precio_nuevo' => $precio,
            'costo_anterior' => $costoAnt,
            'costo_nuevo' => $costo,
            'origen' => $origen,
        ]);

        return true;
    }
}
