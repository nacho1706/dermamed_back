<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashShift\CloseCashShiftRequest;
use App\Http\Requests\CashShift\OpenCashShiftRequest;
use App\Http\Resources\CashShiftResource;
use App\Services\CashShiftService;
use Illuminate\Http\Request;

class CashShiftController extends Controller
{
    public function __construct(
        private readonly CashShiftService $cashShiftService,
    ) {}

    /**
     * Get paginated history of cash shifts. Loads payments only on detail (show)
     * to keep the index response light — before this fix the index eagerly
     * loaded every payment and expense for every shift ever and returned the
     * full unpaginated list.
     */
    public function index(Request $request)
    {
        $perPage = max(1, min(100, (int) ($request->input('per_page', 10))));
        $page = max(1, (int) ($request->input('page', 1)));

        $paginator = \App\Models\CashShift::with(['openedBy', 'closedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return CashShiftResource::collection($paginator);
    }

    /**
     * Get the currently open cash shift.
     */
    public function current()
    {
        $shift = $this->cashShiftService->getCurrentShift();

        if (! $shift) {
            return response()->json([
                'success' => true,
                'message' => 'No hay caja abierta actualmente.',
                'data' => null,
            ]);
        }

        return $this->successResponse(
            new CashShiftResource($shift),
            'Caja actual obtenida exitosamente.'
        );
    }

    /**
     * Open a new cash shift.
     */
    public function open(OpenCashShiftRequest $request)
    {
        $shift = $this->cashShiftService->openShift($request->validated());

        return $this->createdResponse(
            new CashShiftResource($shift),
            'Caja abierta exitosamente.'
        );
    }

    /**
     * Close the current cash shift.
     */
    public function close(CloseCashShiftRequest $request)
    {
        $shift = $this->cashShiftService->closeShift($request->validated());

        return $this->successResponse(
            new CashShiftResource($shift),
            'Caja cerrada exitosamente.'
        );
    }
}
