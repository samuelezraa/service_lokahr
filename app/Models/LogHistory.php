<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogHistory extends Model
{
    use HasFactory;

    protected $table = 'log_histories';

    protected $fillable = [
        'type',
        'menu',
        'sub_menu',
        'pic',
        'date',
        'description',
    ];

    /**
     * Helper untuk mencatat log otomatis
     */
    public static function record($type, $menu, $subMenu, $description, $pic = 'User Trial IT')
    {
        self::create([
            'type'        => $type, // 'Create' | 'Edit' | 'Delete'
            'menu'        => $menu,
            'sub_menu'    => $subMenu,
            'pic'         => $pic,
            'date'        => now()->toDateString(),
            'description' => $description,
        ]);
    }
}