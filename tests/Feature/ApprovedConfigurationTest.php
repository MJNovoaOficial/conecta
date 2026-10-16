<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use App\Models\Notificacion;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Jobs\CloseResolvedTicketsJob;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Notifications\SlaWarningNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketUpdatedNotification;
use App\Support\TicketNotificationPreferences;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApprovedConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private function setting(string $key, string $value, string $type='boolean', string $group='notifications'): void
    {
        SystemSetting::updateOrCreate(['key'=>$key],['value'=>$value,'type'=>$type,'group'=>$group]);
        SystemSetting::forgetCached($key);
    }

    private function ticket(): Ticket
    {
        return Ticket::create(['ticket_number'=>'CFG-'.fake()->unique()->numerify('########'),'title'=>'Consulta de configuración','description'=>'Prueba aislada.','status'=>'in_progress','priority'=>'medium']);
    }

    public function test_opciones_generales_y_login_son_informativas_sla_se_conserva_en_sidebar(): void
    {
        config(['auth.login_throttle_enabled' => true]);
        $response=$this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))->assertOk();
        foreach (['app_name','app_timezone','tickets_per_page','allow_guest_tickets','max_login_attempts','login_lockout_minutes','auto_close_enabled','user_response_timeout_hours'] as $key) {
            $response->assertDontSee('name="settings['.$key.']"',false);
        }
        $response->assertSee('90 segundos')->assertSee(config('app.timezone'))->assertSee('15 tickets')
            ->assertSee('href="'.route('admin.sla.index').'"',false)->assertDontSee('id="tab-sla"',false);
        $this->assertDatabaseCount('system_settings',0);
    }

    public function test_no_se_puede_cambiar_info_sla_login_invitados_o_adjuntos_por_post_directo(): void
    {
        $this->setting('app_name','Valor anterior','string','general');
        $this->setting('auto_close_enabled','1','boolean','sla');
        $before=SystemSetting::orderBy('key')->get()->map(fn($s)=>$s->getAttributes())->all();
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'),['settings'=>[
            'app_name'=>'Nombre nuevo','app_timezone'=>'Pacific/Honolulu','allow_guest_tickets'=>'0',
            'tickets_per_page'=>'100','max_login_attempts'=>'1',
            'login_lockout_minutes'=>'30','auto_close_enabled'=>'0',
        ]])->assertSessionHasNoErrors();
        $this->assertSame($before,SystemSetting::orderBy('key')->get()->map(fn($s)=>$s->getAttributes())->all());
        $this->assertSame(5,AuthController::LOGIN_MAX_ATTEMPTS);
        $this->assertSame(90,AuthController::LOGIN_LOCKOUT_SECONDS);
    }

    public function test_default_ausente_no_se_comparte_entre_consumidores_ni_acepta_cache_vieja(): void
    {
        Cache::put('setting_missing_key',false,3600);
        $this->assertSame(120,SystemSetting::get('missing_key',120));
        $this->assertNull(SystemSetting::get('missing_key'));
        $this->assertTrue(SystemSetting::get('missing_key',true));
        $this->assertDatabaseCount('system_settings',0);
        $this->setting('missing_key','0');
        $this->assertFalse(SystemSetting::get('missing_key',true));
    }

    public function test_sin_sesion_guardada_se_conserva_lifetime_del_servidor(): void
    {
        config(['session.lifetime'=>37]);
        $this->actingAs(User::factory()->create())->get(route('profile.index'))->assertOk();
        $this->assertSame(37,config('session.lifetime'));
        $this->assertDatabaseCount('system_settings',0);
    }

    public function test_sesion_editable_se_aplica_a_lifetime_y_expira_exactamente_por_inactividad(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user=User::factory()->create();
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'),['settings'=>['session_timeout_minutes'=>'2']])->assertSessionHasNoErrors();
        $this->assertSame(2,SystemSetting::get('session_timeout_minutes'));
        $this->actingAs($user)->get(route('profile.index'))->assertOk();
        $this->assertSame(2,config('session.lifetime'));
        $start=now()->copy();
        $this->travel(119)->seconds();
        $this->withSession(['last_activity_at'=>$start])->get(route('notifications.count'))->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->travel(1)->seconds();
        $this->withSession(['last_activity_at'=>$start])->get(route('profile.index'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_polls_de_campana_no_renuevan_inactividad_y_actividad_real_si(): void
    {
        $this->travelTo(now()->startOfSecond());
        $this->setting('session_timeout_minutes','10','integer','security');
        $user=User::factory()->create();
        $start=now()->copy();
        $this->actingAs($user)->withSession(['last_activity_at'=>$start]);
        $this->travel(1)->minutes();
        foreach (['notifications.count','notifications.recent'] as $route) {
            $this->get(route($route))->assertOk();
            $this->assertTrue(session('last_activity_at')->equalTo($start));
        }
        $this->get(route('profile.index'))->assertOk();
        $this->assertTrue(session('last_activity_at')->equalTo(now()));
        $this->travel(9)->minutes();
        $this->get(route('notifications.count'))->assertOk();
        $this->travel(1)->minutes();
        $this->get(route('notifications.count'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_sesion_fuera_de_limites_no_se_guarda_y_no_hay_cambios_parciales(): void
    {
        $this->actingAs(User::factory()->administrador()->create());
        foreach (['0','1441','texto','1.5'] as $value) {
            $this->post(route('admin.settings.update'),['settings'=>['session_timeout_minutes'=>$value,'notify_on_close'=>'0']])->assertSessionHasErrors('settings.session_timeout_minutes');
            $this->assertDatabaseCount('system_settings',0);
        }
    }

    public function test_avisos_por_defecto_permanecen_habilitados_sin_inicializacion(): void
    {
        $user=User::factory()->create(); $ticket=$this->ticket();
        foreach (['new_ticket','assigned','comment','resolved','closed','forwarded','reopened','info_requested'] as $event) {
            $this->assertTrue(TicketNotificationPreferences::eventEnabled($event));
            $this->assertNotNull(Notificacion::notify($user->id,$event,'Prueba','Mensaje',$ticket->id));
            $this->assertSame(['mail'],(new TicketUpdatedNotification($ticket,'Mensaje',$event))->via($user));
        }
        $this->assertDatabaseCount('system_settings',0);
    }

    public function test_desactivar_evento_afecta_correo_y_campana_sin_tocar_otros_eventos(): void
    {
        $user=User::factory()->create(); $ticket=$this->ticket();
        $old=Notificacion::notify($user->id,'comment','Mensaje anterior');
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'),['settings'=>['notify_on_comment'=>'0']])->assertSessionHasNoErrors();
        $this->assertNull(Notificacion::notify($user->id,'comment','No crear'));
        $this->assertSame([],(new TicketUpdatedNotification($ticket,'Mensaje','comment'))->via($user));
        $this->assertNotNull(Notificacion::notify($user->id,'assigned','Asignación visible'));
        $this->assertSame(['mail'],(new TicketUpdatedNotification($ticket,'Mensaje','assigned'))->via($user));
        $this->assertNotNull($old->fresh());
        $this->post(route('admin.settings.update'),['settings'=>['notify_on_comment'=>'1']])->assertSessionHasNoErrors();
        $this->assertSame(['mail'],(new TicketUpdatedNotification($ticket,'Mensaje','comment'))->via($user));
    }

    public function test_mapeo_de_todos_los_eventos_globales_y_sus_clases(): void
    {
        $user=User::factory()->create(); $ticket=$this->ticket();
        foreach (array_values(array_unique(TicketNotificationPreferences::EVENT_KEYS)) as $key) $this->setting($key,'0');
        foreach (array_keys(TicketNotificationPreferences::EVENT_KEYS) as $event) {
            $this->assertFalse(TicketNotificationPreferences::eventEnabled($event));
            $this->assertNull(Notificacion::notify($user->id,$event,'No crear'));
            $this->assertSame([],(new TicketUpdatedNotification($ticket,'Mensaje',$event))->via($user));
        }
        $this->assertSame([],(new NewTicketNotification($ticket))->via($user));
        $this->assertSame([],(new TicketCreatedNotification($ticket))->via($user));
        $this->assertSame(['mail'],(new SlaWarningNotification($ticket,30))->via($user));
    }

    public function test_cada_usuario_puede_desactivar_solo_su_correo_y_no_el_de_otro(): void
    {
        $first=User::factory()->create(['alternate_email'=>'alternate@example.test']);
        $other=User::factory()->create(); $ticket=$this->ticket();
        $beforeFirst=$first->fresh()->getAttributes(); $beforeOther=$other->fresh()->getAttributes();
        $this->actingAs($first)->post(route('profile.notifications'),['ticket_email_enabled'=>'0','user_id'=>$other->id,'role'=>'admin'])->assertSessionHasNoErrors();
        $this->assertFalse($first->receivesTicketEmails());
        $this->assertTrue($other->receivesTicketEmails());
        $this->assertSame([],(new NewTicketNotification($ticket))->via($first));
        $this->assertSame([],(new TicketCreatedNotification($ticket))->via($first));
        $this->assertSame([],(new TicketUpdatedNotification($ticket))->via($first));
        $this->assertSame(['mail'],(new TicketUpdatedNotification($ticket))->via($other));
        $this->assertNotNull(Notificacion::notify($first->id,'comment','Campana se conserva'));
        $this->assertSame($beforeFirst,$first->fresh()->getAttributes());
        $this->assertSame($beforeOther,$other->fresh()->getAttributes());
        $this->assertDatabaseHas('system_settings',['key'=>TicketNotificationPreferences::emailKey($first->id),'group'=>'user_preferences','value'=>'0']);
        $this->assertDatabaseMissing('system_settings',['key'=>TicketNotificationPreferences::emailKey($other->id)]);
        $this->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_preferencia_personal_es_idempotente_y_se_puede_reactivar(): void
    {
        $user=User::factory()->soporte()->create();
        $this->actingAs($user);
        for($i=0;$i<2;$i++)$this->post(route('profile.notifications'),['ticket_email_enabled'=>'0'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('system_settings',1);
        $this->assertDatabaseCount('audit_logs',1);
        $this->post(route('profile.notifications'),['ticket_email_enabled'=>'1'])->assertSessionHasNoErrors();
        $this->assertTrue($user->receivesTicketEmails());
        $this->get(route('profile.index'))->assertOk()->assertSee('Notificaciones de mis tickets');
    }

    public function test_optout_no_suprime_recuperacion_de_clave_ni_correo_alternativo_y_cancelaria_sla_en_cola(): void
    {
        $user=User::factory()->create(['alternate_email'=>'alternate@example.test']); $ticket=$this->ticket();
        $this->actingAs($user)->post(route('profile.notifications'),['ticket_email_enabled'=>'0'])->assertSessionHasNoErrors();
        $reset=new ResetPassword('token-de-prueba');
        $this->assertSame(['mail'],$reset->via($user));
        $this->assertSame([$user->email,'alternate@example.test'],$user->routeNotificationForMail($reset));
        $warning=new SlaWarningNotification($ticket,30);
        $this->assertSame([],$warning->via($user));
        $this->assertFalse($warning->shouldSend($user,'mail'));
    }

    public function test_api_personal_rechaza_payload_invalido_y_no_esta_disponible_sin_login(): void
    {
        $this->post(route('profile.notifications'),['ticket_email_enabled'=>'0'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        foreach ([[],['ticket_email_enabled'=>'yes'],['ticket_email_enabled'=>['0']]] as $payload) {
            $this->post(route('profile.notifications'),$payload)->assertSessionHasErrors('ticket_email_enabled');
        }
        $this->assertDatabaseCount('system_settings',0);
    }

    public function test_preferencias_personales_no_aparecen_ni_se_modifican_en_configuracion_admin(): void
    {
        $user=User::factory()->create();
        $key=TicketNotificationPreferences::emailKey($user->id);
        $this->setting($key,'0','boolean','user_preferences');
        $before=SystemSetting::where('key',$key)->firstOrFail()->getAttributes();
        $this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))->assertOk()->assertDontSee($key);
        $this->post(route('admin.settings.update'),['settings'=>[$key=>'1']])->assertSessionHasNoErrors();
        $this->assertSame($before,SystemSetting::where('key',$key)->firstOrFail()->getAttributes());
    }

    public function test_comentarios_resolucion_y_cierre_funcionan_con_avisos_desactivados(): void
    {
        Notification::fake(); Mail::fake();
        $owner=User::factory()->create(); $agent=User::factory()->soporte()->create();
        $ticket=$this->ticket(); $ticket->update(['user_id'=>$owner->id,'assigned_to'=>$agent->id]);
        foreach (array_values(array_unique(TicketNotificationPreferences::EVENT_KEYS)) as $key) $this->setting($key,'0');
        $this->actingAs($agent)->post(route('tickets.addComment',$ticket),['comment'=>'El soporte puede responder aunque no se envíen avisos.'])->assertSessionHasNoErrors();
        $this->assertSame(1,$ticket->comments()->count());
        $this->post(route('tickets.resolve',$ticket),['solution_text'=>'Se registró una solución y se mantiene el proceso.'])->assertSessionHas('success');
        $this->assertSame('resolved',$ticket->fresh()->status);
        $this->travel(1)->hours(); (new CloseResolvedTicketsJob)->handle();
        $this->assertSame('closed',$ticket->fresh()->status);
        $this->assertDatabaseCount('notificaciones',0);
        Notification::assertNothingSent();
    }

    public function test_optout_del_solicitante_suprime_correo_de_respuesta_pero_conserva_campana(): void
    {
        Notification::fake();
        $owner=User::factory()->create(); $agent=User::factory()->soporte()->create();
        $ticket=$this->ticket(); $ticket->update(['user_id'=>$owner->id,'assigned_to'=>$agent->id]);
        $this->actingAs($owner)->post(route('profile.notifications'),['ticket_email_enabled'=>'0'])->assertSessionHasNoErrors();
        $this->actingAs($agent)->post(route('tickets.addComment',$ticket),['comment'=>'Respuesta que queda visible en el caso.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notificaciones',['user_id'=>$owner->id,'type'=>'comment','ticket_id'=>$ticket->id]);
        $this->assertSame(1,$ticket->comments()->count());
        Notification::assertNothingSent();
    }

    public function test_lifetime_configurable_tambien_se_aplica_a_cookie_con_driver_database(): void
    {
        $this->travelTo(now()->startOfSecond());
        $this->setting('session_timeout_minutes','6','integer','security');
        config(['session.driver'=>'database','session.lifetime'=>120]);
        app('session')->forgetDrivers(); app()->forgetInstance('session.store');
        $response=$this->actingAs(User::factory()->create())->get(route('profile.index'))->assertOk();
        $this->assertSame(6,config('session.lifetime'));
        $cookies=collect($response->headers->getCookies())->keyBy(fn($cookie)=>$cookie->getName());
        $this->assertNotNull($cookies->get(config('session.cookie')));
        $this->assertEqualsWithDelta(now()->timestamp+360,$cookies->get(config('session.cookie'))->getExpiresTime(),2);
    }

    public function test_metadatos_legacy_se_muestran_bien_y_solo_se_corrigen_en_opciones_enviadas(): void
    {
        SystemSetting::create(['key'=>'notify_on_comment','value'=>'0','type'=>'string','group'=>'general','label'=>'Etiqueta propia']);
        SystemSetting::create(['key'=>'session_timeout_minutes','value'=>'7','type'=>'string','group'=>'general']);
        $before=SystemSetting::orderBy('key')->get()->map(fn($s)=>$s->getAttributes())->all();
        $this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))->assertOk()
            ->assertSee('name="settings[notify_on_comment]"',false)->assertSee('name="settings[session_timeout_minutes]"',false)->assertSee('Etiqueta propia');
        $this->assertSame($before,SystemSetting::orderBy('key')->get()->map(fn($s)=>$s->getAttributes())->all());
        $this->post(route('admin.settings.update'),['settings'=>['notify_on_comment'=>'0']])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('system_settings',['key'=>'notify_on_comment','value'=>'0','type'=>'boolean','group'=>'notifications','label'=>'Etiqueta propia']);
        $this->assertDatabaseHas('system_settings',['key'=>'session_timeout_minutes','type'=>'string','group'=>'general','value'=>'7']);
        $this->post(route('admin.settings.update'),['settings'=>['session_timeout_minutes'=>'9']])->assertSessionHasNoErrors();
        $this->assertSame(9,SystemSetting::get('session_timeout_minutes'));
    }
}
