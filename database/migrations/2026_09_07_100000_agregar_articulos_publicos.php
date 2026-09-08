<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca qué artículos puede leer alguien que todavía no inició sesión.
 *
 * El asistente pasa a estar también en la pantalla de login, donde no hay
 * sesión y por lo tanto no hay forma de saber quién pregunta. Sin este
 * interruptor, poner el asistente ahí publicaría toda la base de conocimiento
 * a cualquiera que llegue a la página.
 *
 * Por defecto en false: un artículo nuevo no se expone solo. Alguien de
 * soporte tiene que decidir, artículo por artículo, que ese contenido puede
 * leerlo cualquiera. Es más trabajo de configuración, pero el error de
 * olvidarse deja contenido de menos, no de más.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->boolean('publico')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropColumn('publico');
        });
    }
};
