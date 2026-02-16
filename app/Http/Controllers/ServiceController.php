<?php

namespace App\Http\Controllers;

use App\Factories\ServiceFactory;
use App\Http\Requests\Service\IndexServicesRequest;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
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

        return ServiceResource::collection($paginador);
    }

    public function store(StoreServiceRequest $request)
    {
        $validated = $request->validated();

        $service = ServiceFactory::fromRequest($validated);
        $service->save();

        return (new ServiceResource($service))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Service $service)
    {
        return new ServiceResource($service);
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $validated = $request->validated();

        $service = ServiceFactory::fromRequest($validated, $service);
        $service->save();

        return new ServiceResource($service);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully',
        ]);
    }
}
