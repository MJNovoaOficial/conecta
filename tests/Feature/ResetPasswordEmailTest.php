<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ResetPasswordEmailTest extends TestCase
{
    public function test_el_correo_tiene_fondos_solidos_y_conserva_ambos_enlaces(): void
    {
        $resetUrl = 'https://example.test/reset-password/token-de-prueba?email=ana%40example.test';
        $html = view('emails.reset_password', [
            'user' => new User(['name' => 'Ana']),
            'resetUrl' => $resetUrl,
        ])->render();

        $this->assertStringContainsString('bgcolor="#1a2332"', $html);
        $this->assertStringContainsString('bgcolor="#2563eb"', $html);
        $this->assertStringContainsString('background-color:#2563eb;color:#ffffff', $html);
        $this->assertStringNotContainsString('linear-gradient', $html);
        $this->assertStringContainsString('Restablecer mi contraseña', $html);
        $this->assertSame(2, substr_count($html, 'href="'.e($resetUrl).'"'));
        $this->assertStringContainsString('Hola, Ana.', $html);
        $this->assertStringContainsString('60 minutos', $html);
    }

    public function test_el_correo_sigue_escapando_los_datos(): void
    {
        $html = view('emails.reset_password', [
            'user' => new User(['name' => '<script>prueba</script>']),
            'resetUrl' => 'https://example.test/reset-password/token?email=ana%40example.test&x="prueba"',
        ])->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;prueba&lt;/script&gt;', $html);
        $this->assertStringContainsString('&amp;x=&quot;prueba&quot;', $html);
    }
}
