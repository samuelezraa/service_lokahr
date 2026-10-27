<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'period_code',
        'employee_id',
        'nip',
        'employee_name',
        'department_id',
        'department_name',
        'sub_department_id',
        'sub_department_name',
        'work_schedule_scheme_id',
        'work_scheme',
        'total_days',
        'work_days',
        'off_days',
        'ph_days',
        'permit_days',
        'alpha_days',
        'sick_days',
        'leave_days',
        'late_days',
        'ref',
        'is_active',
    ];

    protected $casts = [
        'total_days'  => 'integer',
        'work_days'   => 'integer',
        'off_days'    => 'integer',
        'ph_days'     => 'integer',
        'permit_days' => 'integer',
        'alpha_days'  => 'integer',
        'sick_days'   => 'integer',
        'leave_days'  => 'integer',
        'late_days'   => 'integer',
        'is_active'   => 'boolean',
    ];

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function workScheduleScheme()
    {
        return $this->belongsTo(WorkScheduleScheme::class);
    }

    public function scheduleDetails()
    {
        return $this->hasMany(ScheduleDetail::class);
    }

    public function scheduleSchemes()
    {
        return $this->hasMany(ScheduleScheme::class);
    }
}