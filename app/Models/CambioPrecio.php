<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CambioPrecio extends Model
{
    protected $table = 'cambios_precio';

    protected $fillable = [
        'articulo_id', 'user_id', 'precio_anterior', 'precio_nuevo',
        'costo_anterior', 'costo_nuevo', 'origen',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
