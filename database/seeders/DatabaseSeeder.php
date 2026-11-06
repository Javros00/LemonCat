<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea los 4 usuarios y carga el catálogo inicial de productos.
     * Contraseñas iniciales: cámbialas en Usuarios al entrar por primera vez.
     */
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Administrador', 'username' => 'admin', 'role' => User::ROL_ADMIN, 'password' => 'admin123'],
            ['name' => 'Vendedor 1', 'username' => 'vendedor1', 'role' => User::ROL_VENDEDOR, 'password' => 'venta123'],
            ['name' => 'Vendedor 2', 'username' => 'vendedor2', 'role' => User::ROL_VENDEDOR, 'password' => 'venta123'],
            ['name' => 'Vendedor 3', 'username' => 'vendedor3', 'role' => User::ROL_VENDEDOR, 'password' => 'venta123'],
        ];

        foreach ($usuarios as $u) {
            User::updateOrCreate(['username' => $u['username']], $u + ['activo' => true]);
        }

        $archivo = database_path('seeders/data/productos.csv');
        if (! is_file($archivo)) {
            return;
        }

        $fh = fopen($archivo, 'r');
        $encabezado = fgetcsv($fh);
        while (($fila = fgetcsv($fh)) !== false) {
            $d = array_combine($encabezado, $fila);
            Articulo::updateOrCreate(
                ['clave' => trim($d['clave'])],
                [
                    'descripcion' => trim($d['descripcion']),
                    'tipo' => trim($d['tipo']),
                    'precio' => (float) $d['precio'],
                    'costo' => (float) $d['costo'],
                    'activo' => true,
                ]
            );
        }
        fclose($fh);
    }
}
