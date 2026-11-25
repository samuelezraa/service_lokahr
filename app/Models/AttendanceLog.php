<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AttendanceLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'date',
        'machine_ip',
        'payload_data',
    ];

    protected $casts = [
        'date' => 'date',
        'payload_data' => 'array',
    ];

    public function attendanceLogDetails()
    {
        return $this->hasMany(AttendanceLogDetail::class);
    }

    public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->useLogName('Laporan')
        ->logFillable()
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
}