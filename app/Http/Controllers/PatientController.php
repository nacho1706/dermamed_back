<?php

namespace App\Http\Controllers;

use App\Actions\Patient\ImportPatientsAction;
use App\Factories\PatientFactory;
use App\Http\Requests\Patient\ImportPatientRequest;
use App\Http\Requests\Patient\IndexPatientsRequest;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Support\Search;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Patient::class, 'patient');
    }

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
            $query->where('first_name', 'ilike', '%'.Search::escapeLike($validated['first_name']).'%');
        }

        if (isset($validated['last_name'])) {
            $query->where('last_name', 'ilike', '%'.Search::escapeLike($validated['last_name']).'%');
        }

        if (isset($validated['cuit'])) {
            $query->where('cuit', 'ilike', '%'.Search::escapeLike($validated['cuit']).'%');
        }

        if (isset($validated['search'])) {
            $search = Search::escapeLike($validated['search']);
            $query->where(function ($q) use ($search, $validated) {
                if (ctype_digit(str_replace(' ', '', $validated['search']))) {
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

        if (isset($validated['insurance_provider'])) {
            $query->whereHas('healthInsurance', function ($q) use ($validated) {
                $q->where('name', 'ilike', '%'.Search::escapeLike($validated['insurance_provider']).'%');
            });
        }

        if (isset($validated['province'])) {
            $query->where('province', 'ilike', '%'.Search::escapeLike($validated['province']).'%');
        }

        // Sorting
        $sort = $validated['sort'] ?? '';
        match ($sort) {
            'name_asc'    => $query->orderBy('first_name')->orderBy('last_name'),
            'name_desc'   => $query->orderByDesc('first_name')->orderByDesc('last_name'),
            'created_asc' => $query->orderBy('created_at'),
            default       => $query->orderByDesc('created_at'),
        };

        $paginador = $query->with('healthInsurance')->paginate($cantidad, ['*'], 'page', $pagina);

        return PatientResource::collection($paginador);

    }

    public function store(StorePatientRequest $request)
    {
        $validated = $request->validated();

        $patient = PatientFactory::fromRequest($validated);
        $patient->save();
        $patient->load('healthInsurance');

        return (new PatientResource($patient))
            ->response()
            ->setStatusCode(201);

    }

    public function show(\Illuminate\Http\Request $request, Patient $patient)
    {
        $patient->load('healthInsurance');

        if ($request->user()->hasRole('doctor')) {
            // Limit to the 20 most recent records to avoid massive payloads
            // for chronic patients. A paginated endpoint should be used to
            // browse the full history.
            $patient->load(['medicalRecords' => fn ($q) => $q->latest('date')->limit(20)]);
        }

        return new PatientResource($patient);
    }


    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $validated = $request->validated();

        $patient = PatientFactory::fromRequest($validated, $patient);
        $patient->save();
        $patient->load('healthInsurance');

        return new PatientResource($patient);

    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return response()->json([
            'message' => 'Patient deleted successfully',
        ]);
    }

    public function import(ImportPatientRequest $request, ImportPatientsAction $action)
    {
        $this->authorize('create', Patient::class);

        $result = $action->execute($request->file('file'));

        return response()->json([
            'message' => $result['message'],
            'imported_count' => $result['imported_count'],
            'errors' => $result['errors'],
        ], $result['status'] === 200 ? 200 : 422);
    }
}
