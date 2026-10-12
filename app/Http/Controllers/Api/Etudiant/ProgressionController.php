<?php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Inscription;
use App\Models\Progression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProgressionController extends Controller
{
    /**
     * POST /api/etudiant/progression/{coursId}
     *
     * Marque un cours comme "en cours" ou "terminé"
     * Body :
     *  - action : 'start' | 'complete' | 'progress'
     *  - pourcentage (optionnel, pour 'progress')
     *  - temps_passe_minutes (optionnel)
     */
    public function update(Request $request, $coursId)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'action' => 'required|in:start,complete,progress',
                'pourcentage' => 'nullable|integer|min:0|max:100',
                'temps_passe_minutes' => 'nullable|integer|min:0',
            ]);

            // Vérifier que l'étudiant a accès à ce cours
            $cours = Cours::findOrFail($coursId);

            $hasAccess = Inscription::where('etudiant_id', $user->id)
                ->where('formation_id', $cours->formation_id)
                ->where('statut', 'valide')
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé à ce cours.',
                ], 403);
            }

            // Récupérer ou créer la progression
            $progression = Progression::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'cours_id' => $coursId,
                ],
                [
                    'pourcentage' => 0,
                    'statut' => 'non_commence',
                ]
            );

            // Appliquer l'action
            switch ($validated['action']) {
                case 'start':
                    $progression->statut = 'en_cours';
                    $progression->started_at = $progression->started_at ?? now();
                    $progression->last_activity_at = now();
                    if ($progression->pourcentage === 0) {
                        $progression->pourcentage = 10;
                    }
                    break;

                case 'progress':
                    $progression->pourcentage = $validated['pourcentage'] ?? $progression->pourcentage;
                    $progression->statut = $progression->pourcentage >= 100 ? 'termine' : 'en_cours';
                    $progression->last_activity_at = now();
                    if ($progression->statut === 'termine' && !$progression->completed_at) {
                        $progression->completed_at = now();
                    }
                    break;

                case 'complete':
                    $progression->pourcentage = 100;
                    $progression->statut = 'termine';
                    $progression->completed_at = now();
                    $progression->last_activity_at = now();
                    break;
            }

            if (!empty($validated['temps_passe_minutes'])) {
                $progression->temps_passe_minutes += $validated['temps_passe_minutes'];
            }

            $progression->save();

            return response()->json([
                'success' => true,
                'message' => 'Progression mise à jour.',
                'data' => $progression,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur progression update', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour.',
            ], 500);
        }
    }

    /**
     * GET /api/etudiant/progression/formation/{formationId}
     *
     * Retourne la progression détaillée pour une formation
     */
    public function formationProgress(Request $request, $formationId)
    {
        try {
            $user = $request->user();

            // Vérifier l'accès
            $inscription = Inscription::where('etudiant_id', $user->id)
                ->where('formation_id', $formationId)
                ->where('statut', 'valide')
                ->first();

            if (!$inscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formation non trouvée ou accès refusé.',
                ], 404);
            }

            // Cours de la formation
            $cours = Cours::where('formation_id', $formationId)->get();

            // Progressions de l'utilisateur pour ces cours
            $progressions = Progression::where('user_id', $user->id)
                ->whereIn('cours_id', $cours->pluck('id'))
                ->get()
                ->keyBy('cours_id');

            // Calcul
            $totalCours = $cours->count();
            $coursTermines = $progressions->where('statut', 'termine')->count();
            $pourcentage = $totalCours > 0
                ? round(($coursTermines / $totalCours) * 100)
                : 0;

            // Détail par cours
            $details = $cours->map(function ($c) use ($progressions) {
                $p = $progressions->get($c->id);

                return [
                    'cours_id' => $c->id,
                    'titre' => $c->titre ?? $c->nom ?? 'Cours',
                    'statut' => $p?->statut ?? 'non_commence',
                    'pourcentage' => $p?->pourcentage ?? 0,
                    'started_at' => $p?->started_at,
                    'completed_at' => $p?->completed_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'formation_id' => (int) $formationId,
                    'total_cours' => $totalCours,
                    'cours_termines' => $coursTermines,
                    'pourcentage' => $pourcentage,
                    'cours' => $details,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur formation progress', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de progression.',
            ], 500);
        }
    }
}