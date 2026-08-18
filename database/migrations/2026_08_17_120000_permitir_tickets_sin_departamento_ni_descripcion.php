<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite guardar tickets sin departamento y sin descripción.
 *
 * El formulario simplificado (Reunión 4) hizo opcionales ambos campos en la
 * validación, pero las columnas seguían siendo obligatorias en la base: crear
 * un ticket sin llenarlos terminaba en un error 500, justo en el caso que la
 * simplificación buscaba habilitar.
 *
 * La clave foránea hacia departamentos se mantiene: si viene un departamento
 * tiene que existir, solo que ahora puede no venir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->change();
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Los tickets que ya se crearon sin estos datos impedirían volver atrás,
        // así que se les pone un valor antes de restaurar la restricción.
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('description')->nullable(false)->default('')->change();
        });

        // department_id no admite un valor por defecto razonable: revertir exige
        // decidir a qué departamento van esos tickets, y eso no lo puede
        // resolver una migración.
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
        });
    }
};
