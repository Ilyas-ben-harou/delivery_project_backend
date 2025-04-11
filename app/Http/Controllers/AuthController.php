<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Livreur;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'boolean',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Load the specific user type data
        if ($user->role === 'admin') {
            $user->load('admin');
        } elseif ($user->role === 'client') {
            $user->load('client');
        } elseif ($user->role === 'livreur') {
            $user->load('livreur');
        }

        // Delete existing tokens
        $user->tokens()->delete();

        // Create a new token with expiration based on remember me
        $tokenExpiration = $request->remember ? 60 * 24 * 30 : 60 * 24; // 30 days or 1 day
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes($tokenExpiration))->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
    public function me(Request $request)
    {
        $user = $request->user();

        // Load the specific user type data
        if ($user->role === 'admin') {
            $user->load('admin');
        } elseif ($user->role === 'client') {
            $user->load('client');
        } elseif ($user->role === 'livreur') {
            $user->load('livreur');
        }

        return response()->json(['user' => $user]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'We cannot find an user with that email address.'
            ], 404);
        }

        // Send reset password link
        $status = Password::broker('users')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent to your email']);
        }

        return response()->json(['message' => 'Unable to send password reset link'], 500);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been reset successfully']);
        }

        return response()->json([
            'message' => 'Invalid token provided'
        ], 400);
    }
}