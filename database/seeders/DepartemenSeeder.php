<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartemenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('departments')->insert([
            [
                'code'       => 'DP001',
                'name'       => 'IT',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code'       => 'DP002',
                'name'       => 'HRD',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code'       => 'DP003',
                'name'       => 'Operasional',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}