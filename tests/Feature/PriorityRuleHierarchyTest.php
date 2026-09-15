<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\PriorityRule;
use App\Models\Subcategoria;
use App\Models\TipoIncidente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityRuleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_puede_asociar_una_subcategoria_de_otra_categoria(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $otraCategoria = Categoria::create(['name' => 'Software']);
        $subcategoriaAjena = Subcategoria::create([
            'categoria_id' => $otraCategoria->id,
            'name' => 'Licencias',
        ]);

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.priority-rules.store'), [
                'categoria_id' => $categoria->id,
                'subcategoria_id' => $subcategoriaAjena->id,
                'priority' => 'high',
            ])
            ->assertSessionHasErrors('subcategoria_id');

        $this->assertDatabaseCount(PriorityRule::class, 0);
    }

    public function test_no_se_puede_asociar_un_tipo_de_otra_subcategoria(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $subcategoria = Subcategoria::create([
            'categoria_id' => $categoria->id,
            'name' => 'Impresoras',
        ]);
        $otraSubcategoria = Subcategoria::create([
            'categoria_id' => $categoria->id,
            'name' => 'Monitores',
        ]);
        $tipoAjeno = TipoIncidente::create([
            'subcategoria_id' => $otraSubcategoria->id,
            'name' => 'Pantalla negra',
        ]);

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.priority-rules.store'), [
                'categoria_id' => $categoria->id,
                'subcategoria_id' => $subcategoria->id,
                'tipo_incidente_id' => $tipoAjeno->id,
                'priority' => 'high',
            ])
            ->assertSessionHasErrors('tipo_incidente_id');

        $this->assertDatabaseCount(PriorityRule::class, 0);
    }
}
