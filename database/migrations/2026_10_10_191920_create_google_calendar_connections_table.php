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
        Schema::create('google_calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->string('google_email');
            $table->string('calendar_id')->default('primary');
            $table->string('calendar_name')->default('Calendario principal');
            $table->text('refresh_token');
            $table->foreignId('connected_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('connected_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_calendar_connections');
    }
};
