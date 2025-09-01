<?php

namespace App\Services;

use App\Models\User;

class LogisticsContactService
{
    public function getLogisticsManager(): ?User
    {
        $q = User::query()->where('is_manager', true);
        if (schema_has_column('users', 'department')) {
            $q->orderByRaw("CASE WHEN department = 'Logistics' THEN 0 ELSE 1 END");
        }
        $manager = $q->orderBy('id')->first();

        if (!$manager) {
            $manager = User::query()
                ->whereIn('role', ['Manager','Dispatch','Logistics'])
                ->orderBy('id')
                ->first();
        }

        if (!$manager) {
            $email = config('logistics.manager_email');
            if ($email) $manager = User::where('email', $email)->first();
        }

        return $manager;
    }
}
