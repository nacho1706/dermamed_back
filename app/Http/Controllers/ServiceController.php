<?php

namespace App\Http\Controllers;

use App\Factories\ServiceFactory;
use App\Http\Requests\Service\IndexServicesRequest;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index(IndexServicesRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Service::query();

        if (isset($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }

        $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

        return response()->json([
            'data' => $paginador->items(),
            'current_page' => $paginador->currentPage(),
            'total_pages' => $paginador->lastPage(),
            'total_registros' => $paginador->total(),
        ]);
    }

    public function store(StoreServiceRequest $request)
    {
        $validated = $request->validated();

        $service = ServiceFactory::fromRequest($validated);
        $service->save();

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully',
            'service' => $service,
        ], 201);
    }

    public function show($id)
    {
        $service = Service::find($id);

        if (! $service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'service' => $service,
        ]);
    }

    public function update(UpdateServiceRequest $request, $id)
    {
        $service = Service::find($id);

        if (! $service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        $validated = $request->validated();

        $service = ServiceFactory::fromRequest($validated, $service);
        $service->save();

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully',
            'service' => $service,
        ]);
    }

    public function destroy($id)
    {
        $service = Service::find($id);

        if (! $service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully',
        ]);
    }
}
