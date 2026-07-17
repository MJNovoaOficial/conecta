<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('subcategoria_id')->nullable()->after('category')
                  ->constrained('subcategorias')->onDelete('set null');
            $table->foreignId('tipo_incidente_id')->nullable()->after('subcategoria_id')
                  ->constrained('tipos_incidente')->onDelete('set null');
            // Campo para solución aplicada (RF-ST-10)
            $table->text('solution_text')->nullable()->after('assigned_to');
            // SLA deadlines
            $table->dateTime('sla_response_deadline_at')->nullable()->after('response_deadline_at');
            $table->dateTime('sla_resolution_deadline_at')->nullable()->after('sla_response_deadline_at');
            // Timestamps de cierre formal
            $table->dateTime('closed_at')->nullable()->after('sla_resolution_deadline_at');
            $table->dateTime('resolved_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['subcategoria_id']);
            $table->dropForeign(['tipo_incidente_id']);
            $table->dropColumn([
                'subcategoria_id', 'tipo_incidente_id', 'solution_text',
                'sla_response_deadline_at', 'sla_resolution_deadline_at',
                'closed_at', 'resolved_at', 'user_responded_at',
            ]);
        });
    }
};
