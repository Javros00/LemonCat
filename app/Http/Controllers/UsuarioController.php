<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function lista()
    {
        return view('usuarios.lista', [
            'usuarios' => User::orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")->orderBy('username')->get(),
        ]);
    }

    /** Cambiar nombre, contraseña o activar/desactivar. */
    public function actualizar(Request $request, User $usuario)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
        ], [], ['name' => 'nombre', 'password' => 'contraseña']);

        $usuario->name = $datos['name'];
        if (! empty($datos['password'])) {
            $usuario->password = $datos['password']; // se cifra solo (cast "hashed")
        }

        // El administrador no se puede desactivar a sí mismo
        if (! $usuario->esAdmin()) {
            $usuario->activo = $request->boolean('activo');
        }
        $usuario->save();

        return back()->with('ok', "Usuario {$usuario->username} actualizado.");
    }
}
