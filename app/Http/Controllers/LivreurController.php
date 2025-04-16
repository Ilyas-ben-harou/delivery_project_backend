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
    public function index()
    {
        try {
            $livreurs = Livreur::with(['user', 'zoneGoegraphic'])->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $livreurs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch livreurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            $livreur = Livreur::with(['user', 'zoneGoegraphic'])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $livreur
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Livreur not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function updateDisponibleByAdmin($id,Request $request)
    {
        try {
            $livreur = Livreur::findOrFail($id);
            $livreur->disponible = $request->disponible;
            $livreur->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Disponibility updated successfully',
                'data' => $livreur
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update disponibility',
                'error' => $e->getMessage()
            ], 500);
        }
    }
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
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $livreur = Livreur::findOrFail($id);
            $user = User::findOrFail($livreur->user_id);
            
            $livreur->delete();
            $user->delete();
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Livreur deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete livreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
