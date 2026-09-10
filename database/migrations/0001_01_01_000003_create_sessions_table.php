<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla donde se guardan las sesiones iniciadas.
 *
 * Faltaba. Laravel la trae en la misma migración que crea la tabla de usuarios,
 * y en este proyecto esa migración se reemplazó por una propia para la tabla
 * 'usuarios': la tabla de sesiones se perdió en el reemplazo.
 *
 * No se notó porque en desarrollo SESSION_DRIVER está en 'file', que guarda las
 * sesiones en storage y no necesita base de datos. Pero .env.example —la
 * plantilla de producción— indica 'database', así que un despliegue hecho según
 * la documentación fallaba en la PRIMERA petición, antes de mostrar el login:
 *
 *     SQLSTATE[42S02]: Base table or view not found:
 *     Table 'conecta.sessions' doesn't exist
 *
 * Con la tabla creada, las dos configuraciones funcionan.
 *
 * Es el esquema estándar de Laravel. user_id lleva índice pero no clave
 * foránea, igual que en el original: si se borra un usuario, conviene que sus
 * sesiones queden huérfanas y expiren solas, no que el borrado falle.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
