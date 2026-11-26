<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [
            [
                'employee_id'     => '1241',
                'nip'             => '000004321',
                'full_name'       => 'Nova Hendri',
                'department_code' => 'DP001',
                'department_name' => 'IT',
                'username'        => '1241',
                'fallback_email'  => 'nova@gmail.com',
                'role'            => 'IT',
            ],
            [
                'employee_id'     => '3010',
                'nip'             => '000018822',
                'full_name'       => 'Lionel Messi',
                'department_code' => 'DP002',
                'department_name' => 'HRD',
                'username'        => '3010',
                'fallback_email'  => 'lionel.3010@lokahr.local',
                'role'            => 'HR',
            ],
            [
                'employee_id'     => '3412',
                'nip'             => '224444442',
                'full_name'       => 'Ilham Kurniawan',
                'department_code' => 'DP003',
                'department_name' => 'Operasional',
                'username'        => '3412',
                'fallback_email'  => 'ilham.3412@lokahr.local',
                'role'            => 'OPERASIONAL',
            ],
        ];

        $hasDepartmentId   = Schema::hasColumn('users', 'department_id');
        $hasDepartmentName = Schema::hasColumn('users', 'department');
        $hasDepartmentCode = Schema::hasColumn('users', 'department_code');
        $hasRoleColumn     = Schema::hasColumn('users', 'role');

        foreach ($targets as $target) {
            $employee = Employee::with('department')
                ->where('employee_id', $target['employee_id'])
                ->orWhere('nip', $target['nip'])
                ->orWhere('full_name', $target['full_name'])
                ->first();

            $department = $employee?->department
                ?? Department::where('code', $target['department_code'])
                    ->orWhere('name', $target['department_name'])
                    ->first();

            $departmentId   = $employee?->department_id ?? $department?->id;
            $departmentName = $department?->name ?? $target['department_name'];
            $departmentCode = $department?->code ?? $target['department_code'];

            $username = $employee?->employee_id
                ?: ($employee?->nip ?: $target['username']);

            $email = !empty($employee?->email)
                ? $employee->email
                : $target['fallback_email'];

            $payload = [
                'name'     => $employee?->full_name ?? $target['full_name'],
                'username' => $username,
                'email'    => $email,
                'password' => Hash::make($employee?->nip ?? $target['nip'] ?? Str::random(12)),
            ];

            if ($hasDepartmentId) {
                $payload['department_id'] = $departmentId;
            }
            if ($hasDepartmentName) {
                $payload['department'] = $departmentName;
            }
            if ($hasDepartmentCode) {
                $payload['department_code'] = $departmentCode;
            }
            if ($hasRoleColumn) {
                $payload['role'] = $target['role'];
            }

            User::updateOrCreate(
                ['username' => $username],
                $payload
            );
        }
    }
}