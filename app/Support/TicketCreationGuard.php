<?php

namespace App\Support;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Reintentos de un mismo formulario, no tickets distintos con el mismo asunto. */
final class TicketCreationGuard
{
    private const REQUEST_KEY = 'ticket_creation_key';
    private const RECEIPT_SECONDS = 86400;

    public static function run(Request $request, callable $create)
    {
        $request->validate(['submission_token' => 'nullable|uuid']);
        // Compatibilidad con clientes anteriores al despliegue del formulario.
        if (!$request->filled('submission_token')) {
            return $create();
        }

        $guest = $request->routeIs('tickets.guest.store');
        if ($guest) {
            $request->validate(['guest_email' => 'required|email|max:255']);
        }
        // No usar IP: personas distintas pueden compartir la red de Dimak.
        // Para invitados, el UUID aleatorio del formulario es la credencial del
        // envío, junto a su correo. Un reintento no depende de retener cookies.
        $actor = $guest
            ? 'guest:'.hash('sha256', strtolower(trim($request->input('guest_email'))))
            : 'user:'.$request->user()->id;
        $key = 'ticket-create:'.hash('sha256', $actor.'|'.$request->input('submission_token'));
        $lock = Cache::lock($key.':lock', 120);
        if (!$lock->get()) {
            return back()->withInput($request->except(['_token', 'attachments']))
                ->withErrors(['general' => 'Tu solicitud ya se está enviando. Espera unos segundos antes de volver a intentarlo.']);
        }

        try {
            $ticketId = Cache::get($key);
            $ticket = $ticketId ? Ticket::find($ticketId) : null;
            if ($ticket && ($guest ? $ticket->user_id === null : $ticket->user_id === $request->user()->id)) {
                return ($guest
                    ? redirect()->route('tickets.guest.show', ['token' => $ticket->guest_token])
                    : redirect()->route('tickets.show', $ticket))
                    ->with('success', 'Tu solicitud ya fue registrada: '.$ticket->ticket_number);
            }

            $request->attributes->set(self::REQUEST_KEY, $key);
            return $create();
        } finally {
            $request->attributes->remove(self::REQUEST_KEY);
            $lock->release();
        }
    }

    public static function create(Request $request, array $attributes): Ticket
    {
        $key = $request->attributes->get(self::REQUEST_KEY);
        if (!$key) {
            return Ticket::create($attributes);
        }

        // Registrar antes de adjuntos/correos: si una tarea posterior falla,
        // reintentar no debe crear otro ticket. Con cache database en la misma
        // conexión, ambos registros se confirman en esta transacción.
        return DB::transaction(function () use ($key, $attributes) {
            $ticket = Ticket::create($attributes);
            if (!Cache::put($key, $ticket->id, self::RECEIPT_SECONDS)) {
                throw new \RuntimeException('No se pudo registrar el comprobante del envío.');
            }
            return $ticket;
        });
    }
}
