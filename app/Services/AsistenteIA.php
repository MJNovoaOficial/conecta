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
    public const RESPUESTA     = 'respuesta';      // el modelo contestó
    public const SIN_COBERTURA = 'sin_cobertura';  // la base no cubre el tema
    public const NO_DISPONIBLE = 'no_disponible';  // el servidor no responde

    public function disponible(): bool
    {
        return (bool) config('chatbot.enabled');
    }

    /**
     * @return array{tipo:string, texto:string, fuentes:\Illuminate\Support\Collection}
     */
    public function responder(string $pregunta): array
    {
        $palabras = Articulo::palabrasClave($pregunta);

        if (empty($palabras)) {
            return $this->sinCobertura();
        }

        $articulos = Articulo::activos()
            ->conPuntaje($pregunta)
            ->limit((int) config('chatbot.articulos_contexto', 2))
            ->get();

        if ($articulos->isEmpty()) {
            return $this->sinCobertura();
        }

        // El puntaje crudo favorece las preguntas largas, así que se divide por
        // la cantidad de palabras buscadas. Sin esto, una consulta ajena pero
        // larga puede empatar con una consulta corta y pertinente.
        $relevancia = $articulos->first()->puntaje / count($palabras);

        if ($relevancia < (float) config('chatbot.umbral_relevancia', 1.8)) {
            return $this->sinCobertura();
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

    private function sinCobertura(): array
    {
        return [
            'tipo'    => self::SIN_COBERTURA,
            'texto'   => 'No encontré nada sobre eso en la base de conocimiento. '
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
