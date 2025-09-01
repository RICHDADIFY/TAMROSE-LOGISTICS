<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PortsSeeder extends Seeder
{
    public function run(): void
    {
        $ports = [
            ['code' => 'FOT',   'name' => 'Onne FOT'],
            ['code' => 'FLT',   'name' => 'Onne FLT'],
            ['code' => 'WAS',   'name' => 'Onne WAS'],
            ['code' => 'Jaffer','name' => 'Onne Jaffer'],
        ];

        foreach ($ports as $p) {
            \App\Models\Port::updateOrCreate(['code' => $p['code']], $p);
        }
    }
}

