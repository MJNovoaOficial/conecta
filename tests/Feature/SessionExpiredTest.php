<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionExpiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_formulario_con_token_vencido_muestra_el_aviso_sin_crear_el_ticket(): void
    {
        config(['app.debug' => false]);
        $this->app['env'] = 'production';

        $this->post(route('tickets.store'), ['title' => 'No debe guardarse'])
            ->assertStatus(419)
            ->assertSee('La página ha expirado')
            ->assertSee('Volver a iniciar sesión')
            ->assertSee('href="'.route('login').'"', false);

        $this->assertSame(0, Ticket::count());
    }

}
