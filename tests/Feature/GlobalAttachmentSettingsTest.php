<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Support\TicketAttachmentLimits;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalAttachmentSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp(); Notification::fake(); Mail::fake(); Storage::fake('local');
        RateLimiter::clear('guest_ticket:127.0.0.1'); RateLimiter::clear('guest_comment:127.0.0.1');
    }

    private function apply(array $data): void
    {
        $this->actingAs(User::factory()->administrador()->create())->post(route('admin.settings.update'),['settings'=>$data])->assertSessionHasNoErrors();
        Auth::logout();
    }

    private function activate(int $files=2,int $size=1): void
    {
        $this->apply(['attachments_global_enabled'=>'1','max_attachment_size_mb'=>(string)$size,'max_attachments_per_ticket'=>(string)$files]);
    }

    private function pdf(int $size=10,string $name='prueba.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name,$size,'application/pdf');
    }

    private function ticket(?User $owner): Ticket
    {
        return Ticket::create(['ticket_number'=>'ATT-'.fake()->unique()->numerify('##########'),'user_id'=>$owner?->id,
            'assigned_to'=>User::factory()->soporte()->create()->id,'title'=>'Consulta con archivos','description'=>'Conservar los datos.',
            'status'=>'in_progress','priority'=>'medium','guest_name'=>$owner?null:'Invitado','guest_email'=>$owner?null:'guest@example.test',
            'guest_token'=>$owner?null:fake()->unique()->regexify('[a-z]{40}')]);
    }

    public function test_instalar_o_guardar_otras_opciones_no_cambia_los_limites_originales(): void
    {
        $this->assertSame(['files'=>5,'kilobytes'=>51200],TicketAttachmentLimits::forRequest());
        $this->assertSame(['files'=>3,'kilobytes'=>5120],TicketAttachmentLimits::forRequest(true));
        $this->seed(SystemSettingsSeeder::class);
        $this->apply(['session_timeout_minutes'=>'120','notify_on_comment'=>'1']);
        $this->assertFalse(TicketAttachmentLimits::globallyEnabled());
        $this->assertSame(['files'=>5,'kilobytes'=>51200],TicketAttachmentLimits::forRequest());
        $this->assertSame(['files'=>3,'kilobytes'=>5120],TicketAttachmentLimits::forRequest(true));
    }

    public function test_activar_aplica_un_solo_limite_a_ambos_y_desactivar_recupera_los_originales(): void
    {
        $this->activate(2,1);
        foreach ([false,true] as $comment)$this->assertSame(['files'=>2,'kilobytes'=>1024],TicketAttachmentLimits::forRequest($comment));
        $this->apply(['attachments_global_enabled'=>'0']);
        $this->assertSame(['files'=>5,'kilobytes'=>51200],TicketAttachmentLimits::forRequest());
        $this->assertSame(['files'=>3,'kilobytes'=>5120],TicketAttachmentLimits::forRequest(true));
        $this->assertDatabaseHas('system_settings',['key'=>'max_attachment_size_mb','value'=>'1']);
    }

    public function test_activacion_incompleta_o_fuera_de_topes_no_guarda_ni_parcialmente(): void
    {
        $this->actingAs(User::factory()->administrador()->create());
        foreach ([
            ['attachments_global_enabled'=>'1'],
            ['attachments_global_enabled'=>'1','max_attachment_size_mb'=>'1'],
            ['attachments_global_enabled'=>'1','max_attachment_size_mb'=>'51','max_attachments_per_ticket'=>'2'],
            ['attachments_global_enabled'=>'1','max_attachment_size_mb'=>'1','max_attachments_per_ticket'=>'6'],
            ['attachments_global_enabled'=>'1','max_attachment_size_mb'=>'0','max_attachments_per_ticket'=>'1'],
            ['attachments_global_enabled'=>'1','max_attachment_size_mb'=>'1.5','max_attachments_per_ticket'=>'1'],
        ] as $payload){
            $this->post(route('admin.settings.update'),['settings'=>$payload])->assertSessionHasErrors();
            $this->assertDatabaseCount('system_settings',0); $this->assertDatabaseCount('audit_logs',0);
        }
    }

    public function test_creacion_registrada_y_de_invitado_rechaza_cantidad_y_tamano_excedidos(): void
    {
        $this->activate();
        $owner=User::factory()->create();
        foreach ([false,true] as $guest){
            if($guest)Auth::logout(); else $this->actingAs($owner);
            $route=$guest?'tickets.guest.store':'tickets.store';
            $base=['title'=>'Solicitud con adjuntos','description'=>'Detalles.','guest_name'=>'Invitado','guest_email'=>'guest@example.test'];
            $this->post(route($route),$base+['attachments'=>[$this->pdf(10,'uno.pdf'),$this->pdf(10,'dos.pdf'),$this->pdf(10,'tres.pdf')]])->assertSessionHasErrors('attachments');
            $this->post(route($route),$base+['attachments'=>[$this->pdf(1025)]])->assertSessionHasErrors('attachments.0');
            $this->assertDatabaseCount('tickets',0); $this->assertSame(0,TicketAttachment::count());
        }
    }

    public function test_respuestas_registradas_y_de_invitados_rechazan_excesos_y_admiten_el_limite_exacto(): void
    {
        $this->activate();
        foreach ([false,true] as $guest){
            $owner=$guest?null:User::factory()->create(); $ticket=$this->ticket($owner);
            if($guest)Auth::logout();else $this->actingAs($owner);
            $url=$guest?route('tickets.guest.comment',$ticket->guest_token):route('tickets.addComment',$ticket);
            $this->post($url,['comment'=>'Respuesta con tres archivos.','attachments'=>[$this->pdf(10,'uno.pdf'),$this->pdf(10,'dos.pdf'),$this->pdf(10,'tres.pdf')]])->assertSessionHasErrors('attachments');
            $this->post($url,['comment'=>'Respuesta con archivo grande.','attachments'=>[$this->pdf(1025)]])->assertSessionHasErrors('attachments.0');
            $this->assertSame(0,$ticket->comments()->count());
            $this->post($url,['comment'=>'Respuesta válida con el tamaño exacto.','attachments'=>[$this->pdf(1024,'uno.pdf'),$this->pdf(1024,'dos.pdf')]])->assertSessionHasNoErrors();
            $this->assertSame(1,$ticket->comments()->count()); $this->assertSame(2,$ticket->attachments()->count());
        }
    }

    public function test_creacion_registrada_y_guest_admiten_archivos_dentro_del_limite_y_conservan_tokens(): void
    {
        $this->activate();
        $this->actingAs(User::factory()->create())->post(route('tickets.store'),['title'=>'Registrado válido','attachments'=>[$this->pdf(1024,'uno.pdf'),$this->pdf(1024,'dos.pdf')]])->assertSessionHasNoErrors();
        Auth::logout();
        $this->post(route('tickets.guest.store'),['title'=>'Invitado válido','guest_name'=>'Invitado','guest_email'=>'guest@example.test','attachments'=>[$this->pdf(1024)]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('tickets',2); $this->assertSame(3,TicketAttachment::count());
        $guest=Ticket::whereNull('user_id')->firstOrFail(); $this->assertNotEmpty($guest->guest_token);
        $this->get(route('tickets.guest.show',$guest->guest_token))->assertOk();
    }

    public function test_archivos_existentes_no_se_modifican_y_siguen_disponibles_tras_bajar_limites(): void
    {
        $owner=User::factory()->create();
        $this->actingAs($owner)->post(route('tickets.store'),['title'=>'Archivo anterior','attachments'=>[$this->pdf(2048,'anterior.pdf')]])->assertSessionHasNoErrors();
        $attachment=TicketAttachment::firstOrFail(); $before=$attachment->getAttributes(); $ticket=Ticket::firstOrFail(); $beforeTicket=$ticket->getAttributes();
        $this->activate(1,1);
        $this->assertSame($before,$attachment->fresh()->getAttributes()); $this->assertSame($beforeTicket,$ticket->fresh()->getAttributes());
        Storage::disk('local')->assertExists($attachment->file_path);
        $this->actingAs($owner)->get(route('files.attachment',$attachment))->assertOk();
    }

    public function test_limite_global_no_habilita_chat_cerrado_ni_tipos_peligrosos(): void
    {
        $this->activate(5,50);
        $owner=User::factory()->create(); $ticket=$this->ticket($owner); $ticket->update(['status'=>'closed']);
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment',$ticket),['comment'=>'No debe abrir el chat.','attachments'=>[$this->pdf()]])->assertSessionHasErrors('comment');
        $this->assertSame(0,$ticket->comments()->count());
        $this->actingAs($owner)->post(route('tickets.store'),['title'=>'Tipo no permitido','attachments'=>[UploadedFile::fake()->create('ejecutable.php',1,'application/pdf')]])->assertSessionHasNoErrors();
        $this->assertSame(0,TicketAttachment::count());
    }

    public function test_politica_invalida_no_inventa_limites_y_actualizar_config_invalida_cache(): void
    {
        $this->activate(2,1); $this->assertSame(1024,TicketAttachmentLimits::forRequest()['kilobytes']);
        $this->apply(['max_attachment_size_mb'=>'2']);
        $this->assertSame(2048,TicketAttachmentLimits::forRequest()['kilobytes']);
        SystemSetting::where('key','max_attachment_size_mb')->update(['value'=>'999']); SystemSetting::forgetCached('max_attachment_size_mb');
        $this->assertSame(['files'=>5,'kilobytes'=>51200],TicketAttachmentLimits::forRequest());
        $this->assertSame(['files'=>3,'kilobytes'=>5120],TicketAttachmentLimits::forRequest(true));
    }

    public function test_limites_visibles_coinciden_con_validacion_global(): void
    {
        $this->activate(2,1);
        $this->get(route('tickets.guest.create'))->assertOk()->assertSee('Máximo 2 archivos')->assertSee('hasta 1 MB por archivo');
        $owner=User::factory()->create(); $ticket=$this->ticket($owner);
        $this->actingAs($owner)->get(route('tickets.create'))->assertOk()->assertSee('Máximo 2 archivos');
        $this->get(route('tickets.show',$ticket))->assertOk()->assertSee('Máximo 2 archivos')->assertSee('hasta 1 MB por archivo');
    }
}
