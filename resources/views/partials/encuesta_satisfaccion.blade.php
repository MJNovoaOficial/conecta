{{--
    Encuesta de satisfacción del ticket.

    La responde quien pidió la ayuda —con cuenta o como invitado— cuando el
    ticket ya está cerrado, y una sola vez. Soporte ve lo que le respondieron.

    La plataforma la usan personas poco habituadas a sistemas, así que se pide
    lo mínimo: tocar una carita. El comentario es opcional.
--}}
@php
    $encuesta      = $ticket->encuesta;
    $sesion        = auth()->user();
    $esSolicitante = $sesion
        ? $ticket->user_id !== null && $ticket->user_id === $sesion->id
        : $ticket->isGuestTicket();
    $esPersonal    = $sesion && ($sesion->isSupport() || $sesion->isAdmin());
    $caras         = \App\Models\EncuestaSatisfaccion::CARAS;
    $etiquetas     = \App\Models\EncuestaSatisfaccion::ETIQUETAS;
@endphp

@if($esSolicitante && $ticket->admiteEncuesta())
<style>
.enc-grupo  { border:0; padding:0; margin:0 0 8px; min-width:0; }
.enc-titulo { font-size:.78rem; color:#4a5568; font-weight:600; margin-bottom:6px; float:none; width:auto; padding:0; }
.enc-caras  { display:grid; grid-template-columns:repeat(5, 1fr); gap:4px; }
.enc-cara {
    position:relative; display:flex; flex-direction:column; align-items:center; gap:3px;
    padding:7px 2px; border:2px solid #e2e8f0; border-radius:10px;
    background:#fff; cursor:pointer; transition:border-color .15s, background .15s;
}
/* El radio queda invisible pero sigue siendo el control real: se elige con
   el teclado y el lector de pantalla lo anuncia con su nombre. */
.enc-cara input { position:absolute; opacity:0; width:1px; height:1px; }
.enc-emoji { font-size:1.55rem; line-height:1; }
.enc-texto { font-size:.6rem; color:#64748b; text-align:center; line-height:1.15; }
.enc-cara:hover { border-color:#fbbf24; }
.enc-cara:has(input:checked) { border-color:#f59e0b; background:#fffbeb; }
.enc-cara:has(input:checked) .enc-texto { color:#92400e; font-weight:700; }
.enc-cara:has(input:focus-visible) { outline:3px solid #93c5fd; outline-offset:1px; }
</style>

<div class="side-card" style="border-left:3px solid #f59e0b;">
    <div class="side-card-header" style="color:#92400e;"><i class="fas fa-star me-1"></i> ¿Cómo te atendimos?</div>
    <div class="side-card-body">
        <p style="font-size:.8rem;color:#4a5568;margin-bottom:10px;">
            Tu ticket ya está cerrado. Cuéntanos cómo fue la atención: nos ayuda a mejorar.
        </p>

        <form method="POST"
              action="{{ $sesion ? route('encuesta.responder', $ticket) : route('encuesta.invitado', $ticket->guest_token) }}">
            @csrf

            <fieldset class="enc-grupo">
                <legend class="enc-titulo">Elige una carita</legend>
                <div class="enc-caras">
                    @foreach($caras as $valor => $cara)
                        <label class="enc-cara">
                            <input type="radio" name="calificacion" value="{{ $valor }}" required
                                   {{ (string) old('calificacion') === (string) $valor ? 'checked' : '' }}>
                            <span class="enc-emoji" aria-hidden="true">{{ $cara }}</span>
                            <span class="enc-texto">{{ $etiquetas[$valor] }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @error('calificacion')
                <div style="color:#e74c3c;font-size:.76rem;margin:-2px 0 8px;">{{ $message }}</div>
            @enderror

            <label for="encComentario" style="font-size:.78rem;color:#4a5568;font-weight:600;display:block;margin-bottom:5px;">
                ¿Algo que quieras contarnos? <span style="font-weight:400;color:#94a3b8;">(opcional)</span>
            </label>
            <textarea name="comentario" id="encComentario" rows="2" maxlength="1000"
                      style="width:100%;border:1.5px solid #cbd5e0;border-radius:7px;padding:8px 10px;font-size:.82rem;resize:vertical;">{{ old('comentario') }}</textarea>
            @error('comentario')
                <div style="color:#e74c3c;font-size:.76rem;margin-top:4px;">{{ $message }}</div>
            @enderror

            <button type="submit" class="side-btn" style="background:#f59e0b;color:#fff;margin-top:8px;">
                <i class="fas fa-paper-plane"></i> Enviar calificación
            </button>
        </form>
    </div>
</div>

@elseif($esSolicitante && $encuesta)
<div class="side-card" style="border-left:3px solid #22c55e;">
    <div class="side-card-header" style="color:#065f46;"><i class="fas fa-heart me-1"></i> Ya calificaste esta atención</div>
    <div class="side-card-body" style="font-size:.82rem;color:#4a5568;">
        <span style="font-size:1.3rem;vertical-align:middle;" aria-hidden="true">{{ $encuesta->cara() }}</span>
        La calificaste como <strong>{{ $encuesta->etiqueta() }}</strong>. ¡Gracias!
    </div>
</div>
@endif

{{-- Soporte ve lo que le respondieron, para aprender de cada caso. --}}
@if($esPersonal && ! $esSolicitante && $encuesta)
<div class="side-card" style="border-left:3px solid #f59e0b;">
    <div class="side-card-header" style="color:#92400e;"><i class="fas fa-star me-1"></i> Calificación del solicitante</div>
    <div class="side-card-body">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:1.8rem;line-height:1;" aria-hidden="true">{{ $encuesta->cara() }}</span>
            <div>
                <div style="font-size:.9rem;color:#1a2332;">
                    <strong>{{ $encuesta->etiqueta() }}</strong>
                    <span style="color:#64748b;">({{ $encuesta->calificacion }}/5)</span>
                </div>
                <div style="font-size:.72rem;color:#94a3b8;">{{ $encuesta->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
        @if($encuesta->comentario)
            <div style="margin-top:10px;padding:8px 10px;background:#fffbeb;border-radius:7px;font-size:.8rem;color:#4a5568;white-space:pre-wrap;">{{ $encuesta->comentario }}</div>
        @endif
    </div>
</div>
@endif
