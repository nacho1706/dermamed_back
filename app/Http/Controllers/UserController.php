<?php

namespace App\Http\Controllers;

use App\Factories\UserFactory;
use App\Http\Requests\User\IndexUsersRequest;
use App\Http\Requests\User\LoginUserRequest;
use App\Http\Requests\User\RegisterUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();

        $user = UserFactory::fromRequest($validated);
        $user->save();
        $user->load('roles');

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginUserRequest $request)
    {
        $validated = $request->validated();

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::where('email', $validated['email'])->with('roles')->first();

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function index(IndexUsersRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['per_page'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = User::query()->with('roles');

        if (isset($validated['name'])) {
            $query->where('name', 'like', '%'.$validated['name'].'%');
        }

        if (isset($validated['email'])) {
            $query->where('email', 'like', '%'.$validated['email'].'%');
        }

        if (isset($validated['role_id'])) {
            $query->whereHas('roles', function ($q) use ($validated) {
                $q->where('roles.id', $validated['role_id']);
            });
        }

        if (isset($validated['role'])) {
            $query->whereHas('roles', function ($q) use ($validated) {
                $q->where('roles.name', $validated['role']);
            });
        }

        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

        return UserResource::collection($paginador);
    }

    public function show(User $user)
    {
        $user->load('roles');

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $user = UserFactory::fromRequest($validated, $user);
        $user->save();

        if (isset($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        $user->load('roles');

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function me()
    {
        $user = auth()->user();
        $user->load('roles');

        return new UserResource($user);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return response()->json([
            'token' => $token,
        ]);
    }
}
