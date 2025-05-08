<?php

namespace App\Http\Controllers;

use App\Events\LivreurUnavailable;
use App\Models\Livreur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LivreurController extends Controller
{
    public function index()
    {
        try {
            $livreurs = Livreur::with(['user', 'zones'])->get();

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
            $livreur = Livreur::with(['user', 'zones'])->findOrFail($id);

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

    public function updateDisponibleByAdmin($id, Request $request)
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
            'zone_geographic_ids' => 'required|array',
            'zone_geographic_ids.*' => 'integer|exists:zone_geographics,id',
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
                'disponible' => true,
                'nomber_livraisons' => 0,
                'adresse' => $request->adresse,
            ]);

            // Attacher plusieurs zones au livreur
            $livreur->zones()->attach($request->zone_geographic_ids);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Livreur created successfully',
                'data' => [
                    'user' => $user,
                    'livreur' => $livreur->load('zones'),
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

            // Détacher les zones (optionnel car cascade possible)
            $livreur->zones()->detach();

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

    public function updateAvailability(Request $request)
    {
        $user = Auth::user();
        $livreur = $user->livreur;

        if (!$livreur) {
            return response()->json(['message' => 'User is not a livreur'], 403);
        }

        $request->validate([
            'disponible' => 'required|boolean',
            'unavailablePeriod.start' => 'required_if:disponible,false|date',
            'unavailablePeriod.end' => 'required_if:disponible,false|date|after:unavailablePeriod.start',
            'unavailablePeriod.reason' => 'required_if:disponible,false|string|max:255'
        ]);

        $livreur->disponible = $request->disponible;

        if (!$request->disponible) {
            event(new LivreurUnavailable(
                $livreur,
                $request->input('unavailablePeriod.reason'),
                [
                    'start' => $request->input('unavailablePeriod.start'),
                    'end' => $request->input('unavailablePeriod.end')
                ]
            ));
        } else {
            $livreur->unavailable_start = null;
            $livreur->unavailable_end = null;
            $livreur->unavailable_reason = null;
        }

        $livreur->save();

        return response()->json([
            'message' => 'Availability updated successfully',
            'livreur' => $livreur
        ]);
    }
}
