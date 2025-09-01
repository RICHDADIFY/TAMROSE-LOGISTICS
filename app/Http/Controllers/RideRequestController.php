<?php
namespace App\Http\Controllers;







use Inertia\Inertia;
use App\Models\{TripRequest, Trip, Vehicle, User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;   // ✅ correct import
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use App\Notifications\TripAssignedToDriver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Models\Vessel;
use Illuminate\Support\Facades\DB;










class RideRequestController extends Controller
{
    /**
     * Staff index: show ONLY the authenticated user's requests.
     * We authorize 'create' just to ensure the user is authenticated (per policy).
     */
    public function index(Request $request)
    {
        $this->authorize('create', TripRequest::class);

        $requests = TripRequest::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(fn($r) => [
                'id'           => $r->id,
                'origin'       => $r->origin,
                'destination'  => $r->destination,
                'desired_time' => optional($r->desired_time)
                ->timezone(config('app.timezone'))
                ->toIso8601String(),
                'passengers'   => $r->passengers,
                'purpose'      => $r->purpose,
                'status'       => $r->status,
                'created_at'   => $r->created_at->toIso8601String(),
            ]);

        return Inertia::render('RideRequests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Show create form (any authenticated user).
     */
  
// ...




public function create(Request $request)
{
    $this->authorize('create', \App\Models\TripRequest::class);

    $bases = ['Office','Onne'];

    // ---------- VESSELS (join ports only when columns exist) ----------
    $hasPorts    = Schema::hasTable('ports');
    $hasPortCode = $hasPorts && Schema::hasColumn('ports','code');
    $hasPortLat  = $hasPorts && Schema::hasColumn('ports','lat');
    $hasPortLng  = $hasPorts && Schema::hasColumn('ports','lng');

    $vesselQ = DB::table('vessels')->orderBy('vessels.name','asc');
    if ($hasPorts) {
        $vesselQ->leftJoin('ports','ports.id','=','vessels.default_port_id');
    }

    $vesselCols = ['vessels.id','vessels.name','vessels.default_port_id'];
    if ($hasPortCode) $vesselCols[] = DB::raw('ports.code as port_code');
    if ($hasPortLat)  $vesselCols[] = DB::raw('ports.lat  as port_lat');
    if ($hasPortLng)  $vesselCols[] = DB::raw('ports.lng  as port_lng');

    $vessels = $vesselQ->limit(500)->get($vesselCols)->map(function ($v) use ($hasPortCode, $hasPortLat, $hasPortLng) {
        return [
            'id'    => (int) $v->id,
            'label' => $v->name . (($hasPortCode && !empty($v->port_code)) ? " ({$v->port_code})" : ''),
            'lat'   => $hasPortLat ? ($v->port_lat ?? null) : null,
            'lng'   => $hasPortLng ? ($v->port_lng ?? null) : null,
        ];
    })->values();

    // ---------- GUEST HOUSES (safe defaults + column guards) ----------
    $guestHouses = collect();
    if (Schema::hasTable('guest_houses')) {
        $ghCols = ['id','name'];
        if (Schema::hasColumn('guest_houses','lat')) $ghCols[] = 'lat';
        if (Schema::hasColumn('guest_houses','lng')) $ghCols[] = 'lng';

        $guestHouses = DB::table('guest_houses')->orderBy('name','asc')
            ->get($ghCols)
            ->map(fn($g) => [
                'id'    => (int) $g->id,
                'label' => $g->name,
                'lat'   => property_exists($g,'lat') ? $g->lat : null,
                'lng'   => property_exists($g,'lng') ? $g->lng : null,
            ])->values();
    }

    // 👇 fallback if table is absent/empty
    if ($guestHouses->isEmpty()) {
        $guestHouses = collect(config('locations.guest_houses', []))
            ->map(function ($g, $i) {
                return [
                    'id'    => $g['id'] ?? ($i + 1),
                    'label' => $g['label'] ?? 'Guest house',
                    'lat'   => $g['lat']   ?? null,
                    'lng'   => $g['lng']   ?? null,
                ];
            })->values();
    }
    return \Inertia\Inertia::render('RideRequests/Create', [
        'meta' => [
            'bases'        => $bases,
            'vessels'      => $vessels,
            'guest_houses' => $guestHouses, // always defined
        ],
    ]);
}

    /**
     * Store a new ride request (any authenticated user).
     */
  // app/Http/Controllers/RideRequestController.php


public function store(Request $request)
{
    $this->authorize('create', \App\Models\TripRequest::class);

    $data = $request->validate([
        'origin'           => ['required','string','min:2','max:120'],
        'destination'      => ['required','string','min:2','max:120','different:origin'],
        'desired_time'     => ['required','date','after:now'],
        'passengers'       => ['required','integer','min:1','max:14'],
        'purpose'          => ['nullable','string','max:180'],
        // helpers:
        'destination_mode' => ['nullable', Rule::in(['free','vessel','guest'])],
        'vessel_id'        => ['nullable','integer'],
        'guest_id'         => ['nullable','integer'],
    ]);

    // Clean up
    $origin      = preg_replace('/\s+/', ' ', trim($data['origin']));
    $destText    = preg_replace('/\s+/', ' ', trim($data['destination']));
    $mode     = $request->input('destination_mode','free');
    $toLat = $toLng = null;

    if ($mode === 'vessel' && $request->filled('vessel_id')) {
    $hasPorts    = Schema::hasTable('ports');
    $hasPortCode = $hasPorts && Schema::hasColumn('ports','code');
    $hasPortLat  = $hasPorts && Schema::hasColumn('ports','lat');
    $hasPortLng  = $hasPorts && Schema::hasColumn('ports','lng');

    $q = DB::table('vessels');
    if ($hasPorts) $q->leftJoin('ports','ports.id','=','vessels.default_port_id');

    $select = ['vessels.name'];
    if ($hasPortCode) $select[] = DB::raw('ports.code as port_code');
    if ($hasPortLat)  $select[] = DB::raw('ports.lat  as port_lat');
    if ($hasPortLng)  $select[] = DB::raw('ports.lng  as port_lng');

    $v = $q->where('vessels.id',(int)$request->input('vessel_id'))->first($select);

    if ($v) {
        $destText = $v->name . (($hasPortCode && !empty($v->port_code)) ? " ({$v->port_code})" : '');

        // ✅ FORCE all vessel destinations to Onne GPS
        $onne  = config('locations.bases.Onne', ['lat' => 4.723816, 'lng' => 7.151618]);
        $toLat = $onne['lat'];
        $toLng = $onne['lng'];
    }
}

    if ($mode === 'guest' && $request->filled('guest_id')) {
        if (\Illuminate\Support\Facades\Schema::hasTable('guest_houses')) {
            $g = \DB::table('guest_houses')->where('id',(int)$request->input('guest_id'))->first(['name','lat','lng']);
            if ($g) { $destText = $g->name; $toLat = $g->lat ?? null; $toLng = $g->lng ?? null; }
        } else {
            $g = collect(config('locations.guest_houses', []))
                ->first(fn($row)=> (int)($row['id'] ?? -1) === (int)$request->input('guest_id'));
            if ($g) { $destText = $g['name'] ?? $destText; $toLat = $g['lat'] ?? null; $toLng = $g['lng'] ?? null; }
        }
    }

    // Create request (use your actual DB column names)
    $trip = \App\Models\TripRequest::create([
        'user_id'          => $request->user()->id,
        'direction'        => $request->input('direction','one-way'),
        'from_location'    => $origin,
        'to_location'      => $destText,
        'to_lat'           => $toLat,
        'to_lng'           => $toLng,
        'desired_departure'=> Carbon::parse($data['desired_time']),
        'passengers'       => (int) $data['passengers'],
        'purpose'          => $data['purpose'] ?? null,
        'status'           => \App\Models\TripRequest::STATUS_PENDING,
    ]);

    // Background geocode will still run if coords are missing for free-text
    \App\Jobs\GeocodeTripRequest::dispatch($trip->id);

    return redirect()->route('ride-requests.index')->with('success','Ride request submitted.');
}

    /**
     * Show a single request (owner or manager).
     */
  public function show(Request $httpRequest, \App\Models\TripRequest $ride_request)
{
    $this->authorize('view', $ride_request);

    // Eager load related models
    $ride_request->load(['trip.driver','trip.vehicle','user']);

    $trip    = $ride_request->trip;
    $driver  = $trip?->driver;
    $vehicle = $trip?->vehicle;
    $viewer  = $httpRequest->user();

    // Optional metric
    $completedTrips = $driver
        ? \App\Models\Trip::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->count()
        : 0;

    // Avatar url for driver
    $avatarUrl = $driver
        ? (
            ($driver->profile_photo_url ?? null)
                ?: 'https://ui-avatars.com/api/?name=' . urlencode($driver->name) . '&background=10B981&color=fff'
        )
        : null;

    // ---- Contact panel logic (backend-driven visibility) ----
    $isDriverViewer  = $viewer && $viewer->role === 'Driver';
    $isManagerViewer = $viewer && ($viewer->is_manager || in_array($viewer->role, ['Manager','Dispatch','Logistics']));
    $isOwnerViewer   = $viewer && $ride_request->user_id === $viewer->id;

    $manager = $this->getLogisticsManager();

    $asCard = function (? \App\Models\User $u, string $tag) {
        if (!$u) return null;
        return [
            'tag'       => $tag, // 'driver' | 'manager' | 'requester'
            'name'      => $u->name,
            'email'     => $u->email,
            'phone'     => $u->phone ?? null,
            'whatsapp'  => $u->phone ?? null,
            'avatar'    => $u->profile_photo_url
                ?? ($u->profile_photo
                    ? asset($u->profile_photo)
                    : 'https://ui-avatars.com/api/?name=' . urlencode($u->name) . '&background=2563eb&color=fff'),
            'canMessage'=> true,
            'canCall'   => !empty($u->phone),
        ];
    };

    $contacts = [];
    if ($isDriverViewer) {
        // Driver sees only Logistics Manager
        if ($manager) $contacts[] = $asCard($manager, 'manager');
    } elseif ($isOwnerViewer) {
        // Staff (requester)
        if ($ride_request->driver_id && $driver) $contacts[] = $asCard($driver, 'driver');
        if ($manager) $contacts[] = $asCard($manager, 'manager');
    } elseif ($isManagerViewer) {
        // Manager view: Driver (if any) + Requester
        if ($driver) $contacts[] = $asCard($driver, 'driver');
        $contacts[] = $asCard($ride_request->user, 'requester');
    } else {
        // Safe default
        if ($manager) $contacts[] = $asCard($manager, 'manager');
    }
    $contacts = array_values(array_filter($contacts));

    return \Inertia\Inertia::render('RideRequests/Show', [
        // existing request payload
        'request' => [
            'id'           => $ride_request->id,
            'user_id'      => $ride_request->user_id,
            'origin'       => $ride_request->origin,
            'destination'  => $ride_request->destination,
            'desired_time' => optional($ride_request->desired_time)->toIso8601String(),
            'passengers'   => $ride_request->passengers,
            'purpose'      => $ride_request->purpose,
            'status'       => $ride_request->status,
            'manager_note' => $ride_request->manager_note,
            'created_at'   => optional($ride_request->created_at)->toIso8601String(),
        ],

        // driver panel data (null when not yet assigned)
        'driver' => $driver ? [
            'id'              => $driver->id,
            'name'            => $driver->name,
            'phone'           => $driver->phone ?? null,
            'avatar_url'      => $avatarUrl,
            'completed_trips' => $completedTrips,
            'vehicle'         => $vehicle ? [
                'label' => $vehicle->display_label ?? $vehicle->label ?? ("Vehicle #{$vehicle->id}"),
                'plate' => $vehicle->plate_number ?? null,
                'model' => $vehicle->model ?? null,
            ] : null,
        ] : null,

        // NEW: contacts array for the ContactPanel
        'contacts' => $contacts,

        // NEW: map coords for TinyMap (read-only, fit-bounds)
        'map' => [
            'from' => ($ride_request->from_lat && $ride_request->from_lng)
                ? ['lat' => (float)$ride_request->from_lat, 'lng' => (float)$ride_request->from_lng]
                : null,
            'to'   => ($ride_request->to_lat && $ride_request->to_lng)
                ? ['lat' => (float)$ride_request->to_lat,   'lng' => (float)$ride_request->to_lng]
                : null,
        ],

        // abilities (unchanged)
        'can' => [
            'approve' => \Illuminate\Support\Facades\Gate::allows('approve', $ride_request),
            'reject'  => \Illuminate\Support\Facades\Gate::allows('reject',  $ride_request),
            'cancel'  => \Illuminate\Support\Facades\Gate::allows('cancel',  $ride_request),
        ],
    ]);
}



    /**
     * Staff cancel their own pending request.
     */
    public function cancel(Request $req, \App\Models\TripRequest $ride_request)
{
    $this->authorize('update', $ride_request); // owner + pending per policy

    if ($ride_request->status !== 'pending') {
        return back()->with('error', 'This request is already processed.');
    }

    $ride_request->status = 'rejected';
    $ride_request->manager_note = 'Cancelled by requester';
    $ride_request->save();

    return redirect()->route('ride-requests.index')->with('success', 'Request cancelled.');
}


    /**
     * Manager dispatch board: list pending requests.
     */
 // app/Http/Controllers/RideRequestController.php

public function dispatch(\Illuminate\Http\Request $req)
{
    // read once
    $mapsDisabled = (bool) env('MAPS_DISABLED', true);

    // Case-insensitive canonical base lookup
    $baseFor = static function (?string $label): ?array {
        if (!$label) return null;
        $needle = trim($label);
        $bases  = config('locations.bases', []);
        foreach ($bases as $name => $coords) {
            if (strcasecmp(trim($name), $needle) === 0) {
                return $coords;
            }
        }
        return null;
    };

    // Normalize one TripRequest into a card (labels + coords)
    $asCard = function (\App\Models\TripRequest $r) use ($baseFor, $mapsDisabled) {
        $originLabel      = $r->origin;
        $destinationLabel = $r->destination;

        // Start with stored coords
        $fromLat = $r->from_lat; $fromLng = $r->from_lng;
        $toLat   = $r->to_lat;   $toLng   = $r->to_lng;

        // Canonical bases
        if ($originLabel && ($b = $baseFor($originLabel))) {
            $fromLat = $b['lat']; $fromLng = $b['lng'];
        }
        if ($destinationLabel && ($b = $baseFor($destinationLabel))) {
            $toLat = $b['lat']; $toLng = $b['lng'];
        }

        // ⛔️ Fallback geocoding only when maps enabled; never throw if denied
        if (
            !$mapsDisabled &&
            ($fromLat === null || $fromLng === null) &&
            $originLabel && !$baseFor($originLabel) &&
            app()->bound(\App\Services\Geocoder::class)
        ) {
            try {
                $geo = app(\App\Services\Geocoder::class)->geocode($originLabel);
                if ($geo && isset($geo['lat'],$geo['lng'])) { $fromLat = $geo['lat']; $fromLng = $geo['lng']; }
            } catch (\Throwable $e) {
                Log::warning('dispatch.geocode_origin_failed', ['q'=>$originLabel,'error'=>$e->getMessage()]);
            }
        }

        if (
            !$mapsDisabled &&
            ($toLat === null || $toLng === null) &&
            $destinationLabel && !$baseFor($destinationLabel) &&
            app()->bound(\App\Services\Geocoder::class)
        ) {
            try {
                $geo = app(\App\Services\Geocoder::class)->geocode($destinationLabel);
                if ($geo && isset($geo['lat'],$geo['lng'])) { $toLat = $geo['lat']; $toLng = $geo['lng']; }
            } catch (\Throwable $e) {
                Log::warning('dispatch.geocode_destination_failed', ['q'=>$destinationLabel,'error'=>$e->getMessage()]);
            }
        }

        // Persist back once (only if we actually got coords)
        $dirty = [];
        if ($r->from_lat === null && $fromLat !== null) $dirty['from_lat'] = $fromLat;
        if ($r->from_lng === null && $fromLng !== null) $dirty['from_lng'] = $fromLng;
        if ($r->to_lat   === null && $toLat   !== null) $dirty['to_lat']   = $toLat;
        if ($r->to_lng   === null && $toLng   !== null) $dirty['to_lng']   = $toLng;
        if ($dirty) $r->forceFill($dirty)->save();

        return [
            'id'             => $r->id,
            'requested_by'   => optional($r->user)->name,
            'origin'         => $originLabel,
            'destination'    => $destinationLabel,
            'desired_time'  => optional($r->desired_time)
            ->timezone(config('app.timezone'))
            ->toIso8601String(), // keep ISO so the pill math stays easy

        'desired_input' => optional($r->desired_time)
            ?->timezone(config('app.timezone'))
            ->format('Y-m-d\TH:i'), // for <input type="datetime-local">

            'passengers'     => $r->passengers,
            'purpose'        => $r->purpose,
            'status'         => $r->status,
            'from_lat' => $fromLat !== null ? (float)$fromLat : null,
            'from_lng' => $fromLng !== null ? (float)$fromLng : null,
            'to_lat'   => $toLat   !== null ? (float)$toLat   : null,
            'to_lng'   => $toLng   !== null ? (float)$toLng   : null,
        ];
    };

    // PENDING
    $pending = \App\Models\TripRequest::with('user')
        ->where('status','pending')
        ->orderBy('desired_departure')
        ->get()->map($asCard)->values();

    // READY
    $ready = \App\Models\TripRequest::with('user')
        ->where('status','approved')
        ->whereNull('trip_id')
        ->orderBy('desired_departure')
        ->get()->map($asCard)->values();

    $vehicles = \App\Models\Vehicle::active()->orderBy('label')->get()
        ->map(fn($v)=>['id'=>$v->id,'label'=>$v->label ?: ($v->plate_number ?: "Vehicle #{$v->id}")])->values();

    $drivers = \App\Models\User::role('Driver')
        ->select('id','name','phone','profile_photo_path')->orderBy('name')->get()
        ->map(fn($d)=>[
            'id'=>$d->id,'name'=>$d->name,'phone'=>$d->phone,
            'avatar_url'=>$d->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($d->name).'&background=10B981&color=fff',
        ])->values();

    $activeTrips = \App\Models\Trip::with(['vehicle:id,label,plate_number','driver:id,name'])
        ->whereIn('status',['scheduled','dispatched','in-progress'])
        ->orderByDesc('depart_at')->take(20)->get()
        ->map(fn($t)=>[
            'id'=>$t->id,
            'vehicle'=>$t->vehicle?->label ?? $t->vehicle?->display_label ?? ("Vehicle #{$t->vehicle_id}"),
            'driver'=>$t->driver?->name,
            'depart_at'=>optional($t->depart_at)->toDateTimeString(),
            'status'=>$t->status,
            'label'=>sprintf('#%d • %s • %s • %s',
                $t->id,
                $t->vehicle?->label ?? $t->vehicle?->display_label ?? "Vehicle #{$t->vehicle_id}",
                $t->driver?->name ?? '—',
                optional($t->depart_at)->format('Y-m-d H:i')),
        ])->values();

    return \Inertia\Inertia::render('Dispatch/Index', [
        'pending'      => $pending,
        'ready'        => $ready,
        'vehicles'     => $vehicles,
        'drivers'      => $drivers,
        'active_trips' => $activeTrips,
    ]);
}

    /**
     * Manager rejects a request (pending/assigned).
     */
    public function reject(Request $req, \App\Models\TripRequest $ride_request)
{
    $this->authorize('reject', $ride_request); // manager

    if (!in_array($ride_request->status, ['pending','assigned'])) {
        return back()->with('error','This request cannot be rejected.');
    }

    $req->validate(['note' => ['nullable','string','max:200']]);

    $ride_request->status = 'rejected';
    $ride_request->manager_note = $req->string('note')->toString() ?: 'Rejected';
    $ride_request->save();

    return back()->with('success', 'Request rejected.');
}

// app/Http/Controllers/RideRequestController.php

public function assign(Request $request, \App\Models\TripRequest $rideRequest)
{
    if (!$request->user()?->is_manager) abort(403);

    // Basic input validation (keeps your vehicle active rule)
    $data = $request->validate([
        'vehicle_id' => [
            'required',
            Rule::exists('vehicles', 'id')->where(fn($q) => $q->where('active', true)),
        ],
        'driver_id'  => ['required','exists:users,id'],
        'depart_at'  => ['nullable','date'],
        'return_at'  => ['nullable','date','after:depart_at'],
        'notes'      => ['nullable','string','max:2000'],
    ]);

    // ----- Canonical scheduling times (fallbacks to request's desired* fields) -----
    $fallbackDepart = $rideRequest->desired_departure ?? $rideRequest->desired_time ?? null;
    $fallbackReturn = $rideRequest->desired_return ?? null;

    try {
        $departAt = \Illuminate\Support\Carbon::parse($data['depart_at'] ?? ($fallbackDepart?->toDateTimeString()));
        $returnAt = isset($data['return_at']) || $fallbackReturn
            ? \Illuminate\Support\Carbon::parse(($data['return_at'] ?? $fallbackReturn?->toDateTimeString()))
            : null;
    } catch (\Throwable $e) {
        throw ValidationException::withMessages(['schedule' => 'Invalid date/time provided.']);
    }

    if ($returnAt && $returnAt->lte($departAt)) {
        throw ValidationException::withMessages(['return_at' => 'Return time must be after depart time.']);
    }

    // ----- Request must be approved & not already linked -----
    $status = strtolower((string) $rideRequest->status);
    if ($status !== \App\Models\TripRequest::STATUS_APPROVED) {
        throw ValidationException::withMessages(['request' => 'Request is not approved.']);
    }
    if ($rideRequest->trip_id) {
        throw ValidationException::withMessages(['request' => 'Request is already linked to a trip.']);
    }

    // ----- Prevent the same requester from double-booking in the window -----
if ($tripId = $this->requesterConflict((int) $rideRequest->user_id, $departAt, $returnAt)) {
    throw ValidationException::withMessages([
        'request' => "Requester is already on Trip #{$tripId} in that time window. ".
                     "Either attach this request to that trip or choose a different vehicle/time.",
    ]);
}


    // ----- Capacity check -----
    $vehicle   = \App\Models\Vehicle::findOrFail((int)$data['vehicle_id']);
    $capacity  = (int)($vehicle->capacity ?? 0);
    $needed    = (int)($rideRequest->passengers ?? 1);

    if ($capacity > 0 && $needed > $capacity) {
        throw ValidationException::withMessages([
            'vehicle_id' => "Passengers ($needed) exceed vehicle capacity ($capacity)."
        ]);
    }

    // ----- Overlap checks (active trips only) -----
    if ($this->vehicleConflict((int)$data['vehicle_id'], $departAt, $returnAt)) {
        throw ValidationException::withMessages(['vehicle_id' => 'Vehicle has a conflicting trip in that window.']);
    }
    if ($this->driverConflict((int)$data['driver_id'], $departAt, $returnAt)) {
        throw ValidationException::withMessages(['driver_id' => 'Driver has a conflicting trip in that window.']);
    }

    // ----- Create trip & link request (atomic) -----
    $direction = $rideRequest->direction ?: 'one-way';

    \DB::transaction(function () use ($request, $rideRequest, $data, $departAt, $returnAt, $direction) {
        $trip = \App\Models\Trip::create([
            'vehicle_id' => (int)$data['vehicle_id'],
            'driver_id'  => (int)$data['driver_id'],
            'direction'  => $direction,
            'depart_at'  => $departAt,
            'return_at'  => $returnAt,
            'status'     => 'scheduled',
            'notes'      => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $rideRequest->update([
            'trip_id' => $trip->id,
            'status'  => \App\Models\TripRequest::STATUS_ASSIGNED,
        ]);

        // Notify driver
        if ($driver = \App\Models\User::find($data['driver_id'])) {
            $driver->notify(new \App\Notifications\TripAssignedToDriver($trip));
        }
    });

    return back()->with('success','Trip created and request assigned.');
}

/**
 * Attach an APPROVED request to an existing active trip.
 * Route: POST /dispatch/{ride_request}/attach/{trip}
 */
public function attachToTrip(
    \Illuminate\Http\Request $request,
    \App\Models\TripRequest $rideRequest,
    \App\Models\Trip $trip
) {
    // Manager only
    if (!($request->user()?->is_manager)) {
        abort(403);
    }

    // Trip must be active
    if (!in_array($trip->status, ['scheduled','dispatched','in-progress'], true)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'request' => 'You can only attach to an active trip.',
        ]);
    }

    // Request must be approved and not linked yet
    $status = strtolower((string)$rideRequest->status);
    if ($status !== \App\Models\TripRequest::STATUS_APPROVED) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'request' => 'Request is not approved.',
        ]);
    }
    if ($rideRequest->trip_id) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'request' => 'Request is already linked to a trip.',
        ]);
    }

    // Prevent same person twice on the same trip
    if ($trip->requests()->where('user_id', $rideRequest->user_id)->exists()) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'request' => 'This requester is already on this trip.',
        ]);
    }

    // Prevent requester double-booking in the same window (exclude this trip)
    $departAt = $trip->depart_at ?? now();
    $returnAt = $trip->return_at ?? $departAt;
    if (($conflictTripId = $this->requesterConflict((int)$rideRequest->user_id, $departAt, $returnAt))
        && (int)$conflictTripId !== (int)$trip->id) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'request' => "This requester is already on Trip #{$conflictTripId} in that window.",
        ]);
    }

    // Capacity guard (sum already-attached passengers + this request)
    $vehicle   = $trip->vehicle; // assuming Trip belongsTo Vehicle
    $capacity  = (int)($vehicle->capacity ?? 0);
    $needed    = (int)($rideRequest->passengers ?? 1);

    if ($capacity > 0) {
        $used = (int)$trip->requests()->sum('passengers');
        $remaining = max(0, $capacity - $used);
        if ($needed > $remaining) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'capacity' => "Vehicle capacity would be exceeded. Remaining seats: {$remaining}.",
            ]);
        }
    }

    // Link it
    $rideRequest->update([
        'trip_id' => $trip->id,
        'status'  => \App\Models\TripRequest::STATUS_ASSIGNED,
    ]);

    return back()->with('success', "Attached request #{$rideRequest->id} to Trip #{$trip->id}.");
}



// Only these statuses can block a new assignment
private const ACTIVE_STATUSES = ['scheduled','dispatched','in-progress'];

private function vehicleConflict(int $vehicleId, $start, $end): bool
{
    $start = $start instanceof Carbon ? $start : Carbon::parse($start);
    $end   = $end   ? ($end instanceof Carbon ? $end : Carbon::parse($end)) : $start;

    return \App\Models\Trip::query()
        ->where('vehicle_id', $vehicleId)
        ->whereIn('status', self::ACTIVE_STATUSES)
        ->where(function($q) use ($start,$end){
            $q->whereBetween('depart_at', [$start, $end])
              ->orWhereBetween('return_at', [$start, $end])
              ->orWhere(function($q2) use ($start,$end){
                  $q2->where('depart_at', '<=', $start)
                     ->where(function($q3) use ($end){
                         $q3->whereNull('return_at')->orWhere('return_at', '>=', $end);
                     });
              });
        })
        ->exists();
}

private function driverConflict(int $driverId, $start, $end): bool
{
    $start = $start instanceof Carbon ? $start : Carbon::parse($start);
    $end   = $end   ? ($end instanceof Carbon ? $end : Carbon::parse($end)) : $start;

    return \App\Models\Trip::query()
        ->where('driver_id', $driverId)
        ->whereIn('status', self::ACTIVE_STATUSES)
        ->where(function($q) use ($start,$end){
            $q->whereBetween('depart_at', [$start, $end])
              ->orWhereBetween('return_at', [$start, $end])
              ->orWhere(function($q2) use ($start,$end){
                  $q2->where('depart_at', '<=', $start)
                     ->where(function($q3) use ($end){
                         $q3->whereNull('return_at')->orWhere('return_at', '>=', $end);
                     });
              });
        })
        ->exists();
}

    
    private function getLogisticsManager(): ?\App\Models\User
{
    // Prefer someone flagged as is_manager
    $q = User::query()->where('is_manager', true);

    // Only sort by department if the column exists
    if (Schema::hasColumn('users', 'department')) {
        $q->orderByRaw("CASE WHEN department = 'Logistics' THEN 0 ELSE 1 END");
    }

    $manager = $q->orderBy('id')->first();

    if (!$manager) {
        // Fallback #1: plain 'role' column if present
        if (Schema::hasColumn('users', 'role')) {
            $manager = User::query()
                ->whereIn('role', ['Manager','Dispatch','Logistics'])
                ->orderBy('id')
                ->first();
        } else {
            // Fallback #2: Spatie roles if available
            try {
                if (method_exists(User::class, 'role')) {
                    $manager = User::role(['Manager','Dispatch','Logistics'])
                        ->orderBy('id')
                        ->first();
                }
            } catch (\Throwable $e) {
                // ignore if Spatie not installed
            }
        }
    }

    if (!$manager) {
        // Fallback #3: configured email
        $email = Config::get('logistics.manager_email');
        if ($email) {
            $manager = User::where('email', $email)->first();
        }
    }

    return $manager;
}

// ...

public function attach(Request $request, \App\Models\TripRequest $rideRequest, \App\Models\Trip $trip)
{
    // Managers only
    if (!($request->user()?->is_manager)) abort(403);

    // Only attach approved, unlinked requests
    $status = strtolower((string) $rideRequest->status);
    if ($status !== \App\Models\TripRequest::STATUS_APPROVED || $rideRequest->trip_id) {
        throw ValidationException::withMessages([
            'request' => 'Request must be approved and not already attached to a trip.',
        ]);
    }

    // Trip must be active (allowed statuses)
    if (!in_array($trip->status, ['scheduled','dispatched','in-progress'], true)) {
        throw ValidationException::withMessages([
            'request' => 'You can only attach to an active trip.',
        ]);
    }

    // ✅ SAME PERSON already on THIS trip? (your question)
    if ($trip->requests()->where('user_id', $rideRequest->user_id)->exists()) {
        throw ValidationException::withMessages([
            'request' => 'This requester is already on this trip.',
        ]);
    }

    // Capacity guard (sum existing passengers + this request)
    $trip->loadMissing('vehicle');
    $capacity = (int) ($trip->vehicle->capacity ?? 0);
    $current  = (int) $trip->requests()->sum('passengers');
    $needed   = (int) ($rideRequest->passengers ?? 1);

    if ($capacity > 0 && ($current + $needed) > $capacity) {
        throw ValidationException::withMessages([
            'capacity' => "Vehicle capacity exceeded: {$current}/{$capacity} already booked; need +{$needed}.",
        ]);
    }

    // Optional sanity: ensure desired time fits trip window (soft check)
    if ($rideRequest->desired_departure && $trip->depart_at && $trip->return_at) {
        $dt = Carbon::parse($rideRequest->desired_departure);
        if ($dt->lt($trip->depart_at) || $dt->gt($trip->return_at)) {
            // Not fatal; change to ValidationException if you want to enforce strictly
            // throw ValidationException::withMessages(['schedule' => 'Requested time is outside trip window.']);
        }
    }

    // Link it
    $rideRequest->update([
        'trip_id' => $trip->id,
        'status'  => \App\Models\TripRequest::STATUS_ASSIGNED,
    ]);

    return back()->with('success', "Attached to Trip #{$trip->id}.");
}

// Check if a requester already sits on any trip in the time window.
// Returns the conflicting trip id (or null if none).
private function requesterConflict(int $userId, $start, $end): ?int
{
    $start = $start instanceof Carbon ? $start : Carbon::parse($start);
    $end   = $end   ? ($end instanceof Carbon ? $end : Carbon::parse($end)) : $start;

    $trip = \App\Models\Trip::query()
        ->whereIn('status', self::ACTIVE_STATUSES)
        ->where(function($q) use ($start,$end){
            $q->whereBetween('depart_at', [$start, $end])
              ->orWhereBetween('return_at', [$start, $end])
              ->orWhere(function($q2) use ($start,$end){
                  $q2->where('depart_at', '<=', $start)
                     ->where(function($q3) use ($end){
                         $q3->whereNull('return_at')->orWhere('return_at', '>=', $end);
                     });
              });
        })
        ->whereHas('requests', fn($rq) => $rq->where('user_id', $userId))
        ->orderByDesc('depart_at')
        ->first(['id']);

    return $trip?->id;
}



}
