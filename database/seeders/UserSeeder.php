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

        // Akun Admin Sales
        User::updateOrCreate(
            ['email' => 'admin.sales@lokahr.com'],
            [
                'name' => 'Admin Sales',
                'username' => 'AdminSales',
                'password' => Hash::make('password123'),
                'role' => 'SALES',
            ]
        );

        // Akun Admin Management
        User::updateOrCreate(
            ['email' => 'admin.management@lokahr.com'],
            [
                'name' => 'Admin Management',
                'username' => 'AdminManagement',
                'password' => Hash::make('password123'),
                'role' => 'MANAGEMENT',
            ]
        );
    }
}