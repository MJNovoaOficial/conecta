<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Categoria;
use App\Models\PriorityRule;
use App\Models\Subcategoria;
use App\Models\TipoIncidente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PriorityRuleController extends Controller
{
    public function index()
    {
        $rules        = PriorityRule::with(['categoria', 'subcategoria', 'tipoIncidente'])
                            ->orderBy('categoria_id')
                            ->orderBy('subcategoria_id')
                            ->orderBy('tipo_incidente_id')
                            ->get();
        $categorias   = Categoria::orderBy('name')->get();
        $subcategorias = Subcategoria::with('categoria')->orderBy('name')->get();
        $tipos        = TipoIncidente::with('subcategoria.categoria')->orderBy('name')->get();

        return view('admin.priority_rules.index', compact('rules', 'categorias', 'subcategorias', 'tipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria_id'      => 'required|exists:categorias,id',
            'subcategoria_id'   => 'nullable|exists:subcategorias,id',
            'tipo_incidente_id' => 'nullable|exists:tipos_incidente,id',
            'priority'          => 'required|in:low,medium,high,critical',
            'description'       => 'nullable|string|max:200',
        ]);

        $rule = PriorityRule::create($request->only(
            'categoria_id', 'subcategoria_id', 'tipo_incidente_id', 'priority', 'description'
        ));

        AuditLog::log('Regla de prioridad creada', "Prioridad '{$rule->priority}' para cat. ID {$rule->categoria_id}");

        return back()->with('success', 'Regla de prioridad creada correctamente.');
    }

    public function destroy(PriorityRule $priorityRule)
    {
        AuditLog::log('Regla de prioridad eliminada', "ID {$priorityRule->id}");
        $priorityRule->delete();
        return back()->with('success', 'Regla eliminada.');
    }
}
