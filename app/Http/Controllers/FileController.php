<?php

namespace App\Http\Controllers;

use App\Models\ArticuloImagen;
use App\Models\Manual;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
        $attachment->loadMissing(['ticket', 'comment']);
        $ticket = $attachment->ticket;

        Gate::authorize($attachment->comment?->is_internal ? 'viewInternal' : 'view', $ticket);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->response(
            $attachment->file_path,
            $attachment->file_name
        );
    }

    // ── Imágenes de artículos ─────────────────────────────────────────────────
    // Cualquier usuario autenticado puede verlas: son material de autoayuda,
    // igual que el texto del artículo. Se sirven desde el disco privado para
    // que no queden accesibles sin sesión.

    public function serveArticuloImagen(ArticuloImagen $imagen): StreamedResponse
    {
        $imagen->loadMissing('articulo');

        // Las imágenes de borradores no se entregan por un ID adivinado. El
        // administrador sí las necesita para previsualizar la edición.
        abort_unless($imagen->articulo && ($imagen->articulo->is_active || Auth::user()->isAdmin()), 404);
        abort_unless(Storage::disk('local')->exists($imagen->ruta), 404);

        return Storage::disk('local')->response($imagen->ruta);
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
