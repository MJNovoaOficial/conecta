<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\Department;
use App\Models\Manual;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketDelegationMigrationSafetyTest extends TestCase
{
    // Real DDL, outside RefreshDatabase's transaction. Tests\TestCase checks
    // the database allowlist BEFORE this trait runs. Never execute in production.
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
        // The test exercises only the new migration's up/down. Do not execute
        // unrelated historical down() methods as suite cleanup: an old TEXT
        // default rollback is unsupported by this MySQL. The next database
        // test must instead rebuild its disposable, allowlisted database.
        $this->beforeApplicationDestroyed(function () {
            RefreshDatabaseState::$migrated = false;
        });
    }

    public function test_migracion_aditiva_conserva_filas_esquemas_usuarios_conocimiento_y_archivos_existentes(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Department::create(['name' => 'Departamento existente', 'is_active' => true]);
        $ticket = Ticket::create(['ticket_number' => 'HISTORICO-MIGRACION', 'user_id' => $user->id,
            'title' => 'No cambiar este caso', 'description' => 'Contenido anterior.', 'status' => 'open', 'priority' => 'medium']);
        TicketComment::create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'comment' => 'Respuesta anterior conservada.']);
        TicketAttachment::create(['ticket_id' => $ticket->id, 'file_path' => 'qa/historico.txt',
            'file_name' => 'historico.txt', 'file_type' => 'text/plain', 'file_size' => 18, 'uploaded_by' => $user->id]);
        Articulo::create(['title' => 'Conocimiento del asistente', 'content' => 'Instrucciones que ya existían.',
            'created_by' => $user->id, 'is_active' => true, 'publico' => false]);
        Manual::create(['titulo' => 'Manual conservado', 'archivo_path' => 'qa/manual.pdf',
            'archivo_nombre_original' => 'manual.pdf', 'uploaded_by' => $user->id, 'is_active' => true]);
        SystemSetting::create(['key' => 'custom-existing', 'value' => 'no reemplazar', 'type' => 'string', 'group' => 'general']);
        Storage::disk('local')->put('qa/historico.txt', 'Archivo conservado');
        Storage::disk('local')->put('qa/manual.pdf', 'PDF de prueba conservado');
        $tables = ['usuarios', 'departamentos', 'tickets', 'comentarios_ticket', 'adjuntos_ticket', 'articulos', 'manuales', 'system_settings'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = ['columns' => Schema::getColumnListing($table), 'rows' => DB::table($table)->orderBy('id')->get()->toJson()];
        }
        $migration = require database_path('migrations/2026_10_19_210000_create_ticket_delegations_table.php');
        // Simulate deployment over existing records; only this EMPTY new table
        // is removed and recreated, never a legacy table or persisted user data.
        $migration->down();
        $this->assertFalse(Schema::hasTable('ticket_delegations'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('ticket_delegations'));
        $this->assertDatabaseCount('ticket_delegations', 0);
        foreach ($tables as $table) {
            $this->assertSame($before[$table]['columns'], Schema::getColumnListing($table));
            $this->assertSame($before[$table]['rows'], DB::table($table)->orderBy('id')->get()->toJson());
        }
        $this->assertSame('Archivo conservado', Storage::disk('local')->get('qa/historico.txt'));
        $this->assertSame('PDF de prueba conservado', Storage::disk('local')->get('qa/manual.pdf'));
    }
}
