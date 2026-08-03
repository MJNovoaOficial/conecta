<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priority_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('cascade');
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategorias')->onDelete('cascade');
            $table->foreignId('tipo_incidente_id')->nullable()->constrained('tipos_incidente')->onDelete('cascade');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('description', 200)->nullable();
            $table->timestamps();
            // Índice para búsqueda rápida (nombre corto por límite MySQL)
            $table->index(['categoria_id', 'subcategoria_id', 'tipo_incidente_id'], 'prio_rules_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priority_rules');
    }
};
