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
        Schema::create('attendance_log_details', function (Blueprint $table) {
        $table->id();
        $table->foreignId('attendance_log_id')->nullable()->constrained('attendance_logs')->onDelete('cascade');
        $table->date('date');
        $table->string('nik');
        $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
        $table->string('department_name');
        $table->string('employee_name');
        $table->dateTime('attendance_time');
        $table->string('attendance_type'); // in / out / break_in / break_out
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_log_details');
    }
};
