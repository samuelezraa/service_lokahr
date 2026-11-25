<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AnnualEvent extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'is_national_holiday',
        'description',
        'is_active',
    ];

    protected $casts = [
        'start_date'          => 'date:Y-m-d',
        'end_date'            => 'date:Y-m-d',
        'is_national_holiday' => 'boolean',
        'is_active'           => 'boolean',
    ];

    /**
     * Konfigurasi Spatie Activitylog untuk mencatat perubahan Before vs After
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Master Data')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}