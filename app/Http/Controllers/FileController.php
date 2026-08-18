<?php

namespace App\Http\Controllers;

use App\Models\Manual;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve archivos privados con control de acceso.
 * Todos los métodos requieren autenticación (middleware 'auth' en rutas).
 *
 * Reunión 4 — Seguridad: los archivos ya no quedan expuestos en /storage/
 * y se sirven exclusivamente a través de este controlador.
 */
class FileController extends Controller
{
    // ── Avatares ─────────────────────────────────────────────────────────────
    // Cualquier usuario autenticado puede ver avatares (identificación visual).

    public function serveAvatar(string $filename): StreamedResponse
    {
        $path = 'avatars/' . $filename;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    // ── Adjuntos de Tickets ───────────────────────────────────────────────────
    // Solo pueden descargar: el creador del ticket, agentes asignados y admins/soporte.

    public function serveAttachment(TicketAttachment $attachment): StreamedResponse
    {
        $user   = Auth::user();
        $ticket = $attachment->ticket;

        $canAccess = $user->isAdmin()
            || $user->isSupport()
            || $ticket->user_id === $user->id
            || $ticket->assigned_to === $user->id;

        abort_unless($canAccess, 403, 'No tienes permiso para ver este archivo.');
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->response(
            $attachment->file_path,
            $attachment->file_name
        );
    }

    // ── Manuales PDF ─────────────────────────────────────────────────────────
    // Cualquier usuario autenticado puede descargar. El contador lo lleva ManualController.

    public function serveManual(Manual $manual): StreamedResponse
    {
        abort_unless($manual->is_active, 404);
        abort_unless(Storage::disk('local')->exists($manual->archivo_path), 404);

        $manual->increment('downloads_count');

        return Storage::disk('local')->download(
            $manual->archivo_path,
            $manual->archivo_nombre_original
        );
    }
}
