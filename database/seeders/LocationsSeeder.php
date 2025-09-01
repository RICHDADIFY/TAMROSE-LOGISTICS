<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Office',      'type' => 'office'],
            ['name' => 'Onne',        'type' => 'onne_cluster'],
            ['name' => 'Guest House', 'type' => 'guest_house'],
        ];

        foreach ($rows as $row) {
            \App\Models\Location::updateOrCreate(
                ['name' => $row['name']],
                $row + ['active' => true]
            );
        }
    }
}

