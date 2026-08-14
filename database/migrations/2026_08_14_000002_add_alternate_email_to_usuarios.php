<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega correo alternativo a usuarios (Reunión 3).
     * Permite al usuario registrar un correo personal/alternativo para
     * recibir notificaciones si su correo corporativo falla.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('alternate_email')->nullable()->after('email')
                  ->comment('Correo alternativo opcional para notificaciones de respaldo');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('alternate_email');
        });
    }
};
