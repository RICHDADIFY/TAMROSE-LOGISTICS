<?php

namespace App\Http\Controllers;

use App\Models\CustodyEvent;
use App\Models\Consignment;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;




class CustodyEventController extends Controller
{
 

public function index(Consignment $consignment)
{
    $this->authorize('view', $consignment->trip);

    $events = CustodyEvent::where('consignment_id', $consignment->id)
        ->orderBy('created_at','asc')
        ->get()
        ->map(function ($e) {
            // build a public URL if you stored in 'public' disk
            $sigUrl = null;
            if (!empty($e->signature_path ?? $e->signature)) {
                $path = $e->signature_path ?? $e->signature;
                $sigUrl = Storage::disk('public')->url($path);
            }

            // normalize photos
            $photos = [];
            $raw = $e->photos_json ?? $e->photos ?? null;
            if (is_string($raw)) {
                $arr = json_decode($raw, true);
                if (is_array($arr)) {
                    $photos = array_map(fn($p) => is_string($p) ? Storage::disk('public')->url($p) : $p, $arr);
                }
            } elseif (is_array($raw)) {
                $photos = array_map(fn($p) => is_string($p) ? Storage::disk('public')->url($p) : $p, $raw);
            }

            return [
                'id'             => $e->id,
                'type'           => $e->type ?? $e->status ?? 'event',
                'occurred_at'    => optional($e->occurred_at ?? $e->created_at)->toIso8601String(),
                'by_user'        => null, // add relation if you have it
                'receiver_name'  => $e->receiver_name ?? null,
                'receiver_phone' => $e->receiver_phone ?? null,
                'otp_used'       => $e->otp_used ?? null,
                'lat'            => $e->lat ?? null,
                'lng'            => $e->lng ?? null,
                'photos'         => $photos,
                'signature_url'  => $sigUrl,
            ];
        })
        ->values();

    return response()->json(['ok' => true, 'data' => $events]);
}

    public function store(Request $request, Trip $trip, Consignment $consignment)
    {
        // guard that this consignment belongs to this trip
        abort_unless((int)$consignment->trip_id === (int)$trip->id, 404);

        $this->authorize('create', [CustodyEvent::class, $consignment]);

        $data = $request->validate([
            'type'           => ['required','in:loaded,enroute,onsite,delivered,return_collected,return_delivered,failed'],
            'occurred_at'    => ['nullable','date'],
            'lat'            => ['nullable','numeric','between:-90,90'],
            'lng'            => ['nullable','numeric','between:-180,180'],
            'receiver_name'  => ['nullable','string','max:191'],
            'receiver_phone' => ['nullable','string','max:32'],
            'otp_used'       => ['nullable','string','max:12'],
            'signature_path' => ['nullable','string','max:191'],
            'photos'         => ['nullable','array'],
            'photos.*'       => ['nullable','string'], // store paths from frontend upload
            'note'           => ['nullable','string'],
        ]);

        $event = CustodyEvent::create([
            'consignment_id' => $consignment->id,
            'user_id'        => $request->user()->id,
            'type'           => $data['type'],
            'occurred_at'    => $data['occurred_at'] ?? now(),
            'lat'            => $data['lat'] ?? null,
            'lng'            => $data['lng'] ?? null,
            'receiver_name'  => $data['receiver_name'] ?? null,
            'receiver_phone' => $data['receiver_phone'] ?? null,
            'otp_used'       => $data['otp_used'] ?? null,
            'signature_path' => $data['signature_path'] ?? null,
            'photos_json'    => $data['photos'] ?? [],
            'note'           => $data['note'] ?? null,
        ]);

        return response()->json(['ok' => true, 'event' => $event]);
    }

   

public function verifyOtp(Request $request, Consignment $consignment)
{
    try {
        $this->authorize('create', [CustodyEvent::class, $consignment]);

        $requireOtp  = (bool) ($consignment->require_otp ?? false);
        // Driver can explicitly send mode, but we also infer from presence of a signature.
        $mode        = $request->string('mode')->lower()->value(); // 'otp' | 'signature' | ''
        $useSignature = $request->hasFile('signature') || $mode === 'signature';

        $base = [
            'receiver_name'  => ['required','string','max:191'],
            'receiver_phone' => ['nullable','string','max:32'],
            'photos.*'       => ['nullable','image','max:5120'],
            'lat'            => ['nullable','numeric'],
            'lng'            => ['nullable','numeric'],
            'note'           => ['nullable','string','max:500'],
        ];

        $rules = array_merge($base, [
            // If OTP is required and no signature is being used -> OTP must be present
            'otp'       => [Rule::requiredIf($requireOtp && !$useSignature), 'nullable','string','max:12'],
            // If OTP is not required OR a signature is being used -> signature must be present
            'signature' => [Rule::requiredIf(!$requireOtp || $useSignature), 'nullable','image','mimes:png,webp,jpeg','max:256'],
        ]);

        $validated = $request->validate($rules);

        // OTP check only if we're *not* using signature in a consignment that requires OTP
        if ($requireOtp && !$useSignature) {
            if (!$consignment->delivery_otp) {
                return response()->json(['ok' => false, 'error' => 'No OTP set. Ask manager to generate one.'], 422);
            }
            if ($consignment->otp_expires_at && now()->gt($consignment->otp_expires_at)) {
                return response()->json(['ok' => false, 'error' => 'OTP expired. Ask manager to regenerate.'], 422);
            }
            if (!hash_equals((string)$consignment->delivery_otp, (string)$request->input('otp'))) {
                return response()->json(['ok' => false, 'error' => 'Invalid OTP.'], 422);
            }
        }

        // Save photos
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ((array)$request->file('photos') as $file) {
                $photoPaths[] = $file->store("custody/consignments/{$consignment->id}", 'public');
            }
        }

        // Save signature
        $signaturePath = null;
        if ($request->hasFile('signature')) {
            $signaturePath = $request->file('signature')
                ->store("custody/consignments/{$consignment->id}", 'public');
        }

        // Create event
        $event = new CustodyEvent();
        $event->consignment_id = $consignment->id;
        $event->user_id        = $request->user()->id;
        $event->type           = 'delivered';
        $event->occurred_at    = now();
        $event->receiver_name  = $validated['receiver_name'];
        $event->receiver_phone = $validated['receiver_phone'] ?? null;
        $event->lat            = $validated['lat'] ?? null;
        $event->lng            = $validated['lng'] ?? null;
        $event->otp_used       = ($requireOtp && !$useSignature) ? (string) $request->input('otp') : null;
        $event->photos_json    = $photoPaths ? json_encode($photoPaths) : null;
        $event->signature_path = $signaturePath;

        // Add a clear note if signature fallback was used while OTP required
        $note = (string) ($validated['note'] ?? '');
        if ($requireOtp && $useSignature) {
            $note = trim($note . ' ' . '(Signature fallback used; OTP not provided)');
        }
        $event->note = $note ?: null;

        $event->save();

        // Update consignment + clear OTP (never reuse)
        $consignment->forceFill([
            'status'         => 'delivered',
            'delivery_otp'   => null,
            'otp_expires_at' => null,
        ])->save();

        return response()->json([
            'ok'        => true,
            'event_id'  => $event->id,
            'photos'    => $photoPaths,
            'signature' => $signaturePath,
        ]);
    } catch (ValidationException $e) {
        return response()->json(['ok' => false, 'error' => $e->errors()], 422);
    } catch (AuthorizationException $e) {
        return response()->json(['ok' => false, 'error' => 'Not allowed'], 403);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => 'Server error'], 500);
    }
}

  public function prepareDelivery(Request $request, Consignment $consignment)
{
    try {
        $this->authorize('prepareOtp', [CustodyEvent::class, $consignment]);

        if (!$consignment->require_otp) {
            return response()->json(['ok' => false, 'error' => 'OTP disabled for this consignment. Use signature flow.']);
        }

        $expired = $consignment->otp_expires_at && now()->gt($consignment->otp_expires_at);
        if (!$consignment->delivery_otp || $expired) {
            $consignment->generateDeliveryOtp(); // uses model tunables
            $consignment->refresh();
        }

        return response()->json([
            'ok'         => true,
            'otp'        => $consignment->delivery_otp, // you may hide this in prod
            'expires_at' => optional($consignment->otp_expires_at)->toIso8601String(),
        ]);
    } catch (AuthorizationException $e) {
        return response()->json(['ok' => false, 'error' => 'Not allowed'], 403);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => 'Server error'], 500);
    }
}



public function setRequireOtp(Request $request, Consignment $consignment)
{
    // only managers
    if (!($request->user()->is_manager ?? false)) {
        return response()->json(['ok' => false, 'error' => 'Not allowed'], 403);
    }

    $data = $request->validate([
        'require_otp' => ['required','boolean'],
    ]);

    $consignment->require_otp = $data['require_otp'];

    // if turning OFF, clear any lingering OTP
    if (!$consignment->require_otp) {
        $consignment->delivery_otp   = null;
        $consignment->otp_expires_at = null;
    }

    $consignment->save();

    return response()->json([
        'ok'          => true,
        'require_otp' => (bool) $consignment->require_otp,
    ]);
}

public function deliveryMeta(\App\Models\Consignment $consignment)
{
    $this->authorize('view', $consignment->trip); // same policy you use for viewing trip

    return response()->json([
        'ok'            => true,
        'require_otp'   => (bool) ($consignment->require_otp ?? false),
        'otp_set'       => !empty($consignment->delivery_otp),
        'expires_at'    => optional($consignment->otp_expires_at)->toIso8601String(),
        'now'           => now()->toIso8601String(),
    ]);
}


}
