<?php

namespace App\Support;

/**
 * Catálogo compartido. Solo se conectan las opciones aprobadas; generales,
 * invitados, login y plazos históricos permanecen informativos o separados.
 */
class SystemSettingsCatalog
{
    public const GROUPS = ['general', 'attachments', 'notifications', 'sla', 'security'];

    public static function defaults(): array
    {
        return [
            ['key'=>'app_name','value'=>'Conecta','type'=>'string','group'=>'general','label'=>'Nombre del sistema','description'=>'Nombre informado por la configuración actual de la aplicación.'],
            ['key'=>'app_timezone','value'=>'America/Santiago','type'=>'string','group'=>'general','label'=>'Zona horaria','description'=>'Hora y zona efectivas de Laravel/PHP en el servidor. No modifica ni reinterpreta fechas guardadas.'],
            ['key'=>'tickets_per_page','value'=>'15','type'=>'integer','group'=>'general','label'=>'Tickets por página','description'=>'Cantidad actual de tickets antes de pasar a la siguiente página del listado.'],
            ['key'=>'allow_guest_tickets','value'=>'1','type'=>'boolean','group'=>'general','label'=>'Acceso de invitados','description'=>'La creación y el seguimiento sin login mediante enlace privado se mantienen habilitados.'],
            ['key'=>'attachments_global_enabled','value'=>'0','type'=>'boolean','group'=>'attachments','label'=>'Usar límites globales de adjuntos','description'=>'Al activar, los límites siguientes se aplican a creación y respuestas, también de invitados. Desactivado conserva los límites anteriores.'],
            ['key'=>'max_attachment_size_mb','value'=>'50','type'=>'integer','group'=>'attachments','label'=>'Tamaño máximo por archivo (MB)','description'=>'De 1 a 50 MB. El servidor puede imponer un límite menor; se conservan los tipos de archivo permitidos.'],
            ['key'=>'max_attachments_per_ticket','value'=>'5','type'=>'integer','group'=>'attachments','label'=>'Cantidad máxima de archivos por envío','description'=>'De 1 a 5 archivos en cada creación o respuesta. No modifica archivos ya guardados.'],
            ['key'=>'session_timeout_minutes','value'=>(string) env('SESSION_TIMEOUT_MINUTES',120),'type'=>'integer','group'=>'security','label'=>'Cierre de sesión por inactividad (minutos)','description'=>'Editable: de 1 a 1440 minutos. Se aplica a la inactividad y a la duración de la sesión.'],
            ['key'=>'max_login_attempts','value'=>'5','type'=>'integer','group'=>'security','label'=>'Intentos máximos de login','description'=>'Preferencia almacenada; el login conserva su protección actual.'],
            ['key'=>'login_lockout_minutes','value'=>'15','type'=>'integer','group'=>'security','label'=>'Bloqueo por intentos fallidos','description'=>'Regla vigente: 90 segundos después de 5 fallos por cuenta. El valor histórico almacenado no modifica esta regla.'],
            ['key'=>'notify_on_new_ticket','value'=>'1','type'=>'boolean','group'=>'notifications','label'=>'Avisos de nuevos tickets','description'=>'Activa o desactiva los avisos actuales por correo y campana. El enlace de seguimiento de invitados se conserva.'],
            ['key'=>'notify_on_assignment','value'=>'1','type'=>'boolean','group'=>'notifications','label'=>'Avisos de asignación','description'=>'Activa o desactiva los avisos de asignación por correo y campana.'],
            ['key'=>'notify_on_comment','value'=>'1','type'=>'boolean','group'=>'notifications','label'=>'Avisos de comentarios','description'=>'Activa o desactiva los avisos de comentarios por correo y campana.'],
            ['key'=>'notify_on_status_change','value'=>'1','type'=>'boolean','group'=>'notifications','label'=>'Avisos de cambios de estado','description'=>'Activa o desactiva avisos de resolución, derivación y solicitudes de información.'],
            ['key'=>'notify_on_close','value'=>'1','type'=>'boolean','group'=>'notifications','label'=>'Avisos de cierre','description'=>'Activa o desactiva los avisos de cierre; no impide cerrar el ticket.'],
            ['key'=>'support_email','value'=>'soporte@conecta.cl','type'=>'string','group'=>'notifications','label'=>'Email del equipo de soporte','description'=>'Correo de referencia; no cambia el remitente configurado para los envíos.'],
            ['key'=>'user_response_timeout_hours','value'=>'2','type'=>'integer','group'=>'sla','label'=>'Plazo de respuesta del usuario (horas)','description'=>'Preferencia almacenada; se conserva el plazo actual de respuesta.'],
            ['key'=>'auto_close_enabled','value'=>'1','type'=>'boolean','group'=>'sla','label'=>'Cierre automático por inactividad','description'=>'Preferencia almacenada; no cambia las tareas actuales de cierre.'],
        ];
    }
}
