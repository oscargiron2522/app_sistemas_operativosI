<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $datos = $request->validate([
            'usuario'  => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        if (Auth::attempt($datos)) {
            $request->session()->regenerate();
            Bitacora::registrar('Inicio de sesión', null, 'Correcto');
            return redirect()->intended(route('dashboard'));
        }

        Bitacora::registrar('Inicio de sesión', null, 'Fallido');
        return back()->withErrors(['usuario' => 'Usuario o contraseña incorrectos.'])->onlyInput('usuario');
    }

    public function logout(Request $request)
    {
        Bitacora::registrar('Cierre de sesión', null, 'Correcto');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
