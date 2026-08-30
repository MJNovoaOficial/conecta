<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite comentarios sin usuario registrado.
 *
 * Un invitado no tiene cuenta, así que sus respuestas no pueden apuntar a un
 * usuario. Hasta ahora la columna era obligatoria y por eso un invitado
 * simplemente no podía responder: se le pedía información y no tenía cómo
 * entregarla, hasta que el ticket se cerraba solo por falta de respuesta.
 *
 * El nombre de quien escribió se toma de guest_name del ticket.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comentarios_ticket', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Los comentarios de invitados impedirían restaurar la restricción, así
        // que se eliminan primero. Es información que se pierde: revertir esta
        // migración borra las respuestas que dieron los invitados.
        Schema::table('comentarios_ticket', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
