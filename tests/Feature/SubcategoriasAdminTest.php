<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\TipoIncidente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubcategoriasAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_abre_la_categoria_y_ve_sus_subcategorias_y_tipos(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'name' => 'Impresoras']);
        TipoIncidente::create(['subcategoria_id' => $subcategoria->id, 'name' => 'No imprime']);

        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('category-modal-'.$categoria->id)
            ->assertSee('openCategory('.$categoria->id.')')
            ->assertSee('Impresoras')
            ->assertSee('No imprime')
            ->assertSee(route('admin.subcategorias.store', $categoria))
            ->assertSee(route('admin.subcategorias.update', $subcategoria))
            ->assertSee('subcategory-edit-'.$subcategoria->id)
            ->assertSee(route('admin.tipos.store', $subcategoria));
    }

    public function test_el_administrador_agrega_una_subcategoria_a_la_categoria_abierta(): void
    {
        $categoria = Categoria::create(['name' => 'Software']);

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.subcategorias.store', $categoria), [
                'name' => 'Licencias',
                'description' => 'Gestión de licencias de software.',
            ])
            ->assertRedirect()
            ->assertSessionHas('open_category', $categoria->id);

        $this->assertDatabaseHas('subcategorias', [
            'categoria_id' => $categoria->id,
            'name' => 'Licencias',
            'is_active' => true,
        ]);
    }

    public function test_el_tipo_nuevo_queda_vinculado_a_su_subcategoria(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'name' => 'Impresoras']);

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.tipos.store', $subcategoria), ['name' => 'No imprime'])
            ->assertRedirect()
            ->assertSessionHas('open_category', $categoria->id);

        $this->assertDatabaseHas('tipos_incidente', [
            'subcategoria_id' => $subcategoria->id,
            'name' => 'No imprime',
        ]);
    }

    public function test_un_error_de_validacion_reabre_la_categoria(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $url = route('admin.categories.index');

        $this->actingAs(User::factory()->administrador()->create())
            ->from($url)
            ->post(route('admin.subcategorias.store', $categoria), [
                'name' => '',
                'category_context' => $categoria->id,
                'form_context' => 'category-'.$categoria->id,
            ])
            ->assertRedirect($url)
            ->assertSessionHasErrors('name');

        $this->get($url)
            ->assertOk()
            ->assertSee('openCategory('.$categoria->id.');', false);
    }

    public function test_al_eliminar_un_tipo_se_mantiene_abierta_su_categoria(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'name' => 'Impresoras']);
        $tipo = TipoIncidente::create(['subcategoria_id' => $subcategoria->id, 'name' => 'No imprime']);

        $this->actingAs(User::factory()->administrador()->create())
            ->delete(route('admin.tipos.destroy', $tipo))
            ->assertRedirect()
            ->assertSessionHas('open_category', $categoria->id);

        $this->assertDatabaseMissing('tipos_incidente', ['id' => $tipo->id]);
    }

    public function test_se_edita_una_subcategoria_con_tickets_desde_su_categoria(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'name' => 'Impresoras']);
        $ticket = Ticket::create([
            'ticket_number' => 'TK-0000012345',
            'user_id' => User::factory()->create()->id,
            'title' => 'Impresora de red',
            'description' => 'No conecta',
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'medium',
            'subcategoria_id' => $subcategoria->id,
        ]);
        $this->actingAs(User::factory()->administrador()->create());

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Impresoras')
            ->assertSee('subcategory-edit-'.$subcategoria->id)
            ->assertSee(route('admin.subcategorias.update', $subcategoria));

        $this->from(route('admin.categories.index'))
            ->put(route('admin.subcategorias.update', $subcategoria), [
                'name' => 'Impresoras de red',
                'description' => 'Equipos compartidos',
                'is_active' => 0,
            ])->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('open_category', $categoria->id);

        $this->assertDatabaseHas('subcategorias', [
            'id' => $subcategoria->id,
            'name' => 'Impresoras de red',
            'description' => 'Equipos compartidos',
            'is_active' => 0,
        ]);
        $this->assertSame($subcategoria->id, $ticket->fresh()->subcategoria_id);

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Impresoras de red')
            ->assertSee('Inactiva')
            ->assertDontSee('href="'.route('admin.subcategorias.index').'"', false);

        $this->put(route('admin.subcategorias.update', $subcategoria), [
            'name' => 'Impresoras de red',
            'description' => 'Equipos compartidos',
            'is_active' => 1,
        ])->assertRedirect();
        $this->assertSame(1, (int) $subcategoria->fresh()->is_active);
        $this->assertSame($subcategoria->id, $ticket->fresh()->subcategoria_id);
    }

    public function test_un_error_al_editar_reabre_la_categoria_y_conserva_el_formulario(): void
    {
        $categoria = Categoria::create(['name' => 'Hardware']);
        $subcategoria = Subcategoria::create(['categoria_id' => $categoria->id, 'name' => 'Impresoras']);
        $url = route('admin.categories.index');

        $this->actingAs(User::factory()->administrador()->create())
            ->from($url)
            ->put(route('admin.subcategorias.update', $subcategoria), [
                'name' => '',
                'description' => 'Texto sin guardar',
                'is_active' => 0,
                'category_context' => $categoria->id,
                'form_context' => 'edit-'.$subcategoria->id,
            ])
            ->assertRedirect($url)
            ->assertSessionHasErrors('name');

        $this->get($url)
            ->assertOk()
            ->assertSee('openCategory('.$categoria->id.');', false)
            ->assertSee('id="subcategory-edit-'.$subcategoria->id.'"', false)
            ->assertSee('Texto sin guardar');

        $this->assertSame('Impresoras', $subcategoria->fresh()->name);
    }

    public function test_la_url_antigua_redirige_a_categorias_sin_pagina_duplicada(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.subcategorias.index'))
            ->assertRedirect(route('admin.categories.index'));
    }

    public function test_un_usuario_comun_no_puede_administrar_categorias(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.categories.index'))->assertForbidden();
        $this->get(route('admin.subcategorias.index'))->assertForbidden();

        $subcategoria = Subcategoria::create([
            'categoria_id' => Categoria::create(['name' => 'Hardware'])->id,
            'name' => 'Impresoras',
        ]);
        $this->put(route('admin.subcategorias.update', $subcategoria), [
            'name' => 'Modificada',
            'is_active' => 0,
        ])->assertForbidden();
        $this->assertSame('Impresoras', $subcategoria->fresh()->name);
    }
}
