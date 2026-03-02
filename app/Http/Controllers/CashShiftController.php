<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashShift\CloseCashShiftRequest;
use App\Http\Requests\CashShift\OpenCashShiftRequest;
use App\Http\Resources\CashShiftResource;
use App\Services\CashShiftService;

class CashShiftController extends Controller
{
    public function __construct(
        private readonly CashShiftService $cashShiftService,
    ) {}

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
