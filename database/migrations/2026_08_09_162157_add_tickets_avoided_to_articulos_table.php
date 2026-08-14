<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuenta cuántas veces un artículo evitó que se abriera un ticket.
 *
 * Es la métrica que justifica la base de conocimiento: sin ella no hay forma
 * de saber si sirve de algo o solo es contenido que nadie lee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->unsignedInteger('tickets_avoided')->default(0)->after('helpful_no');
        });
    }

    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropColumn('tickets_avoided');
        });
    }
};
