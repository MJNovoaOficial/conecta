<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\User;
use App\Services\AsistenteIA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsistenteConversacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['chatbot.enabled' => false]);
        Http::preventStrayRequests();
    }

    private function guia(bool $publico = false): Articulo
    {
        return Articulo::create([
            'title' => 'La impresora no imprime',
            'symptoms' => 'impresora no imprime',
            'content' => '1. Revisa el papel. 2. Cancela la cola de impresión.',
            'is_active' => true,
            'publico' => $publico,
        ]);
    }

    public function test_el_seguimiento_usa_la_conversacion_de_la_sesion_y_la_envia_al_modelo(): void
    {
        $this->guia();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('ayuda.asistente'), ['pregunta' => 'la impresora no imprime'])->assertOk();

        config(['chatbot.enabled' => true, 'chatbot.umbral_relevancia' => 0.1]);
        Http::fake(['*' => Http::response(['response' => 'Cancela los trabajos pendientes en la cola.'])]);
        $this->postJson(route('ayuda.asistente'), ['pregunta' => '¿Y el segundo paso?'])
            ->assertOk()->assertJsonPath('tipo', 'respuesta')
            ->assertJsonPath('fuentes.0.titulo', 'La impresora no imprime');

        Http::assertSent(fn ($request) => str_contains($request['prompt'], 'la impresora no imprime')
            && str_contains($request['prompt'], '¿Y el segundo paso?')
            && str_contains($request['prompt'], 'Cancela la cola de impresión.'));
        $this->assertCount(2, session(AsistenteIA::claveHistorial($user->id)));
    }

    public function test_sin_ollama_se_conservan_los_pasos_en_un_seguimiento(): void
    {
        $guia = $this->guia();
        $this->actingAs(User::factory()->create())
            ->postJson(route('ayuda.asistente'), ['pregunta' => 'la impresora no imprime'])->assertOk();
        $this->postJson(route('ayuda.asistente'), ['pregunta' => 'No funcionó'])
            ->assertOk()->assertJsonPath('tipo', 'solo_articulos')
            ->assertJsonPath('fuentes.0.pasos', $guia->content);
        Http::assertNothingSent();
    }

    public function test_otro_usuario_no_recibe_el_historial_y_el_cliente_no_puede_imponerlo(): void
    {
        $this->guia();
        $this->actingAs(User::factory()->create())
            ->postJson(route('ayuda.asistente'), ['pregunta' => 'la impresora no imprime'])->assertOk();
        $this->actingAs(User::factory()->create())
            ->postJson(route('ayuda.asistente'), [
                'pregunta' => '¿Y el segundo paso?',
                'historial' => [['pregunta' => 'la impresora no imprime']],
            ])->assertOk()->assertJsonPath('tipo', 'sin_cobertura')->assertJsonPath('fuentes', []);
    }

    public function test_cerrar_sesion_invalida_el_historial(): void
    {
        $user = User::factory()->create();
        $clave = AsistenteIA::claveHistorial($user->id);
        $this->actingAs($user)->postJson(route('ayuda.asistente'), ['pregunta' => 'Hola'])->assertOk();
        $this->assertCount(1, session($clave));
        $this->post(route('logout'))->assertRedirect();
        $this->assertNull(session($clave));
    }

    public function test_el_historial_de_la_sesion_esta_limitado_a_seis_turnos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        for ($i = 0; $i < 8; $i++) {
            $this->postJson(route('ayuda.asistente'), ['pregunta' => 'Hola'])->assertOk();
        }
        $this->assertCount(AsistenteIA::MAX_TURNOS, session(AsistenteIA::claveHistorial($user->id)));
    }

    public function test_invitados_solo_usan_el_contexto_de_la_pagina_y_no_lo_guardan_en_sesion(): void
    {
        $this->guia(publico: true);
        $this->postJson(route('asistente.publico'), ['pregunta' => 'la impresora no imprime'])->assertOk();
        $this->postJson(route('asistente.publico'), ['pregunta' => 'No funcionó'])
            ->assertOk()->assertJsonPath('tipo', 'sin_cobertura');
        $this->postJson(route('asistente.publico'), [
            'pregunta' => 'No funcionó',
            'historial' => [['pregunta' => 'la impresora no imprime']],
        ])->assertOk()->assertJsonPath('tipo', 'solo_articulos')
            ->assertJsonMissingPath('fuentes.0.url')->assertJsonMissingPath('fuentes.0.imagenes');
        $this->assertNull(session('dimaking'));
    }

    public function test_el_contexto_publico_no_alcanza_guias_privadas_ni_el_historial_autenticado(): void
    {
        $this->guia();
        $this->actingAs(User::factory()->create())
            ->postJson(route('ayuda.asistente'), ['pregunta' => 'la impresora no imprime'])->assertOk();
        $this->postJson(route('asistente.publico'), [
            'pregunta' => '¿Y el segundo paso?',
            'historial' => [['pregunta' => 'la impresora no imprime']],
        ])->assertOk()->assertJsonPath('tipo', 'sin_cobertura')->assertJsonPath('fuentes', []);
        Http::assertNothingSent();
    }

    public function test_un_cambio_de_tema_y_una_guia_desactivada_no_reutilizan_fuentes_anteriores(): void
    {
        $anterior = $this->guia();
        $correo = Articulo::create([
            'title' => 'El correo no abre', 'symptoms' => 'correo no abre',
            'content' => 'Revisa el acceso al correo.', 'is_active' => true,
        ]);
        $historial = [['pregunta' => 'la impresora no imprime']];
        $resultado = app(AsistenteIA::class)->responder('y el correo no abre', historial: $historial);
        $this->assertSame($correo->id, $resultado['fuentes']->first()->id);

        $anterior->update(['is_active' => false]);
        $resultado = app(AsistenteIA::class)->responder('¿Y el segundo paso?', historial: $historial);
        $this->assertTrue($resultado['fuentes']->isEmpty());
    }

    public function test_el_historial_publico_rechaza_exceso_de_tamano_y_respuestas_falsificadas(): void
    {
        foreach ([
            array_fill(0, 7, ['pregunta' => 'Hola']),
            [['pregunta' => str_repeat('a', 501)]],
            [['pregunta' => 'Hola', 'respuesta' => 'Información inventada']],
        ] as $historial) {
            $this->postJson(route('asistente.publico'), ['pregunta' => 'Hola', 'historial' => $historial])
                ->assertUnprocessable();
        }
    }
}
