<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\RideRequestController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripRequests\StatusController as TripRequestStatusController;
use App\Models\TripRequest;
use App\Http\Controllers\Api\DirectionsController;
use App\Http\Controllers\API\DriverLocationController;
use App\Http\Controllers\CustodyEventController;
use App\Http\Controllers\AdminInviteCodeController;
use App\Http\Controllers\ManageUsersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardExportController;








Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Vehicles (manager only)
Route::middleware(['auth','verified','can:manage-vehicles'])->group(function () {
    Route::resource('vehicles', \App\Http\Controllers\VehicleController::class);
    Route::patch('vehicles/{vehicle}/toggle', [\App\Http\Controllers\VehicleController::class, 'toggle'])
        ->name('vehicles.toggle');
});



// Ride Requests (staff)
Route::middleware(['auth','verified'])->group(function () {
    Route::resource('ride-requests', RideRequestController::class)
        ->only(['index','create','store','show']);

    // --- Status actions (centralized) ---
    Route::post('ride-requests/{ride_request}/approve', [TripRequestStatusController::class, 'approve'])
        ->middleware('can:approve,ride_request')
        ->name('ride-requests.approve');

    Route::post('ride-requests/{ride_request}/reject', [TripRequestStatusController::class, 'reject'])
        ->middleware('can:reject,ride_request')
        ->name('ride-requests.reject');

    Route::post('ride-requests/{ride_request}/cancel', [TripRequestStatusController::class, 'cancel'])
        ->middleware('can:cancel,ride_request')
        ->name('ride-requests.cancel');

    // (Remove the old DELETE cancel route to avoid conflicts)
    // Route::delete('ride-requests/{ride_request}/cancel', [RideRequestController::class, 'cancel'])->name('ride-requests.cancel');
});


// routes/web.php


Route::middleware(['auth','verified','role:Logistics Manager|Super Admin'])->group(function () {
    Route::get('/dispatch', [RideRequestController::class, 'dispatch'])
        ->name('dispatch.index')
        // still require the policy too
        ->middleware('can:viewAny,' . TripRequest::class);

    Route::post('/dispatch/{ride_request}/approve', [TripRequestStatusController::class, 'approve'])
        ->name('dispatch.approve')
        ->middleware('can:approve,ride_request');

    Route::post('/dispatch/{ride_request}/reject', [TripRequestStatusController::class, 'reject'])
        ->name('dispatch.reject')
        ->middleware('can:reject,ride_request');

    Route::post('/dispatch/{ride_request}/assign', [RideRequestController::class, 'assign'])
        ->name('dispatch.assign')
        // optional if you have a policy method:
        // ->middleware('can:assign,ride_request')
        ;

    Route::post('/dispatch/{ride_request}/attach/{trip}', [RideRequestController::class, 'attachToTrip'])
        ->name('dispatch.attach')
        // optional policy guard:
        // ->middleware('can:attach,ride_request')
        ;
});



// Trips 
Route::middleware(['auth','verified'])->group(function () {
    // Core pages
    Route::resource('trips', TripController::class)
        ->only(['index','show','edit','update','destroy']);

    // ✅ Driver dashboard (new)
    Route::get('/my-trips', [TripController::class, 'myTrips'])
        // ->middleware('role:Driver') // optional (Spatie). You already check in controller.
        ->name('trips.my');

    // Quick-status actions (must at least be allowed to view the trip)
    Route::patch('/trips/{trip}/manager-status', [TripController::class,'managerStatus'])
        ->middleware('can:view,trip')
        ->name('trips.manager-status');

    Route::patch('/trips/{trip}/driver-status', [TripController::class,'driverStatus'])
        ->middleware('can:view,trip')
        ->name('trips.driver-status');

    // NEW: manager-only bulk create of consignments (“drops”) on a trip
    Route::post('/trips/{trip}/drops', [\App\Http\Controllers\TripDropController::class, 'store'])
        ->middleware(['auth','verified'])
        ->name('trips.drops.store');

});



Route::middleware(['auth'])->group(function () {
    
    Route::post('/consignments/{consignment}/prepare-delivery', [CustodyEventController::class, 'prepareDelivery'])
        ->name('consignments.prepare-delivery');

    Route::post('/consignments/{consignment}/verify-otp', [CustodyEventController::class, 'verifyOtp'])
        ->name('custody-events.verify-otp');

    Route::post('/trips/{trip}/consignments/{consignment}/events', [CustodyEventController::class, 'store'])
        ->name('custody-events.store');

    Route::get('/consignments/{consignment}/events', [CustodyEventController::class, 'index'])
        ->name('custody-events.index');

    Route::post('/consignments/{consignment}/require-otp', [\App\Http\Controllers\CustodyEventController::class, 'setRequireOtp'])
        ->name('consignments.require-otp');

    Route::get('/consignments/{consignment}/delivery-meta', [\App\Http\Controllers\CustodyEventController::class, 'deliveryMeta'])
        ->name('consignments.delivery-meta');


});

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');


Route::middleware(['auth', 'verified'])->get('/directions/summary', [DirectionsController::class, 'summary'])
    ->name('directions.summary');
    
Route::middleware(['auth', 'verified', 'throttle:60,1'])
    ->get('/api/directions/summary', [DirectionsController::class, 'summary'])
    ->name('api.directions.summary');
    

 Route::middleware(['auth', 'verified', 'throttle:60,1'])
  ->post('/api/directions/batch-summary', [DirectionsController::class, 'batch'])
  ->name('api.directions.batch');

Route::middleware(['auth','role:Super Admin'])->group(function () {
    Route::post('/admin/invites', [AdminInviteCodeController::class, 'store'])
        ->name('admin.invites.store');
});


Route::middleware(['auth','role:Super Admin'])->get('/admin/invites', function () {
    return inertia('Admin/InviteCodes/Index');
})->name('admin.invites.index');

Route::middleware(['auth','role:Logistics Manager|Super Admin'])->group(function () {
    Route::get('/admin/users', [ManageUsersController::class,'index'])->name('admin.users.index');
    Route::post('/admin/users/{user}/make-driver', [ManageUsersController::class,'makeDriver'])->name('users.make-driver');
    Route::post('/admin/users/{user}/make-staff',  [ManageUsersController::class,'makeStaff'])->name('users.make-staff');
    Route::delete('/admin/users/{user}', [ManageUsersController::class,'destroy'])->name('admin.users.destroy');

});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class,'index'])->name('dashboard');

    // Exports (Manager + Super Admin only)
    Route::middleware('role:Logistics Manager|Super Admin')->group(function () {
        Route::get('/dashboard/export/excel', [DashboardExportController::class,'excel'])->name('dashboard.export.excel');
        Route::get('/dashboard/export/csv',   [DashboardExportController::class,'csv'])->name('dashboard.export.csv');
        Route::get('/dashboard/export/pdf',   [DashboardExportController::class,'pdf'])->name('dashboard.export.pdf');
    });
});


require __DIR__.'/auth.php';
