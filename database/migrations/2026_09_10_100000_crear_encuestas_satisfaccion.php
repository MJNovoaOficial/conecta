<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encuesta de satisfacción: quien pidió la ayuda califica la atención una vez
 * que el ticket está cerrado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuestas_satisfaccion', function (Blueprint $table) {
            $table->id();

            // Una por ticket, y la restricción la hace cumplir la base, no solo
            // el controlador: dos envíos simultáneos del mismo formulario no
            // pueden dejar dos calificaciones.
            $table->foreignId('ticket_id')->unique()->constrained('tickets')->cascadeOnDelete();

            // Quién atendía cuando se respondió. Se guarda aparte del ticket
            // para que una reasignación posterior no le mueva la calificación a
            // otro agente.
            $table->foreignId('agente_id')->nullable()->constrained('usuarios')->nullOnDelete();

            // Quién respondió. Nulo cuando es un invitado sin cuenta.
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();

            // De 1 (muy mala) a 5 (muy buena).
            $table->unsignedTinyInteger('calificacion');
            $table->text('comentario')->nullable();

            $table->timestamps();

            // El reporte por agente promedia por aquí.
            $table->index(['agente_id', 'calificacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas_satisfaccion');
    }
};
