<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'dni',
        'cuit',
        'email',
        'phone',
        'birth_date',
        'street',
        'street_number',
        'floor',
        'apartment',
        'city',
        'province',
        'zip_code',
        'country',
        'insurance_provider',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * Get the formatted full address.
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = [];

        if ($this->street) {
            $address = $this->street;
            if ($this->street_number) {
                $address .= ' '.$this->street_number;
            }
            if ($this->floor || $this->apartment) {
                $subParts = [];
                if ($this->floor) {
                    $subParts[] = $this->floor.'°';
                }
                if ($this->apartment) {
                    $subParts[] = $this->apartment;
                }
                $address .= ' ('.implode(' ', $subParts).')';
            }
            $parts[] = $address;
        }

        if ($this->city) {
            $parts[] = $this->city;
        }

        if ($this->province) {
            $parts[] = $this->province;
        }

        if ($this->zip_code) {
            $parts[] = 'CP '.$this->zip_code;
        }

        return count($parts) > 0 ? implode(', ', $parts) : null;
    }

    /**
     * Get the appointments for this patient.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the medical records for this patient.
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Get the invoices for this patient.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
