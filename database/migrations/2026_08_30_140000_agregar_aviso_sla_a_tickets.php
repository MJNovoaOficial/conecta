<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registra de qué plazo se avisó que el ticket estaba por vencer.
 *
 * Guarda la fecha límite sobre la que se avisó, no el momento del aviso. Así la
 * misma columna resuelve las dos preguntas:
 *
 *   - ¿ya avisé? -> coincide con el plazo actual
 *   - ¿le movieron el plazo? -> no coincide, y corresponde avisar de nuevo
 *
 * Guardar el momento del aviso no sirve: al avisar, el plazo siempre está en el
 * futuro, así que cualquier comparación contra él da siempre el mismo
 * resultado y el aviso se repite en cada pasada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_warned_for')
                  ->nullable()
                  ->after('sla_resolution_deadline_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('sla_warned_for');
        });
    }
};
