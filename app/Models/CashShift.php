<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'opening_time',
        'closing_time',
        'opening_balance',
        'closing_balance',
        'user_id_opened',
        'user_id_closed',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_time' => 'datetime',
            'closing_time' => 'datetime',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
        ];
    }

    /**
     * Get the user who opened this cash shift.
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_opened');
    }

    /**
     * Get the user who closed this cash shift.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_closed');
    }

    /**
     * Get the payments registered during this cash shift.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }
}
