<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\TipoIncidente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubcategoriasAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_puede_ver_la_opcion_de_subcategorias_y_sus_tipos(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'name' => 'Impresoras']);
        TipoIncidente::create(['subcategoria_id' => $subcategoria->id, 'name' => 'No imprime']);

        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.subcategorias.index'))
            ->assertOk()
            ->assertSee('Subcategorías')
            ->assertSee('Impresoras')
            ->assertSee('No imprime');
    }

    public function test_el_administrador_puede_crear_una_subcategoria_desde_su_pantalla(): void
    {
        $categoria = Categoria::create(['name' => 'Software']);

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.subcategorias.store-direct'), [
                'categoria_id' => $categoria->id,
                'name' => 'Licencias',
                'description' => 'Gestión de licencias de software.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subcategorias', [
            'categoria_id' => $categoria->id,
            'name' => 'Licencias',
            'is_active' => true,
        ]);
    }

    public function test_un_usuario_comun_no_puede_administrar_subcategorias(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.subcategorias.index'))
            ->assertForbidden();
    }
}
