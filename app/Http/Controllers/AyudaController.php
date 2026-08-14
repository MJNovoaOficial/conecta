<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Categoria;
use Illuminate\Http\Request;

/**
 * Base de conocimiento vista desde el lado del usuario (RN-18).
 *
 * El administrador escribe los artículos desde ArticuloController; aquí solo se
 * consultan. Nunca se muestran artículos desactivados.
 */
class AyudaController extends Controller
{
    /**
     * Buscador y listado de artículos.
     */
    public function index(Request $request)
    {
        $articulos = Articulo::activos()
            ->with('categoria')
            ->when($request->filled('q'), fn ($q) => $q->buscar($request->q))
            ->when($request->filled('categoria_id'), fn ($q) => $q->where('categoria_id', $request->categoria_id))
            ->when(!$request->filled('q'), fn ($q) => $q->orderByDesc('tickets_avoided')->orderByDesc('views'))
            ->paginate(10)
            ->withQueryString();

        $categorias = Categoria::orderBy('name')->get();

        return view('ayuda.index', compact('articulos', 'categorias'));
    }

    /**
     * Muestra un artículo y registra la visita.
     */
    public function show(Articulo $articulo)
    {
        abort_unless($articulo->is_active, 404);

        // increment() actualiza directo en la base, sin leer y volver a guardar.
        // Evita perder cuentas si dos personas lo abren a la vez.
        $articulo->increment('views');

        $relacionados = Articulo::activos()
            ->where('id', '!=', $articulo->id)
            ->when($articulo->categoria_id, fn ($q) => $q->where('categoria_id', $articulo->categoria_id))
            ->limit(3)
            ->get();

        return view('ayuda.show', compact('articulo', 'relacionados'));
    }

    /**
     * Devuelve sugerencias en JSON mientras el usuario escribe el asunto del
     * ticket. Lo consume el JavaScript del formulario.
     */
    public function sugerencias(Request $request)
    {
        $termino = trim((string) $request->q);

        // Con menos de 4 caracteres cualquier búsqueda devuelve medio catálogo.
        if (mb_strlen($termino) < 4) {
            return response()->json([]);
        }

        $articulos = Articulo::activos()
            ->buscar($termino)
            ->limit(3)
            ->get(['id', 'title']);

        return response()->json(
            $articulos->map(fn ($a) => [
                'id'    => $a->id,
                'title' => $a->title,
                'url'   => route('ayuda.show', $a),
            ])
        );
    }

    /**
     * Registra si el artículo le sirvió a quien lo leyó.
     */
    public function util(Request $request, Articulo $articulo)
    {
        $request->validate(['util' => 'required|in:si,no']);

        $articulo->increment($request->util === 'si' ? 'helpful_yes' : 'helpful_no');

        return back()->with(
            'success',
            $request->util === 'si'
                ? '¡Gracias! Nos ayuda a saber qué contenido sirve.'
                : 'Gracias por avisar. Revisaremos este artículo.'
        );
    }

    /**
     * El usuario resolvió su problema con el artículo y desistió de abrir el
     * ticket. Es la métrica que justifica toda la base de conocimiento.
     */
    public function evitado(Articulo $articulo)
    {
        $articulo->increment('tickets_avoided');
        $articulo->increment('helpful_yes');

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Nos alegra que se haya resuelto. No se abrió ningún ticket.');
    }
}
