<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FormationController extends Controller
{
    /**
     * Liste des formations.
     */
    public function index(): JsonResponse
    {
        try {

            $formations = Formation::query()
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get([
                    'id',
                    'titre',
                    'description',
                    'image',
                    'prix',
                    'duree',
                    'unite_duree',
                    'niveau',
                    'domaine',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Formations récupérées avec succès',
                'data' => $formations,
            ]);

        } catch (\Throwable $e) {

            Log::error('Erreur récupération formations', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des formations',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    /**
     * Afficher une formation.
     */
    public function show(string $id): JsonResponse
    {
        try {

            $formation = Formation::find($id);

            if (!$formation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formation introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $formation,
            ]);

        } catch (\Throwable $e) {

            Log::error('Erreur récupération formation', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }
}