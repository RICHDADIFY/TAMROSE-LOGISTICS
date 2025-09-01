<?php

namespace App\Http\Controllers\TripRequests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TripRequest;
use App\Services\RequestStatusService;
use App\Jobs\GeocodeTripRequest;

// use App\Enums\TripRequestStatus; // ← remove for now

class StatusController extends Controller
{
    public function approve(Request $request, TripRequest $ride_request)
{
    $this->authorize('update', $ride_request);

    // 1) Approve (your existing service)
    RequestStatusService::set(
        $ride_request,
        'approved',
        $request->user()->id,
        $request->input('note')
    );

    // 2) Canonicalize bases right away (Office/Onne etc.)
    $bases = config('locations.bases', []);
    $clean = static fn(?string $s) => $s ? preg_replace('/\s+/u', ' ', trim($s)) : null;

    $baseFor = static function (?string $label) use ($bases, $clean): ?array {
        if (!$label) return null;
        $needle = mb_strtolower($clean($label));
        foreach ($bases as $name => $coords) {
            $key = mb_strtolower($clean($name));
            if ($needle === $key) {
                return [
                    'lat' => isset($coords['lat']) ? (float) $coords['lat'] : null,
                    'lng' => isset($coords['lng']) ? (float) $coords['lng'] : null,
                ];
            }
        }
        return null;
    };

    $dirty = [];

    if ($ride_request->from_location && ($b = $baseFor($ride_request->from_location))) {
        if ($b['lat'] !== null && $b['lng'] !== null) {
            $dirty['from_lat'] = $b['lat'];
            $dirty['from_lng'] = $b['lng'];
        }
    }

    if ($ride_request->to_location && ($b = $baseFor($ride_request->to_location))) {
        if ($b['lat'] !== null && $b['lng'] !== null) {
            $dirty['to_lat'] = $b['lat'];
            $dirty['to_lng'] = $b['lng'];
        }
    }

    if (!empty($dirty)) {
        $ride_request->forceFill($dirty)->save();
    }

    // 3) Safety net: queue background geocoding if missing OR out of Nigeria
    $ride_request->refresh();

    $inNg = static function (?float $lat, ?float $lng): bool {
        if ($lat === null || $lng === null) return false;
        return $lat >= 3.0 && $lat <= 14.5 && $lng >= 2.0 && $lng <= 15.5; // rough NG box
    };

    $needsGeo =
        ($ride_request->from_lat === null || $ride_request->from_lng === null) ||
        ($ride_request->to_lat   === null || $ride_request->to_lng   === null) ||
        !$inNg($ride_request->from_lat, $ride_request->from_lng) ||
        !$inNg($ride_request->to_lat,   $ride_request->to_lng);

    if ($needsGeo) {
        GeocodeTripRequest::dispatch($ride_request->id)->afterCommit();
    }

    return back()->with('success', 'Request approved.');
}

    public function reject(Request $request, TripRequest $ride_request)
    {
        $this->authorize('update', $ride_request);

        RequestStatusService::set(
            $ride_request,
            'rejected',                 // ← string
            $request->user()->id,
            $request->input('note')
        );

        return back()->with('success','Request rejected.');
    }

    public function cancel(Request $request, TripRequest $ride_request)
    {
        $this->authorize('update', $ride_request);

        RequestStatusService::set(
            $ride_request,
            'cancelled',                // ← string
            $request->user()->id,
            $request->input('note')
        );

        return back()->with('success','Request cancelled.');
    }
}
