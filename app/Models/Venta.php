<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $table = 'ventas';

    public const FORMAS_PAGO = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'transferencia' => 'Transferencia',
    ];

    protected $fillable = [
        'user_id', 'fecha', 'forma_pago', 'total', 'costo_total', 'pago_con',
        'cancelada', 'cancelada_por', 'cancelada_en', 'motivo_cancelacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'cancelada_en' => 'datetime',
            'cancelada' => 'boolean',
            'total' => 'decimal:2',
            'costo_total' => 'decimal:2',
            'pago_con' => 'decimal:2',
        ];
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('cancelada', false);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function canceladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function getFolioAttribute(): string
    {
        return str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getCambioAttribute(): ?float
    {
        if ($this->pago_con === null) {
            return null;
        }

        return round((float) $this->pago_con - (float) $this->total, 2);
    }
}
