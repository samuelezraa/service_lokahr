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
        $itDept = Department::where('code', 'DP001')->orWhere('name', 'IT')->first() ?? Department::first();
        $hrDept = Department::where('code', 'DP002')->orWhere('name', 'HRD')->first() ?? Department::first();
        $opsDept = Department::where('code', 'DP003')->orWhere('name', 'like', '%Operasional%')->first() ?? Department::first();

        $subApp     = SubDepartment::where('code', 'SUB001')->orWhere('name', 'like', '%Frontend%')->orWhere('name', 'like', '%App%')->first() ?? SubDepartment::first();
        $subInfra   = SubDepartment::where('code', 'SUB002')->orWhere('name', 'like', '%Backend%')->orWhere('name', 'like', '%Infra%')->first() ?? SubDepartment::first();
        $subRecruit = SubDepartment::where('code', 'SUB003')->orWhere('name', 'like', '%Recruit%')->first() ?? SubDepartment::first();

        $posProgrammer = Position::where('code', 'POS01')->orWhere('title', 'like', '%Programmer%')->first() ?? Position::first();
        $posNetwork    = Position::where('code', 'POS02')->orWhere('title', 'like', '%Network%')->first() ?? Position::first();
        $posRecruit    = Position::where('code', 'POS03')->orWhere('title', 'like', '%Talent%')->first() ?? Position::first();

        $gradeOfficer = Grade::where('code', 'GR001')->orWhere('name', 'like', '%Staff%')->first() ?? Grade::first();
        $gradeSpv     = Grade::where('code', 'GR002')->orWhere('name', 'like', '%Senior%')->orWhere('name', 'like', '%Supervisor%')->first() ?? Grade::first();

        $scheme52 = WorkScheduleScheme::first();

        $employees = [
            [
                'nik'                     => '1001',
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
                'nik'                     => '1002',
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
                'nik'                     => '1003',
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
            // Data Tambahan Baru
            [
                'nik'                     => '1004',
                'full_name'               => 'Deni Setiawan',
                'email'                   => 'deni@lokahr.com',
                'phone'                   => '081344556677',
                'department_id'           => $itDept?->id,
                'sub_department_id'       => $subApp?->id,
                'position_id'             => $posProgrammer?->id,
                'grade_id'                => $gradeOfficer?->id,
                'work_schedule_scheme_id' => $scheme52?->id,
                'join_date'               => '2025-02-01',
                'birth_date'              => '1998-11-04',
                'is_active'               => true,
            ],
            [
                'nik'                     => '1005',
                'full_name'               => 'Eka Rahmawati',
                'email'                   => 'eka@lokahr.com',
                'phone'                   => '081355667788',
                'department_id'           => $hrDept?->id,
                'sub_department_id'       => $subRecruit?->id,
                'position_id'             => $posRecruit?->id,
                'grade_id'                => $gradeSpv?->id,
                'work_schedule_scheme_id' => $scheme52?->id,
                'join_date'               => '2024-11-10',
                'birth_date'              => '1995-07-18',
                'is_active'               => true,
            ],
            [
                'nik'                     => '1006',
                'full_name'               => 'Fajar Nugraha',
                'email'                   => 'fajar@lokahr.com',
                'phone'                   => '081366778899',
                'department_id'           => $opsDept?->id,
                'sub_department_id'       => $subInfra?->id,
                'position_id'             => $posNetwork?->id,
                'grade_id'                => $gradeOfficer?->id,
                'work_schedule_scheme_id' => $scheme52?->id,
                'join_date'               => '2025-08-20',
                'birth_date'              => '1999-03-25',
                'is_active'               => true,
            ],
            [
                'nik'                     => '1007',
                'full_name'               => 'Gita Permata',
                'email'                   => 'gita@lokahr.com',
                'phone'                   => '081377889900',
                'department_id'           => $opsDept?->id,
                'sub_department_id'       => $subInfra?->id,
                'position_id'             => $posNetwork?->id,
                'grade_id'                => $gradeOfficer?->id,
                'work_schedule_scheme_id' => $scheme52?->id,
                'join_date'               => '2026-02-14',
                'birth_date'              => '2001-09-09',
                'is_active'               => false,
            ],
            [
                'nik'                     => '1008',
                'full_name'               => 'Hendy Santoso',
                'email'                   => 'hendy@lokahr.com',
                'phone'                   => '081388990011',
                'department_id'           => $itDept?->id,
                'sub_department_id'       => $subApp?->id,
                'position_id'             => $posProgrammer?->id,
                'grade_id'                => $gradeSpv?->id,
                'work_schedule_scheme_id' => $scheme52?->id,
                'join_date'               => '2023-05-01',
                'birth_date'              => '1994-12-30',
                'is_active'               => true,
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