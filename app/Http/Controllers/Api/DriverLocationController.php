<?php

namespace App\Http\Controllers\Api;   



use App\Http\Controllers\Controller;
use App\Models\DriverLocation;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;

class DriverLocationController extends Controller
{
    public function store(Request $request, Trip $trip)
    {
        $user = $request->user();

        // 1) Ensure authed user is the driver assigned to this trip
        if ((int)($trip->driver_id ?? 0) !== (int)$user->id) {
            return response()->json([
                'ok'       => false,
                'accepted' => false,
                'reason'   => 'not_trip_driver',
            ], 403);
        }

        
        // 2) Ensure trip is "in-progress"
        $statusNorm = strtolower(str_replace('_', '-', (string)($trip->status ?? '')));
        $inProgress = ($statusNorm === 'in-progress');
        
        if (! $inProgress) {
            return response()->json([
                'ok'       => false,
                'accepted' => false,
                'reason'   => 'trip_not_in_progress',
            ], 409);
        }


        // 3) Validate payload
        $data = $request->validate([
            'lat'         => ['required','numeric','between:-90,90'],
            'lng'         => ['required','numeric','between:-180,180'],
            'heading'     => ['nullable','integer','between:0,359'],
            'speed'       => ['nullable','numeric','min:0','max:300'],
            'recorded_at' => ['nullable','date'], // ISO8601 preferred
        ]);

        // 3a) Parse recorded_at safely; default to now()
        try {
            $recordedAt = isset($data['recorded_at']) ? Carbon::parse($data['recorded_at']) : now();
        } catch (\Throwable $e) {
            return response()->json([
                'ok'       => false,
                'accepted' => false,
                'reason'   => 'invalid_recorded_at',
                'message'  => 'recorded_at is not a valid datetime.',
            ], 422);
        }

        // 3b) Reject far-future timestamps (device clock skew)
        if ($recordedAt->gt(now()->addMinutes(2))) {
            return response()->json([
                'ok'       => false,
                'accepted' => false,
                'reason'   => 'future_timestamp',
                'message'  => 'recorded_at is too far in the future.',
            ], 422);
        }

        // 4) Discard out-of-order pings vs last accepted for this trip+driver
        $lastKey = "driver_last_point:trip:{$trip->id}:driver:{$user->id}";
        $lastTs  = Cache::get($lastKey); // iso string or null

        if ($lastTs && $recordedAt->lte(Carbon::parse($lastTs))) {
            return response()->json([
                'ok'                => true,
                'accepted'          => false,
                'reason'            => 'out_of_order',
                'last_recorded_at'  => $lastTs,
            ], 202);
        }

        // 5) Extra safety: minimum interval beyond global rate-limit
        $intervalKey = "driver_min_interval:trip:{$trip->id}:driver:{$user->id}";
        if (Cache::has($intervalKey)) {
            return response()->json([
                'ok'       => false,
                'accepted' => false,
                'reason'   => 'too_frequent',
            ], 429);
        }

        // 6) Accept & store (normalize precision a bit)
        $payload = [
            'trip_id'     => $trip->id,
            'driver_id'   => $user->id,
            'lat'         => round((float)$data['lat'], 7),
            'lng'         => round((float)$data['lng'], 7),
            'heading'     => $data['heading'] ?? null,
            'speed'       => isset($data['speed']) ? round((float)$data['speed'], 2) : null,
            'recorded_at' => $recordedAt,
        ];

        try {
            $loc = DriverLocation::create($payload);
        } catch (QueryException $e) {
            // MySQL duplicate key (unique trip_id+driver_id+recorded_at) → treat as dedup
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                return response()->json([
                    'ok'       => true,
                    'accepted' => false,
                    'reason'   => 'duplicate',
                ], 202);
            }
            throw $e; // bubble up unknown DB errors
        }

        // 7) Update caches
        Cache::put($lastKey, $recordedAt->toIso8601String(), 3600);
        Cache::put($intervalKey, 1, now()->addSeconds(8));

        return response()->json([
            'ok'          => true,
            'accepted'    => true,
            'id'          => $loc->id,
            'recorded_at' => $loc->recorded_at->toIso8601String(),
        ]);
    }
    
   public function recent(\Illuminate\Http\Request $request, \App\Models\Trip $trip)
{
    // Single source of truth: TripPolicy@view
    if ($request->user()->cannot('view', $trip)) {
        return response()->json(['ok' => false, 'reason' => 'forbidden'], 403);
    }

    // Accept either minutes or count (or both). Clamp to sane limits.
    $minutes = $request->has('minutes') ? (int) $request->query('minutes', 60) : null;
    $count   = (int) $request->query('count', 200);
    $count   = max(1, min($count, 1000));
    if (!is_null($minutes)) {
        $minutes = max(1, min($minutes, 1440)); // up to 24h
    }

    $select = ['lat','lng','heading','speed','recorded_at'];

    // Build base query
    $q = \App\Models\DriverLocation::query()
        ->where('trip_id', $trip->id)
        ->orderBy('recorded_at');

    // Prefer time window if provided
    if (!is_null($minutes)) {
        $q->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    $points = $q->take($count)->get($select);

    // Friendly fallback: if empty with minutes filter, return last N points
    if ($points->isEmpty() && !is_null($minutes)) {
        $points = \App\Models\DriverLocation::query()
            ->where('trip_id', $trip->id)
            ->orderByDesc('recorded_at')
            ->take($count)
            ->get($select)
            ->reverse()   // chronological asc
            ->values();
    }

    return response()->json([
        'ok'     => true,
        'tripId' => $trip->id,
        'count'  => $points->count(),
        'last'   => $points->last(),
        'points' => $points,
    ]);
}


public function eta(\Illuminate\Http\Request $request, \App\Models\Trip $trip)
{
    // ✅ Single source of truth: TripPolicy@view
    if ($request->user()->cannot('view', $trip)) {
        return response()->json(['ok' => false, 'reason' => 'forbidden'], 403);
    }

    // 1) Last driver point
    $last = \App\Models\DriverLocation::where('trip_id', $trip->id)
        ->orderByDesc('recorded_at')
        ->first(['lat','lng','recorded_at']);

    if (! $last) {
        return response()->json(['ok'=>false,'reason'=>'no_driver_point'], 404);
    }

    // 2) Destination coords (prefer Trip fields; fallback to linked request)
    $toLat = $trip->to_lat ?? $trip->request->to_lat ?? null;
    $toLng = $trip->to_lng ?? $trip->request->to_lng ?? null;
    if ($toLat === null || $toLng === null) {
        return response()->json(['ok'=>false,'reason'=>'no_destination_coords'], 422);
    }

    // ✅ Short-circuit while maps are paused — no Google calls, same response shape
    if (env('MAPS_DISABLED', false)) {
        return response()->json([
            'ok'          => true,
            'tripId'      => $trip->id,
            'from'        => ['lat'=>$last->lat,'lng'=>$last->lng,'recorded_at'=>$last->recorded_at],
            'to'          => ['lat'=>(float)$toLat,'lng'=>(float)$toLng],
            'eta_seconds' => null,
            'eta_minutes' => null,
            'distance_m'  => null,
            'raw'         => ['distance_text'=>null, 'duration_text'=>null],
            'reason'      => 'maps_disabled',
        ]);
    }

    // 3) Call Distance Matrix (server key)
    $apiKey = config('services.google.maps_server_key')
        ?? config('services.google_maps.server_key');

    if (! $apiKey) {
        return response()->json(['ok'=>false,'reason'=>'no_api_key'], 500);
    }

    $params = [
        'origins'        => $last->lat.','.$last->lng,
        'destinations'   => $toLat.','.$toLng,
        'departure_time' => 'now',
        'key'            => $apiKey,
    ];

    $res = \Illuminate\Support\Facades\Http::timeout(6)->retry(1, 300)
        ->get('https://maps.googleapis.com/maps/api/distancematrix/json', $params);

    if (! $res->ok()) {
        return response()->json(['ok'=>false,'reason'=>'dm_http_error','status'=>$res->status()], 502);
    }

    $j    = $res->json();
    $elem = $j['rows'][0]['elements'][0] ?? null;
    if (($elem['status'] ?? '') !== 'OK') {
        return response()->json(['ok'=>false,'reason'=>'dm_status','detail'=>$elem['status'] ?? 'UNKNOWN'], 502);
    }

    $durationSec = (int)($elem['duration_in_traffic']['value'] ?? $elem['duration']['value'] ?? 0);
    $distanceM   = (int)($elem['distance']['value'] ?? 0);

    return response()->json([
        'ok'          => true,
        'tripId'      => $trip->id,
        'from'        => ['lat'=>$last->lat,'lng'=>$last->lng,'recorded_at'=>$last->recorded_at],
        'to'          => ['lat'=>(float)$toLat,'lng'=>(float)$toLng],
        'eta_seconds' => $durationSec,
        'eta_minutes' => $durationSec ? round($durationSec/60) : null,
        'distance_m'  => $distanceM,
        'raw'         => [
            'distance_text' => $elem['distance']['text'] ?? null,
            'duration_text' => $elem['duration']['text'] ?? null,
        ],
    ]);
}

}
