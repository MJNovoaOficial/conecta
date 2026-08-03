<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_department_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->unique()->constrained('categorias')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departamentos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_department_rules');
    }
};
