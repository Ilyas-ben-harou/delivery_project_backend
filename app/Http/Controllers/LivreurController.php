<?php

namespace App\Http\Controllers;

use App\Models\Livreur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LivreurController extends Controller
{
    public function store(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'telephone' => ['required', 'string', 'regex:/^(?:\+212|0)[5-7][0-9]{8}$/'],
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'cin' => ['required', 'string', 'regex:/^[A-Z]{1,2}[0-9]{5,6}$/'],
            'zone_geographic_id' => 'required|integer|exists:zone_geographics,id',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'phone_number' => $request->telephone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'livreur',
            ]);

            $livreur = Livreur::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'cin' => $request->cin,
                'zone_geographic_id' => $request->zone_geographic_id, // Changed to match your migration
                'disponible' => true,
                'nomber_livraisons' => 0,
                'adresse' => $request->adresse,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Livreur created successfully',
                'data' => [
                    'user' => $user,
                    'livreur' => $livreur
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create livreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
