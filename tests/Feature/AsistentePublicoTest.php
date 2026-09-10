<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Services\AsistenteIA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El aislamiento del asistente de la pantalla de login.
 *
 * Es la prueba más importante de la base de conocimiento. DIMAKING responde
 * antes de iniciar sesión, donde no se sabe quién pregunta. Si el filtro de
 * artículos públicos falla, la plataforma le entrega su manual interno
 * —nombres de servidores, sistemas, procedimientos— a cualquiera que llegue
 * a la página. No es un error que se vea: el asistente responde igual de bien.
 *
 * Por eso se prueba desde los dos lados: que lo público salga, y sobre todo
 * que lo privado no.
 */
class AsistentePublicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sin servidor de modelos: estas pruebas son sobre qué artículos
        // alcanza cada vía, no sobre cómo se redacta la respuesta. Así corren
        // en cualquier máquina y no dependen de que Ollama esté arriba.
        config(['chatbot.enabled' => false]);
    }

    private function articulo(string $titulo, string $contenido, bool $publico): Articulo
    {
        return Articulo::create([
            'title'     => $titulo,
            'symptoms'  => $contenido,
            'content'   => $contenido,
            'is_active' => true,
            'publico'   => $publico,
        ]);
    }

    // ── El filtro ─────────────────────────────────────────────────────

    public function test_sin_sesion_no_se_alcanza_un_articulo_privado(): void
    {
        $this->articulo(
            'La VPN no conecta desde la casa',
            'Revisa la conexion VPN hacia el servidor interno de la empresa.',
            publico: false,
        );

        $resultado = app(AsistenteIA::class)->responder('la vpn no conecta', soloPublicos: true);

        $this->assertTrue($resultado['fuentes']->isEmpty(), 'un artículo privado no puede salir sin sesión');
        $this->assertSame(AsistenteIA::SIN_COBERTURA, $resultado['tipo']);
    }

    public function test_con_sesion_ese_mismo_articulo_si_se_alcanza(): void
    {
        $this->articulo(
            'La VPN no conecta desde la casa',
            'Revisa la conexion VPN hacia el servidor interno de la empresa.',
            publico: false,
        );

        $resultado = app(AsistenteIA::class)->responder('la vpn no conecta');

        $this->assertCount(1, $resultado['fuentes']);
    }

    public function test_sin_sesion_si_se_alcanza_un_articulo_publico(): void
    {
        $this->articulo(
            'Olvide mi contrasena',
            'Usa la opcion de recuperar contrasena en la pantalla de inicio.',
            publico: true,
        );

        $resultado = app(AsistenteIA::class)->responder('olvide mi contrasena', soloPublicos: true);

        $this->assertCount(1, $resultado['fuentes']);
        $this->assertSame('Olvide mi contrasena', $resultado['fuentes']->first()->title);
    }

    public function test_entre_varios_articulos_solo_salen_los_publicos(): void
    {
        // Los dos hablan de contraseñas, así que los dos calzan con la
        // consulta. Lo único que los separa es el interruptor.
        $this->articulo(
            'Olvide mi contrasena',
            'Recupera tu contrasena desde la pantalla de inicio.',
            publico: true,
        );
        $this->articulo(
            'Cambiar la contrasena del servidor de respaldos',
            'Ingresa al servidor de respaldos y cambia la contrasena del administrador.',
            publico: false,
        );

        $resultado = app(AsistenteIA::class)->responder('contrasena', soloPublicos: true);

        foreach ($resultado['fuentes'] as $articulo) {
            $this->assertTrue(
                $articulo->publico,
                "el artículo \"{$articulo->title}\" no es público y salió sin sesión"
            );
        }
    }

    public function test_un_articulo_publico_pero_inactivo_tampoco_sale(): void
    {
        // Desactivar un artículo tiene que bastar para sacarlo de circulación,
        // sin obligar a acordarse de desmarcar además lo de público.
        $articulo = $this->articulo(
            'Olvide mi contrasena',
            'Usa la opcion de recuperar contrasena en la pantalla de inicio.',
            publico: true,
        );
        $articulo->update(['is_active' => false]);

        $resultado = app(AsistenteIA::class)->responder('olvide mi contrasena', soloPublicos: true);

        $this->assertTrue($resultado['fuentes']->isEmpty());
    }

    public function test_un_articulo_nuevo_nace_privado(): void
    {
        // El valor por defecto es la única protección contra el olvido: si
        // alguien crea un artículo sobre un sistema interno y nadie piensa en
        // esto, no puede quedar expuesto por omisión.
        $articulo = Articulo::create([
            'title'     => 'Acceso al servidor de nomina',
            'content'   => 'Credenciales y procedimiento interno.',
            'is_active' => true,
        ]);

        $this->assertFalse((bool) $articulo->fresh()->publico);
    }

    // ── La ruta pública ───────────────────────────────────────────────

    public function test_la_ruta_publica_responde_sin_iniciar_sesion(): void
    {
        $this->articulo(
            'Olvide mi contrasena',
            'Usa la opcion de recuperar contrasena en la pantalla de inicio.',
            publico: true,
        );

        $this->postJson(route('asistente.publico'), ['pregunta' => 'olvide mi contrasena'])
            ->assertOk()
            ->assertJsonStructure(['tipo', 'texto', 'fuentes']);
    }

    public function test_la_ruta_publica_no_entrega_enlaces_ni_imagenes(): void
    {
        // Las rutas del artículo y de sus imágenes exigen sesión. Mandar un
        // enlace ahí devolvería a la persona al login del que quiere salir.
        $this->articulo(
            'Olvide mi contrasena',
            'Usa la opcion de recuperar contrasena en la pantalla de inicio.',
            publico: true,
        );

        $respuesta = $this->postJson(route('asistente.publico'), ['pregunta' => 'olvide mi contrasena']);

        $fuentes = $respuesta->json('fuentes');

        $this->assertNotEmpty($fuentes);
        foreach ($fuentes as $fuente) {
            $this->assertArrayHasKey('titulo', $fuente);
            $this->assertArrayNotHasKey('url', $fuente);
            $this->assertArrayNotHasKey('imagenes', $fuente);
        }
    }

    public function test_la_ruta_con_sesion_sigue_exigiendo_sesion(): void
    {
        $this->postJson(route('ayuda.asistente'), ['pregunta' => 'olvide mi contrasena'])
            ->assertUnauthorized();
    }

    public function test_la_pregunta_se_valida(): void
    {
        $this->postJson(route('asistente.publico'), ['pregunta' => 'no'])
            ->assertStatus(422);

        $this->postJson(route('asistente.publico'), [])
            ->assertStatus(422);
    }
}
