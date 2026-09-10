<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La búsqueda de la base de conocimiento.
 *
 * La usan tres lugares: el asistente, las sugerencias mientras se escribe el
 * asunto de un ticket y el buscador del panel de administración. Cuando falla
 * no hay error: la persona simplemente no encuentra el artículo que existe y
 * abre un ticket que se podría haber evitado.
 *
 * Estas pruebas corren sobre MySQL porque las siglas se buscan con REGEXP, que
 * SQLite no tiene.
 */
class BusquedaArticulosTest extends TestCase
{
    use RefreshDatabase;

    private function articulo(string $titulo, string $sintomas, string $contenido): Articulo
    {
        return Articulo::create([
            'title'     => $titulo,
            'symptoms'  => $sintomas,
            'content'   => $contenido,
            'is_active' => true,
        ]);
    }

    private function impresora(): Articulo
    {
        return $this->articulo(
            'La impresora no imprime',
            'impresora no imprime, no sale la hoja, trabajo en cola',
            'Verifica que la impresora esté encendida y que tenga papel.'
        );
    }

    // ── Formas distintas de la misma palabra ──────────────────────────

    public function test_no_puedo_imprimir_encuentra_el_articulo_de_la_impresora(): void
    {
        // El caso que motivó el cambio. "imprimir" no aparece en ninguna parte
        // del artículo —dice "imprime" e "impresora"—, así que comparando el
        // texto tal cual no calzaba nada. Y era el ejemplo que la propia
        // burbuja de ayuda le sugería escribir a la persona.
        $impresora = $this->impresora();
        $this->articulo('El monitor no enciende', 'pantalla negra, sin imagen', 'Revisa el cable de video.');

        $resultado = Articulo::activos()->conPuntaje('no puedo imprimir')->get();

        $this->assertCount(1, $resultado);
        $this->assertTrue($resultado->first()->is($impresora));
    }

    public function test_las_sugerencias_del_formulario_tambien_la_encuentran(): void
    {
        // Las sugerencias usan buscar() y no conPuntaje(). Las dos pasan por la
        // misma comparación, pero conviene probar las dos vías por separado.
        $this->impresora();

        $this->actingAs(User::factory()->create())
            ->getJson(route('ayuda.sugerencias', ['q' => 'no puedo imprimir']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'La impresora no imprime']);
    }

    public function test_la_raiz_calza_con_las_otras_formas_de_la_palabra(): void
    {
        $this->assertSame('imprim', Articulo::raiz('imprimir'));
        $this->assertStringContainsString(Articulo::raiz('imprimir'), 'imprime');

        // "-isimo" tiene que cortarse antes que "-o", o "lentisimo" quedaría
        // en "lentisim" y no calzaría con "lento".
        $this->assertSame('lent', Articulo::raiz('lentisimo'));
        $this->assertStringContainsString(Articulo::raiz('lentisimo'), 'va lento');

        $this->assertStringContainsString(Articulo::raiz('trabajar'), 'trabajo remoto');
    }

    public function test_la_raiz_nunca_queda_de_menos_de_cuatro_letras(): void
    {
        // Una raíz muy corta calza dentro de cualquier cosa. "sonido" no puede
        // quedar en "son", que aparece en medio de "persona" o "razón".
        $this->assertSame('sonid', Articulo::raiz('sonido'));

        // Las palabras cortas no se tocan: suelen ser nombres propios o siglas.
        $this->assertSame('wifi', Articulo::raiz('wifi'));
        $this->assertSame('pdf', Articulo::raiz('pdf'));
        $this->assertSame('vpn', Articulo::raiz('vpn'));
    }

    // ── Lo que no debe cambiar ────────────────────────────────────────

    public function test_las_siglas_de_dos_letras_siguen_calzando_como_palabra_completa(): void
    {
        // Buscar "ip" como fragmento calzaba dentro de "equipo". La raíz no
        // aplica a las siglas: siguen buscándose como palabra entera.
        $ip = $this->articulo(
            'Cómo ver mi dirección IP',
            'direccion ip, mi ip',
            'Abre la consola y escribe el comando.'
        );
        $this->articulo('El equipo no enciende', 'equipo apagado', 'Revisa el cable del equipo.');

        $resultado = Articulo::activos()->conPuntaje('cual es mi ip')->get();

        $this->assertCount(1, $resultado, 'el artículo del equipo no debería calzar con "ip"');
        $this->assertTrue($resultado->first()->is($ip));
    }

    public function test_queda_no_cuenta_como_palabra_de_busqueda(): void
    {
        // Antes, "dónde queda Recursos Humanos" encontraba el artículo de los
        // programas que "se quedan cargando", solo por esa palabra.
        $this->articulo(
            'Un programa no abre o se queda cargando',
            'programa no abre, se queda cargando',
            'Cierra el programa desde el administrador de tareas.'
        );

        $this->assertTrue(
            Articulo::activos()->conPuntaje('donde queda recursos humanos')->get()->isEmpty()
        );
    }

    public function test_una_consulta_ajena_a_soporte_no_encuentra_nada(): void
    {
        $this->impresora();
        $this->articulo('El monitor no enciende', 'pantalla negra, sin imagen', 'Revisa el cable de video.');

        $this->assertTrue(
            Articulo::activos()->conPuntaje('cuando me pagan el sueldo')->get()->isEmpty()
        );
    }
}
