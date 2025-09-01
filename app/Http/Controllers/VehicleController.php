<?php

// app/Http/Controllers/VehicleController.php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
 use Illuminate\Validation\Rule;
 

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::orderBy('label')->paginate(15)->through(function ($v) {
            return [
                'id' => $v->id,
                'label' => $v->label,
                'type' => $v->type,
                'plate_number' => $v->plate_number,
                'capacity' => $v->capacity,
                'active' => (bool) $v->active,
            ];
        });

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function create()
    {
        return Inertia::render('Vehicles/Create');
    }

   



public function store(Request $request)
{
    $data = $request->validate([
        'label'        => ['required','string','max:191'],
        'type'         => ['required','string','max:191'],
        'make'         => ['nullable','string','max:60'],
        'model'        => ['nullable','string','max:60'],
        'year'         => ['nullable','integer','min:1990','max:'.(date('Y')+1)],
        'plate_number' => ['required','string','max:191','unique:vehicles,plate_number'],
        'capacity'     => ['required','integer','min:1','max:60'],
        'active'       => ['nullable','boolean'],
        'notes'        => ['nullable','string','max:5000'],
    ]);

    $data['active'] = $request->boolean('active', true);

    Vehicle::create($data);

    return redirect()->route('vehicles.index')->with('success', 'Vehicle created.');
}


   public function edit(Vehicle $vehicle)
{
    return Inertia::render('Vehicles/Edit', [
        'vehicle' => $vehicle->only(
            'id',
            'label',
            'type',
            'plate_number',
            'capacity',
            'active',
            'make',
            'model',
            'year',
            'notes'
        ),
    ]);
}



    public function update(Request $request, Vehicle $vehicle)
{
    $data = $request->validate([
        'label'        => ['required','string','max:191'],
        'type'         => ['required','string','max:191'],
        'make'         => ['nullable','string','max:60'],
        'model'        => ['nullable','string','max:60'],
        'year'         => ['nullable','integer','min:1990','max:'.(date('Y')+1)],
        'plate_number' => ['required','string','max:191','unique:vehicles,plate_number,'.$vehicle->id],
        'capacity'     => ['required','integer','min:1','max:60'],
        'active'       => ['nullable','boolean'],
        'notes'        => ['nullable','string','max:5000'],
    ]);

    $data['active'] = $request->boolean('active', true);

    $vehicle->update($data);

    return redirect()->route('vehicles.index')->with('success', 'Vehicle updated.');
}



    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return back()->with('success', 'Vehicle deleted.');
    }

    public function toggle(Vehicle $vehicle)
    {
        $vehicle->active = ! $vehicle->active;
        $vehicle->save();

        return back()->with('success', 'Vehicle status updated.');
    }
}
