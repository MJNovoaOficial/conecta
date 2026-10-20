<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql' && !DB::connection()->pretending()) {
            $parents = DB::select('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN (?, ?)',
                [DB::connection()->getDatabaseName(), 'tickets', 'usuarios']);
            if (count($parents) !== 2 || collect($parents)->contains(fn ($table) => strcasecmp($table->ENGINE ?? '', 'InnoDB') !== 0)) {
                throw new RuntimeException('No se creó la tabla de encargos: tickets y usuarios deben usar InnoDB. Verifica el servidor; esta migración no convierte ni altera tablas existentes.');
            }
        }
        // Tabla nueva: no reinterpretar asignaciones históricas ni actualizar
        // usuarios, roles, tickets, propuestas o archivos existentes.
        Schema::create('ticket_delegations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('delegate_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('responsible_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('delegated_by')->constrained('usuarios')->restrictOnDelete();
            // Fotografía del departamento al encargarlo; no condiciona las bajas
            // del catálogo ni cambia el departamento original del ticket.
            $table->unsignedBigInteger('department_id');
            $table->text('instructions');
            $table->enum('status', ['active', 'submitted', 'approved', 'revoked'])->default('active');
            $table->text('proposal_text')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('reopened_count')->default(0);
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();

            $table->index(['ticket_id', 'id']);
            $table->index(['delegate_id', 'status']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ticket_delegations')) return;
        if (DB::table('ticket_delegations')->exists()) {
            throw new RuntimeException('No se revierte la tabla de encargos porque contiene registros. Desactiva TICKET_DELEGATION_ENABLED sin borrar su historial.');
        }

        Schema::dropIfExists('ticket_delegations');
    }
};
