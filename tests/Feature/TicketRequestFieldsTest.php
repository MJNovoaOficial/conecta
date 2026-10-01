<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CategoryDepartmentRule;
use App\Models\Department;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TicketRequestFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        RateLimiter::clear('guest_ticket:127.0.0.1');
    }

    public function test_los_formularios_con_sesion_no_piden_departamento_ni_dispositivo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('tickets.create'))->assertOk()
            ->assertDontSee('name="department_id"', false)
            ->assertDontSee('name="device_type"', false)
            ->assertSee('name="subcategoria_id"', false);

        $modal = view('partials.create_ticket_modal')->render();
        $this->assertStringNotContainsString('name="department_id"', $modal);
        $this->assertStringNotContainsString('name="device_type"', $modal);
        $this->assertStringContainsString('name="tipo_incidente_id"', $modal);
    }

    public function test_el_ticket_toma_el_departamento_de_la_cuenta_sin_pedir_dispositivo(): void
    {
        $department = Department::create(['name' => 'Ventas', 'is_active' => true]);
        $user = User::factory()->create(['department_id' => $department->id]);
        RateLimiter::clear('create_ticket:'.$user->id);

        $this->actingAs($user)->post(route('tickets.store'), ['title' => 'Necesito ayuda'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $ticket = Ticket::sole();
        $this->assertEquals($department->id, $ticket->department_id);
        $this->assertNull($ticket->device_type);
    }

    public function test_un_departamento_enviado_manualmente_no_reemplaza_el_de_la_cuenta(): void
    {
        $department = Department::create(['name' => 'Ventas', 'is_active' => true]);
        $other = Department::create(['name' => 'Contabilidad', 'is_active' => true]);
        $user = User::factory()->create(['department_id' => $department->id]);
        RateLimiter::clear('create_ticket:'.$user->id);

        $this->actingAs($user)->post(route('tickets.store'), [
            'title' => 'Necesito ayuda', 'department_id' => $other->id,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertEquals($department->id, Ticket::sole()->department_id);
    }

    public function test_se_conserva_la_regla_automatica_de_departamento_por_categoria(): void
    {
        $department = Department::create(['name' => 'Ventas', 'is_active' => true]);
        $destination = Department::create(['name' => 'Soporte', 'is_active' => true]);
        $category = Categoria::create(['name' => 'Software']);
        $subcategory = Subcategoria::create(['name' => 'Correo', 'categoria_id' => $category->id]);
        CategoryDepartmentRule::create(['categoria_id' => $category->id, 'department_id' => $destination->id]);
        $user = User::factory()->create(['department_id' => $department->id]);
        RateLimiter::clear('create_ticket:'.$user->id);

        $this->actingAs($user)->post(route('tickets.store'), [
            'title' => 'No abre el correo', 'subcategoria_id' => $subcategory->id,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertEquals($destination->id, Ticket::sole()->department_id);
    }

    public function test_una_cuenta_sin_departamento_puede_solicitar_ayuda(): void
    {
        $user = User::factory()->create(['department_id' => null]);
        RateLimiter::clear('create_ticket:'.$user->id);

        $this->actingAs($user)->post(route('tickets.store'), ['title' => 'Necesito ayuda'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertNull(Ticket::sole()->department_id);
    }

    public function test_el_invitado_conserva_el_departamento_en_el_formulario_y_el_guardado(): void
    {
        $department = Department::create(['name' => 'Ventas', 'is_active' => true]);
        $this->get(route('tickets.guest.create'))->assertOk()
            ->assertSee('name="department_id"', false)
            ->assertSee('Ventas')
            ->assertDontSee('name="guest_department"', false)
            ->assertDontSee('name="device_type"', false);

        $this->post(route('tickets.guest.store'), [
            'guest_name' => 'Ana', 'guest_email' => 'ana@example.test',
            'department_id' => $department->id,
            'title' => 'Necesito ayuda',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $ticket = Ticket::sole();
        $this->assertNull($ticket->user_id);
        $this->assertEquals($department->id, $ticket->department_id);
        $this->assertNull($ticket->guest_department);
    }
}
