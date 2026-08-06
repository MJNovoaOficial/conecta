<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Muestra el perfil del usuario con formulario de cambio de contraseña.
     */
    public function index()
    {
        return view("profile.index", ["user" => Auth::user()]);
    }

    /**
     * Actualiza la contraseña del usuario autenticado.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            "current_password" => "required|string",
            "password"         => "required|string|min:8|confirmed|different:current_password",
        ], [
            "password.different" => "La nueva contraseña debe ser diferente a la actual.",
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(["current_password" => "La contraseña actual no es correcta."]);
        }

        $user->update(["password" => Hash::make($request->password)]);

        return back()->with("success", "Contraseña actualizada correctamente.");
    }
}
