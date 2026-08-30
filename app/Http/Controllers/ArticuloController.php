<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\ArticuloImagen;
use App\Models\AuditLog;
use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Administración de la base de conocimiento (RN-18).
 *
 * Los artículos no se eliminan, se desactivan: igual que los usuarios. Un
 * artículo puede estar enlazado desde un ticket y borrarlo dejaría el rastro
 * incompleto.
 */
class ArticuloController extends Controller
{
    public function index(Request $request)
    {
        $articulos = Articulo::with(['categoria', 'autor'])
            ->when($request->filled('q'), fn ($q) => $q->buscar($request->q))
            ->when($request->filled('categoria_id'), fn ($q) => $q->where('categoria_id', $request->categoria_id))
            ->when($request->filled('estado'), fn ($q) => $q->where('is_active', $request->estado === 'activos'))
            ->when(!$request->filled('q'), fn ($q) => $q->orderByDesc('id'))
            ->paginate(15)
            ->withQueryString();

        $categorias = Categoria::orderBy('name')->get();

        return view('admin.articulos.index', compact('articulos', 'categorias'));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['created_by'] = Auth::id();
        $datos['is_active']  = true;

        $articulo = Articulo::create($datos);
        $this->guardarImagenes($request, $articulo);

        AuditLog::record('articulo.created', 'Articulo', $articulo->id, [
            'title' => $articulo->title,
        ]);

        return back()->with('success', 'Artículo creado correctamente.');
    }

    public function edit(Articulo $articulo)
    {
        $categorias    = Categoria::orderBy('name')->get();
        $subcategorias = Subcategoria::orderBy('name')->get();

        return view('admin.articulos.edit', compact('articulo', 'categorias', 'subcategorias'));
    }

    public function update(Request $request, Articulo $articulo)
    {
        $articulo->update($this->validar($request));

        $this->actualizarDescripciones($request, $articulo);
        $this->eliminarImagenes($request, $articulo);
        $this->guardarImagenes($request, $articulo);

        AuditLog::record('articulo.updated', 'Articulo', $articulo->id, [
            'title' => $articulo->title,
        ]);

        return redirect()
            ->route('admin.articulos.index')
            ->with('success', 'Artículo actualizado.');
    }

    // ── Imágenes de apoyo ─────────────────────────────────────────────

    /**
     * Extensiones aceptadas. Solo imágenes: no es un espacio para adjuntar
     * documentos, para eso están los manuales.
     */
    private const IMAGENES_PERMITIDAS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Guarda las imágenes nuevas al final del instructivo.
     *
     * Van al disco privado, igual que los adjuntos: se sirven por
     * FileController y no quedan accesibles sin sesión.
     */
    private function guardarImagenes(Request $request, Articulo $articulo): void
    {
        if (! $request->hasFile('imagenes')) {
            return;
        }

        $request->validate([
            'imagenes'   => ['array', 'max:15'],
            'imagenes.*' => ['image', 'mimes:' . implode(',', self::IMAGENES_PERMITIDAS), 'max:4096'],
        ], [
            'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
            'imagenes.*.max'   => 'Cada imagen debe pesar menos de 4 MB.',
        ]);

        // Las nuevas se agregan después de las que ya estaban.
        $orden = (int) $articulo->imagenes()->max('orden');

        foreach ($request->file('imagenes') as $archivo) {
            if (! $archivo->isValid()) {
                continue;
            }

            // Nombre propio: el original puede traer acentos, espacios o
            // repetirse entre artículos distintos.
            $nombre = Str::uuid() . '.' . strtolower($archivo->getClientOriginalExtension());
            $ruta   = $archivo->storeAs('articulos/' . $articulo->id, $nombre, 'local');

            $articulo->imagenes()->create([
                'ruta'            => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'orden'           => ++$orden,
            ]);
        }
    }

    /**
     * Guarda el texto que acompaña a cada imagen y su posición.
     */
    private function actualizarDescripciones(Request $request, Articulo $articulo): void
    {
        foreach ((array) $request->input('imagen_descripcion', []) as $id => $descripcion) {
            $articulo->imagenes()
                ->whereKey($id)
                ->update([
                    'descripcion' => $descripcion !== '' ? mb_substr($descripcion, 0, 300) : null,
                    'orden'       => (int) $request->input("imagen_orden.{$id}", 0),
                ]);
        }
    }

    /**
     * Borra las imágenes marcadas, del disco y de la base.
     *
     * Aquí sí se elimina de verdad: a diferencia de un artículo, una imagen no
     * deja rastro en ningún ticket y conservarla solo ocupa espacio.
     */
    private function eliminarImagenes(Request $request, Articulo $articulo): void
    {
        $ids = (array) $request->input('eliminar_imagen', []);

        if (empty($ids)) {
            return;
        }

        foreach ($articulo->imagenes()->whereKey($ids)->get() as $imagen) {
            Storage::disk('local')->delete($imagen->ruta);
            $imagen->delete();
        }
    }

    /**
     * Activa o desactiva el artículo. Un artículo inactivo deja de aparecer
     * en las búsquedas de los usuarios, pero se conserva.
     */
    public function toggle(Articulo $articulo)
    {
        $articulo->update(['is_active' => !$articulo->is_active]);

        AuditLog::record('articulo.toggled', 'Articulo', $articulo->id, [
            'is_active' => $articulo->is_active,
        ]);

        return back()->with(
            'success',
            $articulo->is_active ? 'Artículo activado.' : 'Artículo desactivado.'
        );
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'title'           => 'required|string|max:255',
            'symptoms'        => 'nullable|string|max:1000',
            'content'         => 'required|string|max:10000',
            'categoria_id'    => 'nullable|exists:categorias,id',
            'subcategoria_id' => 'nullable|exists:subcategorias,id',
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'max'      => 'El campo :attribute no puede superar los :max caracteres.',
            'exists'   => 'La opción seleccionada en :attribute no es válida.',
        ], [
            'title'           => 'título',
            'symptoms'        => 'síntomas',
            'content'         => 'contenido',
            'categoria_id'    => 'categoría',
            'subcategoria_id' => 'subcategoría',
        ]);
    }
}
