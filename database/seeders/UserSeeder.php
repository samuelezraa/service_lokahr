<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun Administrator / IT
        User::updateOrCreate(
            ['email' => 'admin@lokahr.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'role' => 'IT',
            ]
        );

        // Akun Khusus HR
        User::updateOrCreate(
            ['email' => 'hr@lokahr.com'],
            [
                'name' => 'Staff HR Management',
                'username' => 'hrstaff',
                'password' => Hash::make('password123'),
                'role' => 'HR',
            ]
        );
    }
}