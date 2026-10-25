<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Position;
use App\Models\Grade;
use App\Models\WorkScheduleScheme;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $itDept = Department::where('code', 'DP001')->orWhere('name', 'IT')->first();
        $hrDept = Department::where('code', 'DP002')->orWhere('name', 'HRD')->first();

        $subApp = SubDepartment::where('code', 'SUB001')->orWhere('name', 'like', '%Frontend%')->orWhere('name', 'like', '%App%')->first();
        $subInfra = SubDepartment::where('code', 'SUB002')->orWhere('name', 'like', '%Backend%')->orWhere('name', 'like', '%Infra%')->first();
        $subRecruit = SubDepartment::where('code', 'SUB003')->orWhere('name', 'like', '%Recruit%')->first();

        // Cari POS dari data yang sudah ada (POS01, POS02, dll)
        $posProgrammer = Position::where('code', 'POS01')->orWhere('title', 'like', '%Programmer%')->first();
        $posNetwork    = Position::where('code', 'POS02')->orWhere('title', 'like', '%Network%')->first();
        $posRecruit    = Position::where('code', 'POS03')->orWhere('title', 'like', '%Talent%')->first();

        $gradeOfficer = Grade::where('code', 'GR001')->orWhere('name', 'like', '%Staff%')->first();
        $gradeSpv     = Grade::where('code', 'GR002')->orWhere('name', 'like', '%Senior%')->orWhere('name', 'like', '%Supervisor%')->first();

        $scheme52 = WorkScheduleScheme::first();

        $employees = [
            [
                'nik'                     => '123456',
                'full_name'               => 'Antika Lorien',
                'email'                   => 'antika@lokahr.com',
                'phone'                   => '081234567890',
                'department_id'           => $itDept?->id,
                'sub_department_id'       => $subApp?->id,
                'position_id'             => $posProgrammer?->id,
                'grade_id'                => $gradeOfficer?->id,
                'work_schedule_scheme_id' => $scheme52?->id,
                'join_date'               => '2026-01-01',
                'birth_date'              => '1999-01-01',
                'is_active'               => true,
            ],
            [
                'nik'                     => '123457',
                'full_name'               => 'Budi Pratama',
                'email'                   => 'budi@lokahr.com',
                'phone'                   => '081298765432',
                'department_id'           => $itDept?->id,
                'sub_department_id'       => $subInfra?->id,
                'position_id'             => $posNetwork?->id,
                'grade_id'                => $gradeSpv?->id,
                'work_schedule_scheme_id' => $scheme52?->id,
                'join_date'               => '2025-06-15',
                'birth_date'              => '1997-08-20',
                'is_active'               => true,
            ],
            [
                'nik'                     => '123458',
                'full_name'               => 'Citra Dewi',
                'email'                   => 'citra@lokahr.com',
                'phone'                   => '081311223344',
                'department_id'           => $hrDept?->id,
                'sub_department_id'       => $subRecruit?->id,
                'position_id'             => $posRecruit?->id,
                'grade_id'                => $gradeOfficer?->id,
                'work_schedule_scheme_id' => $scheme52?->id,
                'join_date'               => '2026-03-10',
                'birth_date'              => '2000-05-12',
                'is_active'               => false,
            ],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate(
                ['nik' => $employee['nik']],
                $employee
            );
        }
    }
}