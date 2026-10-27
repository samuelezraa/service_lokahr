<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_attendance_id',
        'day_no',
        'day_name',
        'date',
        'attendance_status',
        'scheduled_check_in',
        'scheduled_check_out',
        'actual_check_in',
        'actual_check_out',
        'status',
        'notes',
        'ref',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function employeeAttendance()
    {
        return $this->belongsTo(EmployeeAttendance::class);
    }
}