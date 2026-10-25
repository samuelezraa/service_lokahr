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
       Schema::create('sub_departments', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('department_code'); // 👈 Pastikan nama kolomnya department_code
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        // Opsional: Foreign key ke tabel departments
        $table->foreign('department_code')->references('code')->on('departments')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_departments');
    }
};
