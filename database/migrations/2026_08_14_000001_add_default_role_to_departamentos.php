<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega default_role a la tabla departamentos (Reunión 3).
     * Permite asignar un rol por defecto al departamento para que
     * los usuarios nuevos lo hereden automáticamente.
     */
    public function up(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            $table->enum('default_role', ['user', 'support', 'admin'])
                  ->default('user')
                  ->after('is_active')
                  ->comment('Rol heredado por los usuarios al ingresar a este departamento');
        });
    }

    public function down(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropColumn('default_role');
        });
    }
};
