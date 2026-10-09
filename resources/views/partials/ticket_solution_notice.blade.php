@if(in_array($ticket->status, ['resolved', 'closed']) && $ticket->solution_text)
<section aria-label="Solución registrada" style="padding:16px;margin-bottom:18px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
    <h3 style="font-size:1rem;color:#166534;margin-bottom:8px;">Solución registrada</h3>
    <div style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $ticket->solution_text }}</div>
    @if($ticket->status === 'resolved' && $ticket->resolved_at && $ticket->history()->where('action', 'solution_registered')->exists())
        <p style="font-size:.85rem;margin-top:10px;">Cierre automático a partir del {{ $ticket->resolved_at->copy()->addHour()->format('d/m/Y H:i') }} (una hora desde la solución). Si el problema sigue, reabre el ticket antes del cierre.</p>
    @elseif($ticket->status === 'closed')
        <p style="font-size:.85rem;margin-top:10px;">Ticket cerrado definitivamente. La solución y la conversación quedan disponibles para consulta.</p>
    @endif
</section>
@endif
