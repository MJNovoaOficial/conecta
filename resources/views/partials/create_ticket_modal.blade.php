{{--
  Partial reutilizable: Modal para abrir nuevo ticket.
  Requiere que $departments esté disponible en la vista que lo incluya.
  JS: mfmt(), syncModalEditor(), submitModalTicket(), loadModalSubcats(), loadModalTipos()
  se definen en layouts/app.blade.php para que estén disponibles globalmente.
--}}
<div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.15);">

      {{-- HEADER --}}
      <div class="modal-header" style="background:linear-gradient(90deg,#1a2332,#243447);border-radius:12px 12px 0 0;padding:16px 22px;">
        <h5 class="modal-title" style="color:#fff;font-size:1rem;font-weight:600;margin:0;" id="newTicketModalLabel">
          <i class="fas fa-plus-circle me-2" style="color:#3498db;"></i>Abrir Nuevo Ticket
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      {{-- BODY --}}
      <div class="modal-body" style="padding:24px;">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" id="modalTicketForm">
          @csrf

          {{-- Asunto --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem;color:#2d3748;">Asunto *</label>
            <input type="text" name="title" class="form-control" placeholder="Describe brevemente el problema..." required
                   style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;">
          </div>

          {{-- Departamento / Prioridad / Dispositivo --}}
          <div class="row g-3 mb-3">
            <div class="col-md-5">
              <label class="form-label fw-semibold" style="font-size:0.85rem;color:#2d3748;">Departamento *</label>
              <select name="department_id" class="form-select" required style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;">
                <option value="">Seleccionar...</option>
                @foreach($departments as $dept)
                  <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              {{-- Prioridad automática: solo el admin puede configurarla --}}
              <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 12px;font-size:.8rem;color:#1d4ed8;display:flex;align-items:center;gap:8px;height:100%;margin-top:auto;">
                <i class="fas fa-magic" style="font-size:.9rem;flex-shrink:0;"></i>
                <div>
                  <div style="font-weight:700;margin-bottom:2px;">Prioridad automática</div>
                  <div style="color:#3b82f6;font-size:.75rem;">El sistema la asigna según la categoría.</div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold" style="font-size:0.85rem;color:#2d3748;">Dispositivo *</label>
              <select name="device_type" class="form-select" required style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;">
                <option value="">Seleccionar...</option>
                <option value="laptop">Laptop</option>
                <option value="desktop">Desktop</option>
                <option value="tablet">Tablet</option>
                <option value="phone">Teléfono</option>
                <option value="printer">Impresora</option>
                <option value="other">Otro</option>
              </select>
            </div>
          </div>

          {{-- Clasificación --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem;color:#2d3748;">Categoría *</label>
            <select id="modalCatSelect" class="form-select mb-2"
                    style="border-radius:7px;border-color:#e2e8f0;font-size:0.87rem;"
                    onchange="loadModalSubcats(this.value)">
              <option value="">Seleccionar categoría...</option>
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
              <option value="">Seleccionar tipo (opcional)...</option>
            </select>
          </div>

          {{-- Descripción --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem;color:#2d3748;">Descripción *</label>
            <div style="border:1.5px solid #e2e8f0;border-radius:7px;overflow:hidden;" id="modalMsgBox">
              <div style="background:#f7f9fc;border-bottom:1px solid #e2e8f0;padding:5px 10px;display:flex;gap:4px;">
                <button type="button" onclick="mfmt('bold')"          class="fmt-btn" title="Negrita"><b>B</b></button>
                <button type="button" onclick="mfmt('italic')"         class="fmt-btn" title="Cursiva"><i>I</i></button>
                <button type="button" onclick="mfmt('underline')"      class="fmt-btn" title="Subrayado"><u>U</u></button>
                <div style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
                <button type="button" onclick="mfmt('insertUnorderedList')" class="fmt-btn"><i class="fas fa-list-ul"></i></button>
                <button type="button" onclick="mfmt('insertOrderedList')"   class="fmt-btn"><i class="fas fa-list-ol"></i></button>
              </div>
              <div id="modalEditor" contenteditable="true"
                   style="min-height:140px;padding:12px;outline:none;font-size:0.86rem;color:#2d3748;line-height:1.6;"
                   oninput="syncModalEditor()"></div>
            </div>
            <textarea name="description" id="modalDescField" style="display:none;"></textarea>
          </div>

          {{-- Adjuntos --}}
          <div class="mb-1">
            <label class="form-label fw-semibold" style="font-size:0.85rem;color:#2d3748;">Adjuntos <small style="font-weight:400;color:#a0aec0;">(máx. 5 archivos, 5MB c/u)</small></label>
            <div style="border:2px dashed #e2e8f0;border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:border-color 0.2s;"
                 onclick="document.getElementById('modalAttach').click()"
                 onmouseenter="this.style.borderColor='#3498db'" onmouseleave="this.style.borderColor='#e2e8f0'">
              <i class="fas fa-paperclip" style="font-size:20px;color:#a0aec0;"></i>
              <div style="font-size:0.8rem;color:#a0aec0;margin-top:4px;">Haz clic para adjuntar</div>
              <div id="modalFileNames" style="font-size:0.79rem;color:#4a5568;margin-top:4px;"></div>
            </div>
            <input type="file" id="modalAttach" name="attachments[]" multiple style="display:none;"
                   onchange="document.getElementById('modalFileNames').textContent = Array.from(this.files).map(f=>f.name).join(', ')">
          </div>

        </form>
      </div>

      {{-- FOOTER --}}
      <div class="modal-footer" style="border-top:1px solid #f0f2f5;padding:14px 22px;">
        <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                style="color:#718096;background:none;border:1px solid #e2e8f0;border-radius:7px;padding:7px 18px;">Cancelar</button>
        <button type="button" onclick="submitModalTicket()"
                style="background:linear-gradient(135deg,#27ae60,#2ecc71);color:#fff;border:none;border-radius:7px;padding:8px 22px;font-weight:600;font-size:0.875rem;cursor:pointer;">
          <i class="fas fa-paper-plane me-1"></i> Enviar Ticket
        </button>
      </div>

    </div>
  </div>
</div>
