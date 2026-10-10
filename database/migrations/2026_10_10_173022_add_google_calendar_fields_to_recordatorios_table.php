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
        Schema::table('recordatorios', function (Blueprint $table) {
            $table->string('google_calendar_event_id')->nullable()->unique()->after('notificado_at');
            $table->boolean('google_calendar_incluir_creador')->default(false)->after('google_calendar_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recordatorios', function (Blueprint $table) {
            $table->dropUnique(['google_calendar_event_id']);
            $table->dropColumn(['google_calendar_event_id', 'google_calendar_incluir_creador']);
        });
    }
};
