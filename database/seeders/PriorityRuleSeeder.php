<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\TipoIncidente;

class PriorityRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure there are categories, subcategories and tipos to reference.
        // This seeder assumes that Categoria, Subcategoria and TipoIncidente seeders have run.
        $cat1 = Categoria::first();
        $cat2 = Categoria::skip(1)->first();
        $sub1 = Subcategoria::where('categoria_id', $cat1->id ?? null)->first();
        $sub2 = Subcategoria::where('categoria_id', $cat2->id ?? null)->first();
        $tipo1 = TipoIncidente::where('subcategoria_id', $sub1->id ?? null)->first();

        DB::table('priority_rules')->insert([
            // Regla muy específica: categoría 1, subcategoría 1, tipo 1 → Prioridad crítica
            [
                'categoria_id' => $cat1->id ?? 1,
                'subcategoria_id' => $sub1->id ?? null,
                'tipo_incidente_id' => $tipo1->id ?? null,
                'priority' => 'critical',
                'description' => 'Regla crítica: categoría/subcategoría/tipo específico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Regla para categoría 1 y subcategoría 2 (cualquier tipo) → Prioridad alta
            [
                'categoria_id' => $cat1->id ?? 1,
                'subcategoria_id' => $sub2->id ?? null,
                'tipo_incidente_id' => null,
                'priority' => 'high',
                'description' => 'Regla alta: categoría 1 + subcategoría 2 (cualquier tipo)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Regla solo por categoría 2 (cualquier subcategoría y tipo) → Prioridad media
            [
                'categoria_id' => $cat2->id ?? 2,
                'subcategoria_id' => null,
                'tipo_incidente_id' => null,
                'priority' => 'medium',
                'description' => 'Regla media: categoría 2 (cualquier subcategoría y tipo)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Regla por defecto para categoría 3 → Prioridad baja
            [
                'categoria_id' => 3,
                'subcategoria_id' => null,
                'tipo_incidente_id' => null,
                'priority' => 'low',
                'description' => 'Regla baja por defecto para categoría 3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
