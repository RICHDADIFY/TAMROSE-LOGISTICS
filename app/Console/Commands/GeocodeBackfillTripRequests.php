<?php

namespace App\Console\Commands;

use App\Jobs\GeocodeTripRequest;
use App\Models\TripRequest;
use Illuminate\Console\Command;

class GeocodeBackfillTripRequests extends Command
{
    protected $signature = 'geo:backfill-trip-requests
        {--chunk=500 : How many rows to scan per chunk}
        {--limit=0   : Stop after queueing this many (0 = no limit)}
        {--queue=low : Queue name to dispatch jobs to}';

    protected $description = 'Queue geocoding jobs for TripRequests missing coordinates';

    public function handle(): int
    {
        $chunk   = (int) $this->option('chunk');
        $limit   = (int) $this->option('limit');
        $queue   = (string) $this->option('queue');

        // Group OR conditions explicitly so future filters don’t break them
        $baseQuery = TripRequest::query()->where(function ($q) {
            $q->whereNull('from_lat')
              ->orWhereNull('from_lng')
              ->orWhereNull('to_lat')
              ->orWhereNull('to_lng');
        });

        $total = (clone $baseQuery)->count();
        if ($limit > 0 && $limit < $total) {
            $this->info("Found {$total} rows missing coords. Will queue first {$limit}.");
        } else {
            $this->info("Found {$total} rows missing coords. Queueing all.");
        }

        if ($total === 0) {
            $this->info('Nothing to do.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($limit > 0 ? $limit : $total);
        $bar->start();

        $queued = 0;

        // chunkById is safer than chunk() (avoids re-visiting rows if data changes)
        $stop = false;
        $baseQuery->orderBy('id')->chunkById($chunk, function ($rows) use (&$queued, $limit, $queue, $bar, &$stop) {
            foreach ($rows as $r) {
                // Respect --limit
                if ($limit > 0 && $queued >= $limit) {
                    $stop = true;
                    break;
                }

                // Dispatch to the desired queue
                GeocodeTripRequest::dispatch($r->id)->onQueue($queue);
                $queued++;
                $bar->advance();
            }

            // Returning false from the chunk callback stops further chunking
            if ($stop) {
                return false;
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Queued {$queued} geocoding job(s). Start your worker if it isn’t running.");

        return self::SUCCESS;
    }
}
