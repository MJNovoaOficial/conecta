@auth
@if($ticket->hasAssignedSupport() && Auth::user()->can('manage', $ticket) && !in_array($ticket->status, ['resolved', 'closed']))
<section aria-label="Registrar solución" style="padding:16px;margin-bottom:18px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:10px;">
    <h3 style="font-size:1rem;color:#5b21b6;margin-bottom:10px;">Registrar solución</h3>
    <p style="font-size:.85rem;margin-bottom:10px;">Describe lo que se solucionó. El ticket quedará Resuelto y se cerrará automáticamente después de una hora si no se reabre.</p>
    <form method="POST" action="{{ route('tickets.resolve', $ticket) }}" data-comment-submit>
        @csrf
        <label for="ticketSolution" style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">Solución aplicada *</label>
        <textarea id="ticketSolution" name="solution_text" rows="4" required minlength="10" maxlength="5000"
                  style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #cbd5e0;border-radius:6px;font-size:1rem;resize:vertical;"
                  placeholder="Explica cómo se resolvió el problema…">{{ old('solution_text', $ticket->solution_text) }}</textarea>
        @error('solution_text')<p style="color:#b91c1c;font-size:.85rem;">{{ $message }}</p>@enderror
        <button type="submit" style="width:100%;min-height:44px;margin-top:8px;padding:10px;border:0;border-radius:6px;background:#7c3aed;color:white;font-size:.95rem;font-weight:600;">Registrar solución y marcar Resuelto</button>
    </form>
</section>
@endif
@endauth
