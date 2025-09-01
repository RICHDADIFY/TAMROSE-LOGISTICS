<?php

namespace App\Observers;

use App\Jobs\GeocodeTripRequest;
use App\Models\TripRequest;

class TripRequestObserver
{
    public function created(TripRequest $tr): void
    {
        dispatch(new GeocodeTripRequest($tr->id))
            ->onQueue('low')
            ->afterCommit();
    }

    public function updated(TripRequest $tr): void
    {
        $labelsChanged = $tr->wasChanged([
            'origin','destination',          // your current names
            'from_location','to_location',   // other variant we saw
            'from_label','to_label',         // another variant
        ]);

        $coordsMissing = empty($tr->from_lat) || empty($tr->from_lng)
                      || empty($tr->to_lat)   || empty($tr->to_lng);

        $approvedNow = $tr->wasChanged('status')
            && strtolower((string)$tr->status) === 'approved';

        if ($labelsChanged || $coordsMissing || $approvedNow) {
            dispatch(new GeocodeTripRequest($tr->id))
                ->onQueue('low')
                ->afterCommit();
        }
    }
}
