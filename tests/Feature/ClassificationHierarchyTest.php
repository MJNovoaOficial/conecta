<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\PriorityRule;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\TipoIncidente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ClassificationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
        RateLimiter::clear('guest_ticket:127.0.0.1');
    }

    private function catalogo(): array
    {
        $red = Categoria::create(['name' => 'Red']);
        $hardware = Categoria::create(['name' => 'Hardware']);
        $vpn = Subcategoria::create(['categoria_id' => $red->id, 'name' => 'VPN']);
        $impresoras = Subcategoria::create(['categoria_id' => $hardware->id, 'name' => 'Impresoras']);
        $tipoVpn = TipoIncidente::create(['subcategoria_id' => $vpn->id, 'name' => 'No conecta']);
        $tipoImpresora = TipoIncidente::create(['subcategoria_id' => $impresoras->id, 'name' => 'No imprime']);

        return [$red, $hardware, $vpn, $impresoras, $tipoVpn, $tipoImpresora];
    }

    private function datosInvitado(array $extra = []): array
    {
        return array_merge([
            'guest_name' => 'Ana Pérez',
            'guest_email' => 'ana@example.test',
            'title' => 'Problema de conexión',
            'description' => 'Necesito ayuda con mi conexión.',
        ], $extra);
    }

    public function test_invitado_no_puede_enviar_un_tipo_de_otra_subcategoria(): void
    {
        [, , $vpn, , , $tipoImpresora] = $this->catalogo();

        $this->from(route('tickets.guest.create'))
            ->post(route('tickets.guest.store'), $this->datosInvitado([
                'subcategoria_id' => $vpn->id,
                'tipo_incidente_id' => $tipoImpresora->id,
            ]))
            ->assertRedirect(route('tickets.guest.create'))
            ->assertSessionHasErrors('tipo_incidente_id')
            ->assertSessionHas('_old_input.description', 'Necesito ayuda con mi conexión.');

        $this->assertSame(0, Ticket::count());
    }

    public function test_invitado_puede_enviar_un_tipo_correcto_o_dejarlo_vacio(): void
    {
        [, , $vpn, , $tipoVpn] = $this->catalogo();

        $this->post(route('tickets.guest.store'), $this->datosInvitado([
            'subcategoria_id' => $vpn->id,
            'tipo_incidente_id' => $tipoVpn->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'subcategoria_id' => $vpn->id,
            'tipo_incidente_id' => $tipoVpn->id,
        ]);

        $this->post(route('tickets.guest.store'), $this->datosInvitado([
            'subcategoria_id' => $vpn->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'subcategoria_id' => $vpn->id,
            'tipo_incidente_id' => null,
        ]);
    }

    public function test_un_tipo_exige_subcategoria_sin_hacer_obligatoria_la_clasificacion(): void
    {
        [, , , , $tipoVpn] = $this->catalogo();

        $this->post(route('tickets.guest.store'), $this->datosInvitado([
            'tipo_incidente_id' => $tipoVpn->id,
        ]))->assertSessionHasErrors('subcategoria_id');

        $this->post(route('tickets.guest.store'), $this->datosInvitado())
            ->assertRedirect();

        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_usuario_registrado_no_puede_enviar_un_tipo_ajeno(): void
    {
        [, , $vpn, , , $tipoImpresora] = $this->catalogo();

        $this->actingAs(User::factory()->create())
            ->post(route('tickets.store'), [
                'title' => 'Problema de conexión',
                'subcategoria_id' => $vpn->id,
                'tipo_incidente_id' => $tipoImpresora->id,
            ])
            ->assertSessionHasErrors('tipo_incidente_id');

        $this->assertSame(0, Ticket::count());
    }

    public function test_usuario_registrado_puede_enviar_un_tipo_propio(): void
    {
        [, , $vpn, , $tipoVpn] = $this->catalogo();

        $this->actingAs(User::factory()->create())
            ->post(route('tickets.store'), [
                'title' => 'Problema de conexión',
                'subcategoria_id' => $vpn->id,
                'tipo_incidente_id' => $tipoVpn->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'subcategoria_id' => $vpn->id,
            'tipo_incidente_id' => $tipoVpn->id,
        ]);
    }

    public function test_admin_no_puede_guardar_regla_con_subcategoria_de_otra_categoria(): void
    {
        [$red, , , $impresoras] = $this->catalogo();

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.priority-rules.store'), [
                'categoria_id' => $red->id,
                'subcategoria_id' => $impresoras->id,
                'priority' => 'high',
            ])
            ->assertSessionHasErrors('subcategoria_id');

        $this->assertSame(0, PriorityRule::count());
    }

    public function test_admin_no_puede_guardar_regla_con_tipo_de_otra_subcategoria(): void
    {
        [$red, , $vpn, , , $tipoImpresora] = $this->catalogo();

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.priority-rules.store'), [
                'categoria_id' => $red->id,
                'subcategoria_id' => $vpn->id,
                'tipo_incidente_id' => $tipoImpresora->id,
                'priority' => 'critical',
            ])
            ->assertSessionHasErrors('tipo_incidente_id');

        $this->assertSame(0, PriorityRule::count());
    }

    public function test_reglas_validas_y_generales_siguen_funcionando(): void
    {
        [$red, , $vpn, , $tipoVpn] = $this->catalogo();
        $this->actingAs(User::factory()->administrador()->create());

        $this->post(route('admin.priority-rules.store'), [
            'categoria_id' => $red->id,
            'subcategoria_id' => $vpn->id,
            'tipo_incidente_id' => $tipoVpn->id,
            'priority' => 'critical',
        ])->assertSessionHasNoErrors();

        $this->post(route('admin.priority-rules.store'), [
            'categoria_id' => $red->id,
            'priority' => 'low',
        ])->assertSessionHasNoErrors();

        $this->assertSame('critical', PriorityRule::resolve($vpn->id, $tipoVpn->id));
        $this->assertDatabaseCount('priority_rules', 2);
    }

    public function test_edicion_de_regla_rechaza_cruce_sin_cambiar_la_regla(): void
    {
        [$red, , $vpn, , $tipoVpn, $tipoImpresora] = $this->catalogo();
        $regla = PriorityRule::create([
            'categoria_id' => $red->id,
            'subcategoria_id' => $vpn->id,
            'tipo_incidente_id' => $tipoVpn->id,
            'priority' => 'high',
        ]);

        $this->actingAs(User::factory()->administrador()->create())
            ->put(route('admin.priority-rules.update', $regla), [
                'categoria_id' => $red->id,
                'subcategoria_id' => $vpn->id,
                'tipo_incidente_id' => $tipoImpresora->id,
                'priority' => 'critical',
            ])
            ->assertSessionHasErrors('tipo_incidente_id');

        $this->assertSame($tipoVpn->id, $regla->fresh()->tipo_incidente_id);
        $this->assertSame('high', $regla->fresh()->priority);
    }

    public function test_reclasificacion_rechaza_cruce_sin_cambiar_el_ticket(): void
    {
        [, , $vpn, , $tipoVpn, $tipoImpresora] = $this->catalogo();
        $admin = User::factory()->administrador()->create();
        $ticket = Ticket::create([
            'ticket_number' => 'TK-PRUEBA-CLASIFICACION',
            'user_id' => User::factory()->create()->id,
            'title' => 'Problema de conexión',
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'medium',
            'subcategoria_id' => $vpn->id,
            'tipo_incidente_id' => $tipoVpn->id,
        ]);

        $this->actingAs($admin)
            ->put(route('tickets.updateClassification', $ticket), [
                'subcategoria_id' => $vpn->id,
                'tipo_incidente_id' => $tipoImpresora->id,
            ])
            ->assertSessionHasErrors('tipo_incidente_id');

        $this->assertSame($tipoVpn->id, $ticket->fresh()->tipo_incidente_id);
    }

    public function test_reclasificacion_valida_sigue_funcionando(): void
    {
        [, , $vpn, , $tipoVpn] = $this->catalogo();
        $ticket = Ticket::create([
            'ticket_number' => 'TK-PRUEBA-VALIDA',
            'user_id' => User::factory()->create()->id,
            'title' => 'Problema de conexión',
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'medium',
        ]);

        $this->actingAs(User::factory()->administrador()->create())
            ->put(route('tickets.updateClassification', $ticket), [
                'subcategoria_id' => $vpn->id,
                'tipo_incidente_id' => $tipoVpn->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($tipoVpn->id, $ticket->fresh()->tipo_incidente_id);
    }
}
