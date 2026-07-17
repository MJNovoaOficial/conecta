<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\SlaConfig;
use App\Models\Subcategoria;
use App\Models\TipoIncidente;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        // ── CATÁLOGO DE CATEGORÍAS / SUBCATEGORÍAS / TIPOS ─────────────────

        $catalogo = [
            'Software' => [
                'Ofimática' => ['Activación de licencia', 'Instalación de Office', 'Error al abrir archivos', 'Actualización de software'],
                'SAP ERP'   => ['Accesos', 'Roles', 'Errores funcionales', 'Consulta de módulo'],
                'Correo Electrónico' => ['Creación de correo', 'Cambio de contraseña', 'Habilitación en equipo', 'Problemas de sincronización'],
                'DTE'       => ['Asignación Folios', 'Error facturación', 'Anulación de documento'],
            ],
            'Hardware' => [
                'Computadores'  => ['No enciende', 'Lentitud', 'Pantalla en blanco', 'Teclado/Mouse defectuoso'],
                'Impresoras'    => ['No imprime', 'Error de papel', 'Calidad de impresión', 'Sin tóner'],
                'Monitores'     => ['Líneas en pantalla', 'No enciende', 'Imagen borrosa'],
                'Periféricos'   => ['Cámara web', 'Auriculares/Micrófono', 'Disco externo', 'USB no reconocido'],
            ],
            'Red / Internet' => [
                'Conectividad'  => ['Sin acceso a internet', 'WiFi lento', 'Red corporativa caída', 'VPN no conecta'],
                'Accesos Remotos' => ['Configuración VPN', 'Escritorio remoto', 'Acceso a servidor'],
                'Carpetas Compartidas' => ['Sin permisos', 'Ruta no disponible', 'Velocidad lenta'],
            ],
            'Cuenta / Acceso' => [
                'Directorio Activo' => ['Contraseña bloqueada', 'Crear usuario', 'Eliminar usuario', 'Cambiar contraseña'],
                'Sistemas Internos'  => ['Sin acceso al sistema', 'Permisos incorrectos', 'Sesión bloqueada'],
            ],
            'Seguridad' => [
                'Antivirus' => ['Virus detectado', 'Actualización de antivirus', 'Falso positivo'],
                'Incidentes' => ['Acceso no autorizado', 'Pérdida de información', 'Correo sospechoso/Phishing'],
            ],
        ];

        foreach ($catalogo as $catName => $subcats) {
            $categoria = Categoria::firstOrCreate(
                ['name' => $catName],
                ['is_active' => true]
            );

            foreach ($subcats as $subcatName => $tipos) {
                $subcategoria = Subcategoria::firstOrCreate(
                    ['categoria_id' => $categoria->id, 'name' => $subcatName],
                    ['is_active' => true]
                );

                foreach ($tipos as $tipoName) {
                    TipoIncidente::firstOrCreate(
                        ['subcategoria_id' => $subcategoria->id, 'name' => $tipoName],
                        ['is_active' => true]
                    );
                }
            }
        }

        $this->command->info('✅ Catálogo de categorías cargado.');

        // ── SLA POR DEFECTO ─────────────────────────────────────────────────

        $slaDefaults = [
            'low'      => ['response_hours' => 24, 'resolution_hours' => 72],
            'medium'   => ['response_hours' => 8,  'resolution_hours' => 48],
            'high'     => ['response_hours' => 4,  'resolution_hours' => 24],
            'critical' => ['response_hours' => 1,  'resolution_hours' => 4],
        ];

        foreach ($slaDefaults as $priority => $times) {
            SlaConfig::updateOrCreate(
                ['priority' => $priority],
                $times
            );
        }

        $this->command->info('✅ Configuraciones SLA por defecto cargadas.');
    }
}
