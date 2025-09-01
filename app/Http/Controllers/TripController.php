<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\Port;
use App\Models\Vessel;
use App\Models\Consignment;
use App\Models\ConsignmentItem;


class TripController extends Controller
{
public function index(Request $request)
{
    $user = $request->user();

    // ✅ Drivers get the MyTrips experience at /trips
    if ($user->hasRole('Driver')) {
        return $this->myTrips($request); // reuse your existing method
    }

    // Managers & staff: your existing index logic
    $query = Trip::with(['vehicle','driver']);

    if ((bool)($user->is_manager ?? false)) {
        // Managers see all trips.
    } else {
        // Staff: only trips they created OR trips where they’re one of the requesters.
        $query->where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('requests', fn($rq) => $rq->where('user_id', $user->id));
        });
    }

    if ($status = $request->query('status')) {
        $query->where('status', $status);
    }

    $trips = $query
        ->orderByRaw("CASE status
            WHEN 'in-progress' THEN 1
            WHEN 'dispatched'  THEN 2
            WHEN 'scheduled'   THEN 3
            WHEN 'completed'   THEN 4
            WHEN 'cancelled'   THEN 5
            ELSE 6 END")
        ->orderByDesc('updated_at')
        ->orderByDesc('depart_at')
        ->paginate(12)
        ->through(fn($t) => [
            'id'        => $t->id,
            'vehicle'   => $t->vehicle?->display_label ?? $t->vehicle?->label,
            'driver'    => $t->driver?->name,
            'direction' => $t->direction,
            'depart_at' => optional($t->depart_at)->toDateTimeString(),
            'return_at' => optional($t->return_at)->toDateTimeString(),
            'status'    => $t->status,
        ]);

    return Inertia::render('Trips/Index', [
        'trips'   => $trips,
        'filters' => ['status' => $status],
    ]);
}

public function show(\App\Models\Trip $trip)
{
    $this->authorize('view', $trip);

    // Load everything we need (incl. all requests + their users + consignments)
    $trip->load([
    'vehicle','driver','request','requests.user',
    'consignments.items','consignments.port','consignments.vessel',
    'consignments.latestEvent', // ⬅️ add this
]);


    // Choose a request to read labels/coords from
    $req = $trip->request ?: $trip->requests()->latest('id')->first();

    // Labels (from DB columns)
    $originLabel      = $req?->from_location;
    $destinationLabel = $req?->to_location;

    // Coords (read-only; NO geocoding or write-backs here)
    $fromLat = $req?->from_lat !== null ? (float) $req->from_lat : null;
    $fromLng = $req?->from_lng !== null ? (float) $req->from_lng : null;
    $toLat   = $req?->to_lat   !== null ? (float) $req->to_lat   : null;
    $toLng   = $req?->to_lng   !== null ? (float) $req->to_lng   : null;

    $driver  = $trip->driver;
    $vehicle = $trip->vehicle;

    $completedTrips = $driver
        ? \App\Models\Trip::where('driver_id', $driver->id)->where('status', 'completed')->count()
        : 0;

    $avatarUrl = $driver
        ? ($driver->profile_photo_url
            ?? 'https://ui-avatars.com/api/?name=' . urlencode($driver->name) . '&background=10B981&color=fff')
        : null;

    /* ---------------- Staff on this trip (could be many) ---------------- */
    $staffUsers = collect($trip->requests ?? [])
        ->pluck('user')
        ->filter()
        ->values();

    // Fallback if only a single `request` exists/loaded
    if ($staffUsers->isEmpty() && $trip->relationLoaded('request') && $trip->request?->user) {
        $staffUsers = collect([$trip->request->user]);
    }

    $staffCount = $staffUsers->count();

    // Helper → normalize user into ContactPanel shape
    $asCard = function (? \App\Models\User $u, string $tag) {
        if (!$u) return null;
        $avatar = $u->profile_photo_url
            ?? ($u->profile_photo ? asset($u->profile_photo) : null);
        return [
            'tag'      => $tag, // 'manager' | 'requester' | 'driver'
            'name'     => $u->name,
            'email'    => $u->email,
            'phone'    => $u->phone ?? null,
            'whatsapp' => $u->phone ?? null,
            'avatar'   => $avatar,
        ];
    };

    // Logistics Manager (for driver view)
    $manager = \App\Models\User::where('is_manager', true)->orderBy('id')->first();
    if (!$manager) {
        $fallbackEmail = config('logistics.manager_email') ?: env('LOGISTICS_MANAGER_EMAIL');
        if ($fallbackEmail) $manager = \App\Models\User::where('email', $fallbackEmail)->first();
    }

    $viewer          = request()->user();
    $isDriverViewer  = $viewer && $trip->driver_id && (int)$viewer->id === (int)$trip->driver_id;
    $isManagerViewer = (bool)($viewer->is_manager ?? false);

    $contacts      = []; // for driver: manager
    $staffContacts = []; // for driver and for manager

    if ($isDriverViewer) {
        if ($manager) $contacts[] = $asCard($manager, 'manager');
        $staffContacts = $staffUsers->map(fn($u) => $asCard($u, 'requester'))->filter()->values()->all();
    }

    if ($isManagerViewer) {
        // managers also get the staff list
        $staffContacts = $staffUsers->map(fn($u) => $asCard($u, 'requester'))->filter()->values()->all();
    }

    /* ------------ Role-based map visibility ------------ */
    $mapsDisabledEnv = (bool) (config('services.google.disable_maps') ?? env('MAPS_DISABLED', false));
    $showMaps = (!$isDriverViewer) && (!$mapsDisabledEnv);

    $consignmentsForUi = $trip->consignments->map(function ($c) {
    $destLabel = $c->destination_label
        ?? ($c->vessel?->name
            ? trim(($c->port?->code ? ($c->port->code . ' • ') : '') . $c->vessel->name)
            : ($c->destination ?: $c->type ?: 'Consignment'));

    $itemsText = $c->items->map(function ($i) {
        $desc = trim((string) $i->description);
        $qty  = trim((string) $i->quantity);
        $unit = trim((string) $i->unit);
        $unitLabel = $unit ? (preg_match('/^[0-9.]+$/', $unit) ? ' units' : " {$unit}") : '';
        return "{$desc} × {$qty}{$unitLabel}";
    })->implode(', ');

    return [
        'id'                => $c->id,
        'trip_id'           => $c->trip_id,
        'type'              => $c->type,
        'destination'       => $c->destination,
        'destination_label' => $destLabel,
        'port'              => $c->port?->code,
        'vessel'            => $c->vessel?->name,
        'return_expected'   => (bool) ($c->return_expected ?? false),
        'items_text'        => $itemsText,
        'last_event'        => $c->latestEvent ? [
            'type'        => $c->latestEvent->type,
            'occurred_at' => optional($c->latestEvent->occurred_at)->toIso8601String(),
        ] : null,
    ];
})->values();


    return \Inertia\Inertia::render('Trips/Show', [
    'trip' => [
        'id'          => $trip->id,
        'vehicle'     => $trip->vehicle,
        'driver'      => $trip->driver,
        'direction'   => $trip->direction,
        'depart_at'   => optional($trip->depart_at)->format('Y-m-d H:i'),
        'return_at'   => optional($trip->return_at)->format('Y-m-d H:i'),
        'status'      => $trip->status,
        'notes'       => $trip->notes,

        // Labels + raw coords (read-only)
        'origin'      => $originLabel,
        'destination' => $destinationLabel,
        'from_lat'    => $fromLat,
        'from_lng'    => $fromLng,
        'to_lat'      => $toLat,
        'to_lng'      => $toLng,

        'staff_count' => $staffCount,

        // ✅ NEW: used by the interactive ConsignmentCard(s)
        'consignments' => $consignmentsForUi,
    ],

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

    // For driver view (Logistics Manager)
    'contacts'       => array_values(array_filter($contacts)),

    // For driver **and** manager view (staff list)
    'staff_contacts' => array_values(array_filter($staffContacts)),

    // (You can keep this old list for now or remove it later)
    'existing_consignments' => $trip->consignments->map(function ($c) {
        return [
            'id'     => $c->id,
            'type'   => $c->type,
            'status' => $c->status,
            'port'   => $c->port?->code,
            'vessel' => $c->vessel?->name ?? ($c->destination_label ?: '—'),
            'require_otp' => (bool) ($c->require_otp ?? false), // 👈 add this
            'items'  => $c->items->map(fn($i) => [
                'description' => $i->description,
                'quantity'    => $i->quantity,
                'unit'        => $i->unit,
            ])->values(),
        ];
    })->values(),

    // Maps
    'show_maps' => $showMaps,
    'map' => $showMaps ? [
        'from' => ($fromLat !== null && $fromLng !== null) ? ['lat' => $fromLat, 'lng' => $fromLng] : null,
        'to'   => ($toLat   !== null && $toLng   !== null) ? ['lat' => $toLat,   'lng' => $toLng]   : null,
    ] : null,

    'config' => $showMaps
        ? ['google' => ['mapsBrowserKey' => config('services.google.maps_browser_key')]]
        : ['google' => ['mapsBrowserKey' => null]],
]);
}


   // app/Http/Controllers/TripController.php (edit)
public function edit(\App\Models\Trip $trip)
{
    $this->authorize('update', $trip);

    $trip->load([
        'vehicle','driver',
        'consignments.items',
        'consignments.port',
        'consignments.vessel',
    ]);

    return \Inertia\Inertia::render('Trips/Edit', [
        'trip' => [
            'id'        => $trip->id,
            'status'    => $trip->status,
            'depart_at' => optional($trip->depart_at)->format('Y-m-d\TH:i'),
            'return_at' => optional($trip->return_at)->format('Y-m-d\TH:i'),
            'notes'     => $trip->notes,
        ],
        'ports'   => \App\Models\Port::orderBy('code')->get(['id','code']),
        'vessels' => \App\Models\Vessel::orderBy('name')->get(['id','name']),

        // what Edit.jsx expects
        'consignments' => $trip->consignments->map(function ($c) {
            return [
                'id'     => $c->id,
                'type'   => $c->type,
                'status' => $c->status,
                'port'   => $c->port?->code,
                'vessel' => $c->vessel?->name ?? ($c->destination_label ?: '—'),
                'items'  => $c->items->map(fn($i) => [
                    'description' => $i->description,
                    'quantity'    => $i->quantity,
                    'unit'        => $i->unit,
                ])->values(),
            ];
        })->values(),
    ]);
}


public function update(Request $request, Trip $trip)
{
    $this->authorize('view', $trip); // 👈 enforce per-trip access

    $data = $request->validate([
        'status'    => ['required','in:scheduled,dispatched,in-progress,completed,cancelled'],
        'notes'     => ['nullable','string','max:2000'],
        'depart_at' => ['nullable','date'],
        'return_at' => ['nullable','date','after:depart_at'],
    ]);

    $trip->update($data);
    return redirect()->route('trips.show',$trip)->with('success','Trip updated.');
}

public function destroy(Trip $trip)
{
    $this->authorize('view', $trip); // 👈 enforce per-trip access

    $trip->delete();
    return redirect()->route('trips.index')->with('success','Trip deleted.');
}

public function managerStatus(Request $request, Trip $trip)
{
    
    $this->authorize('view', $trip); // optional, extra safety
    abort_unless((bool)($request->user()->is_manager ?? false), 403);
    

    $data = $request->validate([
        'action' => ['required', Rule::in(['dispatch','complete','cancel'])],
    ]);

    switch ($data['action']) {
        case 'dispatch':
            // scheduled -> dispatched
            if ($trip->status === 'scheduled') {
                $trip->status = 'dispatched';
                $trip->save();
            }
            break;
        case 'complete':
            // in-progress -> completed
            if ($trip->status === 'in-progress') {
                $trip->status = 'completed';
                $trip->return_at = $trip->return_at ?? now(); // optional
                $trip->save();
            }
            break;
        case 'cancel':
            if (in_array($trip->status, ['scheduled','dispatched','in-progress'], true)) {
                $trip->status = 'cancelled';
                $trip->save();
            }
            break;
    }

    return back()->with('success', 'Trip updated.');
}

public function driverStatus(Request $request, Trip $trip)
{
    $this->authorize('view', $trip);
    abort_unless($request->user()->id === (int)$trip->driver_id, 403);

    $data = $request->validate([
        'action' => ['required', Rule::in(['start','complete'])],
        'note'   => ['nullable','string','max:1000'],
    ]);

    switch ($data['action']) {
        case 'start':
            if (in_array($trip->status, ['scheduled','dispatched'], true)) {
                $trip->status = 'in-progress';
                $trip->depart_at = $trip->depart_at ?? now();
                $trip->save();
            }
            break;

        case 'complete':
            if ($trip->status === 'in-progress') {
                $trip->status = 'completed';
                $trip->return_at = $trip->return_at ?? now();

                if (!empty($data['note'])) {
                    // append to notes (simple journal)
                    $stamp = now()->format('Y-m-d H:i');
                    $by    = $request->user()->name;
                    $trip->notes = trim(($trip->notes ?? '') . "\n[$stamp by $by] " . $data['note']);
                }

                $trip->save();
            }
            break;
    }

    return back()->with('success', 'Trip updated.');
}

// app/Http/Controllers/TripController.php

public function myTrips(Request $request)
{
   $user = $request->user();
    abort_unless($user->hasRole('Driver'), 403);

    // Time anchors (Lagos timezone already set in your app)
    $todayStart = now()->startOfDay();
    $todayEnd   = now()->endOfDay();

    $base = \App\Models\Trip::with(['vehicle','driver'])
        ->where('driver_id', $user->id);

    // Today (scheduled/ dispatched/ in-progress happening today)
    // Today (scheduled / dispatched / in-progress happening today)
$today = (clone $base)
    ->where(function ($q) use ($todayStart, $todayEnd) {
        $q->whereBetween('depart_at', [$todayStart, $todayEnd])
          ->orWhere(function ($qq) use ($todayStart, $todayEnd) {
              $qq->whereBetween('updated_at', [$todayStart, $todayEnd])
                 ->whereIn('status', ['scheduled','dispatched','in-progress']);
          });
    })
    ->orderByRaw("FIELD(status,'in-progress','dispatched','scheduled')")
    ->orderBy('depart_at')
    ->get()
    ->map(fn($t) => $this->toDriverCard($t));


    // Upcoming (after today; scheduled/ dispatched)
    $upcoming = (clone $base)
        ->where('depart_at', '>', $todayEnd)
        ->whereIn('status', ['scheduled','dispatched'])
        ->orderBy('depart_at')
        ->limit(20)
        ->get()
        ->map(fn($t) => $this->toDriverCard($t));

    // Recent completed (most recent 20)
    $recentCompleted = (clone $base)
        ->where('status', 'completed')
        ->orderByDesc('return_at')
        ->orderByDesc('updated_at')
        ->limit(20)
        ->get()
        ->map(fn($t) => $this->toDriverCard($t));

    // Logistics Manager contact (for the card)
    $manager = \App\Models\User::where('is_manager', true)->orderBy('id')->first();
    if (!$manager) {
        $fallbackEmail = config('logistics.manager_email') ?: env('LOGISTICS_MANAGER_EMAIL');
        if ($fallbackEmail) $manager = \App\Models\User::where('email', $fallbackEmail)->first();
    }
    $managerCard = $manager ? [
        'name'  => $manager->name,
        'email' => $manager->email,
        'phone' => $manager->phone ?? null,
        'avatar'=> $manager->profile_photo_url
            ?? ($manager->profile_photo ? asset($manager->profile_photo) : null),
    ] : null;

    return Inertia::render('Trips/MyTrips', [
        'today'           => $today,
        'upcoming'        => $upcoming,
        'recent_completed'=> $recentCompleted,
        'manager'         => $managerCard,
    ]);
}

/**
 * Normalize a trip into a compact driver card shape.
 */
private function toDriverCard(\App\Models\Trip $t): array
{
    // Prefer primary request
    $req = $t->request ?: $t->requests()->latest('id')->first();

    return [
        'id'         => $t->id,
        'status'     => $t->status,
        'depart_at'  => optional($t->depart_at)->format('Y-m-d H:i'),
        'return_at'  => optional($t->return_at)->format('Y-m-d H:i'),
        'direction'  => $t->direction,
        'origin'     => $req?->from_location,
        'destination'=> $req?->to_location,
        'vehicle'    => [
            'label' => $t->vehicle?->display_label ?? $t->vehicle?->label ?? ("Vehicle #{$t->vehicle_id}"),
            'plate' => $t->vehicle?->plate_number,
            'model' => $t->vehicle?->model,
        ],
        // raw coords (handy for “Open in Google Maps” deeplink — no API usage)
        'from_lat'   => $req?->from_lat !== null ? (float)$req->from_lat : null,
        'from_lng'   => $req?->from_lng !== null ? (float)$req->from_lng : null,
        'to_lat'     => $req?->to_lat   !== null ? (float)$req->to_lat   : null,
        'to_lng'     => $req?->to_lng   !== null ? (float)$req->to_lng   : null,
    ];
}


public function storeDrops(Request $request, \App\Models\Trip $trip)
{
    $this->authorize('view', $trip);
    abort_unless((bool)$request->user()->is_manager, 403);

    $data = $request->validate([
        'drops' => ['required','array','min:1','max:12'],

        // Destination selector:
        'drops.*.destination'  => ['required','in:onne,guest_house'],
        'drops.*.port_id'      => ['nullable','integer','exists:ports,id'],     // required when destination=onne
        'drops.*.vessel_id'    => ['nullable','integer','exists:vessels,id'],   // required when destination=onne

        // When destination = guest_house (no port/vessel):
        'drops.*.destination_label' => ['nullable','string','max:120'],

        // Items:
        'drops.*.items'                    => ['array'],
        'drops.*.items.*.description'      => ['required','string','max:255'],
        'drops.*.items.*.quantity'         => ['required','integer','min:1'],
        'drops.*.items.*.unit'             => ['nullable','string','max:24'],

        // Return expected?
        'drops.*.return_expected' => ['sometimes','boolean'],
    ]);

    foreach ($data['drops'] as $row) {
        $destination = $row['destination']; // onne|guest_house

        // Validate required combos
        if ($destination === 'onne') {
            abort_unless(!empty($row['port_id']) && !empty($row['vessel_id']), 422);
        }

        // Resolve contacts from vessel -> port (fallback)
        $port    = !empty($row['port_id'])   ? Port::find($row['port_id'])     : null;
        $vessel  = !empty($row['vessel_id']) ? Vessel::find($row['vessel_id']) : null;

        $contact_name  = $vessel?->contact_name  ?? $port?->contact_name;
        $contact_phone = $vessel?->contact_phone ?? $port?->contact_phone;
        $contact_email = $vessel?->contact_email ?? $port?->contact_email;

        // Create OUTBOUND consignment
        $outbound = Consignment::create([
            'trip_id'      => $trip->id,
            'type'         => 'outbound',
            'status'       => 'pending_load',
            'port_id'      => $destination === 'onne' ? $port?->id : null,
            'vessel_id'    => $destination === 'onne' ? $vessel?->id : null,
            'destination_label' => $destination === 'guest_house' ? ($row['destination_label'] ?? 'Guest House') : null,

            'contact_name'  => $contact_name,
            'contact_phone' => $contact_phone,
            'contact_email' => $contact_email,

            'otp_code'       => $this->makeOtp(),      // MVP: plain; harden later
            'otp_expires_at' => now()->addDay(),
        ]);

        foreach ($row['items'] ?? [] as $it) {
            ConsignmentItem::create([
                'consignment_id' => $outbound->id,
                'description'    => $it['description'],
                'quantity'       => (int) $it['quantity'],
                'unit'           => $it['unit'] ?? null,
            ]);
        }

        // Optional paired RETURN placeholder (so drivers see it on the day)
        if (!empty($row['return_expected'])) {
            $return = Consignment::create([
                'trip_id'      => $trip->id,
                'type'         => 'return',
                'status'       => 'pending_load', // becomes 'collected' at vessel, then 'returned' at Office
                'port_id'      => $outbound->port_id,
                'vessel_id'    => $outbound->vessel_id,
                'destination_label' => $outbound->destination_label,
                'contact_name'  => $contact_name,
                'contact_phone' => $contact_phone,
                'contact_email' => $contact_email,
                'related_consignment_id' => $outbound->id,
            ]);
            $outbound->update(['related_consignment_id' => $return->id]);
        }
    }

    return back()->with('success', 'Drops added to trip.');
}

protected function makeOtp(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}




}
