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
use Illuminate\Support\Facades\Validator;

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
        $path = $file->getRealPath();

        // ── 0. Detect CSV delimiter (Latin Excel uses ; instead of ,) ────────
        $firstLine = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)[0] ?? '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen($path, 'r');

        // ── 1. Read & validate header ────────────────────────────────────────
        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);

            return response()->json([
                'message' => 'El archivo está vacío o no tiene encabezados.',
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

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

        // ── 2. Read ALL rows into memory (no DB writes yet) ──────────────────
        $rows = [];
        $rowNumber = 1;
        while (($rawRow = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            if (array_filter($rawRow, fn ($v) => trim($v) !== '') === []) {
                continue; // skip blank lines
            }
            $data = array_combine($header, array_pad($rawRow, count($header), ''));
            $data = array_map(fn ($v) => trim($v) === '' ? null : trim($v), $data);
            $data['_row'] = $rowNumber;
            $rows[] = $data;
        }
        fclose($handle);

        if (empty($rows)) {
            return response()->json([
                'message' => 'El archivo no contiene filas de datos.',
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

        // ── 3. Build validation payload ─────────────────────────────────────
        $payload = ['rows' => $rows];

        $rules = [];
        $messages = [];

        foreach ($rows as $idx => $row) {
            $rowLabel = "Fila {$row['_row']}";

            $rules["rows.{$idx}.name"] = 'required|string|max:255|unique:services,name';
            $rules["rows.{$idx}.price"] = 'required|numeric|min:0';
            $rules["rows.{$idx}.duration_minutes"] = 'required|integer|min:1';
            $rules["rows.{$idx}.description"] = 'nullable|string|max:255';

            $messages["rows.{$idx}.name.required"] = "{$rowLabel}: El campo 'name' es requerido.";
            $messages["rows.{$idx}.name.max"] = "{$rowLabel}: El nombre no puede superar los 255 caracteres.";
            $messages["rows.{$idx}.name.unique"] = "{$rowLabel}: El servicio '{$row['name']}' ya está registrado en la base de datos.";
            $messages["rows.{$idx}.price.required"] = "{$rowLabel}: El campo 'price' es requerido.";
            $messages["rows.{$idx}.price.numeric"] = "{$rowLabel}: El campo 'price' debe ser numérico.";
            $messages["rows.{$idx}.price.min"] = "{$rowLabel}: El precio no puede ser negativo.";
            $messages["rows.{$idx}.duration_minutes.required"] = "{$rowLabel}: El campo 'duration_minutes' es requerido.";
            $messages["rows.{$idx}.duration_minutes.integer"] = "{$rowLabel}: El campo 'duration_minutes' debe ser un número entero (sin decimales).";
            $messages["rows.{$idx}.duration_minutes.min"] = "{$rowLabel}: El campo 'duration_minutes' debe ser al menos 1.";
        }

        $validator = Validator::make($payload, $rules, $messages);

        // Also check for name duplicates within the file itself
        $nameCounts = array_count_values(
            array_filter(array_column($rows, 'name'), fn ($n) => $n !== null)
        );
        $inFileDuplicates = [];
        foreach ($rows as $row) {
            $name = $row['name'] ?? null;
            if ($name !== null && ($nameCounts[$name] ?? 0) > 1) {
                $inFileDuplicates[] = "Fila {$row['_row']}: El servicio '{$name}' está duplicado dentro del archivo.";
            }
        }

        if ($validator->fails() || ! empty($inFileDuplicates)) {
            $flatErrors = [];

            foreach ($validator->errors()->messages() as $field => $fieldMessages) {
                preg_match('/^rows\.(\d+)\./', $field, $matches);
                $rowIndex = isset($matches[1]) ? (int) $matches[1] : null;
                $rowNum = $rowIndex !== null ? ($rows[$rowIndex]['_row'] ?? ($rowIndex + 2)) : '?';
                $column = preg_replace('/^rows\.\d+\./', '', $field);

                foreach ($fieldMessages as $msg) {
                    $flatErrors[] = "Fila {$rowNum} ({$column}): {$msg}";
                }
            }

            $errors = array_values(array_unique(array_merge($flatErrors, $inFileDuplicates)));

            return response()->json([
                'message' => 'Errores de validación',
                'imported_count' => 0,
                'errors' => $errors,
            ], 422);
        }

        // ── 4. All valid → bulk insert inside a transaction ─────────────────
        $insertRows = array_map(fn ($row) => [
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'price' => (float) $row['price'],
            'duration_minutes' => (int) $row['duration_minutes'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $rows);

        DB::transaction(function () use ($insertRows) {
            DB::table('services')->insert($insertRows);
        });

        $count = count($insertRows);

        return response()->json([
            'message' => "Importación exitosa: {$count} servicio(s) importados correctamente.",
            'imported_count' => $count,
            'errors' => [],
        ]);
    }
}
