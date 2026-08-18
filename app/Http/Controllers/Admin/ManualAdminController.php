<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $file     = $request->file('archivo');
        $filename = Str::slug($request->titulo) . '_' . time() . '.pdf';
        $path     = $file->storeAs('manuales', $filename, 'local'); // disco privado

        Manual::create([
            'titulo'                 => $request->titulo,
            'descripcion'            => $request->descripcion,
            'categoria'              => $request->categoria ?: null,
            'archivo_path'           => $path,
            'archivo_nombre_original'=> $file->getClientOriginalName(),
            'archivo_size'           => $file->getSize(),
            'is_active'              => true,
            'uploaded_by'            => Auth::id(),
        ]);

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

        if ($request->hasFile('archivo')) {
            // Eliminar el anterior
            Storage::disk('local')->delete($manual->archivo_path);

            $file     = $request->file('archivo');
            $filename = Str::slug($request->titulo) . '_' . time() . '.pdf';
            $path     = $file->storeAs('manuales', $filename, 'local'); // disco privado

            $data['archivo_path']            = $path;
            $data['archivo_nombre_original'] = $file->getClientOriginalName();
            $data['archivo_size']            = $file->getSize();
        }

        $manual->update($data);

        return redirect()->route('admin.manuales.index')
            ->with('success', 'Manual actualizado correctamente.');
    }

    public function destroy(Manual $manual)
    {
        Storage::disk('local')->delete($manual->archivo_path);
        $manual->delete();

        return redirect()->route('admin.manuales.index')
            ->with('success', 'Manual eliminado.');
    }
}
