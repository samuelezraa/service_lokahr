<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLogDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_log_id',
        'date',
        'nik',
        'department_id',
        'department_name',
        'employee_name',
        'attendance_time',
        'attendance_type',
    ];

    protected $casts = [
        'date' => 'date',
        'attendance_time' => 'datetime',
    ];

    public function attendanceLog()
    {
        return $this->belongsTo(AttendanceLog::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}