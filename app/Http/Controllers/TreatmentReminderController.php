<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentReminderRequest;
use App\Http\Requests\UpdateTreatmentReminderRequest;
use App\Models\TreatmentReminder;
use App\Services\ClinicSettingService;
use App\Services\TreatmentReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreatmentReminderController extends Controller
{
    public function __construct(
        private TreatmentReminderService $reminders,
        private ClinicSettingService $settings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tab = $request->query('tab', 'active'); // active, upcoming, history
        $query = TreatmentReminder::query()
            ->with(['patient', 'service', 'doctor']);

        match ($tab) {
            'upcoming' => $query->upcoming(),
            'history' => $query->history(),
            default => $query->active(),
        };

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('due_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('due_at', '<=', $request->to);
        }

        $perPage = (int) $request->query('per_page', 20);

        return response()->json($query->orderBy('due_at')->paginate($perPage));
    }

    public function show(TreatmentReminder $treatmentReminder): JsonResponse
    {
        $treatmentReminder->load(['patient', 'service', 'doctor', 'appointment']);

        return response()->json($treatmentReminder);
    }

    public function store(StoreTreatmentReminderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by_user_id'] = $request->user()->id;
        $data['status'] = TreatmentReminder::STATUS_PENDING;
        $data['notify_from_at'] = $data['notify_from_at']
            ?? \Carbon\Carbon::parse($data['due_at'])
                ->subDays($this->settings->get('treatment_reminders.default_anticipation_days'))
                ->toDateString();

        $reminder = TreatmentReminder::create($data);

        return response()->json($reminder->fresh(['patient', 'service', 'doctor']), 201);
    }

    public function update(UpdateTreatmentReminderRequest $request, TreatmentReminder $treatmentReminder): JsonResponse
    {
        $data = $request->validated();

        // Status transitions van por el service.
        if (isset($data['status'])) {
            try {
                $newStatus = $data['status'];
                if ($newStatus === TreatmentReminder::STATUS_CONTACTED) {
                    $this->reminders->markAsContacted($treatmentReminder, $data['contacted_via'] ?? 'other');
                } elseif ($newStatus === TreatmentReminder::STATUS_DISMISSED) {
                    $this->reminders->markAsDismissed($treatmentReminder, $data['dismissed_reason'] ?? null);
                } elseif ($newStatus === TreatmentReminder::STATUS_FULFILLED) {
                    $this->reminders->markAsFulfilled($treatmentReminder);
                } elseif ($newStatus === TreatmentReminder::STATUS_PENDING) {
                    $treatmentReminder->update(['status' => $newStatus]);
                }
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            unset($data['status'], $data['contacted_via'], $data['dismissed_reason']);
        }

        // Postpone explícito.
        if (isset($data['postpone_days'])) {
            $this->reminders->postpone($treatmentReminder, (int) $data['postpone_days']);
            unset($data['postpone_days']);
        }

        if (! empty($data)) {
            $treatmentReminder->update($data);
        }

        return response()->json($treatmentReminder->fresh(['patient', 'service', 'doctor']));
    }

    public function destroy(TreatmentReminder $treatmentReminder): JsonResponse
    {
        $treatmentReminder->delete();

        return response()->json(null, 204);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'active' => TreatmentReminder::active()->count(),
            'upcoming' => TreatmentReminder::upcoming()->count(),
        ]);
    }

    /**
     * GET /api/treatment-reminders/{id}/messages?channel=whatsapp|email
     * Devuelve el mensaje rendereado y los datos de contacto.
     */
    public function messages(Request $request, TreatmentReminder $treatmentReminder): JsonResponse
    {
        $channel = $request->query('channel', 'whatsapp');
        $treatmentReminder->load(['patient', 'service', 'doctor']);

        if ($channel === 'whatsapp') {
            $template = $this->settings->get('treatment_reminders.whatsapp_template');

            return response()->json([
                'channel' => 'whatsapp',
                'message' => $this->reminders->renderMessage($treatmentReminder, $template),
                'phone' => $treatmentReminder->patient?->phone,
            ]);
        }

        if ($channel === 'email') {
            $subjectTpl = $this->settings->get('treatment_reminders.email_subject_template');
            $bodyTpl = $this->settings->get('treatment_reminders.email_body_template');

            return response()->json([
                'channel' => 'email',
                'subject' => $this->reminders->renderMessage($treatmentReminder, $subjectTpl),
                'body' => $this->reminders->renderMessage($treatmentReminder, $bodyTpl),
                'email' => $treatmentReminder->patient?->email,
            ]);
        }

        return response()->json(['message' => 'Channel inválido'], 422);
    }
}
