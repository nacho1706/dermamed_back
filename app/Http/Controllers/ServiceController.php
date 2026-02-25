<?php

namespace App\Http\Controllers;

use App\Factories\ServiceFactory;
use App\Http\Requests\Service\ImportServiceRequest;
use App\Http\Requests\Service\IndexServicesRequest;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index(IndexServicesRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Service::query();

        if (isset($validated['name'])) {
            $query->where('name', 'like', '%'.$validated['name'].'%');
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

    public function import(ImportServiceRequest $request)
    {
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return response()->json([
                'message' => 'El archivo está vacío o no tiene encabezados.',
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

        // Normalize header names
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $requiredColumns = ['name', 'price', 'duration_minutes'];
        $missingColumns = array_diff($requiredColumns, $header);
        if (! empty($missingColumns)) {
            fclose($handle);

            return response()->json([
                'message' => 'Columnas requeridas faltantes: '.implode(', ', $missingColumns),
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        DB::transaction(function () use ($handle, $header, &$imported, &$errors, &$rowNumber) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (count($row) !== count($header)) {
                    $errors[] = "Fila {$rowNumber}: número de columnas incorrecto.";

                    continue;
                }

                $data = array_combine($header, $row);

                // Validate required fields
                if (empty(trim($data['name'] ?? ''))) {
                    $errors[] = "Fila {$rowNumber}: el campo 'name' es obligatorio.";

                    continue;
                }
                if (! is_numeric($data['price'] ?? '')) {
                    $errors[] = "Fila {$rowNumber}: el campo 'price' debe ser numérico.";

                    continue;
                }
                if (! ctype_digit(strval($data['duration_minutes'] ?? '')) || (int) $data['duration_minutes'] < 1) {
                    $errors[] = "Fila {$rowNumber}: el campo 'duration_minutes' debe ser un entero mayor a 0.";

                    continue;
                }

                Service::updateOrCreate(
                    ['name' => trim($data['name'])],
                    [
                        'description' => trim($data['description'] ?? '') ?: null,
                        'price' => (float) $data['price'],
                        'duration_minutes' => (int) $data['duration_minutes'],
                    ]
                );

                $imported++;
            }
        });

        fclose($handle);

        return response()->json([
            'message' => "Importación completada: {$imported} registro(s) procesados.",
            'imported_count' => $imported,
            'errors' => $errors,
        ]);
    }
}
