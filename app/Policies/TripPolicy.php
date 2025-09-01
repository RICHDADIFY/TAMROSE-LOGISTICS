<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function viewAny(User $user): bool
    {
        // We’ll filter the index query by role later; allow reaching the page.
        return true;
    }

    public function view(User $user, Trip $trip): bool
    {
        $isManager = (bool)($user->is_manager ?? false);
        $isDriver  = (int)($trip->driver_id ?? 0) === (int)$user->id;

        // Owner/requester: creator OR any attached request made by this user
        $isOwner = false;

        if ((int)($trip->created_by ?? 0) === (int)$user->id) {
            $isOwner = true;
        } else {
            // Avoid N+1: use loaded relation if present, else exists() query
            $isOwner = $trip->relationLoaded('requests')
                ? $trip->requests->contains('user_id', $user->id)
                : $trip->requests()->where('user_id', $user->id)->exists();
        }

        return $isManager || $isDriver || $isOwner;
    }
}
