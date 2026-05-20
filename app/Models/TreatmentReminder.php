<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreatmentReminder extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONTACTED,
        self::STATUS_DISMISSED,
        self::STATUS_FULFILLED,
    ];

    public const CHANNELS = ['whatsapp', 'email', 'phone', 'in_person', 'other'];

    protected $fillable = [
        'patient_id', 'appointment_id', 'service_id', 'doctor_id', 'created_by_user_id',
        'due_at', 'notify_from_at', 'status',
        'dismissed_reason', 'dismissed_by_user_id',
        'contacted_at', 'contacted_via', 'contacted_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'notify_from_at' => 'date',
            'contacted_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by_user_id');
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by_user_id');
    }

    /**
     * Activos: pending + notify_from_at <= hoy + due_at >= hoy-7d
     * (los que reclaman acción hoy).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->whereDate('notify_from_at', '<=', now()->toDateString())
            ->whereDate('due_at', '>=', now()->subDays(7)->toDateString());
    }

    /**
     * Próximos: pending pero todavía no entró en la ventana de aviso.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->whereDate('notify_from_at', '>', now()->toDateString());
    }

    /**
     * Historial: ya cerrados.
     */
    public function scopeHistory(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_CONTACTED,
            self::STATUS_DISMISSED,
            self::STATUS_FULFILLED,
        ]);
    }
}
