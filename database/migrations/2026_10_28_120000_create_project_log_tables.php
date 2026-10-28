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
            $parent = DB::selectOne('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [DB::connection()->getDatabaseName(), 'usuarios']);
            if (!$parent || strcasecmp($parent->ENGINE ?? '', 'InnoDB') !== 0) {
                throw new RuntimeException('La bitácora requiere usuarios en InnoDB. No se modificaron tablas existentes.');
            }
        }
        foreach (['project_log_projects', 'project_log_entries', 'project_log_attachments'] as $name) {
            if (Schema::hasTable($name)) throw new RuntimeException('Ya existe '.$name.'. Revisar la migración antes de continuar; no se sobrescriben datos.');
        }

        Schema::create('project_log_projects', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 150)->unique();
            $table->timestamps();
        });
        Schema::create('project_log_entries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('project_id')->constrained('project_log_projects')->restrictOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('author_name');
            $table->foreignId('responsible_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('responsible_name');
            $table->string('title', 180);
            $table->text('description');
            $table->string('type', 20);
            $table->date('performed_on');
            $table->uuid('submission_key')->unique();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['performed_on', 'id']);
            $table->index(['project_id', 'performed_on']);
        });
        Schema::create('project_log_attachments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('entry_id')->constrained('project_log_entries')->restrictOnDelete();
            $table->string('path');
            $table->string('original_name', 180);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['project_log_projects', 'project_log_entries', 'project_log_attachments'] as $name) {
            if (Schema::hasTable($name) && DB::table($name)->exists()) {
                throw new RuntimeException('La bitácora contiene registros. Desactivar PROJECT_LOG_ENABLED sin borrar información.');
            }
        }
        Schema::dropIfExists('project_log_attachments');
        Schema::dropIfExists('project_log_entries');
        Schema::dropIfExists('project_log_projects');
    }
};
