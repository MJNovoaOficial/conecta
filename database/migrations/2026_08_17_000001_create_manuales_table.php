<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo de manuales descargables (Reunión 4).
     * El equipo de soporte sube PDFs con guías y los usuarios los descargan.
     */
    public function up(): void
    {
        Schema::create('manuales', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('categoria')->nullable()->comment('Tag/categoría: correo, red, sap, general...');
            $table->string('archivo_path')->comment('Ruta relativa en storage/app/public/manuales/');
            $table->string('archivo_nombre_original')->comment('Nombre original del PDF subido');
            $table->unsignedBigInteger('archivo_size')->default(0)->comment('Tamaño en bytes');
            $table->unsignedInteger('downloads_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->constrained('usuarios')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuales');
    }
};
