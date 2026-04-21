<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Handle user login and issue a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'description' => "User {$user->name} logged in.",
            'ip_address' => $request->ip(),
        ]);

        $user->load('roles.permissions');

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
            'permissions' => $user->getAllPermissions()->pluck('slug'),
            'roles' => $user->roles->pluck('slug'),
        ]);
    }

    /**
     * Handle user logout by revoking the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'logout',
            'description' => "User {$user->name} logged out.",
            'ip_address' => $request->ip(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the authenticated user with roles and permissions.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles.permissions');

        return response()->json([
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('slug'),
            'roles' => $user->roles->pluck('slug'),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required_with:new_password|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        if (!empty($validated['current_password'])) {
            if (!Auth::attempt(['email' => $user->email, 'password' => $validated['current_password']])) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }
        }

        if (!empty($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (!empty($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (!empty($validated['new_password'])) {
            $user->password = bcrypt($validated['new_password']);
        }

        $user->save();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'description' => "User \"{$user->name}\" updated their profile.",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }
}
