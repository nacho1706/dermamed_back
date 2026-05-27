<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_minutes',
        'doctor_commission_percentage',
        'followup_default_days',
        'followup_notify_anticipation_days',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'doctor_commission_percentage' => 'decimal:2',
            'followup_default_days' => 'integer',
            'followup_notify_anticipation_days' => 'integer',
        ];
    }

    /**
     * Get the appointments using this service.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
