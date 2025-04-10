<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
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

        $token = $user->createToken('auth_token')->plainTextToken;

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
}