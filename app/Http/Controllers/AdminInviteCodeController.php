<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminInviteCode;
use App\Notifications\AdminInviteCodeNotification;
use Illuminate\Support\Facades\Notification;

class AdminInviteCodeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'role'   => ['required','in:Super Admin,Logistics Manager'],
            'email'  => ['required','email'],        // who should receive the code
            'uses'   => ['nullable','integer','min:1'],
            'days'   => ['nullable','integer','min:0'], // 0 = never expires
            'notes'  => ['nullable','string','max:255'],
        ]);

        $expires = ($data['days'] ?? 7) > 0 ? now()->addDays((int)$data['days']) : null;
        $maxUses = (int)($data['uses'] ?? 1);

        $invite = AdminInviteCode::mint(
            $data['role'],
            auth()->id(),
            $expires,
            $maxUses
        );
        if (!empty($data['notes'])) {
            $invite->notes = $data['notes'];
            $invite->save();
        }

        // email to the recipient (and optional copy to issuer)
        Notification::route('mail', $data['email'])
            ->notify(new AdminInviteCodeNotification($invite));
        optional(auth()->user())->notify(new AdminInviteCodeNotification($invite, copy:true));

      
return redirect()
  ->route('admin.invites.index')
  ->with('success', "Invite for {$invite->role} was created and emailed to {$data['email']}.")
  ->with('invite', [
      'code' => $invite->code,
      'role' => $invite->role,
      'email' => $data['email'],
      'expires_at' => optional($invite->expires_at)->toDayDateTimeString() ?? 'never',
      'uses' => $invite->max_uses ?? 'unlimited',
  ]);


    }
}
