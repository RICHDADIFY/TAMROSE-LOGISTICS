<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            // Helpful composite indexes for geo queries / batch summaries
            $table->index(['from_lat', 'from_lng'], 'trip_requests_from_idx');
            $table->index(['to_lat', 'to_lng'],     'trip_requests_to_idx');
        });

        // MySQL 8.0.16+ only: CHECK constraints (silently ignored on old MySQL)
        try {
            DB::statement("ALTER TABLE trip_requests
                ADD CONSTRAINT chk_from_lat_range CHECK (from_lat IS NULL OR (from_lat BETWEEN -90 AND 90))");
            DB::statement("ALTER TABLE trip_requests
                ADD CONSTRAINT chk_from_lng_range CHECK (from_lng IS NULL OR (from_lng BETWEEN -180 AND 180))");
            DB::statement("ALTER TABLE trip_requests
                ADD CONSTRAINT chk_to_lat_range   CHECK (to_lat   IS NULL OR (to_lat   BETWEEN -90 AND 90))");
            DB::statement("ALTER TABLE trip_requests
                ADD CONSTRAINT chk_to_lng_range   CHECK (to_lng   IS NULL OR (to_lng   BETWEEN -180 AND 180))");
        } catch (\Throwable $e) {
            // If your server is MySQL < 8.0.16 or MariaDB w/o CHECK support, this will fail. Safe to ignore.
            // You still have app-level guards in the Job + Geocoder.
        }
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropIndex('trip_requests_from_idx');
            $table->dropIndex('trip_requests_to_idx');
        });

        // Best effort to drop constraints (if they exist)
        foreach ([
            'chk_from_lat_range','chk_from_lng_range','chk_to_lat_range','chk_to_lng_range'
        ] as $c) {
            try { DB::statement("ALTER TABLE trip_requests DROP CONSTRAINT {$c}"); } catch (\Throwable $e) {}
        }
    }
};
