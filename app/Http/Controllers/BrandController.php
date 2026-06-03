<?php

namespace App\Http\Controllers;

use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $brands]);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = Brand::create($request->validated());

        return response()->json(['data' => $brand], 201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $brand->update($request->validated());

        return response()->json(['data' => $brand]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar esta marca porque tiene productos asociados.',
                'errors' => ['No se puede eliminar esta marca porque tiene productos asociados.'],
            ], 422);
        }

        $brand->delete();

        return response()->json(['message' => 'Marca eliminada correctamente.']);
    }
}
