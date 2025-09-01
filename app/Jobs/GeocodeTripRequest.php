<?php

namespace App\Jobs;

use App\Models\TripRequest;
use App\Services\Geocoder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeocodeTripRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;      // retry once on transient errors
    public int $timeout = 15;   // seconds

    public function __construct(public int $tripRequestId) {}

    public function handle(Geocoder $geo): void
    {
        $r = TripRequest::find($this->tripRequestId);
        if (!$r) return;

        // Support either naming: from_location/to_location OR from_label/to_label
        $fromLabel = $this->clean((string)($r->origin
            ?? $r->from_location ?? $r->from_label ?? ''));
        $toLabel   = $this->clean((string)($r->destination
            ?? $r->to_location   ?? $r->to_label   ?? ''));


        $bases = (array) config('locations.bases', []);
        $baseMap = $this->buildBaseMap($bases); // norm label -> ['lat'=>..,'lng'=>..]

        // Resolve to coords (base override > existing > geocode)
        $from = $this->resolve($fromLabel, $r->from_lat, $r->from_lng, $geo, $baseMap);
        $to   = $this->resolve($toLabel,   $r->to_lat,   $r->to_lng,   $geo, $baseMap);

        // Decide whether to overwrite
        $shouldOverwriteFrom =
            $this->isBase($fromLabel, $baseMap) ||
            !$this->inNigeria($r->from_lat, $r->from_lng) ||
            ($r->from_lat === null || $r->from_lng === null);

        $shouldOverwriteTo =
            $this->isBase($toLabel, $baseMap) ||
            !$this->inNigeria($r->to_lat, $r->to_lng) ||
            ($r->to_lat === null || $r->to_lng === null);

        $dirty = [];

        if ($from && $shouldOverwriteFrom) {
            $dirty['from_lat'] = round((float)$from['lat'], 7);
            $dirty['from_lng'] = round((float)$from['lng'], 7);
        }
        if ($to && $shouldOverwriteTo) {
            $dirty['to_lat'] = round((float)$to['lat'], 7);
            $dirty['to_lng'] = round((float)$to['lng'], 7);
        }

        if ($dirty) {
            $r->forceFill($dirty)->save();
            Log::info('triprequest.geocoded', ['id' => $r->id, 'set' => $dirty]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('triprequest.geocode_failed', [
            'id' => $this->tripRequestId,
            'msg' => $e->getMessage(),
        ]);
    }

    /* ----------------- helpers ----------------- */

    protected function clean(string $s): string
    {
        return preg_replace('/\s+/u', ' ', trim($s));
    }

    protected function norm(string $s): string
    {
        return mb_strtolower($this->clean($s));
    }

    /**
     * Build a map of normalized base labels/aliases -> coords
     * Supports both config shapes (keyed names, or arrays with aliases).
     */
    protected function buildBaseMap(array $bases): array
    {
        $map = [];
        foreach ($bases as $key => $val) {
            // Shape A: keyed by name
            if (!is_int($key) && is_array($val)) {
                $coords = ['lat' => (float)($val['lat'] ?? 0), 'lng' => (float)($val['lng'] ?? 0)];
                if ($coords['lat'] && $coords['lng']) {
                    $map[$this->norm((string)$key)] = $coords;
                }
                // optional aliases
                foreach ((array)($val['aliases'] ?? []) as $alias) {
                    $map[$this->norm((string)$alias)] = $coords;
                }
                continue;
            }
            // Shape B: list with aliases
            if (is_int($key) && is_array($val)) {
                $coords = ['lat' => (float)($val['lat'] ?? 0), 'lng' => (float)($val['lng'] ?? 0)];
                if (!$coords['lat'] || !$coords['lng']) continue;

                // 'name' (optional)
                if (!empty($val['name'])) {
                    $map[$this->norm((string)$val['name'])] = $coords;
                }
                // aliases (recommended)
                foreach ((array)($val['aliases'] ?? []) as $alias) {
                    $map[$this->norm((string)$alias)] = $coords;
                }
            }
        }
        return $map;
    }

    protected function isBase(?string $label, array $baseMap): bool
    {
        if (!$label) return false;
        return array_key_exists($this->norm($label), $baseMap);
    }

    protected function baseFor(?string $label, array $baseMap): ?array
    {
        if (!$label) return null;
        return $baseMap[$this->norm($label)] ?? null;
    }

    protected function resolve(?string $label, $lat, $lng, Geocoder $geo, array $baseMap): ?array
    {
        // Base labels always override with canonical coords
        if ($label && ($b = $this->baseFor($label, $baseMap))) {
            return $b;
        }

        // Already have coords? keep them
        if ($lat !== null && $lng !== null) {
            return ['lat' => (float) $lat, 'lng' => (float) $lng];
        }

        if (!$label) return null;

        // Geocode once via server key (cached)
        return $geo->geocode($label);
    }

    protected function inNigeria(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) return false;
        // Rough Nigeria bounding box
        return $lat >= 3.0 && $lat <= 14.5 && $lng >= 2.0 && $lng <= 15.5;
    }
}
