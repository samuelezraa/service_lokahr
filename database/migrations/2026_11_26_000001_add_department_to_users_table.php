<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('username')
                    ->constrained('departments')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')
                    ->nullable()
                    ->after('department_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }

            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
        });
    }
};