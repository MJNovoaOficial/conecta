<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public static function screens(): array
    {
        return [
            'login' => ['home', null, ['togglePass()']],
            'registro' => ['register', null, ["togglePass('passField','passIcon', this)", "togglePass('passField2','passIcon2', this)"]],
            'restablecimiento' => ['password.reset.form', null, ["toggleField('newPass','eyeNew')", "toggleField('confPass','eyeConf')"]],
            'perfil' => ['profile.index', 'user', ["toggleField('curPass','eyeCur')", "toggleField('newPass','eyeNew')", "toggleField('confPass','eyeConf')"]],
            'crear usuario' => ['admin.users.create', 'admin', ['togglePass(this)']],
        ];
    }

    #[DataProvider('screens')]
    public function test_los_botones_se_renderizan_sin_cortar_el_controlador_javascript(string $route, ?string $role, array $handlers): void
    {
        Auth::logout();
        if ($role) $this->actingAs(User::factory()->create(['role' => $role]));
        $params = $route === 'password.reset.form' ? ['token' => str_repeat('t', 64), 'email' => 'ana@example.test'] : [];
        $html = $this->get(route($route, $params))->assertOk()->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $buttons = (new \DOMXPath($dom))->query('//button[.//i[contains(concat(" ", normalize-space(@class), " "), " fa-eye ")]]');
        $this->assertSame(count($handlers), $buttons->length);
        foreach ($handlers as $index => $handler) {
            $button = $buttons->item($index);
            $this->assertSame('button', $button->getAttribute('type'));
            $this->assertSame($handler, $button->getAttribute('onclick'));
        }
    }
}
