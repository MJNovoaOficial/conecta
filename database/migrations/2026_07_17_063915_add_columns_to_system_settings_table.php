<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('system_settings', 'type')) {
                $table->string('type', 20)->default('string')->after('value');
            }
            if (!Schema::hasColumn('system_settings', 'group')) {
                $table->string('group', 50)->default('general')->after('type');
            }
            if (!Schema::hasColumn('system_settings', 'label')) {
                $table->string('label', 255)->nullable()->after('group');
            }
            if (!Schema::hasColumn('system_settings', 'description')) {
                $table->text('description')->nullable()->after('label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['type', 'group', 'label', 'description']);
        });
    }
};
