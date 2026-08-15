<?php

namespace App\Http\Controllers;

use App\Services\AsistenteIA;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consultas al asistente de la base de conocimiento (RN-18).
 *
 * Responde en JSON porque el centro de ayuda lo consulta sin recargar la
 * página: el trabajador escribe su problema y ve la respuesta ahí mismo.
 */
class AsistenteController extends Controller
{
    public function preguntar(Request $request, AsistenteIA $asistente): JsonResponse
    {
        if (! $asistente->disponible()) {
            return response()->json(['tipo' => 'apagado'], 404);
        }

        $datos = $request->validate([
            'pregunta' => ['required', 'string', 'min:4', 'max:500'],
        ]);

        $resultado = $asistente->responder($datos['pregunta']);

        return response()->json([
            'tipo'    => $resultado['tipo'],
            'texto'   => $resultado['texto'],
            'fuentes' => $resultado['fuentes']->map(fn ($articulo) => [
                'titulo' => $articulo->title,
                'url'    => route('ayuda.show', $articulo),
            ])->values(),
        ]);
    }
}
