<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripRequestResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();
        $isDriverViewer  = $user && $user->role === 'Driver';
        $isManagerViewer = $user && ($user->is_manager || in_array($user->role, ['Manager','Dispatch','Logistics']));
        $isOwnerViewer   = $user && $this->user_id === $user->id;

        $assignedDriver = $this->driver; // relation on TripRequest
        $manager = app(\App\Services\LogisticsContactService::class)->getLogisticsManager();

        $asCard = function (? \App\Models\User $u, string $tag) {
            if (!$u) return null;
            return [
                'tag'      => $tag,
                'name'     => $u->name,
                'email'    => $u->email,
                'phone'    => $u->phone ?? null,
                'whatsapp' => $u->phone ?? null,
                'avatar'   => $u->profile_photo
                    ? asset($u->profile_photo)
                    : 'https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($u->email ?: $u->name),
                'canMessage'=> true,
                'canCall'   => !empty($u->phone),
            ];
        };

        $contacts = [];
        if ($isDriverViewer) {
            if ($manager) $contacts[] = $asCard($manager, 'manager');
        } elseif ($isOwnerViewer) {
            if ($this->driver_id && $assignedDriver) $contacts[] = $asCard($assignedDriver, 'driver');
            if ($manager) $contacts[] = $asCard($manager, 'manager');
        } elseif ($isManagerViewer) {
            if ($assignedDriver) $contacts[] = $asCard($assignedDriver, 'driver');
            $contacts[] = $asCard($this->user, 'requester');
        } else {
            if ($manager) $contacts[] = $asCard($manager, 'manager');
        }
        $contacts = array_values(array_filter($contacts));

        return [
            'id'           => $this->id,
            'origin'       => $this->origin,
            'destination'  => $this->destination,
            'from_lat'     => $this->from_lat,
            'from_lng'     => $this->from_lng,
            'to_lat'       => $this->to_lat,
            'to_lng'       => $this->to_lng,
            'status'       => $this->status,
            'desired_time' => optional($this->desired_time)->toIso8601String(),
            'driver'       => $assignedDriver ? ['id'=>$assignedDriver->id, 'name'=>$assignedDriver->name] : null,
            'contacts'     => $contacts,
        ];
    }
}
