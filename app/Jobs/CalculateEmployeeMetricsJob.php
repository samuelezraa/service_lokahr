<?php

namespace App\Jobs;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class CalculateEmployeeMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $employees = Employee::all();
        $now = Carbon::now();

        foreach ($employees as $employee) {
            $ageYears = null;
            $workMonths = null;

            if ($employee->getRawOriginal('birth_date')) {
                $birthDate = Carbon::parse($employee->getRawOriginal('birth_date'));
                $ageYears = (int) floor($birthDate->diffInYears($now));
            }

            if ($employee->getRawOriginal('join_date')) {
                $joinDate = Carbon::parse($employee->getRawOriginal('join_date'));
                $workMonths = (int) floor($joinDate->diffInMonths($now));
            }

            $employee->update([
                'age_years'            => $ageYears,
                'work_duration_months' => $workMonths,
            ]);
        }
    }
}