<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Categoria;
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
        $categorias = Categoria::withCount(['subcategorias', 'tickets'])
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categorias'));
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

        foreach ($request->sla as $item) {
            SlaConfig::updateOrCreate(
                ['priority' => $item['priority']],
                [
                    'response_hours'   => $item['response_hours'],
                    'resolution_hours' => $item['resolution_hours'],
                ]
            );
        }

        AuditLog::record('sla.updated', 'SlaConfig', null, $request->sla);

        return back()->with('success', 'Configuración de SLA actualizada.');
    }
}
