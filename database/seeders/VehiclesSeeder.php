<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;

class VehiclesSeeder extends Seeder
{
    public function run(): void
    {
        Vehicle::upsert([
            ['label'=>'Hiace Bus 1',   'type'=>'Bus',   'plate_number'=>'TEMP-BUS-1',   'capacity'=>14, 'active'=>true],
            ['label'=>'Hiace Bus 2',   'type'=>'Bus',   'plate_number'=>'TEMP-BUS-2',   'capacity'=>14, 'active'=>true],
            ['label'=>'Corolla 1',     'type'=>'Car',   'plate_number'=>'TEMP-COR-1',   'capacity'=>5,  'active'=>true],
            ['label'=>'Hilux 1',       'type'=>'Hilux', 'plate_number'=>'TEMP-HIL-1',   'capacity'=>5,  'active'=>true],
            ['label'=>'Hilux 2',       'type'=>'Hilux', 'plate_number'=>'TEMP-HIL-2',   'capacity'=>5,  'active'=>true],
            ['label'=>'Hilux 3',       'type'=>'Hilux', 'plate_number'=>'TEMP-HIL-3',   'capacity'=>5,  'active'=>true],
        ], ['plate_number'], ['label','type','capacity','active']);
    }
}
