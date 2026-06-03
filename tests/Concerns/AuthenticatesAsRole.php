<?php

namespace Tests\Concerns;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Test helpers for picking up the three roles that DatabaseSeeder produces
 * and grabbing a JWT for each — used as `$this->actingAsRole('doctor')`
 * style across the authorization test suite.
 */
trait AuthenticatesAsRole
{
    protected function doctor(): User
    {
        return User::where('email', 'doctor@dermamed.com')->firstOrFail();
    }

    protected function clinicManager(): User
    {
        // 'director' has both clinic_manager + doctor roles in the seeder.
        return User::where('email', 'director@dermamed.com')->firstOrFail();
    }

    protected function receptionist(): User
    {
        return User::where('email', 'recepcion@dermamed.com')->firstOrFail();
    }

    /**
     * Returns a JWT for the given user, ready to drop into withToken().
     */
    protected function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }
}
