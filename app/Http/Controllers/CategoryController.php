<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Categoria;
use App\Models\CategoryDepartmentRule;
use App\Models\Department;
use App\Models\SlaConfig;
use App\Models\Subcategoria;
use App\Models\TipoIncidente;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // ══════════════════════════════════════════
    //  CATEGORÍAS
    // ══════════════════════════════════════════

    public function index()
    {
        $categorias = Categoria::withCount(['subcategorias'])
            ->with(['subcategorias.tiposIncidente'])
            ->orderBy('name')
            ->get()
            ->each(function ($cat) {
                $cat->tickets_count = \App\Models\Ticket::whereIn(
                    'subcategoria_id',
                    $cat->subcategorias->pluck('id')
                )->count();
                // Cargar regla de departamento automático si existe
                $cat->dept_rule = CategoryDepartmentRule::where('categoria_id', $cat->id)->first();
            });

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.categories.index', compact('categorias', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:categorias,name',
            'description' => 'nullable|string|max:500',
        ]);

        $cat = Categoria::create([
            'name'        => trim($request->name),
            'description' => trim($request->description ?? ''),
            'is_active'   => true,
        ]);

        AuditLog::record('categoria.created', 'Categoria', $cat->id, ['name' => $cat->name]);

        return back()->with('success', "Categoría \"{$cat->name}\" creada.");
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:categorias,name,' . $categoria->id,
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ]);

        $categoria->update([
            'name'        => trim($request->name),
            'description' => trim($request->description ?? ''),
            'is_active'   => $request->boolean('is_active', true),
        ]);

        AuditLog::record('categoria.updated', 'Categoria', $categoria->id, $request->all());

        return back()->with('success', "Categoría actualizada.");
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->tickets()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: la categoría tiene tickets asociados.');
        }

        AuditLog::record('categoria.deleted', 'Categoria', $categoria->id, ['name' => $categoria->name]);
        $categoria->delete();

        return back()->with('success', "Categoría eliminada.");
    }

    // ══════════════════════════════════════════
    //  SUBCATEGORÍAS
    // ══════════════════════════════════════════

    public function storeSubcategoria(Request $request, Categoria $categoria)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $sub = $categoria->subcategorias()->create([
            'name'        => trim($request->name),
            'description' => trim($request->description ?? ''),
            'is_active'   => true,
        ]);

        AuditLog::record('subcategoria.created', 'Subcategoria', $sub->id, ['name' => $sub->name, 'categoria' => $categoria->name]);

        return back()->with('success', "Subcategoría \"{$sub->name}\" creada.");
    }

    public function updateSubcategoria(Request $request, Subcategoria $subcategoria)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ]);

        $subcategoria->update([
            'name'        => trim($request->name),
            'description' => trim($request->description ?? ''),
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Subcategoría actualizada.");
    }

    public function destroySubcategoria(Subcategoria $subcategoria)
    {
        if ($subcategoria->tickets()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: la subcategoría tiene tickets asociados.');
        }

        $subcategoria->delete();
        return back()->with('success', "Subcategoría eliminada.");
    }

    // ══════════════════════════════════════════
    //  TIPOS DE INCIDENTE
    // ══════════════════════════════════════════

    public function storeTipo(Request $request, Subcategoria $subcategoria)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $tipo = $subcategoria->tiposIncidente()->create([
            'name'        => trim($request->name),
            'description' => trim($request->description ?? ''),
            'is_active'   => true,
        ]);

        return back()->with('success', "Tipo de incidente \"{$tipo->name}\" creado.");
    }

    public function destroyTipo(TipoIncidente $tipo)
    {
        if ($tipo->tickets()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: el tipo tiene tickets asociados.');
        }

        $tipo->delete();
        return back()->with('success', "Tipo de incidente eliminado.");
    }

    // ══════════════════════════════════════════
    //  AJAX — cargar subcategorías y tipos
    // ══════════════════════════════════════════

    public function getSubcategorias(Categoria $categoria)
    {
        return response()->json(
            $categoria->subcategorias()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
        );
    }

    public function getTipos(Subcategoria $subcategoria)
    {
        return response()->json(
            $subcategoria->tiposIncidente()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
        );
    }

    // ══════════════════════════════════════════
    //  SLA
    // ══════════════════════════════════════════

    public function sla()
    {
        $configs = SlaConfig::orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")->get();
        return view('admin.sla.index', compact('configs'));
    }

    public function updateSla(Request $request)
    {
        $request->validate([
            'sla'                    => 'required|array',
            'sla.*.priority'         => 'required|in:low,medium,high,critical',
            'sla.*.response_hours'   => 'required|integer|min:1|max:720',
            'sla.*.resolution_hours' => 'required|integer|min:1|max:720',
        ]);

        $incoming = collect($request->sla)
            ->map(function (array $item): array {
                return [
                    'priority' => $item['priority'],
                    'response_hours' => (int) $item['response_hours'],
                    'resolution_hours' => (int) $item['resolution_hours'],
                ];
            })
            ->keyBy('priority');

        $existing = SlaConfig::whereIn('priority', $incoming->keys())
            ->get()
            ->keyBy('priority');

        $changes = [];

        foreach ($incoming as $priority => $item) {
            $current = $existing->get($priority);
            $currentResponse = $current ? (int) $current->response_hours : null;
            $currentResolution = $current ? (int) $current->resolution_hours : null;

            $hasChanged = $current === null
                || $currentResponse !== $item['response_hours']
                || $currentResolution !== $item['resolution_hours'];

            if (!$hasChanged) {
                continue;
            }

            SlaConfig::updateOrCreate(
                ['priority' => $priority],
                [
                    'response_hours' => $item['response_hours'],
                    'resolution_hours' => $item['resolution_hours'],
                ]
            );

            $changes[] = [
                'priority' => $priority,
                'response_hours' => $item['response_hours'],
                'resolution_hours' => $item['resolution_hours'],
                'old_response_hours' => $currentResponse,
                'old_resolution_hours' => $currentResolution,
            ];
        }

        if (empty($changes)) {
            return back()->with('success', 'No hubo cambios en la configuración SLA.');
        }

        AuditLog::record('sla.updated', 'SlaConfig', null, $changes);

        return back()->with('success', 'Configuración de SLA actualizada.');
    }

    // ══════════════════════════════════════════
    //  REGLA DE DEPARTAMENTO POR CATEGORÍA
    // ══════════════════════════════════════════

    public function storeDeptRule(Request $request, Categoria $categoria)
    {
        $request->validate([
            'department_id' => 'required|exists:departamentos,id',
        ]);

        CategoryDepartmentRule::updateOrCreate(
            ['categoria_id' => $categoria->id],
            ['department_id' => $request->department_id]
        );

        AuditLog::record('dept_rule.created', 'Categoria', $categoria->id, ['department_id' => $request->department_id]);

        return back()->with('success', "Derivación automática configurada para '{$categoria->name}'.");
    }

    public function destroyDeptRule(Categoria $categoria)
    {
        CategoryDepartmentRule::where('categoria_id', $categoria->id)->delete();
        AuditLog::record('dept_rule.deleted', 'Categoria', $categoria->id);
        return back()->with('success', 'Regla de derivación eliminada.');
    }
}
