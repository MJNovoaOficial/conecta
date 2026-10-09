<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecondaryInterfacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_abre_la_edicion_del_departamento_y_puede_volver_al_listado(): void
    {
        $department = Department::create(['name' => 'Atencion Clientes', 'default_role' => 'support', 'is_active' => true]);
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.departments.edit', $department))->assertOk()
            ->assertSee('href="'.route('admin.departments.index').'"', false)
            ->assertSee('action="'.route('admin.departments.update', $department).'"', false);
    }

    public function test_editar_desde_el_modal_conserva_el_rol_del_departamento(): void
    {
        $department = Department::create(['name' => 'Atencion Clientes', 'default_role' => 'support', 'is_active' => true]);
        $this->actingAs(User::factory()->administrador()->create());
        $html = $this->get(route('admin.departments.index'))->assertOk()->getContent();
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $roleField = $xpath->query('//form[@id="editForm'.$department->id.'"]//input[@name="default_role"]')->item(0);
        $this->assertNotNull($roleField);
        $this->assertSame('support', $roleField->getAttribute('value'));
        $this->put(route('admin.departments.update', $department), [
            'name' => 'Atencion Clientes Nueva', 'description' => 'Descripcion actualizada.',
            'is_active' => '1', 'default_role' => $roleField->getAttribute('value'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.departments.index'));
        $this->assertSame('support', $department->fresh()->default_role);
        $this->assertSame('Atencion Clientes Nueva', $department->fresh()->name);
    }

    public function test_la_edicion_sigue_reservada_al_administrador(): void
    {
        $department = Department::create(['name' => 'Atencion Clientes', 'is_active' => true]);
        foreach (['user','support'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.departments.edit', $department))->assertForbidden();
        }
    }
}
