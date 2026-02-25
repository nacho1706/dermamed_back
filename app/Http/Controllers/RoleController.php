<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     * 
     * Capa 1: Backend - Endpoint de Roles (Ocultamiento)
     * Nunca devuelve el rol system_admin a menos que el usuario sea system_admin.
     */
    public function index()
    {
        $query = Role::query();

        if (!Auth::user()->isSystemAdmin()) {
            $query->where('name', '!=', 'system_admin');
        }

        return RoleResource::collection($query->get());
    }
}
