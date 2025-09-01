<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function hasIndex($table, $index): bool {
        $db = DB::getDatabaseName();
        return !empty(DB::select(
            "SELECT 1 FROM information_schema.statistics
             WHERE table_schema=? AND table_name=? AND index_name=? LIMIT 1",
            [$db, $table, $index]
        ));
    }

    public function up(): void
    {
        // Drop duplicate coord indexes on trip_requests (keep trip_from_coords_idx / trip_to_coords_idx)
        foreach (['trip_requests_from_idx','trip_requests_to_idx'] as $ix) {
            if ($this->hasIndex('trip_requests', $ix)) {
                DB::statement("DROP INDEX $ix ON trip_requests");
            }
        }

        // trips (vehicle_id, depart_at)
        if (!$this->hasIndex('trips','trips_vehicle_depart')) {
            DB::statement('CREATE INDEX trips_vehicle_depart ON trips (vehicle_id, depart_at)');
        }

        // vehicles (active)
        if (!$this->hasIndex('vehicles','vehicles_active')) {
            DB::statement('CREATE INDEX vehicles_active ON vehicles (active)');
        }

        // Optional composite
        if (!$this->hasIndex('vehicles','vehicles_active_type')) {
            DB::statement('CREATE INDEX vehicles_active_type ON vehicles (active, type)');
        }
    }

    public function down(): void
    {
        foreach ([
            ['trips','trips_vehicle_depart'],
            ['vehicles','vehicles_active'],
            ['vehicles','vehicles_active_type'],
        ] as [$t,$i]) {
            if ($this->hasIndex($t,$i)) DB::statement("DROP INDEX $i ON $t");
        }

        // (Optional) recreate the duplicates you removed if you really want symmetry:
        // DB::statement('CREATE INDEX trip_requests_from_idx ON trip_requests (from_lat, from_lng)');
        // DB::statement('CREATE INDEX trip_requests_to_idx   ON trip_requests (to_lat, to_lng)');
    }
};
