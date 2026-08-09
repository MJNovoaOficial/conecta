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
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    // ── Consultas ─────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Palabras que se descartan de la búsqueda por aparecer en cualquier texto.
     * Sin esta lista, buscar "carpetas del servidor" trae todos los artículos,
     * porque "del" coincide en casi todos.
     */
    private const PALABRAS_VACIAS = [
        'del', 'las', 'los', 'una', 'unos', 'unas', 'por', 'con', 'que', 'sin',
        'para', 'como', 'mas', 'muy', 'esta', 'este', 'esto', 'pero', 'sus',
        'les', 'mi', 'me', 'no', 'se', 'el', 'la', 'de', 'en', 'un', 'al', 'lo',
    ];

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
        $termino = trim((string) $termino);

        if ($termino === '') {
            return $query;
        }

        $palabras = array_values(array_filter(
            preg_split('/\s+/', mb_strtolower($termino)),
            fn ($p) => mb_strlen($p) >= 3 && !in_array($p, self::PALABRAS_VACIAS, true)
        ));

        // Si el usuario escribió solo palabras vacías, se busca la frase entera.
        if (empty($palabras)) {
            $palabras = [mb_strtolower($termino)];
        }

        $query->where(function ($q) use ($palabras) {
            foreach ($palabras as $palabra) {
                $q->orWhere('title', 'like', "%{$palabra}%")
                  ->orWhere('symptoms', 'like', "%{$palabra}%")
                  ->orWhere('content', 'like', "%{$palabra}%");
            }
        });

        // Puntaje de relevancia. Los términos viajan como bindings, nunca
        // interpolados en el SQL.
        $expresiones = [];
        $valores     = [];

        foreach ($palabras as $palabra) {
            $expresiones[] = '(CASE WHEN title LIKE ? THEN 3 ELSE 0 END)';
            $expresiones[] = '(CASE WHEN symptoms LIKE ? THEN 2 ELSE 0 END)';
            $expresiones[] = '(CASE WHEN content LIKE ? THEN 1 ELSE 0 END)';
            $valores[] = "%{$palabra}%";
            $valores[] = "%{$palabra}%";
            $valores[] = "%{$palabra}%";
        }

        return $query
            ->orderByRaw(implode(' + ', $expresiones) . ' DESC', $valores)
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
