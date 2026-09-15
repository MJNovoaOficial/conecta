<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Categoria;
use App\Models\PriorityRule;
use App\Models\Subcategoria;
use App\Models\TipoIncidente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
        $rule = PriorityRule::create($this->validatedData($request));

        AuditLog::record('priority_rule.created', 'PriorityRule', $rule->id, ['priority' => $rule->priority, 'categoria_id' => $rule->categoria_id]);

        return back()->with('success', 'Regla de prioridad creada correctamente.');
    }

    // Mostrar formulario de edición de regla de prioridad
    public function edit(PriorityRule $priorityRule)
    {
        $categorias = Categoria::orderBy('name')->get();
        $subcategorias = Subcategoria::with('categoria')->orderBy('name')->get();
        $tipos = TipoIncidente::with('subcategoria.categoria')->orderBy('name')->get();

        return view('admin.priority_rules.edit', compact('priorityRule', 'categorias', 'subcategorias', 'tipos'));
    }

    // Actualizar regla de prioridad existente
    public function update(Request $request, PriorityRule $priorityRule)
    {
        $oldData = $priorityRule->toArray();
        $priorityRule->update($this->validatedData($request));

        AuditLog::record('priority_rule.updated', 'PriorityRule', $priorityRule->id, [
            'old' => $oldData,
            'new' => $priorityRule->toArray(),
        ]);

        return back()->with('success', 'Regla de prioridad actualizada correctamente.');
    }

    public function destroy(PriorityRule $priorityRule)
    {
        AuditLog::record('priority_rule.deleted', 'PriorityRule', $priorityRule->id);
        $priorityRule->delete();
        return back()->with('success', 'Regla eliminada.');
    }

    /**
     * Mantiene coherente la jerarquía que forma la regla de prioridad.
     * El filtro del navegador facilita la elección, pero no es una barrera
     * para una solicitud manual.
     */
    private function validatedData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'categoria_id'      => 'required|exists:categorias,id',
            'subcategoria_id'   => 'nullable|exists:subcategorias,id',
            'tipo_incidente_id' => 'nullable|exists:tipos_incidente,id',
            'priority'          => 'required|in:low,medium,high,critical',
            'description'       => 'nullable|string|max:200',
        ]);

        $validator->after(function ($validator) use ($request): void {
            $categoriaId = (int) $request->input('categoria_id');
            $subcategoria = $request->filled('subcategoria_id')
                ? Subcategoria::find($request->integer('subcategoria_id'))
                : null;
            $tipo = $request->filled('tipo_incidente_id')
                ? TipoIncidente::find($request->integer('tipo_incidente_id'))
                : null;

            if ($subcategoria && $subcategoria->categoria_id !== $categoriaId) {
                $validator->errors()->add('subcategoria_id', 'La subcategoría no pertenece a la categoría seleccionada.');
            }

            if ($tipo && ! $subcategoria) {
                $validator->errors()->add('subcategoria_id', 'Selecciona la subcategoría antes de elegir un tipo de incidente.');
            }

            if ($tipo && $subcategoria && $tipo->subcategoria_id !== $subcategoria->id) {
                $validator->errors()->add('tipo_incidente_id', 'El tipo de incidente no pertenece a la subcategoría seleccionada.');
            }
        });

        return $validator->validate();
    }
}
