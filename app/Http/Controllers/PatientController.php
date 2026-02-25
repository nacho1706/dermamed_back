<?php

namespace App\Http\Controllers;

use App\Factories\PatientFactory;
use App\Http\Requests\Patient\ImportPatientRequest;
use App\Http\Requests\Patient\IndexPatientsRequest;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function index(IndexPatientsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Patient::query();

        if (isset($validated['dni'])) {
            $query->where('dni', $validated['dni']);
        }

        if (isset($validated['first_name'])) {
            $query->where('first_name', 'like', '%'.$validated['first_name'].'%');
        }

        if (isset($validated['last_name'])) {
            $query->where('last_name', 'like', '%'.$validated['last_name'].'%');
        }

        if (isset($validated['cuit'])) {
            $query->where('cuit', 'like', '%'.$validated['cuit'].'%');
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                if (ctype_digit(str_replace(' ', '', $search))) {
                    $q->where('dni', 'ilike', '%'.$search.'%')
                        ->orWhere('phone', 'ilike', '%'.$search.'%');
                } else {
                    $q->where('first_name', 'ilike', '%'.$search.'%')
                        ->orWhere('last_name', 'ilike', '%'.$search.'%')
                        ->orWhere('dni', 'ilike', '%'.$search.'%')
                        ->orWhere('phone', 'ilike', '%'.$search.'%');
                }
            });
        }

        $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

        return PatientResource::collection($paginador);
    }

    public function store(StorePatientRequest $request)
    {
        $validated = $request->validated();

        $patient = PatientFactory::fromRequest($validated);
        $patient->save();

        return (new PatientResource($patient))
            ->response()
            ->setStatusCode(201);
    }

    public function show(\Illuminate\Http\Request $request, Patient $patient)
    {
        if ($request->user()->hasRole('doctor')) {
            $patient->load('medicalRecords');
        }

        return new PatientResource($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $validated = $request->validated();

        $patient = PatientFactory::fromRequest($validated, $patient);
        $patient->save();

        return new PatientResource($patient);
    }

    public function destroy(\Illuminate\Http\Request $request, Patient $patient)
    {
        if (! $request->user()->roles->contains('name', 'clinic_manager')) {
            abort(403, 'Unauthorized action.');
        }

        $patient->delete();

        return response()->json([
            'message' => 'Patient deleted successfully',
        ]);
    }

    public function import(ImportPatientRequest $request)
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

        $requiredColumns = ['first_name', 'last_name', 'dni'];
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
                $data = array_map(fn ($v) => trim($v) === '' ? null : trim($v), $data);

                // Required field validation
                if (empty($data['first_name'])) {
                    $errors[] = "Fila {$rowNumber}: el campo 'first_name' es obligatorio.";

                    continue;
                }
                if (empty($data['last_name'])) {
                    $errors[] = "Fila {$rowNumber}: el campo 'last_name' es obligatorio.";

                    continue;
                }
                if (empty($data['dni']) || ! preg_match('/^\d{7,8}$/', $data['dni'])) {
                    $errors[] = "Fila {$rowNumber}: el campo 'dni' es obligatorio y debe tener 7 u 8 dígitos.";

                    continue;
                }

                Patient::updateOrCreate(
                    ['dni' => $data['dni']],
                    [
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'cuit' => $data['cuit'] ?? null,
                        'email' => $data['email'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'birth_date' => $data['birth_date'] ?? null,
                        'street' => $data['street'] ?? null,
                        'street_number' => $data['street_number'] ?? null,
                        'floor' => $data['floor'] ?? null,
                        'apartment' => $data['apartment'] ?? null,
                        'city' => $data['city'] ?? null,
                        'province' => $data['province'] ?? null,
                        'zip_code' => $data['zip_code'] ?? null,
                        'country' => $data['country'] ?? null,
                        'insurance_provider' => $data['insurance_provider'] ?? null,
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
