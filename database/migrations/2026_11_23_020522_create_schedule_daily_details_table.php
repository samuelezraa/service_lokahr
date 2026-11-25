<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_daily_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->date('date');
            $table->string('day_of_week', 20);
            $table->string('attendance_status', 30)->default('Berangkat'); // Berangkat, Libur, PH, Izin, Cuti
            $table->time('scheduled_check_in')->default('08:00:00');
            $table->time('scheduled_check_out')->default('17:00:00');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_daily_details');
    }
};