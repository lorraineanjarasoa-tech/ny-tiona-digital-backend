<?php
// app/Http/Controllers/Api/Etudiant/CoursController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CoursController extends Controller
{
    /**
     * Voir un cours spécifique
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            
            // Vérifier que l'étudiant a accès à ce cours
            $inscription = Inscription::where('etudiant_id', $user->id)
                ->where('formation_id', $id)
                ->where('statut', 'valide')
                ->first();

            if (!$inscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à ce cours.'
                ], 403);
            }

            $formation = Formation::with(['cours', 'vagues'])->find($id);

            if (!$formation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formation non trouvée.'
                ], 404);
            }

            // Ajouter la progression de l'étudiant
            $formation->progression = $inscription->progression ?? 0;

            return response()->json($formation);

        } catch (\Exception $e) {
            Log::error('Erreur voir cours: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du cours.'
            ], 500);
        }
    }
}