<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lo que respondió quien pidió la ayuda sobre cómo lo atendieron.
 *
 * Una por ticket. Guarda el agente que atendía en ese momento, no el que el
 * ticket tenga asignado después.
 */
class EncuestaSatisfaccion extends Model
{
    protected $table = 'encuestas_satisfaccion';

    protected $fillable = [
        'ticket_id',
        'agente_id',
        'usuario_id',
        'calificacion',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'calificacion' => 'integer',
        ];
    }

    /**
     * Las opciones se muestran como caras con su nombre al lado. Tocar una
     * carita es más fácil que elegir un número para quien no está habituado a
     * este tipo de formularios, y el texto evita que "3" se lea distinto según
     * la persona.
     */
    public const CARAS = [
        1 => '😞',
        2 => '🙁',
        3 => '😐',
        4 => '🙂',
        5 => '😄',
    ];

    public const ETIQUETAS = [
        1 => 'Muy mala',
        2 => 'Mala',
        3 => 'Regular',
        4 => 'Buena',
        5 => 'Muy buena',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agente()
    {
        return $this->belongsTo(User::class, 'agente_id');
    }

    public function cara(): string
    {
        return self::CARAS[$this->calificacion] ?? '';
    }

    public function etiqueta(): string
    {
        return self::ETIQUETAS[$this->calificacion] ?? '';
    }
}
