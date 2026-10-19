<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\ArticuloImagen;
use App\Models\AuditLog;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Support\FilterValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        FilterValidation::validate($request, [
            'q' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], 'admin.articulos.index');

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

        $nuevasRutas = [];
        try {
            DB::transaction(function () use ($request, $datos, &$nuevasRutas) {
                $articulo = Articulo::create($datos);
                $this->guardarImagenes($request, $articulo, $nuevasRutas);
                AuditLog::record('articulo.created', 'Articulo', $articulo->id, [
                    'title' => $articulo->title,
                ]);
            });
        } catch (\Throwable $e) {
            $this->limpiarImagenesSinReferencias($nuevasRutas);
            throw $e;
        }

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
        // Validar también archivos y metadatos ANTES de escribir texto o borrar.
        $datos = $this->validar($request, $articulo);
        $nuevasRutas = [];
        $rutasRetiradas = [];
        try {
            DB::transaction(function () use ($request, $articulo, $datos, &$nuevasRutas, &$rutasRetiradas) {
                $actual = Articulo::query()->lockForUpdate()->findOrFail($articulo->id);
                $eraPublico = $actual->publico;
                $actual->update($datos);
                $this->actualizarDescripciones($request, $actual);
                $rutasRetiradas = $this->eliminarImagenes($request, $actual);
                $this->guardarImagenes($request, $actual, $nuevasRutas);

                AuditLog::record('articulo.updated', 'Articulo', $actual->id, [
                    'title' => $actual->title,
                ]);
                if ($eraPublico !== $actual->publico) {
                    AuditLog::record('articulo.visibilidad_publica', 'Articulo', $actual->id, [
                        'title' => $actual->title,
                        'publico' => $actual->publico,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            $this->limpiarImagenesSinReferencias($nuevasRutas);
            throw $e;
        }
        // El disco no participa de la transacción: borrar solo tras confirmarla.
        $this->limpiarImagenesSinReferencias($rutasRetiradas);

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
    private function guardarImagenes(Request $request, Articulo $articulo, array &$nuevasRutas): void
    {
        if (! $request->hasFile('imagenes')) {
            return;
        }

        // Las nuevas se agregan después de las que ya estaban.
        $orden = (int) $articulo->imagenes()->max('orden');

        foreach ($request->file('imagenes') as $archivo) {
            // Nombre propio: el original puede traer acentos, espacios o
            // repetirse entre artículos distintos.
            $nombre = Str::uuid() . '.' . strtolower($archivo->getClientOriginalExtension());
            $ruta = 'articulos/' . $articulo->id . '/' . $nombre;
            $nuevasRutas[] = $ruta;
            try {
                $guardado = $archivo->storeAs('articulos/' . $articulo->id, $nombre, 'local');
            } catch (\Throwable $e) {
                Log::warning('No se pudo almacenar una imagen de artículo.', ['exception' => get_class($e)]);
                $guardado = false;
            }
            if ($guardado === false) {
                throw ValidationException::withMessages([
                    'imagenes' => 'No se pudieron guardar las imágenes. Intenta nuevamente; no se guardaron cambios.',
                ]);
            }

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
        $descripciones = $request->input('imagen_descripcion', []);
        $ordenes = $request->input('imagen_orden', []);
        foreach (array_unique(array_merge(array_keys($descripciones), array_keys($ordenes))) as $id) {
            $datos = [];
            if (array_key_exists($id, $descripciones)) {
                $datos['descripcion'] = $descripciones[$id] !== '' ? $descripciones[$id] : null;
            }
            if (array_key_exists($id, $ordenes)) {
                $datos['orden'] = (int) $ordenes[$id];
            }
            $articulo->imagenes()->whereKey($id)->update($datos);
        }
    }

    /**
     * Retira solo las filas marcadas y devuelve rutas para limpiar tras el commit.
     */
    private function eliminarImagenes(Request $request, Articulo $articulo): array
    {
        $ids = (array) $request->input('eliminar_imagen', []);

        if (empty($ids)) {
            return [];
        }

        $rutas = [];
        foreach ($articulo->imagenes()->whereKey($ids)->get() as $imagen) {
            $rutas[] = $imagen->ruta;
            $imagen->delete();
        }
        return $rutas;
    }

    private function limpiarImagenesSinReferencias(array $rutas): void
    {
        foreach (array_unique($rutas) as $ruta) {
            try {
                if (! ArticuloImagen::where('ruta', $ruta)->exists()
                    && ! Storage::disk('local')->delete($ruta)) {
                    Log::warning('No se pudo retirar una imagen sin referencias.', ['path' => $ruta]);
                }
            } catch (\Throwable $e) {
                // Conservar el archivo y registrar el incidente: no convertir
                // un guardado confirmado en un error que provoque reintentos.
                Log::warning('Se conserva una imagen al fallar su limpieza.', ['path' => $ruta, 'exception' => get_class($e)]);
            }
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

    private function validar(Request $request, ?Articulo $articulo = null): array
    {
        $datos = $request->validate([
            'title'           => 'required|string|max:255',
            'symptoms'        => 'nullable|string|max:1000',
            'content'         => 'required|string|max:10000',
            'categoria_id'    => 'nullable|exists:categorias,id',
            'subcategoria_id' => 'nullable|exists:subcategorias,id',
            // Solo llega desde el formulario de edición. Si no viene, el
            // artículo conserva lo que tenía, y uno nuevo nace privado.
            'publico'         => 'sometimes|boolean',
            'imagenes'        => ['sometimes', 'array', 'max:15'],
            'imagenes.*'      => ['required', 'image', 'mimes:' . implode(',', self::IMAGENES_PERMITIDAS), 'max:4096'],
            'imagen_descripcion'   => ['sometimes', 'array'],
            'imagen_descripcion.*' => ['nullable', 'string', 'max:300'],
            'imagen_orden'         => ['sometimes', 'array'],
            'imagen_orden.*'       => ['required', 'integer', 'between:0,999'],
            'eliminar_imagen'      => ['sometimes', 'array'],
            'eliminar_imagen.*'    => ['required', 'integer', Rule::exists('articulo_imagenes', 'id')->where('articulo_id', $articulo?->id ?? 0)],
        ], [
            'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
            'imagenes.*.max' => 'Cada imagen debe pesar menos de 4 MB.',
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

        // Las claves de estos mapas son ID de imagen, no valores de formulario.
        // No aceptar datos malformados ni imágenes pertenecientes a otro artículo.
        $ids = array_unique(array_merge(
            array_keys($datos['imagen_descripcion'] ?? []),
            array_keys($datos['imagen_orden'] ?? [])
        ));
        if ($ids !== []) {
            $validos = $articulo ? $articulo->imagenes()->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
            foreach ($ids as $id) {
                if (! in_array((string) $id, $validos, true)) {
                    throw ValidationException::withMessages([
                        'imagenes' => 'Una de las imágenes seleccionadas no pertenece a este artículo. Recarga el formulario.',
                    ]);
                }
            }
        }

        return Arr::only($datos, ['title', 'symptoms', 'content', 'categoria_id', 'subcategoria_id', 'publico']);
    }
}
