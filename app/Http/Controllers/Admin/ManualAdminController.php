<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManualAdminController extends Controller
{
    public function index()
    {
        $manuales = Manual::with('uploader')->latest()->get();
        return view('admin.manuales.index', compact('manuales'));
    }

    public function create()
    {
        return view('admin.manuales.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria'   => 'nullable|string|max:100',
            'archivo'     => 'required|file|mimes:pdf|max:20480', // 20 MB máx
        ], [
            'archivo.mimes' => 'Solo se permiten archivos PDF.',
            'archivo.max'   => 'El PDF no puede superar los 20 MB.',
        ]);

        $path = 'manuales/' . Str::uuid() . '.pdf';
        try {
            $this->guardarPdf($request, $path);
            DB::transaction(function () use ($request, $path) {
                Manual::create([
                    'titulo'                 => $request->titulo,
                    'descripcion'            => $request->descripcion,
                    'categoria'              => $request->categoria ?: null,
                    'archivo_path'           => $path,
                    'archivo_nombre_original'=> $request->file('archivo')->getClientOriginalName(),
                    'archivo_size'           => $request->file('archivo')->getSize(),
                    'is_active'              => true,
                    'uploaded_by'            => Auth::id(),
                ]);
            });
        } catch (\Throwable $e) {
            $this->eliminarPdfSinReferencias($path);
            throw $e;
        }

        return redirect()->route('admin.manuales.index')
            ->with('success', 'Manual "' . $request->titulo . '" subido correctamente.');
    }

    public function edit(Manual $manual)
    {
        return view('admin.manuales.edit', compact('manual'));
    }

    public function update(Request $request, Manual $manual)
    {
        $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria'   => 'nullable|string|max:100',
            'archivo'     => 'nullable|file|mimes:pdf|max:20480',
        ]);

        $data = [
            'titulo'      => $request->titulo,
            'descripcion' => $request->descripcion,
            'categoria'   => $request->categoria ?: null,
            'is_active'   => $request->boolean('is_active'),
        ];

        $path = $request->hasFile('archivo') ? 'manuales/' . Str::uuid() . '.pdf' : null;
        $anterior = null;
        try {
            if ($path !== null) {
                // Primero escribir el reemplazo: un fallo nunca borra el PDF vigente.
                $this->guardarPdf($request, $path);
                $data['archivo_path'] = $path;
                $data['archivo_nombre_original'] = $request->file('archivo')->getClientOriginalName();
                $data['archivo_size'] = $request->file('archivo')->getSize();
            }

            DB::transaction(function () use ($manual, $data, $path, &$anterior) {
                $actual = Manual::query()->lockForUpdate()->findOrFail($manual->id);
                $anterior = $path !== null ? $actual->archivo_path : null;
                $actual->update($data);
            });
        } catch (\Throwable $e) {
            if ($path !== null) {
                $this->eliminarPdfSinReferencias($path);
            }
            throw $e;
        }
        if ($anterior !== null) {
            $this->eliminarPdfSinReferencias($anterior);
        }

        return redirect()->route('admin.manuales.index')
            ->with('success', 'Manual actualizado correctamente.');
    }

    public function destroy(Manual $manual)
    {
        $path = DB::transaction(function () use ($manual) {
            $actual = Manual::query()->lockForUpdate()->findOrFail($manual->id);
            $path = $actual->archivo_path;
            $actual->delete();
            return $path;
        });
        $this->eliminarPdfSinReferencias($path);

        return redirect()->route('admin.manuales.index')
            ->with('success', 'Manual eliminado.');
    }

    private function guardarPdf(Request $request, string $path): void
    {
        try {
            $guardado = $request->file('archivo')->storeAs('manuales', basename($path), 'local');
        } catch (\Throwable $e) {
            Log::warning('No se pudo almacenar un PDF de manual.', ['exception' => get_class($e)]);
            $guardado = false;
        }
        if ($guardado === false) {
            throw ValidationException::withMessages([
                'archivo' => 'No se pudo guardar el PDF. Intenta nuevamente; el manual anterior se conserva.',
            ]);
        }
    }

    private function eliminarPdfSinReferencias(string $path): void
    {
        // Los PDF históricos pueden estar compartidos por una colisión antigua.
        // Si la limpieza falla, conservar un archivo es preferible a perder datos
        // o informar como fallido un guardado que ya se confirmó en la base.
        try {
            if (! Manual::where('archivo_path', $path)->exists()
                && ! Storage::disk('local')->delete($path)) {
                Log::warning('No se pudo retirar un PDF sin referencias.', ['path' => $path]);
            }
        } catch (\Throwable $e) {
            Log::warning('Se conserva un PDF al fallar su limpieza.', ['path' => $path, 'exception' => get_class($e)]);
        }
    }
}
