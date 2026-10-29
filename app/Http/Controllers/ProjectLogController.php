<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProjectLogAttachment;
use App\Models\ProjectLogEntry;
use App\Models\ProjectLogProject;
use App\Models\User;
use App\Support\DisplayDate;
use App\Support\FilterValidation;
use App\Support\ProjectLogAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ProjectLogController extends Controller
{
    public function index(Request $request)
    {
        [$query, $filters] = $this->filtered($request);
        $total = (clone $query)->count();
        $entries = $query->with('attachments')->paginate(15)->withQueryString()->appends($filters);
        return view('project_log.index', compact('entries', 'filters', 'total'));
    }

    public function create()
    {
        return $this->form(new ProjectLogEntry(['responsible_id' => auth()->id(),
            'performed_on' => now(DisplayDate::timezone())->toDateString(), 'submission_key' => (string) Str::uuid()]));
    }

    public function show(int $entry)
    {
        return view('project_log.show', ['entry' => ProjectLogEntry::with(['project', 'attachments'])->findOrFail($entry)]);
    }

    public function edit(int $entry)
    {
        $record = ProjectLogEntry::with('attachments')->findOrFail($entry);
        abort_unless(ProjectLogAccess::canEdit(auth()->user(), $record), 403);
        return $this->form($record);
    }

    private function form(ProjectLogEntry $entry)
    {
        return view('project_log.form', [
            'entry' => $entry,
            'projects' => ProjectLogProject::orderBy('name')->get(),
            'people' => User::where('is_active', true)->whereIn('role', ['support', 'admin'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $paths = [];
        try {
            $entry = DB::transaction(function () use ($request, $data, &$paths) {
                $actor = $this->writingActor($request);
                $existing = ProjectLogEntry::where('submission_key', $data['submission_key'])->first();
                if ($existing) {
                    abort_unless((int) $existing->author_id === (int) $request->user()->id, 409);
                    return $existing;
                }
                $entry = ProjectLogEntry::create($this->attributes($data) + [
                    'author_id' => $actor->id, 'author_name' => $actor->name,
                    'submission_key' => $data['submission_key'],
                ]);
                $this->attach($request, $entry, $paths);
                AuditLog::record('project_log_created', ProjectLogEntry::class, $entry->id, ['project_id' => $entry->project_id]);
                return $entry;
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->cleanup($paths);
            // La restricción única resuelve también dos peticiones simultáneas.
            // Leer después del rollback evita reutilizar un snapshot antiguo.
            $entry = ProjectLogEntry::where('submission_key', $data['submission_key'])->first();
            if (!$entry) throw $e;
            abort_unless((int) $entry->author_id === (int) $request->user()->id, 409);
        } catch (Throwable $e) {
            $this->cleanup($paths);
            throw $e;
        }
        return redirect()->route('project-log.show', $entry)->with('success', 'Actualización publicada en la bitácora.');
    }

    public function update(Request $request, int $entry)
    {
        $record = ProjectLogEntry::findOrFail($entry);
        abort_unless(ProjectLogAccess::canEdit($request->user(), $record), 403);
        $data = $this->validated($request, true);
        $paths = [];
        try {
            DB::transaction(function () use ($request, $entry, $data, &$paths) {
                $actor = $this->writingActor($request);
                $record = ProjectLogEntry::whereKey($entry)->lockForUpdate()->firstOrFail();
                abort_unless(ProjectLogAccess::canEdit($actor, $record), 403);
                if ($record->version !== (int) $data['version']) {
                    throw ValidationException::withMessages(['version' => 'Este registro cambió mientras lo editabas. Recarga la página antes de guardar.']);
                }
                if ($record->attachments()->count() + count($request->file('attachments', [])) > 3) {
                    throw ValidationException::withMessages(['attachments' => 'Cada actualización admite hasta 3 adjuntos en total.']);
                }
                $record->update($this->attributes($data, $record) + ['version' => $record->version + 1]);
                $this->attach($request, $record, $paths);
                AuditLog::record('project_log_updated', ProjectLogEntry::class, $record->id, ['version' => $record->version]);
            });
        } catch (Throwable $e) {
            $this->cleanup($paths);
            throw $e;
        }
        return redirect()->route('project-log.show', $entry)->with('success', 'Actualización guardada.');
    }

    private function validated(Request $request, bool $editing = false): array
    {
        return $request->validate([
            'project_id' => ['nullable', 'required_without:new_project', 'prohibits:new_project', 'integer', 'exists:project_log_projects,id'],
            'new_project' => ['nullable', 'required_without:project_id', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'type' => ['required', Rule::in(array_keys(ProjectLogEntry::TYPES))],
            'performed_on' => ['required', 'date_format:Y-m-d'],
            'responsible_id' => $editing && $request->input('responsible_id') === 'existing'
                ? ['required', Rule::in(['existing'])]
                : ['required', 'integer', Rule::exists('usuarios', 'id')->where(fn ($q) => $q->where('is_active', true)->whereIn('role', ['support', 'admin']))],
            'submission_key' => $editing ? ['exclude'] : ['required', 'uuid'],
            'version' => $editing ? ['required', 'integer', 'min:1'] : ['exclude'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt', 'docx', 'xlsx', 'pptx', 'zip'])->max(5120),
                'extensions:pdf,jpg,jpeg,png,gif,webp,txt,docx,xlsx,pptx,zip'],
        ], [
            'required' => 'Completa el campo :attribute.',
            'required_without' => 'Selecciona un proyecto o escribe el nombre de uno nuevo.',
            'prohibits' => 'Selecciona un proyecto existente o crea uno nuevo, no ambos.',
            'string' => 'El campo :attribute debe ser un texto.',
            'integer' => 'Selecciona una opción válida para :attribute.',
            'exists' => 'La opción de :attribute ya no está disponible.',
            'in' => 'Selecciona un tipo de actualización válido.',
            'date_format' => 'La fecha del trabajo debe ser válida (AAAA-MM-DD).',
            'uuid' => 'El formulario no es válido. Recarga la página.',
            'max.string' => 'El campo :attribute admite hasta :max caracteres.',
            'max.array' => 'Puedes adjuntar hasta :max archivos.',
            'attachments.*.max' => 'Cada adjunto debe pesar como máximo 5 MB.',
            'attachments.*.mimes' => 'Formato no permitido. Usa PDF, JPG, PNG, GIF, WEBP, TXT, DOCX, XLSX, PPTX o ZIP.',
            'attachments.*.extensions' => 'La extensión del adjunto no está permitida.',
            'attachments.*.uploaded' => 'No se pudo recibir el adjunto. Revisa su tamaño y vuelve a seleccionarlo.',
        ], ['title' => 'título', 'description' => 'descripción', 'responsible_id' => 'responsable', 'type' => 'tipo',
            'new_project' => 'nuevo proyecto', 'project_id' => 'proyecto', 'performed_on' => 'fecha del trabajo']);
    }

    private function writingActor(Request $request): User
    {
        // Lectura actual y compartida: respeta revocaciones y no enfrenta dos
        // bloqueos exclusivos de usuario cuando los responsables se cruzan.
        $actor = User::whereKey($request->user()->id)->sharedLock()->first();
        abort_unless(ProjectLogAccess::staff($actor), 403);
        return $actor;
    }

    private function attributes(array $data, ?ProjectLogEntry $existing = null): array
    {
        $project = !empty($data['project_id'])
            ? ProjectLogProject::findOrFail($data['project_id'])
            // La recuperación de un UNIQUE debe leer el estado actual de MySQL,
            // aunque otro autor creara el mismo proyecto después del snapshot.
            : ProjectLogProject::query()->lockForUpdate()->createOrFirst(['name' => $data['new_project']]);
        if ($existing && $data['responsible_id'] === 'existing') {
            $responsible = ['responsible_id' => $existing->responsible_id, 'responsible_name' => $existing->responsible_name];
        } else {
            $person = User::whereKey($data['responsible_id'])->where('is_active', true)->whereIn('role', ['support', 'admin'])->sharedLock()->first();
            if (!$person) throw ValidationException::withMessages(['responsible_id' => 'El responsable ya no está disponible.']);
            $responsible = ['responsible_id' => $person->id, 'responsible_name' => $person->name];
        }
        return [
            'project_id' => $project->id,
            'title' => $data['title'], 'description' => $data['description'], 'type' => $data['type'],
            'performed_on' => $data['performed_on'],
        ] + $responsible;
    }

    private function attach(Request $request, ProjectLogEntry $entry, array &$paths): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->storeAs('project-log/'.$entry->id, Str::uuid().'.'.strtolower($file->getClientOriginalExtension()), 'local');
            if (!$path) throw ValidationException::withMessages(['attachments' => 'No se pudieron guardar los adjuntos. No se guardó ningún cambio; inténtalo de nuevo.']);
            $paths[] = $path;
            $name = preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $file->getClientOriginalName())));
            $entry->attachments()->create(['path' => $path, 'original_name' => Str::limit($name ?: 'adjunto', 170, ''), 'size' => $file->getSize()]);
        }
    }

    private function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            try { Storage::disk('local')->delete($path); } catch (Throwable $e) { report($e); }
        }
    }

    public function download(int $entry, int $attachment)
    {
        $file = ProjectLogAttachment::where('entry_id', $entry)->findOrFail($attachment);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return Storage::disk('local')->download($file->path, $file->original_name, ['Content-Type' => 'application/octet-stream']);
    }

    public function image(int $entry, int $attachment)
    {
        $file = ProjectLogAttachment::where('entry_id', $entry)->findOrFail($attachment);
        abort_unless($file->isPreviewableImage() && Storage::disk('local')->exists($file->path), 404);
        $path = Storage::disk('local')->path($file->path);
        // La extensión decide qué mostrar; el contenido real decide qué servir.
        $info = @getimagesize($path);
        $mime = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_GIF => 'image/gif', IMAGETYPE_WEBP => 'image/webp',
            default => null,
        };
        abort_unless($mime, 404);
        return response()->file($path, ['Content-Type' => $mime, 'Content-Disposition' => 'inline']);
    }

    private function filtered(Request $request): array
    {
        $filters = FilterValidation::validate($request, [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], 'project-log.index');
        $filters['date_from'] = $filters['date_from'] ?? now(DisplayDate::timezone())->startOfMonth()->toDateString();
        $filters['date_to'] = $filters['date_to'] ?? now(DisplayDate::timezone())->endOfMonth()->toDateString();
        if ($filters['date_to'] < $filters['date_from']) {
            throw ValidationException::withMessages(['date_to' => 'La fecha final debe ser igual o posterior a la inicial.'])->redirectTo(route('project-log.index'));
        }
        $query = ProjectLogEntry::with(['project'])->withCount('attachments')
            ->whereBetween('performed_on', [$filters['date_from'], $filters['date_to']])
            ->orderByDesc('performed_on')->orderByDesc('id');
        return [$query, $filters];
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        [$query, $filters] = $this->filtered($request);
        // El mismo límite y selección para los dos formatos, sin truncar datos.
        $entries = $query->limit(501)->get();
        if ($entries->count() > 500) {
            throw ValidationException::withMessages(['date_from' => 'El reporte supera 500 actualizaciones. Acota el rango de fechas para descargarlo completo.'])->redirectTo(route('project-log.index'));
        }
        $filename = 'bitacora_'.$filters['date_from'].'_'.$filters['date_to'];
        if ($format === 'pdf') {
            return Pdf::loadView('project_log.pdf', compact('entries', 'filters'))
                ->setOption('isRemoteEnabled', false)->setPaper('a4')->download($filename.'.pdf');
        }

        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Bitácora');
        $headers = ['Fecha del trabajo', 'Proyecto', 'Título', 'Tipo', 'Responsable', 'Descripción', 'Publicado por', 'Fecha de publicación'];
        foreach ($headers as $i => $value) $sheet->setCellValueExplicit([$i + 1, 1], $value, DataType::TYPE_STRING);
        foreach ($entries as $i => $entry) {
            $values = [$entry->performed_on->format('d/m/Y'), $entry->project->name, $entry->title, ProjectLogEntry::TYPES[$entry->type] ?? $entry->type,
                $entry->responsible_name, $entry->description, $entry->author_name, DisplayDate::format($entry->created_at)];
            foreach ($values as $j => $value) $sheet->setCellValueExplicit([$j + 1, $i + 2], $value, DataType::TYPE_STRING);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H'.max(1, $entries->count() + 1))->getAlignment()->setWrapText(true);
        foreach (['A' => 18, 'B' => 28, 'C' => 36, 'D' => 18, 'E' => 26, 'F' => 70, 'G' => 26, 'H' => 22] as $col => $width) $sheet->getColumnDimension($col)->setWidth($width);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H'.max(1, $entries->count() + 1));
        $path = tempnam(sys_get_temp_dir(), 'conecta_log_');
        if ($path === false) { $book->disconnectWorksheets(); abort(503, 'No se pudo generar el reporte.'); }
        try {
            (new Xlsx($book))->save($path);
            return response()->download($path, $filename.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            @unlink($path);
            throw $e;
        } finally {
            $book->disconnectWorksheets();
        }
    }
}
