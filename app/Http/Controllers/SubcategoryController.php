<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubcategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subcategory::query()->select(['id', 'name', 'category_id']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $subcategories = $query->orderBy('name')->get();

        return response()->json(['data' => $subcategories]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $subcategory = Subcategory::create($validator->validated());

        return response()->json(['data' => $subcategory], 201);
    }

    public function update(Request $request, Subcategory $subcategory): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $subcategory->update($validator->validated());

        return response()->json(['data' => $subcategory]);
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        $subcategory->delete();

        return response()->json(['message' => 'Subcategoría eliminada correctamente.']);
    }
}
