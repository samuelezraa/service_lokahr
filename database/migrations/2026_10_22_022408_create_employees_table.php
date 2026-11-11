<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique()->nullable(); // ID Karyawan Kustom
            $table->string('nip')->unique(); // Ganti dari NIK ke NIP
            $table->string('employee_password'); // Default password adalah NIP (hashed / teks sesuai kebutuhan)
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable(); // Maksimal format tersimpan 62... (maks 14 karakter)
            
            // Relasi ke Master Data
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('sub_department_id')->nullable()->constrained('sub_departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->nullOnDelete();
            $table->foreignId('work_schedule_scheme_id')->nullable()->constrained('work_schedule_schemes')->nullOnDelete();
            
            // Tanggal
            $table->date('join_date')->nullable();
            $table->date('birth_date')->nullable();
            
            // Kolom Tambahan (Umur & Lama Bekerja hasil kalkulasi job)
            $table->integer('age_years')->nullable();          // Satuan Tahun
            $table->integer('work_duration_months')->nullable(); // Satuan Bulan
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};