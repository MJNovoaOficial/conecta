{{--
    Enlace de seguimiento de un ticket de invitado.

    Recibe el ticket y saca el token de ahí. Antes esperaba una variable
    $guestToken que quien lo usaba nunca enviaba —pasaba 'token'—, así que
    cualquier ticket de invitado terminaba en error 500, tanto para el invitado
    como para soporte.
--}}
@props(['ticket'])

<a href="{{ route('tickets.guest.show', ['token' => $ticket->guest_token]) }}"
   class="guest-token-link"
   style="display:inline-flex;align-items:center;gap:5px;margin-left:6px;padding:2px 9px;
          border-radius:999px;background:rgba(255,255,255,.12);color:#cbd5e0;
          font-size:.72rem;font-weight:600;text-decoration:none;">
    <i class="fas fa-link" style="font-size:.68rem;"></i> Enlace de seguimiento
</a>
