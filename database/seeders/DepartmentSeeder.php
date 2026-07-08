<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'IT', 'description' => 'Departamento de Tecnología de la Información'],
            ['name' => 'Recursos Humanos', 'description' => 'Departamento de Recursos Humanos'],
            ['name' => 'Ventas', 'description' => 'Departamento de Ventas'],
            ['name' => 'Contabilidad', 'description' => 'Departamento de Contabilidad'],
            ['name' => 'Operaciones', 'description' => 'Departamento de Operaciones'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
