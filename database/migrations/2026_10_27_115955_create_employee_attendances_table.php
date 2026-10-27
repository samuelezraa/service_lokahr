<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('period_code');              // e.g. "2026-10"
            $table->string('nip');                      // e.g. "99-9999-99"
            $table->string('employee_name');            // Direct column
            $table->string('department_name')->nullable();
            $table->string('sub_department_name')->nullable();
            $table->string('pos_name')->nullable();
            $table->string('grade_name')->nullable();
            $table->string('work_scheme')->default('5-2');
            
            // Rekapitulasi Angka
            $table->integer('total_off_days')->default(0);
            $table->integer('total_ph_days')->default(0);
            $table->integer('total_permit_days')->default(0);
            $table->integer('total_alpha_days')->default(0);
            $table->integer('total_sick_days')->default(0);
            $table->integer('total_leave_days')->default(0);
            $table->integer('total_late_days')->default(0);
            $table->integer('total_work_days')->default(0);
            $table->integer('total_period_days')->default(31);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};