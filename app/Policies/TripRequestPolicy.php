<?php

namespace App\Policies;

use App\Models\TripRequest;
use App\Models\User;

class TripRequestPolicy
{
    /** Helper: treat Logistics Manager and Super Admin as managers */
    private function isManager(User $user): bool
    {
        return $user->hasAnyRole(['Logistics Manager', 'Super Admin']);
    }

    // Managers see all (dispatch board)
    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    // Owner or manager can view a single request
    public function view(User $user, TripRequest $request): bool
    {
        return $user->id === $request->user_id || $this->isManager($user);
    }

    // Any authenticated user can create
    public function create(User $user): bool
    {
        return $user->id !== null;
    }

    /**
     * Update = edit request details (not a status change).
     * Rule: owner OR manager while pending.
     */
    public function update(User $user, TripRequest $request): bool
    {
        $isPending = $request->status === TripRequest::STATUS_PENDING;
        $isOwner   = $user->id === $request->user_id;

        return $isPending && ($isOwner || $this->isManager($user));
    }

    // --- Status transitions ---

    // Approve: manager only, request must be pending
    public function approve(User $user, TripRequest $request): bool
    {
        return $this->isManager($user)
            && $request->status === TripRequest::STATUS_PENDING;
    }

    // Reject: manager only, request must be pending
    public function reject(User $user, TripRequest $request): bool
    {
        return $this->isManager($user)
            && $request->status === TripRequest::STATUS_PENDING;
    }

    /**
     * Cancel:
     * - Owner can cancel while pending.
     * - Manager can cancel while pending OR approved (ops change).
     */
    public function cancel(User $user, TripRequest $request): bool
    {
        if ($user->id === $request->user_id) {
            return $request->status === TripRequest::STATUS_PENDING;
        }

        if ($this->isManager($user)) {
            return in_array($request->status, [
                TripRequest::STATUS_PENDING,
                TripRequest::STATUS_APPROVED,
            ], true);
        }

        return false;
    }
}
