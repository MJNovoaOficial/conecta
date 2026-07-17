<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuración SLA por prioridad
        Schema::create('sla_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->unique();
            $table->unsignedSmallInteger('response_hours')->default(24);   // horas para primera respuesta
            $table->unsignedSmallInteger('resolution_hours')->default(72); // horas para resolución
            $table->timestamps();
        });

        // Configuración general del sistema
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Log de auditoría
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('action');           // e.g. 'ticket.created', 'user.updated'
            $table->string('model')->nullable(); // e.g. 'Ticket'
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('details')->nullable(); // cambios realizados
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // Notificaciones in-app
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('type');             // 'assigned', 'commented', 'status_changed', etc.
            $table->string('title');
            $table->text('body')->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('cascade');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('sla_configs');
    }
};
