@php
    $delegationViewer = auth()->user();
    $delegationEnabled = $delegationViewer && \App\Support\TicketDelegationAccess::available();
    $delegationCurrent = $ticketDelegation ?? ($delegationEnabled ? \App\Support\TicketDelegationAccess::current($ticket) : null);
    $delegationIsManager = $delegationEnabled && ($delegationViewer->isAdmin()
        || ($delegationViewer->isSupport() && $ticket->assigned_to === $delegationViewer->id));
    $delegationIsDelegate = $delegationEnabled
        && \App\Support\TicketDelegationAccess::isDelegate($delegationViewer, $ticket);
    $delegationCanManage = $delegationEnabled && $delegationViewer->can('delegate', $ticket);
    $delegationCanRevoke = $delegationEnabled && $delegationCurrent && $delegationCurrent->status !== 'revoked'
        && $delegationViewer->can('revokeDelegation', $ticket);
    $delegationCanPropose = $delegationIsDelegate && $delegationCurrent?->status === 'active'
        && $delegationViewer->can('proposeDelegation', $ticket);
    $delegationPending = $delegationCurrent && in_array($delegationCurrent->status, ['active', 'submitted'], true);
    $delegationCandidates = $delegateUsers ?? collect();
    $delegationStatusLabels = [
        'active' => 'En trabajo', 'submitted' => 'Pendiente revisión TI',
        'approved' => 'Propuesta aprobada', 'revoked' => 'Encargo retirado',
    ];
    $delegationPrefix = 'delegation-'.$ticket->id;
@endphp

@if($delegationEnabled && ($delegationIsManager || $delegationIsDelegate))
<style>
.delegation-widget { margin:0 0 18px; padding:18px; width:100%; max-width:100%; min-width:0; border:1px solid #cbd5e1; border-radius:10px; background:#fff; color:#243246; font-size:1rem; line-height:1.5; overflow-wrap:anywhere; }
.delegation-widget *, .delegation-widget *::before, .delegation-widget *::after { box-sizing:border-box; }
.delegation-widget .delegation-heading { margin:0; color:#334155; font-size:1.1rem; line-height:1.4; font-weight:700; }
.delegation-widget .delegation-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:12px; }
.delegation-widget .delegation-status { display:inline-block; padding:5px 10px; border-radius:7px; background:#eef2ff; color:#3730a3; font-size:.925rem; }
.delegation-widget .delegation-text { margin:0 0 12px; font-size:1rem; }
.delegation-widget .delegation-muted { color:#526176; }
.delegation-widget .delegation-stack { display:grid; gap:12px; min-width:0; }
.delegation-widget .delegation-meta { margin:0; display:grid; gap:8px; }
.delegation-widget .delegation-meta div { display:flex; flex-wrap:wrap; column-gap:8px; }
.delegation-widget .delegation-meta dt { font-weight:600; }
.delegation-widget .delegation-meta dd { margin:0; min-width:0; }
.delegation-widget .delegation-box { padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; min-width:0; }
.delegation-widget .delegation-box-title { margin:0 0 6px; font-size:1rem; font-weight:600; }
.delegation-widget .delegation-content { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; }
.delegation-widget .delegation-form { margin:0; display:grid; gap:10px; min-width:0; }
.delegation-widget .delegation-label { display:block; margin:0; font-weight:600; font-size:1rem; }
.delegation-widget .delegation-input { display:block; width:100%; max-width:100%; min-width:0; min-height:44px; padding:10px; border:1px solid #94a3b8; border-radius:7px; background:#fff; color:#1e293b; font:inherit; }
.delegation-widget textarea.delegation-input { min-height:110px; resize:vertical; }
.delegation-widget .delegation-input:focus-visible, .delegation-widget .delegation-button:focus-visible, .delegation-widget .delegation-details summary:focus-visible { outline:3px solid #93c5fd; outline-offset:2px; }
.delegation-widget .delegation-button { display:block; width:100%; min-height:44px; padding:10px 14px; border:1px solid #4338ca; border-radius:7px; background:#4338ca; color:#fff; font:inherit; font-weight:600; line-height:1.4; cursor:pointer; white-space:normal; overflow-wrap:anywhere; }
.delegation-widget .delegation-button:disabled { opacity:.65; cursor:wait; }
.delegation-widget .delegation-button-secondary { background:#fff; color:#334155; border-color:#94a3b8; }
.delegation-widget .delegation-button-danger { background:#fff; color:#b91c1c; border-color:#fca5a5; }
.delegation-widget .delegation-error { margin:0; color:#b91c1c; font-size:.925rem; }
.delegation-widget .delegation-details { border:1px solid #cbd5e1; border-radius:8px; min-width:0; }
.delegation-widget .delegation-details summary { padding:11px 12px; min-height:44px; cursor:pointer; font-weight:600; font-size:1rem; }
.delegation-widget .delegation-details-body { padding:0 12px 12px; }
.delegation-widget .delegation-section { padding-top:14px; border-top:1px solid #e2e8f0; }
@media (max-width:600px) { .delegation-widget { padding:14px; } .delegation-widget .delegation-status { width:100%; } }
</style>

<section class="delegation-widget" aria-labelledby="{{ $delegationPrefix }}-heading">
    <div class="delegation-header">
        <h3 class="delegation-heading" id="{{ $delegationPrefix }}-heading">Colaboración por departamento</h3>
        @if($delegationCurrent)
            <span class="delegation-status">{{ $delegationStatusLabels[$delegationCurrent->status] ?? 'Encargo registrado' }}</span>
        @endif
    </div>

    <p class="delegation-text delegation-muted">El encargo da acceso únicamente a este caso. TI conserva la responsabilidad y revisa la solución antes de marcarlo como Resuelto.</p>

    @error('delegation') <p class="delegation-error" role="alert">{{ $message }}</p> @enderror
    @error('version') <p class="delegation-error" role="alert">{{ $message }}</p> @enderror
    @error('expected_id') <p class="delegation-error" role="alert">{{ $message }}</p> @enderror

    <div class="delegation-stack">
        @if($delegationCurrent)
            <dl class="delegation-meta">
                <div><dt>Persona encargada:</dt><dd>{{ $delegationCurrent->delegate?->name ?? 'Usuario no disponible' }}</dd></div>
                <div><dt>Responsable TI:</dt><dd>{{ $delegationCurrent->responsible?->name ?? $ticket->assignedTo?->name ?? 'No disponible' }}</dd></div>
            </dl>

            <div class="delegation-box">
                <h4 class="delegation-box-title">Indicaciones del encargo</h4>
                <p class="delegation-content">{{ $delegationCurrent->instructions }}</p>
            </div>

            @if($delegationCurrent->feedback)
                <div class="delegation-box">
                    <h4 class="delegation-box-title">Últimas indicaciones de TI</h4>
                    <p class="delegation-content">{{ $delegationCurrent->feedback }}</p>
                </div>
            @endif

            @if($delegationCurrent->proposal_text)
                <div class="delegation-box">
                    <h4 class="delegation-box-title">{{ $delegationCurrent->status === 'submitted' ? 'Propuesta de solución por revisar' : ($delegationCurrent->status === 'approved' ? 'Propuesta aprobada por TI' : 'Última propuesta del encargo') }}</h4>
                    <p class="delegation-content">{{ $delegationCurrent->proposal_text }}</p>
                </div>
            @endif

            @if($delegationCurrent->status === 'submitted')
                <p class="delegation-text delegation-muted" role="status">La propuesta está pendiente de revisión de TI. Enviarla no marca el ticket como Resuelto ni inicia su cierre automático.</p>
            @elseif($delegationCurrent->status === 'approved')
                <p class="delegation-text delegation-muted" role="status">TI aprobó esta propuesta. El estado y el plazo vigentes se muestran en el ticket.</p>
            @elseif($delegationCurrent->status === 'revoked')
                <p class="delegation-text delegation-muted" role="status">Este encargo fue retirado. La persona ya no tiene acceso adicional al caso.</p>
            @endif

            @if($delegationCanPropose)
                <form class="delegation-form delegation-section" method="POST" action="{{ route('tickets.delegations.propose', [$ticket, $delegationCurrent]) }}" data-comment-submit>
                    @csrf
                    <input type="hidden" name="version" value="{{ $delegationCurrent->version }}">
                    <label class="delegation-label" for="{{ $delegationPrefix }}-solution">Proponer solución a TI *</label>
                    <p class="delegation-text delegation-muted">Describe lo realizado y cómo comprobaste el resultado. TI podrá aprobarlo o pedir ajustes; solo su aprobación inicia el plazo de cierre de una hora.</p>
                    <textarea class="delegation-input" id="{{ $delegationPrefix }}-solution" name="solution_text" rows="5" required minlength="10" maxlength="5000" placeholder="Explica la solución y su resultado…">{{ old('solution_text', $delegationCurrent->proposal_text) }}</textarea>
                    @error('solution_text') <p class="delegation-error" role="alert">{{ $message }}</p> @enderror
                    <button class="delegation-button" type="submit">Enviar propuesta para revisión de TI</button>
                </form>
            @endif

            @if($delegationCanManage && $delegationCurrent->status === 'submitted')
                <form class="delegation-form delegation-section" method="POST" action="{{ route('tickets.delegations.approve', [$ticket, $delegationCurrent]) }}" data-comment-submit>
                    @csrf
                    <input type="hidden" name="version" value="{{ $delegationCurrent->version }}">
                    <p class="delegation-text delegation-muted">Aprobar registra esta propuesta como solución, marca el ticket como Resuelto e inicia el cierre automático después de una hora si no se reabre.</p>
                    <button class="delegation-button" type="submit">Aprobar propuesta y marcar Resuelto</button>
                </form>

                <details class="delegation-details" @if($errors->has('feedback')) open @endif>
                    <summary>Solicitar ajustes a la persona encargada</summary>
                    <div class="delegation-details-body">
                        <form class="delegation-form" method="POST" action="{{ route('tickets.delegations.changes', [$ticket, $delegationCurrent]) }}" data-comment-submit>
                            @csrf
                            <input type="hidden" name="version" value="{{ $delegationCurrent->version }}">
                            <label class="delegation-label" for="{{ $delegationPrefix }}-feedback">Qué debe revisar o corregir *</label>
                            <textarea class="delegation-input" id="{{ $delegationPrefix }}-feedback" name="feedback" rows="4" required minlength="10" maxlength="2000">{{ old('feedback') }}</textarea>
                            @error('feedback') <p class="delegation-error" role="alert">{{ $message }}</p> @enderror
                            <button class="delegation-button delegation-button-secondary" type="submit">Pedir ajustes sin resolver el ticket</button>
                        </form>
                    </div>
                </details>
            @endif

            @if($delegationCanRevoke)
                <details class="delegation-details">
                    <summary>Retirar este encargo</summary>
                    <div class="delegation-details-body">
                        <form class="delegation-form" method="POST" action="{{ route('tickets.delegations.revoke', [$ticket, $delegationCurrent]) }}" data-comment-submit>
                            @csrf
                            <input type="hidden" name="version" value="{{ $delegationCurrent->version }}">
                            <p class="delegation-text delegation-muted">La persona perderá su acceso adicional al caso. Sus mensajes y la actividad registrada se conservan; TI seguirá como responsable.</p>
                            <button class="delegation-button delegation-button-danger" type="submit">Retirar acceso a este encargo</button>
                        </form>
                    </div>
                </details>
            @endif
        @endif

        @if($delegationCanManage)
            <details class="delegation-details" @if(!$delegationCurrent || $delegationCurrent->status === 'revoked' || $errors->hasAny(['delegate_id', 'instructions'])) open @endif>
                <summary>{{ $delegationPending ? 'Cambiar la persona encargada' : 'Encargar este caso a otro departamento' }}</summary>
                <div class="delegation-details-body">
                    <p class="delegation-text delegation-muted">Selecciona una persona concreta. No se cambia su rol ni recibirá avisos generales de otros tickets.</p>
                    @if($delegationPending)
                        <p class="delegation-text delegation-muted">Al confirmar el nuevo encargo se retirará el acceso adicional de la persona anterior, conservando su actividad.</p>
                    @endif
                    @if($delegationCandidates->isEmpty())
                        <p class="delegation-text delegation-muted" role="status">No hay personas habilitadas de otros departamentos para este encargo.</p>
                    @else
                        <form class="delegation-form" method="POST" action="{{ route('tickets.delegations.store', $ticket) }}" data-comment-submit>
                            @csrf
                            <input type="hidden" name="expected_id" value="{{ $delegationCurrent?->id ?? 0 }}">
                            <input type="hidden" name="version" value="{{ $delegationCurrent?->version ?? 0 }}">
                            <label class="delegation-label" for="{{ $delegationPrefix }}-person">Persona encargada *</label>
                            <select class="delegation-input" id="{{ $delegationPrefix }}-person" name="delegate_id" required>
                                <option value="">Selecciona una persona…</option>
                                @foreach($delegationCandidates->groupBy(fn ($person) => $person->department?->name ?? 'Sin departamento') as $departmentName => $people)
                                    <optgroup label="{{ $departmentName }}">
                                        @foreach($people as $person)
                                            <option value="{{ $person->id }}" @selected((string) old('delegate_id') === (string) $person->id)>{{ $person->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('delegate_id') <p class="delegation-error" role="alert">{{ $message }}</p> @enderror
                            <label class="delegation-label" for="{{ $delegationPrefix }}-instructions">Qué necesitas que realice *</label>
                            <textarea class="delegation-input" id="{{ $delegationPrefix }}-instructions" name="instructions" rows="4" required minlength="10" maxlength="2000" placeholder="Explica el trabajo solicitado para este caso…">{{ old('instructions', $delegationPending ? $delegationCurrent->instructions : '') }}</textarea>
                            @error('instructions') <p class="delegation-error" role="alert">{{ $message }}</p> @enderror
                            <button class="delegation-button" type="submit">{{ $delegationPending ? 'Confirmar nuevo encargo' : 'Asignar este encargo' }}</button>
                        </form>
                    @endif
                </div>
            </details>
        @endif
    </div>
</section>
@endif
