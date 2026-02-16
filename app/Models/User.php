<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'cuit',
        'specialty',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the role for this user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the appointments where this user is the doctor.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    /**
     * Get the medical records created by this doctor.
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'doctor_id');
    }

    /**
     * Get the availability slots for this doctor.
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(DoctorAvailability::class, 'doctor_id');
    }

    /**
     * Check if the user has the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role_id === 1;
    }

    /**
     * Check if the user has the doctor role.
     */
    public function isDoctor(): bool
    {
        return $this->role_id === 2;
    }

    /**
     * Check if the user has the receptionist role.
     */
    public function isReceptionist(): bool
    {
        return $this->role_id === 3;
    }

    /**
     * Check if the user has a specific role by name.
     */
    public function hasRole(string $role): bool
    {
        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role->name === $role;
    }
}
