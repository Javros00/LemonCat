<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROL_ADMIN = 'admin';
    public const ROL_VENDEDOR = 'vendedor';

    protected $fillable = ['name', 'username', 'email', 'role', 'activo', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->role === self::ROL_ADMIN;
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
