<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PruneDriverLocations extends Command
{
    protected $signature = 'driver-locations:prune';
    protected $description = 'Downsample and prune old driver location points';

    public function handle(): int
    {
        $now             = now();
        $keepHiResUntil  = $now->copy()->subHours(72);
        $keepDownToUntil = $now->copy()->subDays(14);
        $deleteOlderThan = $now->copy()->subDays(30);

        // 1) Downsample older than 72h but newer than 14d: keep one point per 5 minutes
        // Strategy: delete rows that are NOT the first in each 5-min bucket per (trip,driver)
        // This uses a temp table approach to avoid heavy window functions on MySQL 8 variations.

        $this->info('Downsampling >72h <=14d to ~5-min resolution...');

        // Create a temporary table of "keepers"
        DB::statement('CREATE TEMPORARY TABLE tmp_keep_ids (id BIGINT PRIMARY KEY)');

        DB::statement("
            INSERT INTO tmp_keep_ids (id)
            SELECT t.id FROM (
                SELECT dl.id,
                       dl.trip_id,
                       dl.driver_id,
                       FLOOR(UNIX_TIMESTAMP(dl.recorded_at) / 300) AS bucket
                FROM driver_locations dl
                WHERE dl.recorded_at <= ? AND dl.recorded_at > ?
            ) t
            INNER JOIN (
                SELECT trip_id, driver_id, FLOOR(UNIX_TIMESTAMP(recorded_at) / 300) AS bucket,
                       MIN(id) AS min_id
                FROM driver_locations
                WHERE recorded_at <= ? AND recorded_at > ?
                GROUP BY trip_id, driver_id, bucket
            ) firsts
            ON t.trip_id = firsts.trip_id
            AND t.driver_id = firsts.driver_id
            AND t.bucket = firsts.bucket
            AND t.id = firsts.min_id
        ", [$keepHiResUntil, $keepDownToUntil, $keepHiResUntil, $keepDownToUntil]);

        // Delete any point in the window not present in tmp_keep_ids
        $deleted = DB::delete("
            DELETE dl FROM driver_locations dl
            WHERE dl.recorded_at <= ? AND dl.recorded_at > ?
              AND dl.id NOT IN (SELECT id FROM tmp_keep_ids)
        ", [$keepHiResUntil, $keepDownToUntil]);
        $this->info("Downsampled rows deleted: {$deleted}");

        DB::statement('DROP TEMPORARY TABLE tmp_keep_ids');

        // 2) Hard delete anything older than 30 days
        $purged = DB::table('driver_locations')
            ->where('recorded_at', '<', $deleteOlderThan)
            ->delete();
        $this->info("Purged rows older than 30 days: {$purged}");

        $this->info('Done.');
        return self::SUCCESS;
    }
}
