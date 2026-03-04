<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        $roles = \Illuminate\Support\Facades\Cache::rememberForever('roles', function () {
            return Role::all();
        });
        return RoleResource::collection($roles);
    }
}
