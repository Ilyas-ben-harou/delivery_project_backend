<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientRegisterControler extends Controller
{
    public function register(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'required|string|regex:/^00212[0-9]{9}$/',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'boutique' => 'required|string|max:255',
            'cin' => 'required|string|max:255',
            'banque' => 'required|string|in:AL BARID BANK,BMCE,BMCI,ATTIJARI',
            'rib' => 'required|string|regex:/^[0-9]{24}$/',
            'ville' => 'required|string|in:ADISS,CASABLANCA,RABAT,MARRAKECH',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'telephone' => $request->telephone,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Make sure it's $request->password (not motDePasse)
            'role' => 'client',
        ]);

        // Create new client
        $client = Client::create([
            'user_id' => $user->id,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'boutique' => $request->boutique,
            'cin' => $request->cin,
            'banque' => $request->banque,
            'rib' => $request->rib,
            'ville' => $request->ville,
            'adresse' => $request->adresse,
        ]);

        // Generate token for authenticated access
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful.',
            'user' => $user,
            'token' => $token
        ], 201);
    }

}

