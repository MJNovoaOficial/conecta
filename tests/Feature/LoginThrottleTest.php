<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function key(string $email): string
    {
        return 'login:account:'.hash('sha256', strtolower(trim($email)));
    }

    private function login(string $email, string $password = 'incorrect-password', string $ip = '198.18.0.1')
    {
        Auth::logout();
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['User-Agent' => 'Conecta-login-regression'])
            ->from('/')->post('/login', ['email' => $email, 'password' => $password]);
    }

    private function failFive(User $user): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login($user->email)->assertSessionHasErrors('email');
        }
    }

    public function test_los_accesos_correctos_de_distintas_cuentas_en_la_misma_red_no_consumen_intentos(): void
    {
        foreach (User::factory()->count(10)->create() as $user) {
            $this->login($user->email, 'password')->assertRedirect('/tickets');
            $this->assertAuthenticatedAs($user);
            $this->assertNull(Cache::get($this->key($user->email)));
        }
        $this->assertSame(10, LoginAttempt::where('successful', true)->count());
        $this->assertDatabaseCount('usuarios', 10);
    }

    public function test_cinco_fallos_bloquean_solo_esa_cuenta_incluso_si_cambia_de_ip(): void
    {
        $this->travelTo(now()->startOfSecond());
        $maria = User::factory()->create();
        $nicolas = User::factory()->create();
        $this->failFive($maria);
        $this->login($maria->email, 'password')->assertStatus(429)->assertHeader('Retry-After', '300');
        $this->assertGuest();
        $this->login($maria->email, 'password', '198.18.0.2')->assertStatus(429);
        $this->login($nicolas->email, 'password')->assertRedirect('/tickets');
        $this->assertAuthenticatedAs($nicolas);
        $this->assertSame(5, LoginAttempt::where('successful', false)->count());
        $this->assertSame(5, Cache::get($this->key($maria->email))['failures']);
    }

    public function test_acertar_antes_del_bloqueo_reinicia_solo_los_fallos_de_la_cuenta(): void
    {
        $first = User::factory()->create();
        $other = User::factory()->create();
        $this->login($other->email);
        for ($i = 0; $i < 4; $i++) $this->login($first->email)->assertSessionHasErrors('email');
        $this->login($first->email, 'password')->assertRedirect('/tickets');
        $this->assertNull(Cache::get($this->key($first->email)));
        $this->assertSame(1, Cache::get($this->key($other->email))['failures']);
        for ($i = 0; $i < 4; $i++) $this->login($first->email)->assertSessionHasErrors('email');
        $this->login($first->email, 'password')->assertRedirect('/tickets');
    }

    public function test_la_espera_dura_cinco_minutos_desde_el_quinto_fallo_y_no_se_prolonga_al_reintentar(): void
    {
        $user = User::factory()->create();
        $this->travelTo(now()->startOfSecond());
        $this->login($user->email);
        $this->travel(14)->minutes();
        for ($i = 0; $i < 4; $i++) $this->login($user->email)->assertSessionHasErrors('email');
        $this->login($user->email, 'password')->assertStatus(429)->assertHeader('Retry-After', '300');
        $this->travel(2)->minutes();
        $this->login($user->email, 'password')->assertStatus(429)->assertHeader('Retry-After', '180');
        $this->travel(3)->minutes();
        $this->login($user->email)->assertSessionHasErrors('email');
        $this->assertSame(1, Cache::get($this->key($user->email))['failures']);
        $this->login($user->email, 'password')->assertRedirect('/tickets');
    }

    public function test_los_fallos_antiguos_expiran_despues_de_la_ventana_de_quince_minutos(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 4; $i++) $this->login($user->email);
        $this->travel(16)->minutes();
        $this->login($user->email)->assertSessionHasErrors('email');
        $this->assertSame(1, Cache::get($this->key($user->email))['failures']);
    }

    public function test_mayusculas_y_espacios_no_crean_otro_contador_para_el_mismo_correo(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.test']);
        for ($i = 0; $i < 5; $i++) $this->login(' MARIA@EXAMPLE.TEST ')->assertSessionHasErrors('email');
        $this->login($user->email, 'password')->assertStatus(429);
        $this->assertSame(5, LoginAttempt::where('email', $user->email)->count());
    }

    public function test_un_correo_inexistente_recibe_la_misma_proteccion_y_el_mismo_error_de_credenciales(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login('unknown@example.test')->assertSessionHasErrors([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }
        $this->login('unknown@example.test')->assertStatus(429);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_variantes_equivalentes_para_mysql_comparten_el_contador_de_la_cuenta_real(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.test']);
        $this->assertSame($user->id, User::where('email', 'mária@example.test')->value('id'));
        for ($i = 0; $i < 5; $i++) $this->login('mária@example.test')->assertSessionHasErrors('email');
        $this->assertSame(5, LoginAttempt::count());
        $this->login($user->email, 'password')->assertStatus(429);
    }

    public function test_las_solicitudes_invalidas_no_consumen_intentos_ni_modifican_usuarios(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 6; $i++) $this->login($user->email, 'short')->assertSessionHasErrors('password');
        $this->assertNull(Cache::get($this->key($user->email)));
        $this->assertDatabaseCount('login_attempts', 0);
        $this->assertDatabaseCount('usuarios', 1);
        $this->login($user->email, 'password')->assertRedirect('/tickets');
    }

    public function test_un_contador_viejo_de_ip_no_bloquea_la_politica_nueva_ni_se_borra_globalmente(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) RateLimiter::hit('login:198.18.0.1', 900);
        $this->login($user->email, 'password')->assertRedirect('/tickets');
        $this->assertSame(5, RateLimiter::attempts('login:198.18.0.1'));
    }

    public function test_un_lock_ocupado_devuelve_una_espera_breve_sin_autenticar_ni_contar_otro_fallo(): void
    {
        $user = User::factory()->create();
        $store = Cache::store();
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('block')->once()->with(5, Mockery::type('Closure'))->andThrow(new LockTimeoutException);
        Cache::partialMock()->shouldReceive('lock')->once()->with($this->key($user->email).':lock', 120)->andReturn($lock);
        $this->login($user->email, 'password')->assertStatus(429)->assertHeader('Retry-After', '1')
            ->assertSee('Se está procesando otro acceso');
        $this->assertGuest();
        $this->assertDatabaseCount('login_attempts', 0);
        $this->assertNull($store->get($this->key($user->email)));
    }

    public function test_el_store_database_existente_soporta_el_guard_sin_tablas_nuevas(): void
    {
        config(['cache.default' => 'database']);
        $user = User::factory()->create();
        $this->failFive($user);
        $this->login($user->email, 'password')->assertStatus(429);
        $this->assertDatabaseCount('cache_locks', 0);
        $this->assertSame(5, LoginAttempt::count());
    }

    public function test_la_contencion_de_apertura_de_un_file_lock_en_windows_no_autentica_ni_devuelve_500(): void
    {
        $user = User::factory()->create();
        $store = Cache::store();
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('block')->once()->andThrow(new \ErrorException(
            'fopen(test-lock): Failed to open stream: Permission denied', 0, E_WARNING,
            base_path('vendor/laravel/framework/src/Illuminate/Filesystem/LockableFile.php')
        ));
        Cache::partialMock()->shouldReceive('lock')->once()->andReturn($lock);
        Cache::shouldReceive('getStore')->once()->andReturn(Mockery::mock(FileStore::class));
        $this->login($user->email, 'password')->assertStatus(429)->assertHeader('Retry-After', '1');
        $this->assertGuest();
        $this->assertDatabaseCount('login_attempts', 0);
        $this->assertNull($store->get($this->key($user->email)));
    }

    public function test_los_errores_ajenos_al_file_lock_no_se_ocultan_como_una_espera_de_login(): void
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('block')->once()->andThrow(new \ErrorException('Error ajeno al lock', 0, E_WARNING, __FILE__));
        Cache::partialMock()->shouldReceive('lock')->once()->andReturn($lock);
        Cache::shouldReceive('getStore')->once()->andReturn(Mockery::mock(FileStore::class));
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Error ajeno al lock');
        $this->login($user->email, 'password');
    }

    public function test_el_store_file_soporta_el_guard_sin_bloquear_otras_cuentas(): void
    {
        $directory = sys_get_temp_dir().'/conecta-login-test-'.bin2hex(random_bytes(8));
        config(['cache.default' => 'login_file_test', 'cache.stores.login_file_test' => [
            'driver' => 'file', 'path' => $directory, 'lock_path' => $directory,
        ]]);
        $blocked = User::factory()->create();
        $other = User::factory()->create();
        $this->failFive($blocked);
        $this->login($blocked->email, 'password')->assertStatus(429);
        $this->login($other->email, 'password')->assertRedirect('/tickets');
        Cache::forget($this->key($blocked->email));
    }

    public function test_el_login_conserva_los_datos_de_la_cuenta_y_los_registros_de_auditoria_previos(): void
    {
        $user = User::factory()->administrador()->create();
        $before = $user->fresh()->getAttributes();
        $old = LoginAttempt::record('previous@example.test', '198.18.0.2', 'Registro previo', false);
        $oldBefore = $old->fresh()->getAttributes();
        $this->login($user->email, 'password')->assertRedirect('/tickets');
        $this->assertAuthenticatedAs($user);
        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertSame($oldBefore, $old->fresh()->getAttributes());
        $this->assertDatabaseCount('login_attempts', 2);
    }
}
