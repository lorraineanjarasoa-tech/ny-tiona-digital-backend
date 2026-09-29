<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Meeting;
use App\Models\Cours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EtudiantController extends Controller
{
    public function stats()
    {
        try {
            $user = auth()->user();
            
            $totalFormations = Inscription::where('etudiant_id', $user->id)
                ->where('statut', 'valide')
                ->count();
                
            $totalMeetings = Meeting::where('created_by', '!=', $user->id)->count();
            
            $messagesNonLus = 0; // À implémenter avec le chat
            
            $coursTermines = 0; // À implémenter avec la progression

            return response()->json([
                'total_formations' => $totalFormations,
                'total_meetings' => $totalMeetings,
                'messages_non_lus' => $messagesNonLus,
                'cours_termines' => $coursTermines,
            ]);
        } catch (\Exception $e) {
            Log::error('Etudiant stats error: ' . $e->getMessage());
            return response()->json([
                'total_formations' => 0,
                'total_meetings' => 0,
                'messages_non_lus' => 0,
                'cours_termines' => 0,
            ]);
        }
    }

    public function mesFormations()
    {
        try {
            $user = auth()->user();
            
            $formations = Inscription::with('formation')
                ->where('etudiant_id', $user->id)
                ->where('statut', 'valide')
                ->get()
                ->map(function ($inscription) {
                    return [
                        'id' => $inscription->formation->id,
                        'nom' => $inscription->formation->titre ?? $inscription->formation->nom,
                        'progression' => rand(0, 100), // À implémenter avec la progression réelle
                    ];
                });

            return response()->json($formations);
        } catch (\Exception $e) {
            Log::error('Mes formations error: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }

    public function meetings()
    {
        try {
            $meetings = Meeting::where('statut', 'planifie')
                ->orWhere('statut', 'en_cours')
                ->orderBy('date_heure')
                ->limit(5)
                ->get();

            return response()->json($meetings);
        } catch (\Exception $e) {
            Log::error('Meetings error: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }

    public function sInscrire(Request $request)
    {
        try {
            $validated = $request->validate([
                'formation_id' => 'required|exists:formations,id',
            ]);

            $user = auth()->user();
            
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
                'statut' => 'en_attente',
                'date_inscription' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription en attente de validation.',
                'data' => $inscription
            ]);
        } catch (\Exception $e) {
            Log::error('Inscription error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription.'
            ], 500);
        }
    }

    public function voirCours($id)
    {
        try {
            $cours = Cours::with('formation')
                ->where('id', $id)
                ->where('est_visible', true)
                ->firstOrFail();

            return response()->json($cours);
        } catch (\Exception $e) {
            Log::error('Voir cours error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Cours non trouvé'
            ], 404);
        }
    }
}