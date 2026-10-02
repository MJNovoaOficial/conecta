<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Administración de departamentos.
 *
 * No había ninguna prueba sobre estas pantallas, y la de edición se caía con
 * error 500 para todos: el botón "Volver" pedía una ruta con un nombre que no
 * existe. Se descubrió el día del lanzamiento, cuando hubo que configurar los
 * departamentos reales.
 */
class AdminDepartamentosTest extends TestCase
{
    use RefreshDatabase;

    private function administrador(): User
    {
        return User::factory()->administrador()->create();
    }

    public function test_el_administrador_ve_el_listado(): void
    {
        $this->actingAs($this->administrador())
            ->get(route('admin.departments.index'))
            ->assertOk();
    }

    public function test_el_administrador_abre_el_formulario_de_crear(): void
    {
        $this->actingAs($this->administrador())
            ->get(route('admin.departments.create'))
            ->assertOk();
    }

    public function test_el_administrador_crea_un_departamento(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('admin.departments.store'), [
                'name'         => 'Logistica',
                'description'  => 'Bodega y despacho',
                'default_role' => 'user',
            ])
            ->assertRedirect(route('admin.departments.index'));

        $this->assertDatabaseHas('departamentos', ['name' => 'Logistica', 'is_active' => true]);
    }

    public function test_el_administrador_abre_la_edicion_de_un_departamento(): void
    {
        // La prueba del error. La página cargaba bien hasta el botón "Volver",
        // que pedía route('admin.departments'): esa ruta no existe con ese
        // nombre, y toda la vista reventaba con RouteNotFoundException.
        $departamento = Department::create(['name' => 'Ventas', 'is_active' => true, 'default_role' => 'user']);

        $this->actingAs($this->administrador())
            ->get(route('admin.departments.edit', $departamento))
            ->assertOk()
            ->assertSee(route('admin.departments.index'), false);
    }

    public function test_el_administrador_actualiza_un_departamento(): void
    {
        $departamento = Department::create(['name' => 'Ventas', 'is_active' => true, 'default_role' => 'user']);

        $this->actingAs($this->administrador())
            ->put(route('admin.departments.update', $departamento), [
                'name'         => 'Ventas Nacionales',
                'description'  => 'Ventas en todo el pais',
                'default_role' => 'support',
                'is_active'    => 1,
            ])
            ->assertRedirect(route('admin.departments.index'));

        $departamento->refresh();

        $this->assertSame('Ventas Nacionales', $departamento->name);
        $this->assertSame('support', $departamento->default_role);
    }

    public function test_quien_no_es_administrador_no_entra(): void
    {
        foreach ([User::factory()->create(), User::factory()->soporte()->create()] as $usuario) {
            $this->actingAs($usuario)
                ->get(route('admin.departments.create'))
                ->assertForbidden();
        }
    }
}
