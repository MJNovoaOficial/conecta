<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renombrar tablas del sistema de inglés a español.
     * Las tablas internas de Laravel (cache, jobs, sessions, etc.) se mantienen igual.
     * Cada rename es idempotente: solo se ejecuta si la tabla origen existe.
     */
    public function up(): void
    {
        $this->safeRename('departments',        'departamentos');
        $this->safeRename('users',              'usuarios');
        // tickets → tickets: sin cambio (anglicismo aceptado)
        $this->safeRename('ticket_comments',    'comentarios_ticket');
        $this->safeRename('ticket_histories',   'historial_ticket');
        $this->safeRename('ticket_attachments', 'adjuntos_ticket');
        $this->safeRename('ticket_assignments', 'asignaciones_ticket');
    }

    /**
     * Revertir — devuelve los nombres originales en inglés.
     */
    public function down(): void
    {
        $this->safeRename('departamentos',       'departments');
        $this->safeRename('usuarios',            'users');
        $this->safeRename('comentarios_ticket',  'ticket_comments');
        $this->safeRename('historial_ticket',    'ticket_histories');
        $this->safeRename('adjuntos_ticket',     'ticket_attachments');
        $this->safeRename('asignaciones_ticket', 'ticket_assignments');
    }

    /**
     * Renombra una tabla solo si la tabla origen existe y la destino NO existe.
     * Esto hace la migración idempotente ante fallos parciales.
     */
    private function safeRename(string $from, string $to): void
    {
        if (Schema::hasTable($from) && !Schema::hasTable($to)) {
            Schema::rename($from, $to);
        }
    }
};
