<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\ActivityObserver;

// Import semua model yang ada di folder app/Models/
use App\Models\AnnualEvent;
use App\Models\AttendanceLog;
use App\Models\AttendanceLogDetail;
use App\Models\CustomWorkHour;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;
use App\Models\Grade;
use App\Models\LogHistory;
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
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   public function boot(): void
    {
        // Daftarkan Observer Global Spatie Activitylog ke seluruh model
        AnnualEvent::observe(ActivityObserver::class);
        AttendanceLog::observe(ActivityObserver::class);
        AttendanceLogDetail::observe(ActivityObserver::class);
        CustomWorkHour::observe(ActivityObserver::class);
        Department::observe(ActivityObserver::class);
        Employee::observe(ActivityObserver::class);
        EmployeeAttendance::observe(ActivityObserver::class);
        EmployeeAttendanceDetail::observe(ActivityObserver::class);
        Grade::observe(ActivityObserver::class);
        LogHistory::observe(ActivityObserver::class);
        Period::observe(ActivityObserver::class);
        Position::observe(ActivityObserver::class);
        Schedule::observe(ActivityObserver::class);
        ScheduleDetail::observe(ActivityObserver::class);
        ScheduleScheme::observe(ActivityObserver::class);
        SubDepartment::observe(ActivityObserver::class);
        User::observe(ActivityObserver::class);
        WorkScheduleScheme::observe(ActivityObserver::class);
    }
}
