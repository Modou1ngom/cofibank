<?php

namespace App\Http\Controllers;

use App\Models\Territory;
use App\Models\User;
use Illuminate\Http\Request;

class TerritoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $territories = Territory::with('responsible')->get();
        return response()->json($territories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:territories,code',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
        ]);

        $territory = Territory::create($validated);
        return response()->json($territory->load('responsible'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $territory = Territory::with(['responsible', 'agencies.chefAgence'])->findOrFail($id);
        return response()->json($territory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $territory = Territory::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'sometimes|string|unique:territories,code,' . $id,
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $territory->update($validated);
        return response()->json($territory->load('responsible'));
    }

    /**
     * Associate a responsible user to a territory
     */
    public function assignResponsible(Request $request, string $id)
    {
        $territory = Territory::findOrFail($id);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        
        // Avertissement si l'utilisateur n'a pas le profil RESPONSABLE_ZONE (mais on permet quand même)
        $warning = null;
        if ($user->profile->code !== 'RESPONSABLE_ZONE') {
            $warning = 'Attention: L\'utilisateur sélectionné n\'a pas le profil Responsable de Zone.';
        }

        $territory->responsible_user_id = $validated['user_id'];
        $territory->save();

        $response = response()->json($territory->load('responsible'));
        
        if ($warning) {
            $response->header('X-Warning', $warning);
        }
        
        return $response;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $territory = Territory::findOrFail($id);
        $territory->delete();

        return response()->json(['message' => 'Territoire supprimé avec succès']);
    }
}
