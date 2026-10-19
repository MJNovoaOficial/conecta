<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\Categoria;
use App\Models\Department;
use App\Models\Manual;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportAndSearchFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function reportFixtures(): array
    {
        $admin = User::factory()->administrador()->create();
        $agent = User::factory()->soporte()->create();
        $requester = User::factory()->create();
        $category = Categoria::create(['name' => 'Conexión y correo']);
        $otherCategory = Categoria::create(['name' => 'Equipos']);
        $subcategory = Subcategoria::create(['categoria_id' => $category->id, 'name' => 'Conexión']);
        $otherSubcategory = Subcategoria::create(['categoria_id' => $otherCategory->id, 'name' => 'Pantallas']);
        $department = Department::create(['name' => 'Operaciones', 'is_active' => true]);
        $otherDepartment = Department::create(['name' => 'Ventas', 'is_active' => true]);
        $selected = Ticket::create([
            'ticket_number' => 'TK-REPORT-SELECTED', 'title' => 'Único seleccionado: conexión',
            'description' => 'Conservar datos.', 'status' => 'in_progress', 'priority' => 'high',
            'user_id' => $requester->id, 'assigned_to' => $agent->id,
            'subcategoria_id' => $subcategory->id, 'department_id' => $department->id,
            'created_at' => '2026-10-10 12:00:00',
        ]);
        $other = Ticket::create([
            'ticket_number' => 'TK-REPORT-OTHER', 'title' => 'Otro caso no seleccionado',
            'description' => 'Conservar otros datos.', 'status' => 'open', 'priority' => 'low',
            'user_id' => $admin->id, 'assigned_to' => $admin->id,
            'subcategoria_id' => $otherSubcategory->id, 'department_id' => $otherDepartment->id,
            'created_at' => '2026-09-01 12:00:00',
        ]);
        $this->actingAs($admin);

        return [$selected, $other, $category, $agent, $requester, $department];
    }

    public static function filterScenarios(): array
    {
        return [
            'búsqueda por título con acentos' => ['search'],
            'búsqueda por número' => ['number'],
            'categoría' => ['category'],
            'todos los filtros combinados' => ['combined'],
            'sin filtros conserva todos los casos' => ['none'],
            'sin coincidencias' => ['empty'],
        ];
    }

    #[DataProvider('filterScenarios')]
    public function test_tres_exportaciones_reales_seleccionan_los_mismos_casos_del_listado(string $scenario): void
    {
        [$selected, $other, $category, $agent, $requester, $department] = $this->reportFixtures();
        $filters = match ($scenario) {
            'search' => ['search' => 'Único seleccionado'],
            'number' => ['search' => 'REPORT-SELECTED'],
            'category' => ['categoria_id' => $category->id],
            'combined' => [
                'search' => 'Único seleccionado', 'categoria_id' => $category->id,
                'status' => 'in_progress', 'priority' => 'high', 'agent_id' => $agent->id,
                'user_id' => $requester->id, 'department_id' => $department->id,
                'date_from' => '2026-10-10', 'date_to' => '2026-10-10',
            ],
            'empty' => ['search' => 'No existe este título'],
            default => [],
        };
        $before = Ticket::orderBy('id')->get()->map->getAttributes()->all();
        $expected = match ($scenario) {
            'none' => [$selected->ticket_number, $other->ticket_number],
            'empty' => [],
            default => [$selected->ticket_number],
        };
        $this->get(route('admin.reports.index', $filters))->assertOk()
            ->assertViewHas('tickets', fn ($tickets) => $tickets->pluck('ticket_number')->all() === $expected);

        $csv = $this->get(route('admin.reports.export', $filters))->assertOk();
        $csvPath = $csv->baseResponse->getFile()->getPathname();
        try {
            $rows = array_map(fn ($line) => str_getcsv($line, ';'), file($csvPath, FILE_IGNORE_NEW_LINES));
            $this->assertSame('Título', $rows[0][1]);
            $this->assertSame($expected, array_column(array_slice($rows, 1), 0));
        } finally {
            @unlink($csvPath);
        }

        $excel = $this->get(route('admin.reports.exportExcel', $filters))->assertOk();
        $excelPath = $excel->baseResponse->getFile()->getPathname();
        $workbook = null;
        try {
            $workbook = IOFactory::load($excelPath);
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('Título', $sheet->getCell('B1')->getValue());
            $numbers = [];
            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                $numbers[] = $sheet->getCell('A'.$row)->getValue();
            }
            $this->assertSame($expected, $numbers);
            if (in_array($selected->ticket_number, $expected, true)) {
                $this->assertSame($selected->title, $sheet->getCell('B2')->getValue());
                $this->assertSame($category->name, $sheet->getCell('F2')->getValue());
            }
        } finally {
            $workbook?->disconnectWorksheets();
            @unlink($excelPath);
        }

        $pdfNumbers = null;
        View::composer('admin.reports.pdf', function ($view) use (&$pdfNumbers) {
            $pdfNumbers = $view->getData()['tickets']->pluck('ticket_number')->all();
        });
        $pdf = $this->get(route('admin.reports.exportPdf', $filters))->assertOk();
        $pdfPath = $pdf->baseResponse->getFile()->getPathname();
        try {
            $this->assertSame('%PDF-', file_get_contents($pdfPath, false, null, 0, 5));
            $this->assertSame($expected, $pdfNumbers);
        } finally {
            @unlink($pdfPath);
        }
        $this->assertSame($before, Ticket::orderBy('id')->get()->map->getAttributes()->all());
    }

    public function test_cada_filtro_existente_se_conserva_y_la_busqueda_no_elude_la_categoria(): void
    {
        [$selected, $other, $category, $agent, $requester, $department] = $this->reportFixtures();
        foreach ([
            ['status' => 'in_progress'], ['priority' => 'high'], ['agent_id' => $agent->id],
            ['user_id' => $requester->id], ['department_id' => $department->id],
            ['date_from' => '2026-10-10'], ['date_to' => '2026-09-30'],
            ['search' => $other->ticket_number, 'categoria_id' => $category->id],
        ] as $filters) {
            $expected = isset($filters['search']) ? [] : [isset($filters['date_to']) ? $other->id : $selected->id];
            $this->get(route('admin.reports.index', $filters))->assertOk()
                ->assertViewHas('tickets', fn ($tickets) => $tickets->pluck('id')->all() === $expected);
        }
    }

    public function test_filtros_malformados_se_rechazan_en_listado_y_cada_exportacion_sin_guardar_datos(): void
    {
        $this->actingAs(User::factory()->administrador()->create());
        $invalid = [
            'search' => ['correo'], 'status' => ['open'], 'priority' => ['high'],
            'agent_id' => ['1'], 'categoria_id' => ['1'], 'user_id' => ['1'],
            'department_id' => ['1'], 'date_from' => '2026-02-30', 'date_to' => 'fecha inválida',
            'page' => ['1'],
        ];
        foreach (['admin.reports.index', 'admin.reports.export', 'admin.reports.exportExcel', 'admin.reports.exportPdf'] as $route) {
            $this->getJson(route($route, $invalid))->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid));
            $url = route($route, $invalid);
            $this->from($url)->get($url)->assertRedirect(route('admin.reports.index'))
                ->assertSessionHasErrors(array_keys($invalid));
            $this->get(route('admin.reports.index'))->assertOk();
        }
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_exportaciones_conservan_acceso_exclusivo_de_administradores(): void
    {
        foreach ([User::factory()->create(), User::factory()->soporte()->create()] as $user) {
            foreach (['admin.reports.export', 'admin.reports.exportExcel', 'admin.reports.exportPdf'] as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
        }
    }

    public function test_ayuda_manuales_y_sugerencias_rechazan_arrays_sin_bucle_de_redireccion(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([
            ['ayuda.index', ['q' => ['correo'], 'categoria_id' => ['1'], 'page' => ['1']], 'ayuda.index'],
            ['ayuda.sugerencias', ['q' => ['correo']], 'ayuda.index'],
            ['manuales.index', ['buscar' => ['correo'], 'categoria' => ['guía']], 'manuales.index'],
        ] as [$route, $filters, $fallback]) {
            $this->getJson(route($route, $filters))->assertUnprocessable()->assertJsonValidationErrors(array_keys($filters));
            $url = route($route, $filters);
            $this->from($url)->get($url)->assertRedirect(route($fallback))->assertSessionHasErrors(array_keys($filters));
            $this->get(route($fallback))->assertOk();
        }
        $this->assertDatabaseCount('articulos', 0);
        $this->assertDatabaseCount('manuales', 0);
    }

    public function test_ayuda_y_manuales_siguen_filtrando_y_no_muestran_elementos_inactivos(): void
    {
        $user = User::factory()->create();
        $category = Categoria::create(['name' => 'Correo']);
        $otherCategory = Categoria::create(['name' => 'Pantallas']);
        foreach ([['Correo principal', $category->id, true], ['Correo de otro grupo', $otherCategory->id, true],
            ['Correo inactivo', $category->id, false]] as [$title, $categoryId, $active]) {
            Articulo::create(['title' => $title, 'content' => 'Ayuda con el correo.', 'categoria_id' => $categoryId, 'is_active' => $active]);
            Manual::create(['titulo' => $title, 'categoria' => (string) $categoryId, 'archivo_path' => 'prueba.pdf',
                'archivo_nombre_original' => 'prueba.pdf', 'uploaded_by' => $user->id, 'is_active' => $active]);
        }
        $this->actingAs($user)->get(route('ayuda.index', ['q' => 'correo', 'categoria_id' => $category->id]))
            ->assertOk()->assertViewHas('articulos', fn ($articles) => $articles->pluck('title')->all() === ['Correo principal']);
        $this->get(route('manuales.index', ['buscar' => 'correo', 'categoria' => $category->id]))
            ->assertOk()->assertViewHas('manuales', fn ($manuals) => $manuals->pluck('titulo')->all() === ['Correo principal']);
        $this->getJson(route('ayuda.sugerencias', ['q' => 'cor']))->assertOk()->assertExactJson([]);
        $this->getJson(route('ayuda.sugerencias', ['q' => 'correo']))->assertOk()->assertJsonCount(2)
            ->assertJsonMissing(['title' => 'Correo inactivo']);
        $this->assertDatabaseCount('articulos', 3);
        $this->assertDatabaseCount('manuales', 3);
    }
}
