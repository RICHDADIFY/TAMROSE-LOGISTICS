<?php

namespace App\Services;

use App\Models\TripRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestRejectedNotification;
use App\Notifications\RequestCancelledNotification;

class RequestStatusService
{
    /**
     * Transition a TripRequest (string-based) and notify stakeholders.
     */
    public static function set(
        TripRequest $req,
        string $to,                 // 'approved' | 'rejected' | 'cancelled'
        ?int $byUserId,
        ?string $note = null
    ): void {
        // Normalize current status (handle legacy)
        $from = strtolower((string) $req->status);
        if ($from === 'assigned') {
            $from = 'approved';
        }

        // Guard unknown current values
        $valid = ['pending','approved','rejected','cancelled'];
        if (!in_array($from, $valid, true)) {
            abort(422, "Unknown current status '{$from}'");
        }

        // Normalize target
        $to = strtolower(trim($to));
        if (!in_array($to, $valid, true)) {
            abort(422, "Unknown target status '{$to}'");
        }

        // Allowed transitions
        $allowed = [
            'pending'   => ['approved','rejected','cancelled'],
            'approved'  => ['cancelled'],
            'rejected'  => [],
            'cancelled' => [],
        ];
        if (!in_array($to, $allowed[$from] ?? [], true)) {
            abort(422, "Invalid transition {$from} -> {$to}");
        }

        // No-op guard
        if ($from === $to) {
            return;
        }

        // Persist + audit
        DB::transaction(function () use ($req, $from, $to, $byUserId, $note) {
            $req->status = $to;
            if ($to === 'approved' && !$req->approved_at) {
                $req->approved_at = now();
                $req->approved_by = $byUserId;
            }
            $req->save();

            $req->histories()->create([
                'from_status' => $from,
                'to_status'   => $to,
                'changed_by'  => $byUserId,
                'note'        => $note,
            ]);
        });

        // ✅ Recipients: requester (owner) + driver (if any), excluding the actor
        $recipients = self::recipients($req, $byUserId);

        // Notify (include note for reject/cancel)
        switch ($to) {
            case 'approved':
                Notification::send($recipients, new RequestApprovedNotification($req));
                break;
            case 'rejected':
                Notification::send($recipients, new RequestRejectedNotification($req, $note));
                break;
            case 'cancelled':
                Notification::send($recipients, new RequestCancelledNotification($req, $note));
                break;
        }
    }

    /**
     * Build unique recipient list: requester + driver (if set), exclude the actor.
     */
    protected static function recipients(TripRequest $req, ?int $byUserId)
    {
        // If you have $req->requester relation, use it; else fall back to $req->user
        $requester = method_exists($req, 'requester') ? $req->requester : $req->user;
        $driver    = $req->driver; // may be null until assigned

        return collect([$requester, $driver])
            ->filter()                                 // drop nulls
            ->reject(fn ($u) => $byUserId && $u->id === $byUserId) // don't email the actor
            ->filter(fn ($u) => $u && $u->email)       // must have email
            ->unique('id')
            ->values();
    }
}
