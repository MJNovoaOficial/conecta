<?php

namespace App\Http\Controllers;

use App\Models\EncuestaSatisfaccion;
use App\Models\Ticket;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Encuesta de satisfacción: quien pidió la ayuda califica la atención.
 *
 * Tiene dos entradas porque los tickets tienen dos tipos de solicitante: quien
 * tiene cuenta responde desde su ticket, y el invitado desde el enlace con
 * token que recibió al abrirlo. Las dos terminan en el mismo guardado.
 */
class EncuestaController extends Controller
{
    public function responder(Request $request, Ticket $ticket): RedirectResponse
    {
        // Solo quien pidió la ayuda califica la atención. Ni soporte ni un
        // administrador pueden responder en su nombre: sería calificarse solos.
        abort_unless($ticket->user_id !== null && $ticket->user_id === Auth::id(), 403);

        return $this->guardar($request, $ticket, Auth::id());
    }

    /**
     * El token del enlace es la única credencial del invitado, igual que para
     * responder o reabrir su ticket.
     */
    public function responderInvitado(Request $request, string $token): RedirectResponse
    {
        $ticket = Ticket::where('guest_token', $token)->firstOrFail();

        return $this->guardar($request, $ticket, null);
    }

    private function guardar(Request $request, Ticket $ticket, ?int $usuarioId): RedirectResponse
    {
        if (! $ticket->admiteEncuesta()) {
            return back()->with('error', 'Esta encuesta ya fue respondida o todavía no está disponible.');
        }

        $mensaje = 'Elige una de las caritas para calificar la atención.';

        $datos = $request->validate([
            'calificacion' => ['required', 'integer', 'between:1,5'],
            'comentario'   => ['nullable', 'string', 'max:1000'],
        ], [
            'calificacion.required' => $mensaje,
            'calificacion.integer'  => $mensaje,
            'calificacion.between'  => $mensaje,
            'comentario.max'        => 'El comentario puede tener hasta 1000 caracteres.',
        ]);

        try {
            EncuestaSatisfaccion::create([
                'ticket_id'    => $ticket->id,
                'agente_id'    => $ticket->assigned_to,
                'usuario_id'   => $usuarioId,
                'calificacion' => $datos['calificacion'],
                'comentario'   => $datos['comentario'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dos envíos casi al mismo tiempo —un doble clic— pasan los dos la
            // comprobación de arriba. La base deja entrar solo uno; el segundo
            // no es un error de la persona, su respuesta ya quedó guardada.
            return back()->with('success', 'Tu respuesta ya había quedado guardada. ¡Gracias!');
        }

        return back()->with('success', '¡Gracias por tu respuesta! Nos ayuda a mejorar la atención.');
    }
}
