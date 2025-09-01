<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManageUsersController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q',''));

        $users = User::query()
            ->with('roles:id,name')
            ->when($q !== '', function($qry) use ($q) {
                $qry->where(function($w) use ($q) {
                    $w->where('name','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%")
                      ->orWhere('phone','like',"%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->through(function($u){
                return [
                    'id'    => $u->id,
                    'name'  => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'roles' => $u->roles->pluck('name')->values(),
                ];
            });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['q' => $q],
        ]);
    }

    public function makeDriver(User $user)
    {
        // Safety: never demote a Super Admin via this endpoint
        if ($user->hasRole('Super Admin')) {
            return back()->with('error','Cannot change a Super Admin via this screen.');
        }

        $user->syncRoles(['Driver']);

        // Keep legacy flags consistent if you still have them
        $changes = [];
        if (\Schema::hasColumn('users','is_manager'))     $changes['is_manager'] = false;
        if (\Schema::hasColumn('users','is_super_admin')) $changes['is_super_admin'] = false;
        if (!empty($changes)) $user->forceFill($changes)->save();

        return back()->with('success', "{$user->name} is now a Driver.");
    }

    public function makeStaff(User $user)
    {
        if ($user->hasRole('Super Admin')) {
            return back()->with('error','Cannot change a Super Admin via this screen.');
        }

        $user->syncRoles(['Staff']);

        $changes = [];
        if (\Schema::hasColumn('users','is_manager'))     $changes['is_manager'] = false;
        if (\Schema::hasColumn('users','is_super_admin')) $changes['is_super_admin'] = false;
        if (!empty($changes)) $user->forceFill($changes)->save();

        return back()->with('success', "{$user->name} has been set to Staff.");
    }

    // app/Http/Controllers/ManageUsersController.php
public function destroy(\App\Models\User $user)
{
    $actor = auth()->user();

    // Don’t let anyone delete themselves
    if ($actor->id === $user->id) {
        return back()->with('error', 'You cannot delete your own account.');
    }

    // Managers can’t delete Managers or Super Admins
    if ($actor->hasRole('Logistics Manager') &&
        ($user->hasRole('Logistics Manager') || $user->hasRole('Super Admin'))) {
        return back()->with('error', 'Managers cannot delete Managers or Super Admins.');
    }

    // Only Super Admins can delete Super Admins — and not the last one
    if ($user->hasRole('Super Admin')) {
        if (! $actor->hasRole('Super Admin')) {
            return back()->with('error', 'Only Super Admins can delete Super Admins.');
        }
        $othersExist = \App\Models\User::role('Super Admin')->where('id','!=',$user->id)->exists();
        if (! $othersExist) {
            return back()->with('error', 'Cannot delete the last Super Admin.');
        }
    }

    // Clean up roles (Spatie doesn’t enforce FKs by default)
    $user->syncRoles([]);

    // Soft delete (or ->forceDelete() if you prefer hard delete)
    $user->delete();

    return back()->with('success', 'Account deleted.');
}

}
