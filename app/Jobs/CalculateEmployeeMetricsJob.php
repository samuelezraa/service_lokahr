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

            // Hitung Umur dalam satuan Tahun (berdasarkan birth_date asli di database)
            if ($employee->getRawOriginal('birth_date')) {
                $birthDate = Carbon::parse($employee->getRawOriginal('birth_date'));
                $ageYears = $birthDate->diffInYears($now);
            }

            // Hitung Lama Bekerja dalam satuan Bulan (berdasarkan join_date asli di database)
            if ($employee->getRawOriginal('join_date')) {
                $joinDate = Carbon::parse($employee->getRawOriginal('join_date'));
                $workMonths = $joinDate->diffInMonths($now);
            }

            // Update kolom tanpa mentrigger event berlebih
            $employee->update([
                'age_years'            => $ageYears,
                'work_duration_months' => $workMonths,
            ]);
        }
    }
}