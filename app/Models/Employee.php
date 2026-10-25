<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'full_name',
        'email',
        'phone',
        'department_id',
        'sub_department_id',
        'position_id',
        'grade_id',
        'work_schedule_scheme_id',
        'join_date',
        'birth_date',
        'is_active',
    ];

    protected $casts = [
        'join_date'  => 'date',
        'birth_date' => 'date',
        'is_active'  => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function workScheduleScheme()
    {
        return $this->belongsTo(WorkScheduleScheme::class);
    }

    public function customWorkHours()
    {
        return $this->hasMany(CustomWorkHour::class);
    }
}