<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $brands]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:brands,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $brand = Brand::create($validator->validated());

        return response()->json(['data' => $brand], 201);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:brands,name,'.$brand->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $brand->update($validator->validated());

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
