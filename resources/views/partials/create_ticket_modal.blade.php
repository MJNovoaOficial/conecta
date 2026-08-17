{{--
  Partial reutilizable: Modal para abrir nuevo ticket — Versión simplificada (Reunión 4).
  Solo 3 campos visibles: Asunto, Descripción, Adjuntos.
  Campos técnicos (Categoría, Departamento, Dispositivo) colapsados bajo "Más detalles".
  Requiere que $departments esté disponible en la vista que lo incluya.
--}}
<div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.15);">

      {{-- HEADER --}}
      <div class="modal-header" style="background:linear-gradient(90deg,#1a2332,#243447);border-radius:12px 12px 0 0;padding:16px 22px;">
        <h5 class="modal-title" style="color:#fff;font-size:1rem;font-weight:600;margin:0;" id="newTicketModalLabel">
          <i class="fas fa-life-ring me-2" style="color:#3498db;"></i>¿En qué te podemos ayudar?
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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

          {{-- ───────────────────────── CAMPO 2: Descripción simple ─────────────────────── --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.88rem;color:#2d3748;">
              <i class="fas fa-comment-dots me-1" style="color:#9b59b6;"></i>
              Cuéntanos más <small style="font-weight:400;color:#a0aec0;">(opcional)</small>
            </label>
            <textarea name="description" id="modalDescField"
                      rows="4" class="form-control"
                      style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;resize:vertical;"
                      placeholder="Explícanos con tus palabras qué está pasando. No te preocupes por ser técnico, te entendemos igual 😊"></textarea>
          </div>

          {{-- ───────────────────────── CAMPO 3: Adjuntos ───────────────────────────────── --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.88rem;color:#2d3748;">
              <i class="fas fa-paperclip me-1" style="color:#e67e22;"></i>
              Adjuntar archivo o video <small style="font-weight:400;color:#a0aec0;">(opcional — máx. 5 archivos)</small>
            </label>
            <div style="border:2px dashed #e2e8f0;border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:border-color 0.2s;"
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

          {{-- ─────────────────── SECCIÓN COLAPSADA: Más detalles (opcional) ────────────── --}}
          <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
            <button type="button" id="toggleDetails"
                    onclick="toggleMoreDetails()"
                    style="width:100%;padding:10px 14px;background:#f7f9fc;border:none;text-align:left;
                           font-size:0.82rem;color:#718096;font-weight:600;cursor:pointer;
                           display:flex;align-items:center;justify-content:space-between;">
              <span><i class="fas fa-sliders-h me-2" style="color:#a0aec0;"></i>Más detalles <span style="font-weight:400;">(opcional)</span></span>
              <i class="fas fa-chevron-down" id="detailsChevron" style="transition:transform .2s;"></i>
            </button>
            <div id="moreDetailsSection" style="display:none;padding:16px 14px;border-top:1px solid #f0f2f5;">

              {{-- Departamento --}}
              <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:0.83rem;color:#4a5568;">Departamento</label>
                <select name="department_id" class="form-select" style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;">
                  <option value="">No sé / No aplica</option>
                  @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Dispositivo --}}
              <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:0.83rem;color:#4a5568;">Dispositivo</label>
                <select name="device_type" class="form-select" style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;">
                  <option value="">No aplica</option>
                  <option value="laptop">Laptop</option>
                  <option value="desktop">Desktop / PC</option>
                  <option value="tablet">Tablet</option>
                  <option value="phone">Teléfono</option>
                  <option value="printer">Impresora</option>
                  <option value="other">Otro</option>
                </select>
              </div>

              {{-- Categoría --}}
              <div class="mb-1">
                <label class="form-label fw-semibold" style="font-size:0.83rem;color:#4a5568;">Categoría</label>
                <select id="modalCatSelect" class="form-select mb-2"
                        style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;"
                        onchange="loadModalSubcats(this.value)">
                  <option value="">No sé / No aplica</option>
                  @foreach(App\Models\Categoria::where('is_active', true)->orderBy('name')->get() as $cat)
                  <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                  @endforeach
                </select>
                <select id="modalSubcatSelect" name="subcategoria_id" class="form-select mb-2"
                        style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;"
                        onchange="loadModalTipos(this.value)" disabled>
                  <option value="">Primero selecciona categoría...</option>
                </select>
                <select id="modalTipoSelect" name="tipo_incidente_id" class="form-select"
                        style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;" disabled>
                  <option value="">Tipo de incidente (opcional)...</option>
                </select>
              </div>

            </div>
          </div>

        </form>
      </div>

      {{-- FOOTER --}}
      <div class="modal-footer" style="border-top:1px solid #f0f2f5;padding:14px 22px;">
        <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                style="color:#718096;background:none;border:1px solid #e2e8f0;border-radius:7px;padding:7px 18px;">Cancelar</button>
        <button type="button" onclick="submitModalTicket()"
                style="background:linear-gradient(135deg,#27ae60,#2ecc71);color:#fff;border:none;border-radius:7px;padding:8px 22px;font-weight:600;font-size:0.875rem;cursor:pointer;">
          <i class="fas fa-paper-plane me-1"></i> Enviar Solicitud
        </button>
      </div>

    </div>
  </div>
</div>

<script>
function toggleMoreDetails() {
    const section = document.getElementById('moreDetailsSection');
    const chevron = document.getElementById('detailsChevron');
    const open = section.style.display === 'block';
    section.style.display = open ? 'none' : 'block';
    chevron.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>

@include('partials.kb_sugerencias_script')
