<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Configuración General ─────────────────────────────
            ['key' => 'app_name',                 'value' => 'Conecta',       'type' => 'string',  'group' => 'general',        'label' => 'Nombre del sistema',                'description' => 'Nombre que aparece en el encabezado y correos.'],
            ['key' => 'app_timezone',             'value' => 'America/Santiago', 'type' => 'string', 'group' => 'general',       'label' => 'Zona horaria',                       'description' => 'Zona horaria del servidor.'],
            ['key' => 'tickets_per_page',         'value' => '15',            'type' => 'integer', 'group' => 'general',        'label' => 'Tickets por página',                'description' => 'Cantidad de tickets mostrados por página en los listados.'],
            ['key' => 'allow_guest_tickets',      'value' => '1',             'type' => 'boolean', 'group' => 'general',        'label' => 'Permitir tickets de invitados',      'description' => 'Si está activo, usuarios sin cuenta pueden abrir tickets.'],
            ['key' => 'max_attachment_size_mb',   'value' => '5',             'type' => 'integer', 'group' => 'general',        'label' => 'Tamaño máximo de adjunto (MB)',      'description' => 'Límite en megabytes por archivo adjunto.'],
            ['key' => 'max_attachments_per_ticket','value'=> '5',             'type' => 'integer', 'group' => 'general',        'label' => 'Adjuntos por ticket',               'description' => 'Número máximo de adjuntos por ticket.'],

            // ── Seguridad ─────────────────────────────────────────
            ['key' => 'session_timeout_minutes',  'value' => '120',           'type' => 'integer', 'group' => 'security',       'label' => 'Timeout de sesión (minutos)',        'description' => 'Minutos de inactividad antes de cerrar la sesión.'],
            ['key' => 'max_login_attempts',       'value' => '5',             'type' => 'integer', 'group' => 'security',       'label' => 'Intentos máximos de login',          'description' => 'Intentos fallidos antes de bloqueo temporal.'],
            ['key' => 'login_lockout_minutes',    'value' => '15',            'type' => 'integer', 'group' => 'security',       'label' => 'Minutos de bloqueo por intentos',   'description' => 'Tiempo de bloqueo tras exceder intentos máximos.'],

            // ── Notificaciones ────────────────────────────────────
            ['key' => 'notify_on_new_ticket',     'value' => '1',             'type' => 'boolean', 'group' => 'notifications',  'label' => 'Notificar al soporte en nuevo ticket', 'description' => 'Envía correo al equipo de soporte cuando se crea un ticket.'],
            ['key' => 'notify_on_assignment',     'value' => '1',             'type' => 'boolean', 'group' => 'notifications',  'label' => 'Notificar al técnico al asignarle un ticket', 'description' => ''],
            ['key' => 'notify_on_comment',        'value' => '1',             'type' => 'boolean', 'group' => 'notifications',  'label' => 'Notificar nuevos comentarios',      'description' => ''],
            ['key' => 'notify_on_status_change',  'value' => '1',             'type' => 'boolean', 'group' => 'notifications',  'label' => 'Notificar cambios de estado',        'description' => ''],
            ['key' => 'notify_on_close',          'value' => '1',             'type' => 'boolean', 'group' => 'notifications',  'label' => 'Notificar cierre de ticket',         'description' => ''],
            ['key' => 'support_email',            'value' => 'soporte@conecta.cl', 'type' => 'string', 'group' => 'notifications', 'label' => 'Email del equipo de soporte',   'description' => 'Correo que aparece como remitente en las notificaciones.'],

            // ── SLA ───────────────────────────────────────────────
            ['key' => 'user_response_timeout_hours', 'value' => '2',         'type' => 'integer', 'group' => 'sla',            'label' => 'Plazo de respuesta del usuario (horas)', 'description' => 'Horas máximas para que el usuario responda antes del cierre automático.'],
            ['key' => 'auto_close_enabled',       'value' => '1',            'type' => 'boolean', 'group' => 'sla',            'label' => 'Cierre automático por inactividad',  'description' => 'Cerrar tickets automáticamente cuando el usuario no responde.'],
        ];

        foreach ($settings as $s) {
            SystemSetting::updateOrCreate(['key' => $s['key']], array_except_key($s, 'key') + ['key' => $s['key']]);
        }

        // Usar insertOrIgnore para evitar duplicados
        foreach ($settings as $s) {
            SystemSetting::firstOrCreate(['key' => $s['key']], $s);
        }

        $this->command->info('✅ Configuraciones del sistema cargadas.');
    }
}

// Helper local para quitar 'key' del array de attributes
if (!function_exists('array_except_key')) {
    function array_except_key(array $arr, string $key): array
    {
        return array_diff_key($arr, [$key => '']);
    }
}
