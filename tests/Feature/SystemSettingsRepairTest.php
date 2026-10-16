<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use App\Support\SystemSettingsCatalog;
use App\Support\TicketNotificationPreferences;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SystemSettingsRepairTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Mail::fake();
    }

    private function snapshot(): array
    {
        return SystemSetting::orderBy('key')->get()->map(fn ($row) => $row->getAttributes())->all();
    }

    private function defaults(): array
    {
        return collect(SystemSettingsCatalog::defaults())->pluck('value', 'key')->all();
    }

    public function test_get_restaura_tres_pestanas_y_solo_seis_opciones_editables_sin_escribir(): void
    {
        $response = $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.settings.index'))->assertOk()->assertSee('Opciones operativas')
            ->assertSee('valores iniciales')->assertSee('Nombre del sistema')
            ->assertSee('Avisos de comentarios')->assertSee('Intentos máximos de login');
        $settings = $response->viewData('settings');
        foreach (['general'=>4, 'attachments'=>3, 'notifications'=>6, 'security'=>3] as $group=>$count) {
            $this->assertCount($count, $settings[$group]);
            foreach ($settings[$group] as $setting) $this->assertFalse($setting->exists);
        }
        $this->assertArrayNotHasKey('sla',$settings);
        $response->assertDontSee('id="tab-sla"',false)->assertDontSee('name="settings[app_name]"',false)->assertDontSee('name="settings[allow_guest_tickets]"',false);
        $this->assertDatabaseCount('system_settings', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_guardar_solo_inicializa_las_seis_opciones_operativas_y_no_parametros_informativos(): void
    {
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'), ['settings'=>$this->defaults()])
            ->assertRedirect(route('admin.settings.index'))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseCount('system_settings', 9);
        $keys=array_merge(['session_timeout_minutes','attachments_global_enabled','max_attachment_size_mb','max_attachments_per_ticket'],array_values(array_unique(TicketNotificationPreferences::EVENT_KEYS)));
        foreach (SystemSettingsCatalog::defaults() as $definition) if(in_array($definition['key'],$keys)) $this->assertDatabaseHas('system_settings', $definition);
        $audit = AuditLog::where('action','settings.updated')->firstOrFail();
        $this->assertCount(9, $audit->details['keys']);
        $this->assertNull($audit->details['changes']['session_timeout_minutes']['old']);
        $this->get(route('admin.settings.index'))->assertOk()->assertDontSee('Las opciones editables aún no guardadas');
    }

    public function test_guardar_solo_un_campo_crea_solo_ese_y_no_rellena_otros_a_escondidas(): void
    {
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'), ['settings'=>['notify_on_comment'=>'0']])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('system_settings', 1);
        $this->assertDatabaseHas('system_settings', ['key'=>'notify_on_comment','value'=>'0','group'=>'notifications']);
    }

    public function test_seeder_completa_faltantes_pero_conserva_valores_metadatos_y_fechas_existentes(): void
    {
        $existing = SystemSetting::create(['key'=>'app_name','value'=>'Marca propia','type'=>'string','group'=>'general','label'=>'Mi etiqueta','description'=>'Descripción propia']);
        $other = SystemSetting::create(['key'=>'custom_flag','value'=>'1','type'=>'boolean','group'=>'states','label'=>'No debe tocarse']);
        $beforeExisting = $existing->fresh()->getAttributes();
        $beforeOther = $other->fresh()->getAttributes();
        $this->seed(SystemSettingsSeeder::class);
        $this->assertDatabaseCount('system_settings', 19);
        $this->assertSame($beforeExisting, $existing->fresh()->getAttributes());
        $this->assertSame($beforeOther, $other->fresh()->getAttributes());
        $before = $this->snapshot();
        $this->travel(5)->minutes();
        $this->seed(SystemSettingsSeeder::class);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_ver_y_guardar_valores_actuales_no_sobrescribe_personalizaciones(): void
    {
        SystemSetting::create(['key'=>'app_name','value'=>'Marca propia','type'=>'string','group'=>'general','label'=>'Nombre personalizado']);
        SystemSetting::create(['key'=>'notify_on_comment','value'=>'0','type'=>'boolean','group'=>'notifications']);
        $before = $this->snapshot();
        $response = $this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))->assertOk()->assertSee(config('app.name'))->assertSee('Nombre personalizado');
        $this->assertSame($before, $this->snapshot());
        $values = collect($response->viewData('settings'))->flatMap(fn ($group) => $group)->pluck('value','key')->all();
        $this->post(route('admin.settings.update'), ['settings'=>$values])->assertSessionHasNoErrors();
        foreach ($before as $row) $this->assertSame($row, SystemSetting::where('key',$row['key'])->firstOrFail()->getAttributes());
        $this->assertSame('0', SystemSetting::where('key','notify_on_comment')->value('value'));
    }

    public function test_metadatos_de_presentacion_faltantes_se_completan_sin_modificar_fila_existente(): void
    {
        $existing = SystemSetting::create(['key'=>'app_name','value'=>'Nombre actual','type'=>'string','group'=>'general','label'=>null,'description'=>null]);
        $before = $existing->fresh()->getAttributes();
        $this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))->assertOk()->assertSee('Nombre del sistema')->assertSee(config('app.name'));
        $this->assertSame($before, $existing->fresh()->getAttributes());
    }

    public function test_booleanos_omitidos_y_grupos_ocultos_no_se_desactivan(): void
    {
        $this->seed(SystemSettingsSeeder::class);
        $hidden = SystemSetting::create(['key'=>'custom_flag','value'=>'1','type'=>'boolean','group'=>'states']);
        $before = $this->snapshot();
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'), ['settings'=>['app_name'=>'Otro nombre','custom_flag'=>'0','state_label_open'=>'Alterado']])->assertSessionHasNoErrors();
        foreach ($before as $row) {
            $this->assertSame($row, SystemSetting::where('key',$row['key'])->firstOrFail()->getAttributes());
        }
        $this->assertSame('1', $hidden->fresh()->value);
        $this->assertDatabaseMissing('system_settings', ['key'=>'state_label_open']);
    }

    public function test_checkbox_envia_cero_explicito_y_solo_cambia_el_parametro_seleccionado(): void
    {
        $this->seed(SystemSettingsSeeder::class);
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'), ['settings'=>['notify_on_comment'=>'0']])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('system_settings', ['key'=>'notify_on_comment','value'=>'0']);
        $this->assertDatabaseHas('system_settings', ['key'=>'notify_on_close','value'=>'1']);
        $this->assertDatabaseHas('system_settings', ['key'=>'auto_close_enabled','value'=>'1']);
        $audit = AuditLog::where('action','settings.updated')->firstOrFail();
        $this->assertSame(['notify_on_comment'], $audit->details['keys']);
        $doc = new \DOMDocument();
        @$doc->loadHTML($this->get(route('admin.settings.index'))->assertOk()->getContent());
        $xpath = new \DOMXPath($doc);
        foreach (array_values(array_unique(TicketNotificationPreferences::EVENT_KEYS)) as $key) {
            $this->assertSame(1, $xpath->query('//input[@type="hidden" and @name="settings['.$key.']" and @value="0"]')->length);
            $this->assertSame(1, $xpath->query('//input[@type="checkbox" and @name="settings['.$key.']" and @value="1"]')->length);
        }
    }

    public function test_payload_invalido_no_escribe_ni_parcialmente(): void
    {
        $this->actingAs(User::factory()->administrador()->create());
        foreach ([
            ['settings'=>['notify_on_close'=>'0','session_timeout_minutes'=>'abc']],
            ['settings'=>['session_timeout_minutes'=>['dato'],'notify_on_comment'=>'0']],
            ['settings'=>['notify_on_comment'=>'2']],
            ['settings'=>['session_timeout_minutes'=>'0']],
            ['settings'=>['session_timeout_minutes'=>'1441']],
            ['settings'=>'texto'],
        ] as $payload) {
            $this->post(route('admin.settings.update'), $payload)->assertSessionHasErrors();
            $this->assertDatabaseCount('system_settings', 0);
            $this->assertDatabaseCount('audit_logs', 0);
        }
    }

    public function test_sin_parametros_enviados_no_inserta_nada_y_repetir_guardado_no_duplica_auditoria(): void
    {
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'), [])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('system_settings', 0);
        $this->post(route('admin.settings.update'), ['settings'=>$this->defaults()])->assertSessionHasNoErrors();
        $before = $this->snapshot();
        $this->travel(1)->minutes();
        $this->post(route('admin.settings.update'), ['settings'=>$this->defaults()])->assertSessionHasNoErrors();
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(1, AuditLog::where('action','settings.updated')->count());
    }

    public function test_booleanos_textuales_no_se_muestran_activos_ni_se_reescriben_sin_cambio(): void
    {
        foreach (['allow_guest_tickets'=>'general','notify_on_comment'=>'notifications','auto_close_enabled'=>'sla'] as $key=>$group) {
            SystemSetting::create(['key'=>$key,'value'=>'false','type'=>'boolean','group'=>$group]);
        }
        $before = $this->snapshot();
        $response = $this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))->assertOk();
        $doc = new \DOMDocument(); @$doc->loadHTML($response->getContent());
        $xpath = new \DOMXPath($doc);
        foreach (['allow_guest_tickets','notify_on_comment','auto_close_enabled'] as $key) {
            $this->assertSame(0,$xpath->query('//input[@type="checkbox" and @name="settings['.$key.']" and @checked]')->length);
        }
        $this->post(route('admin.settings.update'), ['settings'=>['allow_guest_tickets'=>'0','notify_on_comment'=>'0','auto_close_enabled'=>'0']])->assertSessionHasNoErrors();
        $this->assertSame($before,$this->snapshot());
        $this->assertDatabaseCount('audit_logs',0);
    }

    public function test_otro_administrador_que_inicializa_primero_no_es_sobrescrito(): void
    {
        $injected = false;
        DB::connection()->beforeExecuting(function ($query,$bindings,$connection) use (&$injected) {
            // Inyectar antes de la consulta de firstOrCreate, fuera de su
            // savepoint de inserción. Simula una fila ya creada por otra petición.
            if (!$injected && str_starts_with(strtolower($query), 'select')
                && str_contains($query, '`system_settings`') && str_contains($query, 'where')
                && in_array('notify_on_comment', $bindings, true)) {
                $injected = true;
                $connection->table('system_settings')->insert(['key'=>'notify_on_comment','value'=>'1','type'=>'boolean','group'=>'notifications','label'=>'Aviso ganador','created_at'=>now(),'updated_at'=>now()]);
            }
        });
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'), ['settings'=>['notify_on_comment'=>'0']])->assertSessionHasNoErrors();
        $this->assertTrue($injected);
        $this->assertDatabaseHas('system_settings', ['key'=>'notify_on_comment','value'=>'1','label'=>'Aviso ganador']);
        $this->assertDatabaseCount('system_settings', 1);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_solo_administrador_puede_ver_y_guardar(): void
    {
        foreach (['user','support'] as $role) {
            $this->actingAs(User::factory()->create(['role'=>$role]))->get(route('admin.settings.index'))->assertForbidden();
            $this->post(route('admin.settings.update'), ['settings'=>['app_name'=>'No permitido']])->assertForbidden();
        }
        $this->assertDatabaseCount('system_settings', 0);
    }

    public function test_inicializar_no_toca_tickets_usuarios_historial_sla_o_cache_de_login(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create(['ticket_number'=>'SETTINGS-KEEP','user_id'=>$user->id,'title'=>'Ticket real de prueba','description'=>'Conservar descripción','status'=>'closed','priority'=>'medium','solution_text'=>'Solución ya registrada','closed_at'=>now()]);
        TicketHistory::create(['ticket_id'=>$ticket->id,'user_id'=>$user->id,'action'=>'closed']);
        $beforeTicket = $ticket->fresh()->getAttributes();
        $beforeUser = $user->fresh()->getAttributes();
        $history = TicketHistory::firstOrFail()->getAttributes();
        Cache::put('login:account:existing-test', ['failures'=>4], 300);
        Cache::put('setting_app_name','Fallback antiguo',3600);
        $this->seed(SystemSettingsSeeder::class);
        $this->assertSame($beforeTicket, $ticket->fresh()->getAttributes());
        $this->assertSame($beforeUser, $user->fresh()->getAttributes());
        $this->assertSame($history, TicketHistory::firstOrFail()->getAttributes());
        $this->assertDatabaseCount('sla_configs',0);
        $this->assertSame(['failures'=>4],Cache::get('login:account:existing-test'));
        $this->assertNull(Cache::get('setting_app_name'));
    }

    public function test_valores_guardados_no_alteran_login_o_el_acceso_de_invitados_en_esta_etapa(): void
    {
        config(['auth.login_throttle_enabled' => true]);
        $owner = User::factory()->create();
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'), ['settings'=>['allow_guest_tickets'=>'0','max_login_attempts'=>'1','login_lockout_minutes'=>'30']])->assertSessionHasNoErrors();
        Auth::logout();
        $this->get(route('tickets.guest.create'))->assertOk();
        RateLimiter::clear('guest_ticket:127.0.0.1');
        $this->post(route('tickets.guest.store'), ['guest_name'=>'Invitado','guest_email'=>'guest@example.test','title'=>'Crear sin cuenta','description'=>'No bloquear el flujo existente.'])->assertRedirect();
        $this->assertDatabaseHas('tickets', ['guest_email'=>'guest@example.test','status'=>'open']);
        $key = 'login:account:'.hash('sha256',strtolower(trim($owner->email)));
        for ($i=1; $i<=5; $i++) {
            Auth::logout();
            $this->post('/login',['email'=>$owner->email,'password'=>'incorrect-password'])->assertSessionHasErrors('email');
            $state = Cache::get($key);
            $this->assertSame($i,$state['failures']);
            if ($i<5) $this->assertArrayNotHasKey('blocked_until',$state);
            else $this->assertEqualsWithDelta(now()->timestamp+90,$state['blocked_until'],2);
        }
    }

    public function test_textos_personalizados_se_escapan_al_mostrarlos(): void
    {
        $payload = '<img src=x onerror="window.settingsTest=1">';
        SystemSetting::create(['key'=>'app_name','value'=>$payload,'label'=>$payload,'group'=>'general','type'=>'string']);
        $this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))->assertOk()->assertSee($payload)->assertDontSee($payload,false);
    }
}
