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
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable(); // ID Periode (misal: "220001")
            $table->string('name');             // Nama Periode
            $table->date('start_date');
            $table->date('end_date');
            $table->time('late_tolerance')->default('00:15:00'); // Toleransi Keterlambatan
            $table->string('description')->default('Generate Successfully'); // Keterangan Default
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};