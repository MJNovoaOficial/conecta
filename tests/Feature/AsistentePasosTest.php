<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * DIMAKING muestra los pasos de la guía cuando el modelo no los explica.
 *
 * En producción el asistente corre sin servidor de modelos, y la respuesta
 * era solo el título de la guía: quien preguntaba tenía que salir del chat
 * para leerla, y sin sesión ni siquiera podía. Se reportó como "no conversa,
 * manda a páginas de manuales".
 *
 * Estas pruebas fijan qué viaja en la respuesta. Que la burbuja lo dibuje se
 * revisó en el navegador: es JavaScript y aquí no corre.
 */
class AsistentePasosTest extends TestCase
{
    use RefreshDatabase;

    private const PASOS = "1. Revisa que la impresora este encendida y con papel.\n"
                        . "2. Cancela los trabajos pendientes en la cola.";

    private function guia(bool $publico = false): Articulo
    {
        return Articulo::create([
            'title'     => 'La impresora no imprime',
            'symptoms'  => 'impresora no imprime, no sale la hoja',
            'content'   => self::PASOS,
            'is_active' => true,
            'publico'   => $publico,
        ]);
    }

    private function preguntar(string $pregunta)
    {
        return $this->actingAs(User::factory()->create())
            ->postJson(route('ayuda.asistente'), ['pregunta' => $pregunta]);
    }

    public function test_sin_modelo_la_respuesta_trae_los_pasos_de_la_guia(): void
    {
        config(['chatbot.enabled' => false]);
        $this->guia();

        $this->preguntar('la impresora no imprime')
            ->assertOk()
            ->assertJsonPath('tipo', 'solo_articulos')
            ->assertJsonPath('fuentes.0.titulo', 'La impresora no imprime')
            ->assertJsonPath('fuentes.0.pasos', self::PASOS);
    }

    public function test_sin_sesion_tambien_llegan_los_pasos_pero_no_enlaces(): void
    {
        // Es justo donde más faltaban: sin sesión el título ni siquiera es
        // un enlace, así que sin los pasos la persona no tenía nada que leer.
        config(['chatbot.enabled' => false]);
        $this->guia(publico: true);

        $respuesta = $this->postJson(route('asistente.publico'), ['pregunta' => 'la impresora no imprime'])
            ->assertOk()
            ->assertJsonPath('fuentes.0.pasos', self::PASOS);

        $this->assertArrayNotHasKey('url', $respuesta->json('fuentes.0'));
        $this->assertArrayNotHasKey('imagenes', $respuesta->json('fuentes.0'));
    }

    public function test_sin_sesion_nunca_viajan_los_pasos_de_un_articulo_privado(): void
    {
        // Ahora la respuesta lleva el contenido completo, no solo el título.
        // Si el filtro de públicos fallara, lo que se filtraría es el manual
        // interno entero, así que se revisa el texto crudo de la respuesta.
        config(['chatbot.enabled' => false]);
        Articulo::create([
            'title'     => 'Olvide mi contrasena',
            'content'   => 'Usa la opcion de recuperar contrasena en la pantalla de inicio.',
            'is_active' => true,
            'publico'   => true,
        ]);
        Articulo::create([
            'title'     => 'Cambiar la contrasena del servidor de respaldos',
            'content'   => 'Entra a SRV-RESPALDO-01 con la cuenta admin-backup.',
            'is_active' => true,
            'publico'   => false,
        ]);

        $respuesta = $this->postJson(route('asistente.publico'), ['pregunta' => 'contrasena'])
            ->assertOk();

        $this->assertNotEmpty($respuesta->json('fuentes'));
        $this->assertStringNotContainsString('SRV-RESPALDO-01', $respuesta->getContent());
    }

    public function test_si_el_modelo_no_responde_igual_llegan_los_pasos(): void
    {
        config(['chatbot.enabled' => true]);
        Http::fake(['*' => Http::response('', 500)]);
        $this->guia();

        $this->preguntar('la impresora no imprime')
            ->assertOk()
            ->assertJsonPath('tipo', 'no_disponible')
            ->assertJsonPath('fuentes.0.pasos', self::PASOS);
    }

    public function test_si_el_modelo_explico_no_se_repiten_los_pasos(): void
    {
        config(['chatbot.enabled' => true]);
        Http::fake(['*' => Http::response(['response' => 'Revisa que tenga papel y vuelve a imprimir.'])]);
        $this->guia();

        $respuesta = $this->preguntar('la impresora no imprime')
            ->assertOk()
            ->assertJsonPath('tipo', 'respuesta')
            ->assertJsonPath('texto', 'Revisa que tenga papel y vuelve a imprimir.');

        $this->assertArrayNotHasKey('pasos', $respuesta->json('fuentes.0'));
    }
}
