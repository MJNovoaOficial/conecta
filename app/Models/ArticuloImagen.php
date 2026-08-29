<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Imagen de apoyo dentro de un artículo de la base de conocimiento.
 *
 * Se muestra junto al paso que ilustra. La descripción no es decorativa: es lo
 * que leen los lectores de pantalla y lo que aparece si la imagen no carga.
 */
class ArticuloImagen extends Model
{
    protected $table = 'articulo_imagenes';

    protected $fillable = [
        'articulo_id',
        'ruta',
        'nombre_original',
        'descripcion',
        'orden',
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'articulo_id');
    }

    /**
     * URL para mostrar la imagen.
     *
     * Pasa por el controlador de archivos, no por una ruta pública: el archivo
     * vive en el disco privado y solo se entrega a quien tiene sesión.
     */
    public function getUrlAttribute(): string
    {
        return route('files.articulo-imagen', $this);
    }
}
