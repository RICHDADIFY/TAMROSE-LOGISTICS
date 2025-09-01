<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Consignment;
use App\Models\CustodyEvent;

class CustodyEventPolicy
{
    /**
     * Drivers (on this trip) or managers can create custody events
     * e.g., enroute/onsite/delivered/return_* (via store/verifyOtp).
     */
    public function create(User $user, Consignment $consignment): bool
    {
        // Managers
        if (($user->is_manager ?? false)) return true;
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin','Logistics Manager'])) return true;

        // Driver assigned to the trip
        $trip = $consignment->trip; // requires Consignment::trip() relation
        if (!$trip) return false;
        return (int)($trip->driver_id ?? 0) === (int)$user->id;
    }

    /**
     * Only managers should be able to generate/refresh OTPs.
     * Used by /consignments/{consignment}/prepare-delivery
     */
    public function prepareOtp(User $user, Consignment $consignment): bool
    {
        if (($user->is_manager ?? false)) return true;
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin','Logistics Manager'])) return true;
        return false;
    }
}
