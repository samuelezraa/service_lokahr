<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubDepartment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Relasi ke Department Induk
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_code', 'code');
    }
}