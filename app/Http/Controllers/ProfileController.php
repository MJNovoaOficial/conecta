<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Muestra el perfil del usuario.
     */
    public function index()
    {
        return view('profile.index', ['user' => Auth::user()]);
    }

    /**
     * Actualiza la contraseña del usuario autenticado.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'password.different' => 'La nueva contraseña debe ser diferente a la actual.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success_password', 'Contraseña actualizada correctamente.');
    }

    /**
     * Actualiza el email principal y el correo alternativo del usuario.
     * Reunión 3: correo alternativo para recibir notificaciones si falla el corporativo.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'            => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'email'           => 'required|email|max:255|unique:usuarios,email,' . $user->id,
            'alternate_email' => 'nullable|email|max:255|different:email',
        ], [
            'alternate_email.different' => 'El correo alternativo debe ser diferente al correo principal.',
        ]);

        $user->update([
            'name'            => trim($request->name),
            'email'           => strtolower($request->email),
            'alternate_email' => $request->alternate_email ? strtolower($request->alternate_email) : null,
        ]);

        return back()->with('success_profile', 'Información de contacto actualizada correctamente.');
    }

    /**
     * Sube o reemplaza la foto de perfil del usuario.
     * Reunión 4: identificación visual del personal por región.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'avatar.image'  => 'El archivo debe ser una imagen.',
            'avatar.mimes'  => 'Solo se permiten imágenes JPG, PNG o WebP.',
            'avatar.max'    => 'La imagen no puede superar los 2 MB.',
        ]);

        $user = Auth::user();

        // Eliminar avatar anterior si existe
        if ($user->avatar_url && Storage::disk('local')->exists($user->avatar_url)) {
            Storage::disk('local')->delete($user->avatar_url);
        }

        $file = $request->file('avatar');
        $path = $file->storeAs(
            'avatars',
            'user_' . $user->id . '.' . $file->getClientOriginalExtension(),
            'local'   // disco privado — se sirve vía /files/avatar/...
        );

        $user->update(['avatar_url' => $path]);

        return back()->with('success_avatar', 'Foto de perfil actualizada correctamente.');
    }
}
