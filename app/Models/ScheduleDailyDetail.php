<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ScheduleDailyDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'schedule_id',
        'date',
        'day_of_week',
        'attendance_status',
        'scheduled_check_in',
        'scheduled_check_out',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Jadwal')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}