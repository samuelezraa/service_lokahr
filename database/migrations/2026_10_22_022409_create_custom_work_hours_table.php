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
            $table->string('name');
            $table->time('clock_in');
            $table->time('clock_out');
            $table->time('tolerance')->default('00:00:00');
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