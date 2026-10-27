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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // Nama Template / Rekapitulasi Schedule
            $table->string('name')->nullable();

            // Periode & Karyawan
            $table->foreignId('period_id')->nullable()->constrained('periods')->onDelete('cascade');
            $table->string('period_code')->nullable();
            
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->string('nip')->nullable();
            $table->string('employee_name')->nullable();
            
            // Departemen & Sub Departemen
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('department_name')->nullable();
            
            $table->foreignId('sub_department_id')->nullable()->constrained('sub_departments')->onDelete('set null');
            $table->string('sub_department_name')->nullable();
            
            // Skema Kerja
            $table->foreignId('work_schedule_scheme_id')->nullable()->constrained('work_schedule_schemes')->onDelete('set null');
            $table->string('work_scheme')->nullable();

            // Rekapitulasi Hari & Presensi
            $table->integer('total_days')->default(0);
            $table->integer('work_days')->default(0);
            $table->integer('off_days')->default(0);
            $table->integer('ph_days')->default(0);
            $table->integer('permit_days')->default(0);
            $table->integer('alpha_days')->default(0);
            $table->integer('sick_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->integer('late_days')->default(0);

            // Ref & Status
            $table->string('ref')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};