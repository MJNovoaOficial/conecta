<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Campos para tickets de invitados y respuesta del usuario
        Schema::table('tickets', function (Blueprint $table) {
            // Campos de invitado
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_department')->nullable()->after('guest_email');

            // Token para que invitados puedan ver su ticket
            $table->string('guest_token')->nullable()->unique()->after('guest_department');

            // Fecha de respuesta del usuario
            $table->dateTime('user_responded_at')->nullable()->after('response_deadline_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'guest_name',
                'guest_email',
                'guest_department',
                'guest_token',
                'user_responded_at',
            ]);
        });
    }
};
