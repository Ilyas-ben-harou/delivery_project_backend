<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index()
    {
        try {
            $clients = Client::with(['user'])->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $clients
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch client',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function register(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => ['required', 'string', 'regex:/^(?:\+212|0)[5-7][0-9]{8}$/'],
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'boutique' => 'required|string|max:255',
            'cin' => ['required', 'string', 'regex:/^[A-Z]{1,2}[0-9]{5,6}$/'],
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
            'phone_number' => $request->telephone,
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
    public function show($id)
    {
        try {
            $client = Client::with(['user'])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $client
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'client not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    public function update(Request $request, $id)
{
    try {
        $client = Client::findOrFail($id);
        $user = User::findOrFail($client->user_id);

        // Validate request data with correct user ID
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => ['required', 'string', 'regex:/^(?:\+212|0)[5-7][0-9]{8}$/'],
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'boutique' => 'required|string|max:255',
            'cin' => ['required', 'string', 'regex:/^[A-Z]{1,2}[0-9]{5,6}$/'],
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

        DB::beginTransaction();

        // Update user details
        $userData = [
            'phone_number' => $request->telephone,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Update client details
        $client->update([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'boutique' => $request->boutique,
            'cin' => $request->cin,
            'banque' => $request->banque,
            'rib' => $request->rib,
            'ville' => $request->ville,
            'adresse' => $request->adresse,
        ]);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Client updated successfully',
            'data' => [
                'client' => $client->fresh(['user']),
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update client',
            'error' => $e->getMessage()
        ], 500);
    }
}

            
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $client = Client::findOrFail($id);
            $user = User::findOrFail($client->user_id);

            $client->delete();
            $user->delete();
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'client deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete client',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
