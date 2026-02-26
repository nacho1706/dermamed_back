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
use Illuminate\Support\Facades\Validator;

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
                'message'        => 'El archivo está vacío o no tiene encabezados.',
                'imported_count' => 0,
                'errors'         => [],
            ], 422);
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $requiredColumns = ['first_name', 'last_name', 'dni'];
        $missingColumns  = array_diff($requiredColumns, $header);
        if (! empty($missingColumns)) {
            fclose($handle);

            return response()->json([
                'message'        => 'Columnas requeridas faltantes: '.implode(', ', $missingColumns),
                'imported_count' => 0,
                'errors'         => [],
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
                'message'        => 'El archivo no contiene filas de datos.',
                'imported_count' => 0,
                'errors'         => [],
            ], 422);
        }

        // ── 3. Build validation payload ─────────────────────────────────────
        $payload = ['rows' => $rows];

        $rules    = [];
        $messages = [];

        foreach ($rows as $idx => $row) {
            $rowLabel = "Fila {$row['_row']}";

            // ── Identidad ────────────────────────────────────────────────────
            $rules["rows.{$idx}.first_name"]  = 'required|string|max:255';
            $rules["rows.{$idx}.last_name"]   = 'required|string|max:255';
            $rules["rows.{$idx}.dni"]         = 'required|digits_between:7,8|unique:patients,dni';
            $rules["rows.{$idx}.cuit"]        = 'nullable|digits:11|unique:patients,cuit';

            // ── Contacto ─────────────────────────────────────────────────────
            $rules["rows.{$idx}.email"]       = 'nullable|email|unique:patients,email';
            $rules["rows.{$idx}.phone"]       = 'nullable|string|min:8|max:20';

            // ── Fecha de nacimiento ──────────────────────────────────────────
            $rules["rows.{$idx}.birth_date"]  = 'nullable|date_format:Y-m-d';

            // ── Dirección (todos nullable|string|max:255) ────────────────────
            $rules["rows.{$idx}.street"]        = 'nullable|string|max:255';
            $rules["rows.{$idx}.street_number"] = 'nullable|string|max:255';
            $rules["rows.{$idx}.floor"]         = 'nullable|string|max:255';
            $rules["rows.{$idx}.apartment"]     = 'nullable|string|max:255';
            $rules["rows.{$idx}.city"]          = 'nullable|string|max:255';
            $rules["rows.{$idx}.province"]      = 'nullable|string|max:255';
            $rules["rows.{$idx}.zip_code"]      = 'nullable|string|max:255';
            $rules["rows.{$idx}.country"]       = 'nullable|string|max:255';

            // ── Obra social ──────────────────────────────────────────────────
            $rules["rows.{$idx}.insurance_provider"] = 'nullable|string|max:255';

            // ── Mensajes personalizados ──────────────────────────────────────
            $messages["rows.{$idx}.first_name.required"]      = "{$rowLabel}: El campo 'first_name' es requerido.";
            $messages["rows.{$idx}.first_name.max"]           = "{$rowLabel}: El nombre no puede superar los 255 caracteres.";
            $messages["rows.{$idx}.last_name.required"]       = "{$rowLabel}: El campo 'last_name' es requerido.";
            $messages["rows.{$idx}.last_name.max"]            = "{$rowLabel}: El apellido no puede superar los 255 caracteres.";
            $messages["rows.{$idx}.dni.required"]             = "{$rowLabel}: El campo 'dni' es requerido.";
            $messages["rows.{$idx}.dni.digits_between"]       = "{$rowLabel}: El DNI debe tener entre 7 y 8 dígitos numéricos.";
            $messages["rows.{$idx}.dni.unique"]               = "{$rowLabel}: El DNI '{$row['dni']}' ya está registrado en la base de datos.";
            $messages["rows.{$idx}.cuit.digits"]              = "{$rowLabel}: El CUIT debe tener exactamente 11 dígitos numéricos (sin guiones).";
            $messages["rows.{$idx}.cuit.unique"]              = "{$rowLabel}: El CUIT '{$row['cuit']}' ya está registrado en la base de datos.";
            $messages["rows.{$idx}.email.email"]              = "{$rowLabel}: El formato del email es inválido.";
            $messages["rows.{$idx}.email.unique"]             = "{$rowLabel}: El email '{$row['email']}' ya está registrado en la base de datos.";
            $messages["rows.{$idx}.phone.min"]                = "{$rowLabel}: El teléfono debe tener al menos 8 caracteres.";
            $messages["rows.{$idx}.phone.max"]                = "{$rowLabel}: El teléfono no puede superar los 20 caracteres.";
            $messages["rows.{$idx}.birth_date.date_format"]   = "{$rowLabel}: El formato de 'birth_date' es inválido. Usá AAAA-MM-DD (ej: 1990-05-20).";
        }

        $validator = Validator::make($payload, $rules, $messages);

        // Also check for DNI duplicates within the file itself
        $dniCounts = array_count_values(
            array_filter(array_column($rows, 'dni'), fn ($d) => $d !== null)
        );
        $inFileDuplicates = [];
        foreach ($rows as $row) {
            $dni = $row['dni'] ?? null;
            if ($dni !== null && ($dniCounts[$dni] ?? 0) > 1) {
                $inFileDuplicates[] = "Fila {$row['_row']}: El DNI '{$dni}' está duplicado dentro del archivo.";
            }
        }

        if ($validator->fails() || ! empty($inFileDuplicates)) {
            $flatErrors = [];

            foreach ($validator->errors()->messages() as $field => $fieldMessages) {
                // Extract row number from field key, e.g. "rows.3.dni" → row index 3
                preg_match('/^rows\.(\d+)\./', $field, $matches);
                $rowIndex = isset($matches[1]) ? (int) $matches[1] : null;
                $rowNum   = $rowIndex !== null ? ($rows[$rowIndex]['_row'] ?? ($rowIndex + 2)) : '?';
                $column   = preg_replace('/^rows\.\d+\./', '', $field);

                foreach ($fieldMessages as $msg) {
                    $flatErrors[] = "Fila {$rowNum} ({$column}): {$msg}";
                }
            }

            $errors = array_values(array_unique(array_merge($flatErrors, $inFileDuplicates)));

            return response()->json([
                'message'        => 'Errores de validación',
                'imported_count' => 0,
                'errors'         => $errors,
            ], 422);
        }

        // ── 4. All valid → bulk insert inside a transaction ─────────────────
        $insertRows = array_map(fn ($row) => [
            'first_name'         => $row['first_name'],
            'last_name'          => $row['last_name'],
            'dni'                => $row['dni'],
            'cuit'               => $row['cuit'] ?? null,
            'email'              => $row['email'] ?? null,
            'phone'              => $row['phone'] ?? null,
            'birth_date'         => $row['birth_date'] ?? null,
            'street'             => $row['street'] ?? null,
            'street_number'      => $row['street_number'] ?? null,
            'floor'              => $row['floor'] ?? null,
            'apartment'          => $row['apartment'] ?? null,
            'city'               => $row['city'] ?? null,
            'province'           => $row['province'] ?? null,
            'zip_code'           => $row['zip_code'] ?? null,
            'country'            => $row['country'] ?? null,
            'insurance_provider' => $row['insurance_provider'] ?? null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ], $rows);

        DB::transaction(function () use ($insertRows) {
            DB::table('patients')->insert($insertRows);
        });

        $count = count($insertRows);

        return response()->json([
            'message'        => "Importación exitosa: {$count} paciente(s) importados correctamente.",
            'imported_count' => $count,
            'errors'         => [],
        ]);
    }
}
