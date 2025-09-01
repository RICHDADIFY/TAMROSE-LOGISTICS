<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Geocoder
{
    public function geocode(string $label): ?array
    {
        $norm = $this->normalize($label);

        // 1) Bases first (Office, Onne, etc.)
        if ($hit = $this->fromBases($norm)) {
            return $hit + ['source' => 'base'];
        }

        // 2) Cache
        $ttlMinutes = (int) env('GEOCODE_CACHE_MINUTES', 60 * 24 * 7); // default 7 days
        $cacheKey   = 'geo:v2:' . md5($norm);

        return Cache::remember($cacheKey, now()->addMinutes($ttlMinutes), function () use ($label) {
            // 3) API key (support either config shape)
            $apiKey = config('services.google.maps_server_key')
                ?? config('services.google_maps.server_key');

            if (!$apiKey) {
                Log::warning('geocoder.no_key');
                return null;
            }

            // 4) Call Google (biased & restricted to NG)
            $res = Http::timeout(8)
                ->retry(1, 300)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address'    => $label,
                    'region'     => 'ng',         // bias
                    'components' => 'country:NG', // restrict
                    'key'        => $apiKey,
                ]);

            if (!$res->ok()) {
                Log::warning('geocoder.http_failed', ['status' => $res->status()]);
                return null;
            }

            $json   = $res->json();
            $status = $json['status'] ?? '';
            if ($status !== 'OK' || empty($json['results'])) {
                Log::info('geocoder.status', ['status' => $status, 'q' => $label]);
                return null;
            }

            $loc = $json['results'][0]['geometry']['location'] ?? null;
            if (!$loc) return null;

            $lat = (float) ($loc['lat'] ?? 0);
            $lng = (float) ($loc['lng'] ?? 0);

            // 5) Sanity & NG bounds
            if (!$this->valid($lat, $lng)) {
                Log::warning('geocoder.invalid_bounds', compact('lat','lng','label'));
                return null;
            }
            if (!$this->validNg($lat, $lng)) {
                Log::info('geocoder.out_of_ng', compact('label','lat','lng'));
                return null;
            }

            return ['lat' => round($lat, 7), 'lng' => round($lng, 7), 'source' => 'google'];
        });
    }

    public function normalize(string $label): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($label));
        return mb_strtolower($collapsed);
    }

    public function valid(float $lat, float $lng): bool
    {
        return is_finite($lat) && is_finite($lng)
            && $lat >= -90 && $lat <= 90
            && $lng >= -180 && $lng <= 180;
    }

    private function validNg(float $lat, float $lng): bool
    {
        // Rough Nigeria bounding box
        return $lat >= 3.0 && $lat <= 14.5 && $lng >= 2.0 && $lng <= 15.5;
    }

    private function fromBases(string $norm): ?array
    {
        // Expecting config/locations.php to return:
        // ['bases' => [ ['aliases'=>['office','onne'], 'lat'=>..., 'lng'=>...], ... ]]
        $bases = (array) config('locations.bases', []);
        foreach ($bases as $base) {
            $aliases = array_map(
                fn ($s) => mb_strtolower(trim((string) $s)),
                (array) ($base['aliases'] ?? [])
            );
            if (in_array($norm, $aliases, true)) {
                if (isset($base['lat'], $base['lng'])) {
                    return ['lat' => (float) $base['lat'], 'lng' => (float) $base['lng']];
                }
            }
        }
        return null;
    }
}
