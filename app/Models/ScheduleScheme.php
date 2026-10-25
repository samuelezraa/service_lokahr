<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'work_schedule_scheme_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function workScheduleScheme()
    {
        return $this->belongsTo(WorkScheduleScheme::class);
    }
}