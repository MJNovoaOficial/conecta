<?php

namespace Database\Seeders;

use App\Models\Subcategoria;
use App\Models\TipoIncidente;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tickets y tablas relacionadas
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('comentarios_ticket')->truncate();
        DB::table('asignaciones_ticket')->truncate();
        DB::table('adjuntos_ticket')->truncate();
        DB::table('historial_ticket')->truncate();
        DB::table('notificaciones')->truncate();
        DB::table('tickets')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Cargar usuarios
        $admin     = User::where('role', 'admin')->first();
        $soportes  = User::where('role', 'support')->get();
        $soporte0  = $soportes->get(0);
        $soporte1  = $soportes->get(1);
        $soporte2  = $soportes->get(2);
        $usuarios  = User::where('role', 'user')->get();

        // Catálogo
        $subcategorias = Subcategoria::all()->keyBy('name');
        $tiposMap      = TipoIncidente::all()->keyBy('name');

        $getIds = function (string $subName, ?string $tipoName = null) use ($subcategorias, $tiposMap): array {
            return [
                'subcategoria_id'   => $subcategorias->get($subName)?->id,
                'tipo_incidente_id' => $tipoName ? $tiposMap->get($tipoName)?->id : null,
            ];
        };

        $now = Carbon::now();

        $tickets = [
            // ── ABIERTOS ────────────────────────────────────────────────────
            [
                'user'        => $usuarios[0],
                'title'       => 'No puedo iniciar sesión en el portal corporativo',
                'description' => 'Desde esta mañana no puedo acceder al sistema ERP. Aparece el mensaje "Credenciales inválidas" aunque mi contraseña es correcta. Ya intenté restablecerla sin éxito.',
                'status'      => 'open',
                'priority'    => 'high',
                'assigned'    => $soporte0,
                'sub'         => 'Sistemas Internos',
                'tipo'        => 'Sin acceso al sistema',
                'days_ago'    => 0,
            ],
            [
                'user'        => $usuarios[2],
                'title'       => 'Impresora de red no imprime desde ayer',
                'description' => 'La impresora HP LaserJet M404n del piso 3 no responde. Los trabajos de impresión quedan en cola pero nunca se ejecutan. Ya la reinicié dos veces.',
                'status'      => 'open',
                'priority'    => 'medium',
                'assigned'    => $soporte2,
                'sub'         => 'Impresoras',
                'tipo'        => 'No imprime',
                'days_ago'    => 1,
            ],
            [
                'user'        => $usuarios[4],
                'title'       => 'Pantalla del laptop con líneas verticales',
                'description' => 'Aparecieron líneas verticales de color verde en la pantalla de mi laptop Dell Latitude 5420. El equipo tiene 2 años y nunca tuvo golpes.',
                'status'      => 'open',
                'priority'    => 'medium',
                'assigned'    => null,
                'sub'         => 'Monitores',
                'tipo'        => 'Líneas en pantalla',
                'days_ago'    => 2,
            ],
            [
                'user'        => $usuarios[6],
                'title'       => 'VPN no conecta desde trabajo remoto',
                'description' => 'Estoy trabajando desde casa y la VPN GlobalProtect no se conecta. El error es "No se puede contactar al servidor" pero la empresa confirmó que el servidor está activo.',
                'status'      => 'open',
                'priority'    => 'high',
                'assigned'    => $soporte0,
                'sub'         => 'Conectividad',
                'tipo'        => 'VPN no conecta',
                'days_ago'    => 0,
            ],
            [
                'user'        => $usuarios[1],
                'title'       => 'Solicitud de nueva cuenta de correo para empleado',
                'description' => 'Se requiere crear una cuenta de correo corporativo para el nuevo empleado José Ramírez, área de Finanzas, que inicia el lunes próximo.',
                'status'      => 'open',
                'priority'    => 'low',
                'assigned'    => $soporte1,
                'sub'         => 'Directorio Activo',
                'tipo'        => 'Crear usuario',
                'days_ago'    => 3,
            ],

            // ── EN PROCESO ──────────────────────────────────────────────────
            [
                'user'        => $usuarios[3],
                'title'       => 'Error al generar facturas en sistema DTE',
                'description' => 'Al intentar emitir facturas electrónicas el sistema DTE retorna el error código 600. Ya verificamos los folios disponibles y están vigentes. El problema es crítico ya que no podemos facturar.',
                'status'      => 'in_progress',
                'priority'    => 'critical',
                'assigned'    => $soporte1,
                'sub'         => 'DTE',
                'tipo'        => 'Error facturación',
                'days_ago'    => 4,
            ],
            [
                'user'        => $usuarios[5],
                'title'       => 'PC de escritorio se apaga solo de forma aleatoria',
                'description' => 'Mi computador HP ProDesk 400 G7 se apaga aleatoriamente, entre 2 y 4 veces al día. Sospechan que puede ser temperatura o la fuente de poder.',
                'status'      => 'in_progress',
                'priority'    => 'high',
                'assigned'    => $soporte2,
                'sub'         => 'Computadores',
                'tipo'        => 'Lentitud',
                'days_ago'    => 5,
            ],
            [
                'user'        => $usuarios[0],
                'title'       => 'Microsoft Teams no reproduce audio en reuniones',
                'description' => 'Desde la última actualización de Teams no escucho el audio de las reuniones. Mis auriculares Jabra funcionan correctamente en otras aplicaciones. Ya reinstalé Teams sin éxito.',
                'status'      => 'in_progress',
                'priority'    => 'medium',
                'assigned'    => $soporte0,
                'sub'         => 'Ofimática',
                'tipo'        => 'Error al abrir archivos',
                'days_ago'    => 3,
            ],
            [
                'user'        => $usuarios[7],
                'title'       => 'Sin acceso a carpetas compartidas del servidor',
                'description' => 'No puedo acceder a las carpetas del servidor \\\\SRV-FILES\\Proyectos. Dice "No tienes permisos" aunque antes sí podía. Cambió luego de las vacaciones.',
                'status'      => 'in_progress',
                'priority'    => 'medium',
                'assigned'    => $soporte0,
                'sub'         => 'Carpetas Compartidas',
                'tipo'        => 'Sin permisos',
                'days_ago'    => 6,
            ],

            // ── PENDIENTE USUARIO ────────────────────────────────────────────
            [
                'user'        => $usuarios[2],
                'title'       => 'Antivirus bloqueando aplicación de contabilidad',
                'description' => 'El antivirus Sophos está bloqueando la aplicación ContaLink 7.2. Necesito saber si es seguro agregar una excepción o debo actualizar la aplicación.',
                'status'      => 'pending_user',
                'priority'    => 'medium',
                'assigned'    => $soporte1,
                'sub'         => 'Antivirus',
                'tipo'        => 'Falso positivo',
                'days_ago'    => 7,
            ],
            [
                'user'        => $usuarios[4],
                'title'       => 'Teclado inalámbrico con letras que no responden',
                'description' => 'El teclado Logitech K380 tiene las teclas A, S y D que no funcionan. Ya se le cambiaron las pilas.',
                'status'      => 'pending_user',
                'priority'    => 'low',
                'assigned'    => $soporte2,
                'sub'         => 'Periféricos',
                'tipo'        => 'USB no reconocido',
                'days_ago'    => 8,
            ],

            // ── RESUELTOS ───────────────────────────────────────────────────
            [
                'user'        => $usuarios[1],
                'title'       => 'Instalación de Adobe Acrobat Pro en equipo nuevo',
                'description' => 'Requiero instalar Adobe Acrobat Pro DC en mi laptop nueva. Tenemos licencia corporativa por volumen. El equipo es un ThinkPad E15.',
                'status'      => 'resolved',
                'priority'    => 'low',
                'assigned'    => $soporte1,
                'sub'         => 'Ofimática',
                'tipo'        => 'Instalación de Office',
                'days_ago'    => 12,
                'resolved_days_ago' => 9,
            ],
            [
                'user'        => $usuarios[6],
                'title'       => 'Contraseña de Active Directory bloqueada',
                'description' => 'Se me bloqueó la cuenta luego de varios intentos incorrectos de contraseña. Necesito desbloqueo urgente.',
                'status'      => 'resolved',
                'priority'    => 'high',
                'assigned'    => $soporte0,
                'sub'         => 'Directorio Activo',
                'tipo'        => 'Contraseña bloqueada',
                'days_ago'    => 14,
                'resolved_days_ago' => 13,
            ],
            [
                'user'        => $usuarios[3],
                'title'       => 'Monitor secundario no es detectado',
                'description' => 'Conecté un segundo monitor Dell P2422H mediante cable DisplayPort pero el sistema no lo detecta. Probé con otro cable sin éxito.',
                'status'      => 'resolved',
                'priority'    => 'medium',
                'assigned'    => $soporte2,
                'sub'         => 'Monitores',
                'tipo'        => 'No enciende',
                'days_ago'    => 15,
                'resolved_days_ago' => 12,
            ],

            // ── CERRADOS ────────────────────────────────────────────────────
            [
                'user'        => $usuarios[5],
                'title'       => 'Actualización de Windows 11 falla con código 0x80242016',
                'description' => 'Windows Update no puede completar la actualización a la versión 23H2. Falla con error 0x80242016 después de reiniciar.',
                'status'      => 'closed',
                'priority'    => 'medium',
                'assigned'    => $soporte1,
                'sub'         => 'Ofimática',
                'tipo'        => 'Actualización de software',
                'days_ago'    => 30,
                'resolved_days_ago' => 28,
                'closed_days_ago'   => 25,
            ],
            [
                'user'        => $usuarios[7],
                'title'       => 'Webcam no funciona en videollamadas de Zoom',
                'description' => 'La cámara Logitech C920 no es reconocida en Zoom. Sí aparece en el Administrador de dispositivos. Ya actualicé los drivers.',
                'status'      => 'closed',
                'priority'    => 'low',
                'assigned'    => $soporte0,
                'sub'         => 'Periféricos',
                'tipo'        => 'Cámara web',
                'days_ago'    => 28,
                'resolved_days_ago' => 26,
                'closed_days_ago'   => 24,
            ],
            [
                'user'        => $usuarios[0],
                'title'       => 'Error de sincronización en correo Outlook',
                'description' => 'El correo Outlook muestra error 0x8004011D y no descarga los correos nuevos desde hace 2 días.',
                'status'      => 'closed',
                'priority'    => 'high',
                'assigned'    => $soporte2,
                'sub'         => 'Correo Electrónico',
                'tipo'        => 'Problemas de sincronización',
                'days_ago'    => 45,
                'resolved_days_ago' => 43,
                'closed_days_ago'   => 40,
            ],
            [
                'user'        => $usuarios[2],
                'title'       => 'Sin tóner en impresora del área de Finanzas',
                'description' => 'La impresora HP Color LaserJet Pro del área de Finanzas indica "Tóner agotado". Necesitamos repuesto urgente para el cierre de mes.',
                'status'      => 'closed',
                'priority'    => 'high',
                'assigned'    => $soporte1,
                'sub'         => 'Impresoras',
                'tipo'        => 'Sin tóner',
                'days_ago'    => 20,
                'resolved_days_ago' => 19,
                'closed_days_ago'   => 18,
            ],
        ];

        $counter = 1;
        foreach ($tickets as $t) {
            $createdAt  = $now->copy()->subDays($t['days_ago'])->subHours(rand(1, 8));
            $resolvedAt = isset($t['resolved_days_ago'])
                ? $now->copy()->subDays($t['resolved_days_ago'])->subHours(rand(1, 4))
                : null;
            $closedAt = isset($t['closed_days_ago'])
                ? $now->copy()->subDays($t['closed_days_ago'])
                : null;

            $ids     = $getIds($t['sub'], $t['tipo'] ?? null);
            $tickNum = 'TKT-' . $createdAt->format('Y') . str_pad($counter, 4, '0', STR_PAD_LEFT);

            Ticket::create([
                'ticket_number'              => $tickNum,
                'user_id'                    => $t['user']->id,
                'department_id'              => $t['user']->department_id,
                'title'                      => $t['title'],
                'description'                => $t['description'],
                'status'                     => $t['status'],
                'priority'                   => $t['priority'],
                'subcategoria_id'            => $ids['subcategoria_id'],
                'tipo_incidente_id'          => $ids['tipo_incidente_id'],
                'assigned_to'                => $t['assigned']?->id,
                'resolved_at'                => $resolvedAt,
                'closed_at'                  => $closedAt,
                'sla_response_deadline_at'   => $createdAt->copy()->addHours(4),
                'sla_resolution_deadline_at' => $createdAt->copy()->addHours(24),
                'created_at'                 => $createdAt,
                'updated_at'                 => $resolvedAt ?? $createdAt->copy()->addHours(2),
            ]);
            $counter++;
        }
    }
}
