<?php

namespace App\Http\Controllers;

use App\Models\ZoneGeographic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZoneGeographicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $zoneGeographics = ZoneGeographic::select('id', 'city', 'secteur')
                ->orderBy('city')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $zoneGeographics,
                'message' => 'Zones retrieved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching zones: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve zones',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'city' => 'required|string|max:255',
            'secteur' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create a new zone
        $zone = new ZoneGeographic();
        $zone->city = $request->city;
        $zone->secteur = $request->secteur;
        $zone->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Zone created successfully',
            'data' => $zone
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $zone = ZoneGeographic::find($id);

        if (!$zone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Zone not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $zone
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Find the zone
        $zone = ZoneGeographic::find($id);

        if (!$zone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Zone not found'
            ], 404);
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'city' => 'required|string|max:255',
            'secteur' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the zone
        $zone->city = $request->city;
        $zone->secteur = $request->secteur;
        $zone->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Zone updated successfully',
            'data' => $zone
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $zone = ZoneGeographic::find($id);

        if (!$zone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Zone not found'
            ], 404);
        }

        $zone->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Zone deleted successfully'
        ]);
    }
}
