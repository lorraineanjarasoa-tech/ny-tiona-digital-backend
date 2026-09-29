<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use Illuminate\Http\Request;

class CoursController extends Controller
{
    public function index(Request $request)
    {
        $cours = Cours::with('formateur')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cours,
        ]);
    }

    public function show($id)
    {
        $cours = Cours::with('formateur')->find($id);

        if (!$cours) {
            return response()->json([
                'success' => false,
                'message' => 'Cours introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cours,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['formateur_id'] = $request->user()->id;

        $cours = Cours::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cours créé avec succès.',
            'data' => $cours,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $cours = Cours::where('id', $id)
            ->where('formateur_id', $request->user()->id)
            ->first();

        if (!$cours) {
            return response()->json([
                'success' => false,
                'message' => 'Cours introuvable ou accès non autorisé.',
            ], 404);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $cours->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cours modifié avec succès.',
            'data' => $cours,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $cours = Cours::where('id', $id)
            ->where('formateur_id', $request->user()->id)
            ->first();

        if (!$cours) {
            return response()->json([
                'success' => false,
                'message' => 'Cours introuvable ou accès non autorisé.',
            ], 404);
        }

        $cours->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cours supprimé avec succès.',
        ]);
    }
}