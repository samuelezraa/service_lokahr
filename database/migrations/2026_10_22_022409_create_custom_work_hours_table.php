<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_work_hours', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ID otomatis (misal: CUS001)
            $table->date('start_date');
            $table->date('end_date');
            $table->string('work_scheme'); // Skema Hari Kerja (misal: 5-2)
            $table->time('clock_in');
            $table->time('tolerance')->default('00:05:00');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_work_hours');
    }
};