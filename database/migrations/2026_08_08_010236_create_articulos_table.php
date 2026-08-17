<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base de conocimiento (RN-18).
 *
 * Artículos de autoayuda que el equipo de soporte redacta para los problemas
 * más frecuentes, de modo que el usuario pueda resolverlos sin abrir un ticket.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            // Palabras con las que el usuario describiría el problema
            // ("pantalla negra, no da señal, monitor apagado"). Es lo que hace
            // que la búsqueda funcione aunque no use el mismo título.
            $table->text('symptoms')->nullable();

            $table->text('content');

            // Clasificación opcional: permite sugerir artículos según la
            // categoría que el usuario eligió al crear el ticket.
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategorias')->nullOnDelete();

            $table->boolean('is_active')->default(true);

            // Métricas para saber si esto sirve de algo (etapa 2).
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('helpful_yes')->default(0);
            $table->unsignedInteger('helpful_no')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();

            $table->timestamps();

            $table->index('is_active');
            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos');
    }
};
