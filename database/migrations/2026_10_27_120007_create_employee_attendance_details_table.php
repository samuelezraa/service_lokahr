<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_attendance_id')->constrained('employee_attendances')->onDelete('cascade');
            $table->integer('day_no');
            $table->string('day_name');                 // Kamis, Jumat, dll.
            $table->date('date');                       // 2026-10-01
            $table->string('attendance_status')->default('Berangkat'); // Berangkat, Libur, PH, Alfa, Cuti
            $table->time('scheduled_check_in')->default('09:00:00');
            $table->time('scheduled_check_out')->default('18:00:00');
            $table->time('actual_check_in')->default('00:00:00');
            $table->time('actual_check_out')->default('00:00:00');
            $table->string('status')->default('-');     // Status kehadiran
            $table->string('notes')->nullable()->default('-'); // Keterangan dari HR
            $table->string('ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_details');
    }
};