<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VesselsSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'TMC Eagle','TMC Angel','TMC Hawk','TMC Zion','TMC Falcon',
            'TMC Phoenix','TMC Buteos','TMC Bald Eagle','TMC Osprey',
            'TMC Kestrel','TMC Providence','TMC Evolution',
            'Aurora Diamond','Aurora Saphire','Aurora Emirate',
        ];

        foreach ($names as $name) {
            \App\Models\Vessel::updateOrCreate(['name' => $name], ['active' => true]);
        }
    }
}

