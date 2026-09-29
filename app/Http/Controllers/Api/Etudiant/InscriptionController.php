<?php
// app/Http/Controllers/Api/Etudiant/InscriptionController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InscriptionController extends Controller
{
    /**
     * S'inscrire à une formation
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'formation_id' => 'required|exists:formations,id',
                'vague_id' => 'required|exists:vagues,id',
                'reference_bancaire' => 'nullable|string|max:255',
            ]);

            $user = $request->user();

            // Vérifier si déjà inscrit
            $existing = Inscription::where('etudiant_id', $user->id)
                ->where('formation_id', $validated['formation_id'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous êtes déjà inscrit à cette formation.'
                ], 400);
            }

            $inscription = Inscription::create([
                'etudiant_id' => $user->id,
                'formation_id' => $validated['formation_id'],
                'vague_id' => $validated['vague_id'],
                'statut' => 'en_attente',
                'progression' => 0,
                'reference_bancaire' => $validated['reference_bancaire'] ?? null,
                'date_inscription' => now(),
            ]);

            Log::info('Nouvelle inscription', [
                'user_id' => $user->id,
                'formation_id' => $validated['formation_id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription en attente de validation.',
                'data' => $inscription
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur inscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()
            ], 500);
        }
    }
}