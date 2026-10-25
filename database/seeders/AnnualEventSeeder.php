<?php

namespace Database\Seeders;

use App\Models\AnnualEvent;
use Illuminate\Database\Seeder;

class AnnualEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'title'               => 'Maulid Nabi Muhammad SAW',
                'start_date'          => '2026-10-08',
                'end_date'            => '2026-10-08',
                'is_national_holiday' => true,
                'description'         => 'Hari Maulid Nabi',
                'is_active'           => true,
            ],
            [
                'title'               => 'Hari Raya Natal',
                'start_date'          => '2026-12-25',
                'end_date'            => '2026-12-25',
                'is_national_holiday' => true,
                'description'         => 'Hari Natal',
                'is_active'           => true,
            ],
            [
                'title'               => 'Cuti Bersama Akhir Tahun',
                'start_date'          => '2026-12-26',
                'end_date'            => '2026-12-31',
                'is_national_holiday' => true,
                'description'         => 'Libur Panjang / Cuti Bersama Akhir Tahun',
                'is_active'           => true,
            ],
        ];

        foreach ($events as $event) {
            AnnualEvent::updateOrCreate(
                ['title' => $event['title']],
                $event
            );
        }
    }
}