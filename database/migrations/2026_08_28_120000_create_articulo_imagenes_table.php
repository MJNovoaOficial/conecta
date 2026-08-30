<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Imágenes de apoyo para los artículos de la base de conocimiento.
 *
 * Los manuales que hoy circulan por correo son pasos numerados con una captura
 * en cada uno: el texto solo no alcanza a explicar dónde hay que tocar. Esto
 * permite reproducir ese formato dentro de la plataforma, en vez de adjuntar un
 * PDF que nadie puede buscar ni actualizar.
 *
 * Van en tabla aparte, y no como una columna del artículo, porque un
 * instructivo necesita varias imágenes en un orden determinado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulo_imagenes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                  ->constrained('articulos')
                  ->cascadeOnDelete();

            // Ruta dentro del disco privado. Nunca se sirve directa: pasa por
            // FileController, igual que los adjuntos y los manuales.
            $table->string('ruta');
            $table->string('nombre_original');

            // Texto que acompaña a la imagen. Cumple dos funciones: describe el
            // paso y es lo que leen los lectores de pantalla.
            $table->string('descripcion', 300)->nullable();

            // Posición dentro del instructivo. Sin esto el orden de los pasos
            // quedaría a merced de cómo devuelva las filas la base de datos.
            $table->unsignedSmallInteger('orden')->default(0);

            $table->timestamps();

            $table->index(['articulo_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulo_imagenes');
    }
};
