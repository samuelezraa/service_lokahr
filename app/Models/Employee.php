<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'nip',
        'employee_password',
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
        'age_years',
        'work_duration_months',
        'is_active',
    ];

    protected $hidden = [
        'employee_password',
    ];

  protected $casts = [
        'join_date'            => 'date:d-m-Y',
        'birth_date'           => 'date:d-m-Y',
        'age_years'            => 'integer',
        'work_duration_months' => 'integer',
        'is_active'            => 'boolean',
    ];

    /**
     * Function phoneconvert: 
     * Jika input awal "0", diganti menjadi "62". 
     * Maksimal panjang karakter adalah 14.
     */
    public static function phoneconvert(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Bersihkan selain angka
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Jika diawali "0", ganti dengan "62"
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Batasi maksimal 14 karakter
        if (strlen($phone) > 14) {
            $phone = substr($phone, 0, 14);
        }

        return $phone;
    }

    // Accessor untuk mengubah format tanggal saat diambil API (DD-MM-YYYY)
    public function getJoinDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : null;
    }

    public function getBirthDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : null;
    }

    // Relasi
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

    
 /**
     * Relasi ke AttendanceLogDetail berdasarkan NIP atau Employee ID
     */
    public function attendanceLogDetails()
    {
        return $this->hasMany(AttendanceLogDetail::class, 'nip', 'nip');
        // Catatan: Jika relasi di tabel attendance_log_details menggunakan kolom 'nip', 
        // pastikan parameter kedua dan ketiga sesuai dengan kolom penghubungnya di database.
    }

}