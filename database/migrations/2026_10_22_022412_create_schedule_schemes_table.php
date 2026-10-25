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
        Schema::create('schedule_schemes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
        $table->foreignId('work_schedule_scheme_id')->constrained('work_schedule_schemes')->onDelete('cascade');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_schemes');
    }
};
