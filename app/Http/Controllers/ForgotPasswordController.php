<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function showForgotForm()
    {
        return view("auth.forgot_password");
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            "email" => "required|email|max:255",
        ]);

        $user = User::where("email", strtolower($request->email))->first();

        if (!$user) {
            return back()->with("status", "Si ese correo existe en nuestro sistema, recibirás un enlace de recuperación en breve.");
        }

        $token = Str::random(64);
        DB::table("password_reset_tokens")->where("email", $user->email)->delete();
        DB::table("password_reset_tokens")->insert([
            "email"      => $user->email,
            "token"      => Hash::make($token),
            "created_at" => Carbon::now(),
        ]);

        $resetUrl = url("/reset-password/" . $token . "?email=" . urlencode($user->email));
        Mail::send("emails.reset_password", ["user" => $user, "resetUrl" => $resetUrl], function ($m) use ($user) {
            $m->to($user->email, $user->name)
              ->subject("Recuperación de contraseña — Conecta Soporte");
        });

        return back()->with("status", "Si ese correo existe en nuestro sistema, recibirás un enlace de recuperación en breve.");
    }

    public function showResetForm(Request $request, string $token)
    {
        return view("auth.reset_password", [
            "token" => $token,
            "email" => $request->query("email", ""),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            "email"    => "required|email|max:255",
            "token"    => "required|string",
            "password" => "required|string|min:8|confirmed",
        ]);

        $record = DB::table("password_reset_tokens")
            ->where("email", strtolower($request->email))
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(["email" => "El enlace de recuperación no es válido o ha expirado."]);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table("password_reset_tokens")->where("email", $request->email)->delete();
            return back()->withErrors(["email" => "El enlace ha expirado. Solicita uno nuevo."]);
        }

        $user = User::where("email", strtolower($request->email))->first();
        if (!$user) {
            return back()->withErrors(["email" => "No se encontró una cuenta con ese correo."]);
        }

        $user->update(["password" => Hash::make($request->password)]);
        DB::table("password_reset_tokens")->where("email", $request->email)->delete();

        return redirect()->route("home")->with("success", "Contraseña actualizada exitosamente. Puedes iniciar sesión.");
    }
}
