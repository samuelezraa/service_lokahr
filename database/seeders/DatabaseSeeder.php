<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartemenSeeder::class,
            SubDepartemenSeeder::class,
            GradeSeeder::class,
            PosSeeder::class,
            WorkScheduleSchemeSeeder::class,
            AnnualEventSeeder::class,
            EmployeeSeeder::class,      
            CustomWorkHourSeeder::class,  
            ScheduleSeeder::class,
            AttendanceLogDetailSeeder::class,
            PeriodSeeder::class,
            EmployeeAttendanceSeeder::class,
        ]);
    }
}