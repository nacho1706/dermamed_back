<?php

namespace App\Http\Controllers;

use App\Http\Resources\HealthInsuranceResource;
use App\Models\HealthInsurance;

class HealthInsuranceController extends Controller
{
    public function index()
    {
        $insurances = HealthInsurance::orderBy('name')->get();

        return HealthInsuranceResource::collection($insurances);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:health_insurances,name',
        ]);

        $insurance = HealthInsurance::create($validated);

        return (new HealthInsuranceResource($insurance))
            ->response()
            ->setStatusCode(201);
    }

    public function update(\Illuminate\Http\Request $request, HealthInsurance $healthInsurance)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:health_insurances,name,' . $healthInsurance->id,
        ]);

        $healthInsurance->update($validated);

        return new HealthInsuranceResource($healthInsurance);
    }

    public function destroy(HealthInsurance $healthInsurance)
    {
        $healthInsurance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Health insurance deleted successfully',
        ]);
    }
}
