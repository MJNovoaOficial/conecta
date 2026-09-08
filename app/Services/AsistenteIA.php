<?php

namespace App\Services;

use App\Models\Articulo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asistente de la base de conocimiento.
 *
 * Responde consultas de los trabajadores usando únicamente los artículos
 * publicados por soporte. El modelo de lenguaje corre en un servidor de la
 * empresa (Ollama), así que ninguna consulta sale de la red interna.
 *
 * El modelo no aporta conocimiento propio: se le entrega el artículo y se le
 * pide que lo explique. Si la consulta no coincide con ningún artículo, no se
 * le pregunta nada y se ofrece abrir un ticket. Eso es lo que evita que
 * invente procedimientos que la empresa no tiene.
 */
class AsistenteIA
{
    public const RESPUESTA      = 'respuesta';       // el modelo contestó
    public const SOLO_ARTICULOS = 'solo_articulos';  // hay artículos, sin modelo
    public const SIN_COBERTURA  = 'sin_cobertura';   // la base no cubre el tema
    public const NO_DISPONIBLE  = 'no_disponible';   // el servidor no responde

    public function disponible(): bool
    {
        return (bool) config('chatbot.enabled');
    }

    /**
     * Artículos de la base que tratan sobre la consulta.
     *
     * Devuelve una colección vacía cuando la consulta no tiene que ver con la
     * base. Está separado de responder() para que la ayuda funcione aunque el
     * servidor de modelos no exista: buscar el artículo correcto no necesita
     * inteligencia artificial, solo la búsqueda que ya teníamos.
     */
    public function articulosRelevantes(string $pregunta)
    {
        return $this->buscar($pregunta)['articulos'];
    }

    /**
     * Busca y calcula qué tan relacionada está la consulta con lo encontrado.
     *
     * Con $soloPublicos la búsqueda se limita a los artículos que soporte marcó
     * como legibles sin sesión. Es el filtro que separa al asistente de la
     * pantalla de login del que ve un trabajador ya autenticado.
     *
     * @return array{articulos:\Illuminate\Support\Collection, relevancia:float}
     */
    private function buscar(string $pregunta, bool $soloPublicos = false): array
    {
        $vacio = ['articulos' => collect(), 'relevancia' => 0.0];
        $palabras = Articulo::palabrasClave($pregunta);

        if (empty($palabras)) {
            return $vacio;
        }

        $articulos = Articulo::activos()
            ->when($soloPublicos, fn ($q) => $q->publicos())
            ->with('imagenes')
            ->conPuntaje($pregunta)
            ->limit((int) config('chatbot.articulos_contexto', 2))
            ->get();

        if ($articulos->isEmpty()) {
            return $vacio;
        }

        // El puntaje crudo favorece las preguntas largas, así que se divide por
        // la cantidad de palabras buscadas. Sin esto, una consulta ajena pero
        // larga puede empatar con una consulta corta y pertinente.
        $relevancia = $articulos->first()->puntaje / count($palabras);

        return $relevancia < (float) config('chatbot.umbral_articulos', 0.9)
            ? $vacio
            : ['articulos' => $articulos, 'relevancia' => $relevancia];
    }

    /**
     * @return array{tipo:string, texto:string, fuentes:\Illuminate\Support\Collection}
     */
    public function responder(string $pregunta, bool $soloPublicos = false): array
    {
        ['articulos' => $articulos, 'relevancia' => $relevancia] = $this->buscar($pregunta, $soloPublicos);

        if ($articulos->isEmpty()) {
            return $this->sinCobertura($soloPublicos);
        }

        $soloArticulos = [
            'tipo'    => self::SOLO_ARTICULOS,
            // Sin sesión el título no es un enlace, así que "revisa si alguna
            // trata tu problema" mandaría a la persona a hacer clic en algo que
            // no se puede abrir.
            'texto'   => $soloPublicos
                ? 'Esto es lo más parecido que encontré en las guías de acceso. '
                  . 'Si no es tu caso, escríbenos con el botón de abajo.'
                : 'Encontré esto en las guías de soporte. Revisa si alguna trata tu problema:',
            'fuentes' => $articulos,
        ];

        // Sin servidor de modelos igual se entrega el artículo: es la mitad del
        // valor y no depende de ninguna decisión de infraestructura.
        if (! $this->disponible()) {
            return $soloArticulos;
        }

        // Coincidencia débil: se muestra el artículo pero no se le pide al
        // modelo que lo explique. Que la persona lea el título y decida es
        // preferible a una explicación segura y sin fundamento.
        //
        // Antes de iniciar sesión el umbral es más bajo, porque ahí "mostrar el
        // artículo" es solo mostrar su título: la ruta para leerlo exige sesión.
        // Ver la explicación completa en config/chatbot.php.
        $umbral = $soloPublicos
            ? (float) config('chatbot.umbral_relevancia_publico', 1.5)
            : (float) config('chatbot.umbral_relevancia', 2.5);

        if ($relevancia < $umbral) {
            return $soloArticulos;
        }

        $texto = $this->consultarModelo($this->construirPrompt($pregunta, $articulos));

        if ($texto === null) {
            return [
                'tipo'    => self::NO_DISPONIBLE,
                'texto'   => 'El asistente no está disponible en este momento. '
                           . 'Los artículos de abajo tratan sobre lo que consultaste.',
                'fuentes' => $articulos,
            ];
        }

        return [
            'tipo'    => self::RESPUESTA,
            'texto'   => $texto,
            'fuentes' => $articulos,
        ];
    }

    /**
     * Antes de iniciar sesión el mensaje cambia: quien pregunta ahí no puede
     * abrir un ticket normal, pero sí uno como invitado. Mandarlo a "abre un
     * ticket" sin decirle cómo lo deja en el mismo lugar donde estaba.
     */
    private function sinCobertura(bool $soloPublicos = false): array
    {
        return [
            'tipo'    => self::SIN_COBERTURA,
            'texto'   => $soloPublicos
                ? 'No encontré nada sobre eso en las guías que puedo mostrar antes de '
                  . 'iniciar sesión. Si no logras entrar, escríbenos y soporte lo revisa.'
                : 'No encontré nada sobre eso en la base de conocimiento. '
                  . 'Abre un ticket y soporte lo revisa.',
            'fuentes' => collect(),
        ];
    }

    /**
     * El prompt no incluye ninguna frase de rechazo a propósito.
     *
     * Cuando se le ofrecía una ("si no sabes, responde exactamente...") el
     * modelo la usaba aunque tuviera el artículo correcto delante: medido, se
     * negaba en 3 de cada 8 consultas válidas. Decidir la cobertura en PHP y
     * dejarle al modelo una sola tarea resultó mucho más estable.
     */
    private function construirPrompt(string $pregunta, $articulos): string
    {
        $contexto = '';
        foreach ($articulos as $articulo) {
            $contexto .= "### {$articulo->title}\n{$articulo->content}\n\n";
        }

        return "Eres el asistente de la mesa de ayuda de Dimak. Un compañero de trabajo "
             . "te hizo una consulta y abajo tienes el articulo del manual interno que la responde.\n\n"
             . "Tu tarea: explicarle lo que dice el articulo, en espanol, breve y directo.\n"
             . "No agregues nada que no aparezca en el articulo.\n\n"
             . "ARTICULO DEL MANUAL:\n{$contexto}"
             . "CONSULTA: {$pregunta}\n\nTu respuesta:";
    }

    /**
     * Devuelve el texto del modelo, o null si el servidor no respondió.
     *
     * Una caída de Ollama no puede tumbar el centro de ayuda: el buscador y los
     * artículos tienen que seguir funcionando igual.
     */
    private function consultarModelo(string $prompt): ?string
    {
        try {
            $respuesta = Http::timeout((int) config('chatbot.timeout', 60))
                ->post(rtrim(config('chatbot.url'), '/') . '/api/generate', [
                    'model'      => config('chatbot.model'),
                    'prompt'     => $prompt,
                    'stream'     => false,
                    'keep_alive' => config('chatbot.keep_alive', '30m'),
                    'options'    => ['temperature' => (float) config('chatbot.temperatura', 0.2)],
                ]);

            if ($respuesta->failed()) {
                Log::warning('Asistente: el servidor de modelos respondió ' . $respuesta->status());
                return null;
            }

            $texto = trim((string) $respuesta->json('response'));

            return $texto !== '' ? $texto : null;
        } catch (\Throwable $e) {
            Log::warning('Asistente: no se pudo consultar el modelo: ' . $e->getMessage());
            return null;
        }
    }
}
