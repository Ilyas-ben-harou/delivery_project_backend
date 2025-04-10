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
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'phone_number' => 'required|string',
            'user_type' => 'required|in:admin,client,livreur',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'user_type' => $request->user_type,
        ]);

        // Create specific user type record
        if ($request->user_type === 'admin') {
            Admin::create([
                'user_id' => $user->id,
            ]);
        } elseif ($request->user_type === 'client') {
            $request->validate([
                'company_name' => 'required|string',
                'address' => 'required|string',
                'bank_name' => 'required|string',
                'rib' => 'required|string',
            ]);

            Client::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'address' => $request->address,
                'bank_name' => $request->bank_name,
                'rib' => $request->rib,
            ]);
        } elseif ($request->user_type === 'livreur') {
            $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'zone_geographic_id' => 'required|exists:zone_geographics,id',
            ]);

            Livreur::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'zone_geographic_id' => $request->zone_geographic_id,
                'is_available' => true,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

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
        if ($user->user_type === 'admin') {
            $user->load('admin');
        } elseif ($user->user_type === 'client') {
            $user->load('client');
        } elseif ($user->user_type === 'livreur') {
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

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        // Load the specific user type data
        if ($user->user_type === 'admin') {
            $user->load('admin');
        } elseif ($user->user_type === 'client') {
            $user->load('client');
        } elseif ($user->user_type === 'livreur') {
            $user->load('livreur');
        }

        return response()->json(['user' => $user]);
    }
}