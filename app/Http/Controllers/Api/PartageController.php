<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partage;
use Illuminate\Http\Request;

class PartageController extends Controller
{
    public function index()
    {
        $partages = Partage::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $partages,
        ]);
    }

    public function show($id)
    {
        $partage = Partage::find($id);

        if (!$partage) {
            return response()->json([
                'success' => false,
                'message' => 'Partage introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $partage,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
        ]);

        $validated['user_id'] = $request->user()->id;

        $partage = Partage::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Partage créé avec succès.',
            'data' => $partage,
        ], 201);
    }

    public function mesPartages(Request $request)
    {
        $partages = Partage::where(
            'user_id',
            $request->user()->id
        )->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $partages,
        ]);
    }

    public function update(Request $request, $id)
    {
        $partage = Partage::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$partage) {
            return response()->json([
                'success' => false,
                'message' => 'Partage introuvable.',
            ], 404);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'contenu' => 'sometimes|required|string',
        ]);

        $partage->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Partage modifié avec succès.',
            'data' => $partage,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $partage = Partage::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$partage) {
            return response()->json([
                'success' => false,
                'message' => 'Partage introuvable.',
            ], 404);
        }

        $partage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partage supprimé avec succès.',
        ]);
    }

    public function like(Request $request, $id)
    {
        $partage = Partage::find($id);

        if (!$partage) {
            return response()->json([
                'success' => false,
                'message' => 'Partage introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Like enregistré.',
        ]);
    }

    public function commenter(Request $request, $id)
    {
        $validated = $request->validate([
            'contenu' => 'required|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire ajouté.',
            'data' => $validated,
        ], 201);
    }
}