<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registra las reaperturas de un ticket.
 *
 * Hasta ahora, cuando el solicitante decía que la solución no le sirvió, el
 * ticket seguía contando como resuelto: el botón "No, necesito más ayuda" solo
 * llevaba al formulario de comentarios. La tasa de resolución quedaba inflada
 * y no había forma de saber por cuánto.
 *
 * Con el contador aparece un indicador que antes no existía: cuántos problemas
 * se resuelven al primer intento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedSmallInteger('reopened_count')->default(0)->after('resolved_at');
            $table->timestamp('reopened_at')->nullable()->after('reopened_count');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['reopened_count', 'reopened_at']);
        });
    }
};
