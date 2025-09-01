<?php
namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;

class TripDropController extends Controller
{
    public function store(Request $request, Trip $trip)
    {
        $this->authorize('update', $trip); // managers

        $payload = $request->validate([
            'drops'                       => ['required','array','min:1'],
            'drops.*.destination'         => ['required','in:onne,guest_house'],
            'drops.*.port_id'             => ['nullable','integer','exists:ports,id'],
            'drops.*.vessel_id'           => ['nullable','integer','exists:vessels,id'],
            'drops.*.destination_label'   => ['nullable','string','max:255'],
            'drops.*.items'               => ['nullable','array'],
            'drops.*.items.*.description' => ['required','string','max:255'],
            'drops.*.items.*.quantity'    => ['nullable','integer','min:1'],
            'drops.*.items.*.unit'        => ['nullable','string','max:50'],
        ]);

        foreach ($payload['drops'] as $row) {
            // Map UI → your schema
            $isOnne      = $row['destination'] === 'onne';
            $portId      = $isOnne ? ($row['port_id'] ?? null) : null;
            $vesselId    = $isOnne ? ($row['vessel_id'] ?? null) : null;
            $destLabel   = $isOnne ? null : ($row['destination_label'] ?? 'Guest House');

            $consignment = $trip->consignments()->create([
                'type'              => 'outbound',   // simple default
                'status'            => 'pending',    // simple default
                'port_id'           => $portId,
                'vessel_id'         => $vesselId,
                'destination_label' => $destLabel,

                // Optional contact fields left null for now:
                // 'contact_name' , 'contact_phone', 'contact_email',
                // 'otp_code', 'otp_expires_at', 'evidence_json',
                // 'related_consignment_id',
            ]);

            foreach (($row['items'] ?? []) as $it) {
                $consignment->items()->create([
                    'description' => $it['description'],
                    'quantity'    => $it['quantity'] ?? 1,
                    'unit'        => $it['unit'] ?? null,
                ]);
            }
        }

        // After save, go back to Edit so you can see the "Existing consignments" list
        return redirect()
            ->route('trips.edit', $trip->id)
            ->with('success', 'Drops saved.');
    }
}
