<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_code',
        'nip',
        'employee_name',
        'department_name',
        'sub_department_name',
        'pos_name',
        'grade_name',
        'work_scheme',
        'total_off_days',
        'total_ph_days',
        'total_permit_days',
        'total_alpha_days',
        'total_sick_days',
        'total_leave_days',
        'total_late_days',
        'total_work_days',
        'total_period_days',
    ];

    public function details()
    {
        return $this->hasMany(EmployeeAttendanceDetail::class);
    }
}