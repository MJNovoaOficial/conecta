<?php

namespace App\Http\Controllers;

use App\Models\Manual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManualController extends Controller
{
    /**
     * Lista pública de manuales con buscador y filtro por categoría.
     */
    public function index(Request $request)
    {
        $query = Manual::activos()->with('uploader')->latest();

        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($sq) use ($q) {
                $sq->where('titulo', 'like', "%{$q}%")
                   ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        if ($request->filled('categoria')) {
            $query->byCategoria($request->categoria);
        }

        $manuales   = $query->get();
        $categorias = Manual::activos()->distinct()->orderBy('categoria')->pluck('categoria')->filter()->values();

        return view('manuales.index', compact('manuales', 'categorias'));
    }

    /**
     * Sirve el PDF al usuario e incrementa el contador de descargas.
     */
    public function download(Manual $manual)
    {
        abort_unless($manual->is_active, 404);

        $manual->increment('downloads_count');

        return Storage::download(
            $manual->archivo_path,
            $manual->archivo_nombre_original
        );
    }
}
