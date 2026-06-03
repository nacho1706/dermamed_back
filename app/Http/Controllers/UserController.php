<?php

namespace App\Http\Controllers;

use App\Factories\UserFactory;
use App\Http\Requests\User\ChangeMyPasswordRequest;
use App\Http\Requests\User\IndexUsersRequest;
use App\Http\Requests\User\LoginUserRequest;
use App\Http\Requests\User\UpdateMeRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\AuthCookies;
use App\Support\Search;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
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

        // The JWT is delivered as an HttpOnly cookie so XSS cannot read it.
        // The token is also returned in the body for backwards-compat with
        // legacy Bearer clients (tests, server-to-server); the frontend
        // ignores it and reads the cookie set automatically by the browser.
        $response = response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);

        return AuthCookies::attach($response, $token);
    }

    public function index(IndexUsersRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['per_page'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = User::query()->with('roles');

        if (isset($validated['name'])) {
            $query->where('name', 'ilike', '%'.Search::escapeLike($validated['name']).'%');
        }

        if (isset($validated['email'])) {
            $query->where('email', 'ilike', '%'.Search::escapeLike($validated['email']).'%');
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

    public function updateMe(UpdateMeRequest $request)
    {
        $user = auth()->user();
        $user->fill($request->validated());
        $user->save();
        $user->load('roles');

        return new UserResource($user);
    }

    public function changeMyPassword(ChangeMyPasswordRequest $request)
    {
        $user = auth()->user();
        $user->password = Hash::make($request->validated()['password']);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Throwable $e) {
            // If the token was already invalid or missing, log out is still
            // a success from the client's perspective — just clear cookies.
        }

        $response = response()->json([
            'message' => 'Successfully logged out',
        ]);

        return AuthCookies::forget($response);
    }

    public function refresh()
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        $response = response()->json([
            'token' => $token,
        ]);

        return AuthCookies::attach($response, $token);
    }

    /**
     * Issue a fresh CSRF cookie that the frontend can read and echo back
     * in the X-XSRF-TOKEN header. Called once on app boot before any
     * mutation; safe to call repeatedly.
     *
     * Also returns the token in the response body so the frontend can
     * stash it directly in axios defaults — this bypasses any cross-origin
     * cookie weirdness (frontend on :3000 reading cookies set by :8080).
     */
    public function csrfToken()
    {
        $token = AuthCookies::generateCsrfToken();
        $response = response()->json(['token' => $token]);
        $secure = app()->environment('production');

        $response->headers->setCookie(
            \Illuminate\Support\Facades\Cookie::make(
                name: AuthCookies::CSRF_COOKIE,
                value: $token,
                minutes: 60 * 24,
                path: '/',
                domain: null,
                secure: $secure,
                httpOnly: false,
                raw: false,
                sameSite: 'lax',
            )
        );

        return $response;
    }
}
