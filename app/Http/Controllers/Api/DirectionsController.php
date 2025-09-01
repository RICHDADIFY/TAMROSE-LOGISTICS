<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class DirectionsController extends Controller
{
    public function summary(Request $request)
    {
        $request->validate([
            'from' => ['required','string'], // "lat,lng"
            'to'   => ['required','string'], // "lat,lng"
        ]);

        [$fromLat, $fromLng] = array_map('floatval', explode(',', $request->string('from')));
        [$toLat, $toLng]     = array_map('floatval', explode(',', $request->string('to')));

        // bucket departure time to 5 min for cache stability
        $bucket = now()->startOfMinute()->subSeconds(now()->timestamp % 300)->timestamp;
        $cacheKey = sprintf('dir:v1:drv:%s|%s|%d',
            "{$fromLat},{$fromLng}", "{$toLat},{$toLng}", $bucket
        );

        $ttl = now()->addMinutes(10);

        $payload = Cache::remember($cacheKey, $ttl, function () use ($fromLat,$fromLng,$toLat,$toLng) {
            $params = [
                'origin'      => "{$fromLat},{$fromLng}",
                'destination' => "{$toLat},{$toLng}",
                'mode'        => 'driving',
                'departure_time' => 'now',             // traffic-aware
                'alternatives'   => 'false',
                'region'         => 'ng',
                'key'            => config('services.google.maps_server_key'),
            ];

            $res = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/directions/json', $params);

            if (!$res->ok()) {
                return ['ok' => false, 'status' => 'HTTP_'.$res->status()];
            }

            $json = $res->json();
            if (($json['status'] ?? 'ZERO_RESULTS') !== 'OK' || empty($json['routes'])) {
                return ['ok' => false, 'status' => $json['status'] ?? 'UNKNOWN'];
            }

            $route = $json['routes'][0];
            $leg   = $route['legs'][0] ?? null;
            if (!$leg) {
                return ['ok' => false, 'status' => 'NO_LEG'];
            }

            return [
                'ok'   => true,
                'status' => 'OK',
                'summary' => [
                    'distance_text' => $leg['distance']['text'] ?? null,
                    'distance_m'    => $leg['distance']['value'] ?? null,
                    'duration_text' => $leg['duration']['text'] ?? null,
                    'duration_s'    => $leg['duration']['value'] ?? null,
                    'duration_in_traffic_s' => $leg['duration_in_traffic']['value'] ?? null,
                ],
                // overview polyline so the client can draw without another Directions call
                'overview_polyline' => $route['overview_polyline']['points'] ?? null,
                // markers (echoed back for convenience)
                'from' => ['lat' => $fromLat, 'lng' => $fromLng],
                'to'   => ['lat' => $toLat,   'lng' => $toLng],
            ];
        });

        if (!($payload['ok'] ?? false)) {
            return response()->json($payload, 422);
        }

        return response()->json($payload);
    }
    
    public function batch(\Illuminate\Http\Request $request)
{
    $data = $request->validate([
        'items' => ['required','array','min:1','max:50'],
        'items.*.key'  => ['required','string'],
        'items.*.from' => ['required','string'], // "lat,lng"
        'items.*.to'   => ['required','string'], // "lat,lng"
    ])['items'];

    $out = [];
    foreach ($data as $it) {
        try {
            [$fl, $fg] = array_map('floatval', explode(',', $it['from']));
            [$tl, $tg] = array_map('floatval', explode(',', $it['to']));

            // same cache bucketing as 3.2
            $bucket = now()->startOfMinute()->subSeconds(now()->timestamp % 300)->timestamp;
            $cacheKey = sprintf('dir:v1:drv:%s|%s|%d', "{$fl},{$fg}", "{$tl},{$tg}", $bucket);

            $payload = \Cache::remember($cacheKey, now()->addMinutes(10), function () use ($fl,$fg,$tl,$tg) {
                $res = \Http::timeout(8)->get('https://maps.googleapis.com/maps/api/directions/json', [
                    'origin' => "{$fl},{$fg}",
                    'destination' => "{$tl},{$tg}",
                    'mode' => 'driving',
                    'departure_time' => 'now',
                    'alternatives' => 'false',
                    'region' => 'ng',
                    'key' => config('services.google.maps_server_key'),
                ]);
                if (!$res->ok()) return ['ok'=>false,'status'=>'HTTP_'.$res->status()];
                $json = $res->json();
                if (($json['status'] ?? '') !== 'OK' || empty($json['routes'])) {
                    return ['ok'=>false,'status'=>$json['status'] ?? 'UNKNOWN'];
                }
                $leg = $json['routes'][0]['legs'][0] ?? null;
                if (!$leg) return ['ok'=>false,'status'=>'NO_LEG'];
                return [
                    'ok'=>true,'status'=>'OK',
                    'summary'=>[
                        'distance_text'=>$leg['distance']['text'] ?? null,
                        'distance_m'=>$leg['distance']['value'] ?? null,
                        'duration_text'=>$leg['duration']['text'] ?? null,
                        'duration_s'=>$leg['duration']['value'] ?? null,
                        'duration_in_traffic_s'=>$leg['duration_in_traffic']['value'] ?? null,
                    ],
                ];
            });

            $out[$it['key']] = $payload;
        } catch (\Throwable $e) {
            $out[$it['key']] = ['ok'=>false,'status'=>'EXCEPTION'];
        }
    }

    return response()->json(['ok'=>true,'results'=>$out]);
}

}
