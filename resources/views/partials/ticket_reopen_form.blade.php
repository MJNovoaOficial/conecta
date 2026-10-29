@if($ticket->status === \App\Models\Ticket::STATUS_RESOLVED)
@php
    $reopenForGuest = $guestLinkAccess ?? false;
    $reopenAction = $reopenForGuest
        ? route('tickets.guest.reopen', $ticket->guest_token)
        : (Auth::check() && Auth::user()->can('reopen', $ticket) ? route('tickets.reopen', $ticket) : null);
    $reopenDirect = !$reopenForGuest && Auth::check() && Auth::user()->can('manage', $ticket);
    $reopenReasonId = 'ticket-reopen-reason-'.$ticket->id.(request()->routeIs('tickets.panel') ? '-panel' : '');
@endphp
@if($reopenAction)
<section aria-label="Reabrir ticket" data-ticket-reopen style="padding:16px;margin-bottom:18px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;overflow-wrap:anywhere;">
    @if($reopenDirect)
    <p style="font-size:1rem;line-height:1.5;color:#9a3412;margin-bottom:12px;">Puedes reabrir el ticket para continuar la atención. Volverá a En Proceso y se cancelará el cierre pendiente.</p>
    <form method="POST" action="{{ $reopenAction }}" data-comment-submit data-ticket-reopen-direct>
        @csrf
        <button type="submit" style="width:100%;min-height:44px;padding:12px;border:0;border-radius:7px;background:#c2410c;color:white;font-size:1rem;font-weight:600;white-space:normal;">Reabrir ticket</button>
    </form>
    @else
    <p style="font-size:1rem;line-height:1.5;color:#9a3412;margin-bottom:12px;">Si el problema continúa, puedes reabrir el ticket mientras esté Resuelto. Volverá a En Proceso y se cancelará el cierre pendiente.</p>
    <details data-ticket-reopen-reason @if($errors->has('motivo')) open @endif>
        <summary style="cursor:pointer;min-height:44px;padding:12px;border:1px solid #c2410c;border-radius:7px;background:#fff;color:#9a3412;font-size:1rem;font-weight:600;">Reabrir ticket</summary>
        <form method="POST" action="{{ $reopenAction }}" data-comment-submit style="margin-top:14px;">
            @csrf
            <label for="{{ $reopenReasonId }}" style="display:block;font-size:1rem;font-weight:600;margin-bottom:6px;">¿Qué sigue fallando?</label>
            <textarea name="motivo" id="{{ $reopenReasonId }}" rows="3" required minlength="10" maxlength="5000"
                      placeholder="Explica qué ocurrió al probar la solución…"
                      style="width:100%;max-width:100%;box-sizing:border-box;min-height:112px;padding:10px;border:1px solid #cbd5e0;border-radius:7px;font-size:1rem;line-height:1.5;resize:vertical;">{{ old('motivo') }}</textarea>
            @error('motivo')<p style="color:#b91c1c;font-size:.9rem;margin-top:6px;">{{ $message }}</p>@enderror
            <button type="submit" style="width:100%;min-height:44px;margin-top:10px;padding:12px;border:0;border-radius:7px;background:#c2410c;color:white;font-size:1rem;font-weight:600;white-space:normal;">Reabrir el ticket</button>
        </form>
    </details>
    @endif
</section>
@endif
@endif
