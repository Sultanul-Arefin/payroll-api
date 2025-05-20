<?php

namespace App\Http\Traits;

use App\Models\RotaLocation as ModelsRotaLocation;
use Illuminate\Http\Request;

trait RotaLocation
{
    public function rota_locations()
    {
        $rota_locations = ModelsRotaLocation::get();
        return apiResponse(
            data: $rota_locations
        );
    }

    public function store_rota_location(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required'
        ]);

        $rota_location = ModelsRotaLocation::create([
            'name' => $request->name,
            'address' => $request->address,
            'company_id' => auth()->user()->company_id,
        ]);

        return apiResponse(
            data: $rota_location
        );
    }

    public function edit_rota_location(ModelsRotaLocation $rota_location)
    {
        return apiResponse(
            data: $rota_location
        );
    }

    public function update_rota_location(ModelsRotaLocation $rota_location, Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            '_method' => 'required',
        ]);

        $update = $rota_location->update([
            'name' => $request->name,
            'address' => $request->address,
            'company_id' => auth()->user()->company_id,
        ]);

        return apiResponse(
            data: $update
        );
    }

    public function destroy_rota_location(ModelsRotaLocation $rota_location, Request $request)
    {
        $request->validate([
            '_method' => 'required',
        ]);

        $update = $rota_location->delete();

        return apiResponse(
            data: null,
            message: 'Location Deleted Successfully'
        );
    }
}