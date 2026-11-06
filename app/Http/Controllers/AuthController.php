<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function formulario()
    {
        return view('auth.login');
    }

    public function entrar(Request $request)
    {
        $datos = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], ['username' => 'usuario', 'password' => 'contraseña']);

        $credenciales = [
            'username' => mb_strtolower(trim($datos['username'])),
            'password' => $datos['password'],
            'activo' => true,
        ];

        if (Auth::attempt($credenciales, $request->boolean('recordar'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('inicio'));
        }

        return back()
            ->withErrors(['username' => 'Usuario o contraseña incorrectos.'])
            ->onlyInput('username');
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
