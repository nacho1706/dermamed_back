<?php

namespace App\Observers;

use App\Models\Role;
use Illuminate\Support\Facades\Cache;

class RoleObserver
{
    public function saved(Role $role): void
    {
        Cache::forget('roles');
    }

    public function deleted(Role $role): void
    {
        Cache::forget('roles');
    }
}
