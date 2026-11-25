<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\ActivityObserver;

// Import model yang masih memakai ActivityObserver (selain yang sudah pakai LogsActivity)
use App\Models\AttendanceLog;
use App\Models\AttendanceLogDetail;
use App\Models\CustomWorkHour;
use App\Models\Department;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;
use App\Models\Grade;
use App\Models\Period;
use App\Models\Position;
use App\Models\Schedule;
use App\Models\ScheduleDetail;
use App\Models\ScheduleScheme;
use App\Models\SubDepartment;
use App\Models\User;
use App\Models\WorkScheduleScheme;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
       
    }
}