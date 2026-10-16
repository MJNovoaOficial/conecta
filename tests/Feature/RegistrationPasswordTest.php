<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('register:127.0.0.1');
    }

    private function payload(string $password, ?string $confirmation = null): array
    {
        return ['name' => 'Ana Pérez', 'email' => 'ana@example.test',
            'department_id' => Department::create(['name' => 'Ventas', 'is_active' => true])->id,
            'password' => $password, 'password_confirmation' => $confirmation ?? $password];
    }

    public static function validPasswords(): array
    {
        return ['ocho caracteres' => ['Abcd12!@'], 'doce caracteres' => ['Abcd1234!@XY']];
    }

    #[DataProvider('validPasswords')]
    public function test_se_puede_registrar_desde_ocho_caracteres_con_los_requisitos_actuales(string $password): void
    {
        $this->post(route('register'), $this->payload($password))
            ->assertSessionHasNoErrors()->assertRedirect('/tickets');
        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertSame('user', $user->role);
        $this->assertTrue($user->is_active);
    }

    public static function invalidPasswords(): array
    {
        return [
            'siete caracteres' => ['Abc12!@', null, 'La contraseña debe tener al menos 8 caracteres.'],
            'sin mayúscula' => ['abcd12!@', null, 'La contraseña debe incluir mayúsculas y minúsculas.'],
            'sin minúscula' => ['ABCD12!@', null, 'La contraseña debe incluir mayúsculas y minúsculas.'],
            'sin número' => ['Abcdef!@', null, 'La contraseña debe incluir al menos un número.'],
            'sin símbolo' => ['Abcdef12', null, 'La contraseña debe incluir al menos un símbolo.'],
            'confirmación distinta' => ['Abcd12!@', 'Otra12!@', 'La confirmación de la contraseña no coincide.'],
            'vacía' => ['', null, 'Ingresa una contraseña.'],
        ];
    }

    #[DataProvider('invalidPasswords')]
    public function test_rechaza_claves_invalidas_con_mensajes_claros_solo_en_registro(string $password, ?string $confirmation, string $message): void
    {
        $this->from(route('register'))->post(route('register'), $this->payload($password, $confirmation))
            ->assertRedirect(route('register'))->assertSessionHasErrors(['password' => $message]);
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertGuest();
        $this->get(route('register'))->assertOk()->assertSee($message)->assertDontSee('validation.');
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->assertArrayNotHasKey('password_confirmation', session()->getOldInput());
    }

    public function test_formulario_indica_ocho_y_conserva_confirmacion_y_requisitos(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Mínimo 8 caracteres')
            ->assertSee('minlength="8"', false)->assertDontSee('Mínimo 12 caracteres')
            ->assertSee('mayúsculas, minúsculas, números y símbolos.')
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_errores_repetidos_permiten_corregir_el_registro_sin_bloqueo(): void
    {
        $data = $this->payload('Abc12!@');
        for ($i = 0; $i < 6; $i++) {
            $this->from(route('register'))->post(route('register'), $data)
                ->assertRedirect(route('register'))
                ->assertSessionHasErrors(['password' => 'La contraseña debe tener al menos 8 caracteres.']);
        }
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertSame(0, RateLimiter::attempts('register:127.0.0.1'));
        $data['password'] = $data['password_confirmation'] = 'Abcd12!@';
        $this->post(route('register'), $data)->assertSessionHasNoErrors()->assertRedirect('/tickets');
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_un_bloqueo_antiguo_de_registro_no_impide_crear_la_cuenta(): void
    {
        $key = 'register:127.0.0.1';
        for ($i = 0; $i < 3; $i++) RateLimiter::hit($key, 3600);
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 3));
        $this->post(route('register'), $this->payload('Abcd12!@'))
            ->assertSessionHasNoErrors()->assertRedirect('/tickets');
        $this->assertDatabaseCount('usuarios', 1);
        // No se borra caché global ni se alteran contadores existentes.
        $this->assertSame(3, RateLimiter::attempts($key));
    }

    public function test_varias_personas_de_la_misma_red_pueden_registrarse(): void
    {
        $data = $this->payload('Abcd12!@');
        for ($i = 1; $i <= 5; $i++) {
            $data['email'] = 'persona'.$i.'@example.test';
            $this->post(route('register'), $data)->assertSessionHasNoErrors()->assertRedirect('/tickets');
            Auth::logout();
        }
        $this->assertDatabaseCount('usuarios', 5);
        $this->assertSame(0, RateLimiter::attempts('register:127.0.0.1'));
    }
}
