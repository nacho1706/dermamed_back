<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\UserResource;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Mail;
use App\Mail\UserInvitationMail;

class UserInvitationController extends Controller
{
    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'specialty' => 'nullable|string|max:100',
        ]);

        // Create user with pending status
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)), // Temporary random password
            'status' => 'pending_activation',
            'is_active' => true, // Active but pending activation
            'specialty' => $validated['specialty'] ?? null,
        ]);

        $user->roles()->syncWithoutDetaching($validated['role_ids']);

        // Create invitation token
        $token = Str::random(64);
        UserInvitation::create([
            'email' => $validated['email'],
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ]);

        $this->sendInvitationEmail($user, $token);
        
        Log::info("Invitation token for {$user->email}: {$token}");

        return response()->json([
            'message' => 'Invitation sent successfully',
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    public function resend(User $user)
    {
        if ($user->status !== 'pending_activation') {
            return response()->json(['message' => 'User is already active'], 400);
        }

        // Invalidate old invitations
        UserInvitation::where('email', $user->email)->delete();

        // Create new token
        $token = Str::random(64);
        UserInvitation::create([
            'email' => $user->email,
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ]);

        $this->sendInvitationEmail($user, $token);

        Log::info("Resent invitation token for {$user->email}: {$token}");

        return response()->json(['message' => 'Invitation resent successfully']);
    }

    public function verify($token)
    {
        $invitation = UserInvitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid or expired token'], 404);
        }

        return response()->json(['message' => 'Token is valid', 'email' => $invitation->email]);
    }

    public function activate(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $invitation = UserInvitation::where('token', $validated['token'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid or expired token'], 404);
        }

        $user = User::where('email', $invitation->email)->firstOrFail();

        $user->update([
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Delete invitation
        $invitation->delete();

        // Login user
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Account activated successfully',
            'token' => $token,
            'user' => new UserResource($user->load('roles')),
        ]);
    }

    private function sendInvitationEmail(User $user, string $token)
    {
        try {
            Mail::to($user->email)->send(new UserInvitationMail($user, $token));
        } catch (\Exception $e) {
            Log::error('Error sending invitation email via Laravel Mail: ' . $e->getMessage());
        }
    }
}

