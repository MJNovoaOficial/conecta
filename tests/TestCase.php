<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Nombres de base de datos sobre los que se permite correr las pruebas.
     *
     * Las pruebas vacían la base entera en cada corrida. Si por cualquier razón
     * la configuración terminara apuntando a la base de desarrollo o a la de
     * producción —una variable de entorno del sistema que pisa phpunit.xml, un
     * .env.testing mal copiado, un servidor con otra configuración— el
     * resultado sería perder todos los datos sin ningún aviso previo.
     */
    private const BASES_PERMITIDAS = ['conecta_testing', ':memory:'];

    /**
     * La comprobación va aquí y no en setUp(), y la diferencia importa.
     *
     * RefreshDatabase se engancha dentro de setUp() de la clase padre, así que
     * una comprobación puesta después de parent::setUp() corre cuando la base
     * YA está borrada: avisa del incendio desde adentro de la casa quemada.
     * Comprobado de la peor manera posible, borrando la base de desarrollo.
     *
     * refreshApplication() corre antes que los traits y con la configuración ya
     * cargada, que es exactamente el momento que se necesita.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $conexion = config('database.default');
        $base     = config("database.connections.{$conexion}.database");

        if (! in_array($base, self::BASES_PERMITIDAS, true)) {
            $this->fail(
                PHP_EOL
                . '  Las pruebas están apuntando a la base "' . $base . '", que no es una base de pruebas.' . PHP_EOL
                . '  Se detienen antes de tocar nada: correr aquí borraría todos los datos.' . PHP_EOL
                . '  Revisa DB_DATABASE en phpunit.xml. Permitidas: ' . implode(', ', self::BASES_PERMITIDAS) . '.'
                . PHP_EOL
            );
        }
    }
}
