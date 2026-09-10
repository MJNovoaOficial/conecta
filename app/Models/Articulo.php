<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Artículo de la base de conocimiento (RN-18).
 *
 * Instructivo redactado por soporte para que el usuario resuelva por su cuenta
 * un problema frecuente, sin necesidad de abrir un ticket.
 */
class Articulo extends Model
{
    protected $table = 'articulos';

    protected $fillable = [
        'title',
        'symptoms',
        'content',
        'categoria_id',
        'subcategoria_id',
        'is_active',
        'publico',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'publico'   => 'boolean',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Imágenes de apoyo, en el orden en que ilustran los pasos.
     */
    public function imagenes()
    {
        return $this->hasMany(ArticuloImagen::class, 'articulo_id')
                    ->orderBy('orden')
                    ->orderBy('id');
    }

    // ── Consultas ─────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Artículos que puede leer alguien sin haber iniciado sesión.
     *
     * Los consulta el asistente de la pantalla de login, donde no se sabe
     * quién pregunta. Son los que soporte marcó explícitamente: dudas de
     * acceso y contraseñas, que es lo que la persona necesita justo antes
     * de entrar. Todo lo demás exige sesión.
     */
    public function scopePublicos($query)
    {
        return $query->where('publico', true);
    }

    /**
     * Palabras que se descartan de la búsqueda por aparecer en cualquier texto.
     * Sin esta lista, buscar "carpetas del servidor" trae todos los artículos,
     * porque "del" coincide en casi todos.
     *
     * Incluye también los verbos con que la gente arma la frase ("tengo",
     * "quiero", "necesito"): describen la intención, no el problema, y hacen
     * calzar artículos que no tienen nada que ver.
     */
    private const PALABRAS_VACIAS = [
        'del', 'las', 'los', 'una', 'unos', 'unas', 'por', 'con', 'que', 'sin',
        'para', 'como', 'mas', 'muy', 'esta', 'este', 'esto', 'pero', 'sus',
        'les', 'mi', 'me', 'no', 'se', 'el', 'la', 'de', 'en', 'un', 'al', 'lo',
        'es', 'si', 'su', 'ha', 'he', 'ya', 'yo', 'tu', 'te', 'le', 'da', 'va',
        'ir', 'ni', 'os', 'nos', 'cual', 'cuales', 'tengo', 'tiene', 'quiero',
        'necesito', 'puedo', 'pude', 'hacer', 'hago', 'veo', 'ver', 'donde',
        'cuando', 'porque', 'sobre', 'desde', 'hasta', 'algo', 'alguien',
        'favor', 'ayuda', 'ayudar',
        // "queda" arma la frase ("dónde queda", "se me queda") sin decir nada
        // del problema. Hacía calzar "dónde queda Recursos Humanos" con el
        // artículo de programas que se quedan cargando.
        //
        // "hay" parece igual de vacía y NO se agrega a propósito. Medido: sacarla
        // subía "hay reunión mañana" de 2.0 a 3.0 de relevancia —dos palabras en
        // vez de tres para el mismo puntaje—, y con eso cruzaba el umbral del
        // modelo, que pasaba a explicar el artículo del audio de Teams a quien
        // preguntaba por una reunión.
        'queda',
    ];

    /**
     * Terminaciones que raiz() quita, de la más larga a la más corta.
     *
     * El orden importa: si "-o" estuviera antes que "-isimo", "lentisimo"
     * quedaría en "lentisim" y no calzaría con "lento".
     */
    private const TERMINACIONES = [
        'isimos', 'isimas', 'isimo', 'isima', 'amente', 'mente',
        'aciones', 'iciones', 'acion', 'icion', 'ando', 'iendo',
        'adas', 'idas', 'ados', 'idos', 'ada', 'ida', 'ado', 'ido',
        'ar', 'er', 'ir', 'as', 'es', 'os', 'a', 'e', 'o', 's',
    ];

    /**
     * Divide una frase en las palabras que vale la pena buscar.
     *
     * Se aceptan palabras de dos letras porque en informática son siglas con
     * significado propio: IP, PC, TI. Si no queda ninguna palabra útil, se
     * busca la frase entera para no consultar en el vacío.
     */
    public static function palabrasClave(?string $termino): array
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return [];
        }

        $palabras = array_values(array_filter(
            preg_split('/\s+/', mb_strtolower($termino)),
            fn ($p) => mb_strlen($p) >= 2 && !in_array($p, self::PALABRAS_VACIAS, true)
        ));

        return $palabras ?: [mb_strtolower($termino)];
    }

    /**
     * Raíz de una palabra, para que las distintas formas de un mismo verbo o
     * sustantivo calcen entre sí.
     *
     * La búsqueda compara fragmentos de texto, y "imprimir" no es un fragmento
     * de "imprime". Quien escribía "no puedo imprimir" no encontraba el
     * artículo "La impresora no imprime", que además era el ejemplo que
     * sugería la propia burbuja de ayuda. Sin la terminación, "imprim" calza
     * con las dos formas.
     *
     * Es simple a propósito: corta terminaciones comunes del español y nunca
     * deja una raíz de menos de 4 letras. Más corta empieza a calzar dentro de
     * palabras que no tienen nada que ver.
     *
     * Medido sobre 35 consultas escritas como las escribiría una persona, no
     * copiadas de los síntomas: la persona ve el artículo correcto en 35 (antes
     * 33), y las consultas ajenas a soporte no encontraron nada nuevo.
     */
    public static function raiz(string $palabra): string
    {
        if (mb_strlen($palabra) <= 4) {
            return $palabra;
        }

        foreach (self::TERMINACIONES as $terminacion) {
            $largo = mb_strlen($palabra) - mb_strlen($terminacion);

            if ($largo >= 4 && str_ends_with($palabra, $terminacion)) {
                return mb_substr($palabra, 0, $largo);
            }
        }

        return $palabra;
    }

    /**
     * Cómo se compara una palabra contra un campo: [operador, patrón].
     *
     * Las palabras normales se buscan por su raíz y como fragmento, así
     * "carpeta" encuentra "carpetas" e "imprimir" encuentra "imprime". Las
     * siglas de dos letras, en cambio, tienen que calzar como palabra completa:
     * buscar "ip" como fragmento coincide dentro de "equipo", y terminaba
     * respondiendo sobre el cable de red a quien preguntaba por su dirección IP.
     */
    private static function comparacion(string $palabra): array
    {
        if (mb_strlen($palabra) > 2) {
            return ['LIKE', '%' . self::raiz($palabra) . '%'];
        }

        return ['REGEXP', '\\b' . preg_quote($palabra, '/') . '\\b'];
    }

    /**
     * Expresión SQL que puntúa un artículo contra una lista de palabras.
     *
     * Devuelve [expresión, valores]. Los términos viajan como bindings, nunca
     * interpolados en el SQL; el operador sale de comparacion(), no del texto
     * que escribió el usuario.
     */
    private static function expresionPuntaje(array $palabras): array
    {
        $expresiones = [];
        $valores     = [];

        foreach ($palabras as $palabra) {
            [$op, $patron] = self::comparacion($palabra);

            $expresiones[] = "(CASE WHEN title {$op} ? THEN 3 ELSE 0 END)";
            $expresiones[] = "(CASE WHEN symptoms {$op} ? THEN 2 ELSE 0 END)";
            $expresiones[] = "(CASE WHEN content {$op} ? THEN 1 ELSE 0 END)";
            $valores[] = $patron;
            $valores[] = $patron;
            $valores[] = $patron;
        }

        return [implode(' + ', $expresiones), $valores];
    }

    /**
     * Igual que buscar(), pero además trae el puntaje como columna.
     *
     * Lo usa el asistente para decidir si la consulta tiene algo que ver con la
     * base antes de pasársela al modelo.
     */
    public function scopeConPuntaje($query, ?string $termino)
    {
        $palabras = self::palabrasClave($termino);

        if (empty($palabras)) {
            return $query;
        }

        [$suma, $valores] = self::expresionPuntaje($palabras);

        return $query
            ->selectRaw("articulos.*, ({$suma}) as puntaje", $valores)
            ->where(function ($q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    [$op, $patron] = self::comparacion($palabra);

                    $q->orWhere('title', $op, $patron)
                      ->orWhere('symptoms', $op, $patron)
                      ->orWhere('content', $op, $patron);
                }
            })
            ->orderByDesc('puntaje')
            ->orderByDesc('helpful_yes');
    }

    /**
     * Busca artículos por texto libre.
     *
     * Divide la frase en palabras y puntúa cada artículo según cuántas de esas
     * palabras contiene y dónde: el título pesa más que los síntomas, y los
     * síntomas más que el cuerpo. Así "carpetas servidor" gana contra un
     * artículo que solo coincide en una palabra suelta.
     *
     * En caso de empate, primero el que más gente marcó como útil.
     */
    public function scopeBuscar($query, ?string $termino)
    {
        $palabras = self::palabrasClave($termino);

        if (empty($palabras)) {
            return $query;
        }

        $query->where(function ($q) use ($palabras) {
            foreach ($palabras as $palabra) {
                [$op, $patron] = self::comparacion($palabra);

                $q->orWhere('title', $op, $patron)
                  ->orWhere('symptoms', $op, $patron)
                  ->orWhere('content', $op, $patron);
            }
        });

        [$suma, $valores] = self::expresionPuntaje($palabras);

        return $query
            ->orderByRaw($suma . ' DESC', $valores)
            ->orderByDesc('helpful_yes')
            ->orderByDesc('views');
    }

    // ── Utilidades ────────────────────────────────────────────────

    /**
     * Porcentaje de personas a las que el artículo les sirvió.
     * Devuelve null si todavía nadie lo ha calificado.
     */
    public function getUtilidad(): ?int
    {
        $total = $this->helpful_yes + $this->helpful_no;

        return $total > 0 ? (int) round(($this->helpful_yes / $total) * 100) : null;
    }
}
