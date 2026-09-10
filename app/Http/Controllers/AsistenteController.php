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
 *
 * Hay dos entradas y la diferencia importa: preguntar() atiende a quien ya
 * inició sesión y ve toda la base; preguntarPublico() atiende la pantalla de
 * login, donde no se sabe quién está al otro lado.
 */
class AsistenteController extends Controller
{
    /**
     * No exige que el asistente esté encendido.
     *
     * Con el servidor de modelos apagado igual devuelve los artículos que
     * tratan la consulta, así la ayuda sirve desde el primer día y mejora sola
     * cuando el servidor esté disponible.
     */
    public function preguntar(Request $request, AsistenteIA $asistente): JsonResponse
    {
        $resultado = $asistente->responder($this->pregunta($request));

        return response()->json([
            'tipo'    => $resultado['tipo'],
            'texto'   => $resultado['texto'],
            'fuentes' => $resultado['fuentes']->map(fn ($articulo) => [
                'titulo' => $articulo->title,
                'url'    => route('ayuda.show', $articulo),
                // Las imágenes del artículo viajan con la respuesta: para quien
                // no está familiarizado con la tecnología, ver la pantalla
                // resuelve en segundos lo que un párrafo explica mal.
                'imagenes' => $articulo->imagenes->map(fn ($imagen) => [
                    'url'         => $imagen->url,
                    'descripcion' => $imagen->descripcion,
                ])->values(),
            ])->values(),
        ]);
    }

    /**
     * Asistente de la pantalla de login. Sin sesión.
     *
     * Solo consulta los artículos marcados como públicos, que son los de
     * acceso y contraseñas: lo que la persona necesita justo antes de entrar.
     *
     * Devuelve el texto de la explicación y los títulos, pero no enlaces ni
     * imágenes. No es una restricción de seguridad extra —el filtro de verdad
     * es scopePublicos()— sino que esas rutas exigen sesión: un enlace acá
     * mandaría a la persona de vuelta al login del que está tratando de salir.
     */
    public function preguntarPublico(Request $request, AsistenteIA $asistente): JsonResponse
    {
        $resultado = $asistente->responder($this->pregunta($request), soloPublicos: true);

        return response()->json([
            'tipo'    => $resultado['tipo'],
            'texto'   => $resultado['texto'],
            'fuentes' => $resultado['fuentes']
                ->map(fn ($articulo) => ['titulo' => $articulo->title])
                ->values(),
        ]);
    }

    private function pregunta(Request $request): string
    {
        return $request->validate([
            'pregunta' => ['required', 'string', 'min:4', 'max:500'],
        ])['pregunta'];
    }
}
