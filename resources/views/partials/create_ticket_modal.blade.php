{{--
  Partial reutilizable: Modal para abrir nuevo ticket.

  La clasificación (categoría → subcategoría → tipo) va visible, justo
  después del asunto, y no detrás de un acordeón "opcional". Es lo único
  del formulario que decide la prioridad del ticket (PriorityRule::resolve):
  sin ella, cualquier ticket nace con prioridad media, sea cual sea el
  problema real. Escondida y marcada "opcional" casi nadie la abría.

  Sigue sin ser obligatoria —igual que en tickets/create.blade.php—, porque
  exigirla bloquearía a alguien que no sabe bien qué elegir. La cadena de
  categoria→subcategoria→tipo (`loadModalSubcats`, `loadModalTipos`) vive en
  el layout global (resources/views/layouts/app.blade.php), no aquí: así
  funciona en cualquier página que incluya este modal, no solo en la que lo
  declaró.

  No pide Departamento ni Dispositivo: con sesión, el departamento se toma de
  la cuenta (o de la regla automática por categoría, si aplica) y no hay
  Dispositivo en ningún formulario con sesión. Ese es terreno de
  tickets/guest_create.blade.php, donde sí hace falta porque el invitado no
  tiene cuenta de la que tomarlo.
--}}
<div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-lg-down">
    <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.15);">

      {{-- HEADER --}}
      <div class="modal-header" style="background:linear-gradient(90deg,#1a2332,#243447);border-radius:12px 12px 0 0;padding:16px 22px;">
        <h5 class="modal-title" style="color:#fff;font-size:1rem;font-weight:600;margin:0;" id="newTicketModalLabel">
          <i class="fas fa-life-ring me-2" style="color:#3498db;"></i>¿En qué te podemos ayudar?
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar formulario"></button>
      </div>

      {{-- BODY --}}
      <div class="modal-body" style="padding:24px;">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" id="modalTicketForm">
          @csrf

          {{-- ───────────────────────────── CAMPO 1: Asunto ──────────────────────────────── --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.88rem;color:#2d3748;">
              <i class="fas fa-question-circle me-1" style="color:#3498db;"></i>
              ¿Cuál es tu problema o solicitud? *
            </label>
            <input type="text" name="title" id="modalTitleField" class="form-control"
                   placeholder="Ej: No puedo entrar a mi correo, la impresora no imprime..."
                   required autocomplete="off"
                   style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;padding:10px 12px;">

            {{-- Sugerencias de la base de conocimiento (RN-18) --}}
            <div id="kbSugerencias" style="display:none;margin-top:9px;padding:11px 13px;background:#f0f9ff;border:1px solid #bae0fb;border-radius:8px;">
              <div style="font-size:0.78rem;font-weight:700;color:#2980b9;margin-bottom:7px;">
                <i class="fas fa-lightbulb"></i> Quizás esto lo resuelva sin abrir un ticket
              </div>
              <div id="kbLista"></div>
            </div>
          </div>

          {{-- ───────────────────────── CAMPO 2: Clasificación (visible, no colapsada) ──── --}}
          <div class="mb-3" style="background:#f7f9fc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;">
            <div class="ticket-classification-hint" style="font-size:0.8rem;color:#4a5568;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
              <i class="fas fa-sliders-h" style="color:#3498db;"></i>
              <span><strong>Ayúdanos a priorizar tu ticket.</strong> El sistema asigna la urgencia según lo que elijas aquí.</span>
            </div>

            <label class="form-label fw-semibold" style="font-size:0.8rem;color:#4a5568;">Categoría del problema</label>
            <select id="modalCatSelect" class="form-select mb-2"
                    style="border-radius:7px;border-color:#e2e8f0;font-size:0.85rem;"
                    onchange="loadModalSubcats(this.value)">
              <option value="">No sé / No aplica</option>
              @foreach(App\Models\Categoria::where('is_active', true)->orderBy('name')->get() as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
              @endforeach
            </select>
            <select id="modalSubcatSelect" name="subcategoria_id" class="form-select mb-2"
                    style="border-radius:7px;border-color:#e2e8f0;font-size:0.85rem;"
                    onchange="loadModalTipos(this.value)" disabled>
              <option value="">Primero selecciona categoría...</option>
            </select>
            <select id="modalTipoSelect" name="tipo_incidente_id" class="form-select"
                    style="border-radius:7px;border-color:#e2e8f0;font-size:0.85rem;" disabled>
              <option value="">Seleccionar tipo (opcional)...</option>
            </select>
          </div>

          {{-- ───────────────────────── CAMPO 3: Descripción simple ─────────────────────── --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.88rem;color:#2d3748;">
              <i class="fas fa-comment-dots me-1" style="color:#9b59b6;"></i>
              Cuéntanos más <small style="font-weight:400;color:#a0aec0;">(opcional)</small>
            </label>
            <textarea name="description" id="modalDescField"
                      rows="4" class="form-control"
                      style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;resize:vertical;"
                      placeholder="Describe el problema con más detalle..."></textarea>
          </div>

          {{-- ───────────────────────── CAMPO 4: Adjuntos ───────────────────────────────── --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.88rem;color:#2d3748;">
              <i class="fas fa-paperclip me-1" style="color:#e67e22;"></i>
              Adjuntar archivo o video <small style="font-weight:400;color:#a0aec0;">(opcional — máx. 5 archivos)</small>
            </label>
            <div class="ticket-upload-area" style="border:2px dashed #e2e8f0;border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:border-color 0.2s;"
                 onclick="document.getElementById('modalAttach').click()"
                 onmouseenter="this.style.borderColor='#3498db'" onmouseleave="this.style.borderColor='#e2e8f0'">
              <i class="fas fa-cloud-upload-alt" style="font-size:22px;color:#a0aec0;"></i>
              <div style="font-size:0.8rem;color:#a0aec0;margin-top:4px;">Haz clic para adjuntar imágenes, PDF o videos cortos</div>
              <div id="modalFileNames" style="font-size:0.79rem;color:#4a5568;margin-top:4px;"></div>
            </div>
            <input type="file" id="modalAttach" name="attachments[]" multiple style="display:none;"
                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.mov,.webm"
                   onchange="document.getElementById('modalFileNames').textContent = Array.from(this.files).map(f=>f.name).join(', ')">
          </div>

        </form>
      </div>

      {{-- FOOTER --}}
      <div class="modal-footer" style="border-top:1px solid #f0f2f5;padding:14px 22px;">
        <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                style="color:#718096;background:none;border:1px solid #e2e8f0;border-radius:7px;padding:7px 18px;">Cancelar</button>
        <button type="button" id="btnEnviarTicket"
                onclick="enviarTicketSimplificado()"
                style="background:linear-gradient(135deg,#27ae60,#2ecc71);color:#fff;border:none;border-radius:7px;padding:8px 22px;font-weight:600;font-size:0.875rem;cursor:pointer;">
          <i class="fas fa-paper-plane me-1"></i> Enviar Solicitud
        </button>
      </div>

    </div>
  </div>
</div>

<script>
function enviarTicketSimplificado() {
    const titleField = document.getElementById('modalTitleField');
    if (!titleField || !titleField.value.trim()) {
        titleField.style.borderColor = '#ef4444';
        titleField.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.15)';
        titleField.focus();
        titleField.placeholder = '⚠ Este campo es obligatorio';
        return;
    }
    titleField.style.borderColor = '';
    titleField.style.boxShadow = '';
    document.getElementById('modalTicketForm').submit();
}

// Al cerrar el modal, además del form.reset() nativo, se limpia el estado de
// error del asunto: form.reset() no toca los estilos inline que puso
// enviarTicketSimplificado() cuando faltaba completarlo.
//
// La cadena de categoría→subcategoría→tipo y el resto de los campos ya
// quedan cubiertos por el listener global en layouts/app.blade.php, que
// corre en cualquier página que incluya este modal.
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('newTicketModal');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            var titleField = document.getElementById('modalTitleField');
            if (titleField) {
                titleField.style.borderColor = '';
                titleField.style.boxShadow = '';
                titleField.placeholder = 'Ej: No puedo entrar a mi correo, la impresora no imprime...';
            }
        });
    }
});
</script>

@include('partials.kb_sugerencias_script')
