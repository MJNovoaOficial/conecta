<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Support\VersionedAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthCacheFreshnessTest extends TestCase
{
    use RefreshDatabase;

    public static function accessScreens(): array
    {
        return [
            'ingreso real en inicio' => ['home', []],
            'registro' => ['register', []],
            'recuperación' => ['password.forgot', []],
            'restablecimiento' => ['password.reset.form', ['token' => 'qa-reset-token', 'email' => 'qa@example.test']],
        ];
    }

    #[DataProvider('accessScreens')]
    public function test_los_formularios_reales_no_se_almacenan_y_tienen_css_versionados_y_renovacion_acotada(string $route, array $parameters): void
    {
        $response = $this->get(route($route, $parameters))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'data-auth-form-freshness'));
        $response->assertSee('if (event.persisted) window.location.reload();', false);
        foreach (['css/premium.css', 'css/responsive.css'] as $path) {
            $response->assertSee('href="'.VersionedAsset::url($path).'"', false);
        }
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('login_attempts', 0);
    }

    public function test_el_alias_login_y_head_tambien_impiden_almacenar_la_respuesta(): void
    {
        $this->get(route('login'))->assertRedirect(route('home'))->assertHeader('Cache-Control', 'no-store, private');
        $this->head(route('home'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_las_pantallas_de_tickets_y_el_invitado_no_se_recargan_al_volver_atras(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::create(['ticket_number' => 'CACHE-KEEP-TICKET', 'user_id' => $owner->id,
            'title' => 'Conservar formulario de ticket', 'description' => 'Sin cambios de flujo.',
            'status' => 'open', 'priority' => 'medium']);
        $before = $ticket->fresh()->getAttributes();
        foreach (['tickets.index' => [], 'tickets.create' => [], 'tickets.show' => [$ticket]] as $route => $parameters) {
            $response = $this->actingAs($owner)->get(route($route, $parameters))->assertOk()
                ->assertDontSee('data-auth-form-freshness', false);
            $this->assertFalse($response->headers->hasCacheControlDirective('no-store'));
        }
        Auth::logout();
        $this->get(route('tickets.guest.create'))->assertOk()->assertDontSee('data-auth-form-freshness', false);
        $this->assertSame($before, $ticket->fresh()->getAttributes());
    }

    public function test_visitar_el_ingreso_no_invalida_una_sesion_autenticada_ni_sus_datos(): void
    {
        $user = User::factory()->create();
        $before = $user->fresh()->getAttributes();
        $this->actingAs($user)->withSession(['qa_marker' => 'conservar'])
            ->get(route('home'))->assertRedirect(route('tickets.index'))
            ->assertSessionHas('qa_marker', 'conservar');
        $this->assertAuthenticatedAs($user);
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    public function test_token_vencido_sigue_rechazado_y_el_ingreso_fresco_funciona_sin_reenviar_la_clave(): void
    {
        config(['app.debug' => false]);
        $user = User::factory()->create();
        $this->app['env'] = 'production'; // Enable real CSRF verification, not its test bypass.
        $this->withSession(['_token' => 'token-actual'])->post(route('login'), [
            '_token' => 'token-antiguo', 'email' => $user->email, 'password' => 'password',
        ])->assertStatus(419)->assertSee('La página ha expirado')->assertSee('Volver a iniciar sesión');
        $this->assertGuest();
        $this->assertDatabaseCount('login_attempts', 0);
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $fresh = $this->get(route('home'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $dom = new \DOMDocument();
        @$dom->loadHTML($fresh->getContent());
        $token = (new \DOMXPath($dom))->query('//form[@action="'.route('login').'"]//input[@name="_token"]')->item(0)->getAttribute('value');
        $this->post(route('login'), ['_token' => $token, 'email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/tickets')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('login_attempts', 1);
    }
}
