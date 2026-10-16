<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginAccessTest extends TestCase
{
    use RefreshDatabase;

    private function key(User $user): string
    {
        return 'login:account:'.hash('sha256', $user->email);
    }

    private function login(string $email, string $password = 'incorrect-password')
    {
        Auth::logout();
        return $this->withServerVariables(['REMOTE_ADDR' => '198.18.0.1'])
            ->from('/')->post('/login', ['email' => $email, 'password' => $password]);
    }

    public function test_permite_corregir_la_clave_sin_limite_ni_espera(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 12; $i++) {
            $this->login($user->email)->assertRedirect('/')->assertSessionHasErrors([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ])->assertHeaderMissing('Retry-After');
        }
        $this->assertGuest();
        $this->assertNull(Cache::get($this->key($user)));
        $this->assertSame(12, LoginAttempt::where('successful', false)->count());
        $this->login($user->email, 'password')->assertRedirect('/tickets');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, LoginAttempt::where('successful', true)->count());
    }

    public function test_bloqueo_antiguo_y_lock_ocupado_no_impiden_ingresar_ni_se_borran(): void
    {
        config(['cache.default' => 'database']);
        $user = User::factory()->create();
        $key = $this->key($user);
        $state = ['failures' => 5, 'blocked_until' => now()->addMinutes(5)->timestamp];
        Cache::put($key, $state, 3600);
        $lock = Cache::lock($key.':lock', 120);
        $this->assertTrue($lock->get());
        try {
            $this->login($user->email)->assertRedirect('/')->assertSessionHasErrors('email');
            $this->login($user->email, 'password')->assertRedirect('/tickets')->assertHeaderMissing('Retry-After');
            $this->assertAuthenticatedAs($user);
            $this->assertSame($state, Cache::get($key));
            $this->assertTrue($lock->isOwnedByCurrentProcess());
        } finally {
            $lock->release();
        }
    }

    public function test_cuentas_inactivas_y_correos_inexistentes_no_pueden_entrar(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        foreach ([$inactive->email, 'inexistente@example.test'] as $email) {
            for ($i = 0; $i < 6; $i++) {
                $this->login($email, 'password')->assertRedirect('/')->assertSessionHasErrors('email');
                $this->assertGuest();
            }
        }
        $this->assertSame(12, LoginAttempt::where('successful', false)->count());
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_conserva_validacion_del_formulario(): void
    {
        $user = User::factory()->create();
        $this->login($user->email, 'short')->assertSessionHasErrors('password');
        $this->login('correo-invalido', 'password')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('login_attempts', 0);
    }

    public function test_distintas_cuentas_comparten_red_sin_cambiar_roles_ni_datos(): void
    {
        $old = LoginAttempt::record('anterior@example.test', '198.18.0.2', 'Registro previo', false);
        $before = $old->fresh()->getAttributes();
        foreach (User::factory()->count(10)->create() as $user) {
            $attributes = $user->fresh()->getAttributes();
            $this->login($user->email, 'password')->assertRedirect('/tickets');
            $this->assertAuthenticatedAs($user);
            $this->assertSame($attributes, $user->fresh()->getAttributes());
        }
        $this->assertSame($before, $old->fresh()->getAttributes());
        $this->assertSame(10, LoginAttempt::where('successful', true)->count());
    }

    public function test_configuracion_antigua_no_puede_reactivar_el_bloqueo_eliminado(): void
    {
        config(['auth.login_throttle_enabled' => true]);
        $user = User::factory()->create();
        for ($i = 0; $i < 8; $i++) $this->login($user->email)->assertSessionHasErrors('email');
        $this->login($user->email, 'password')->assertRedirect('/tickets');
        $this->assertAuthenticatedAs($user);
    }

    public function test_configuracion_oculta_opciones_retiradas_sin_borrar_datos_historicos(): void
    {
        $stored = SystemSetting::create(['key' => 'login_lockout_minutes', 'value' => '15',
            'type' => 'integer', 'group' => 'security', 'label' => 'Bloqueo por intentos fallidos',
            'description' => 'Regla vigente: 90 segundos después de 5 fallos por cuenta.']);
        $this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))
            ->assertOk()->assertDontSee('Bloqueo por intentos fallidos')
            ->assertDontSee('Intentos máximos de login')->assertDontSee('Se reactiva')
            ->assertDontSee('90 segundos')->assertDontSee('name="settings[max_login_attempts]"', false);
        $this->assertSame('Regla vigente: 90 segundos después de 5 fallos por cuenta.', $stored->fresh()->description);
    }
}
