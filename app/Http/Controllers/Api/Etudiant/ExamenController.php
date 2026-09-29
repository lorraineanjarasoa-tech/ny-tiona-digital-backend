<?php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\Question;
use App\Models\ReponseExamen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamenController extends Controller
{
    /**
     * Liste des examens disponibles pour l'étudiant.
     * GET /etudiant/examens
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Récupère les examens publiés ou fermés
        // Filtre par formation de l'étudiant (via ses inscriptions)
        $formationsIds = DB::table('inscriptions')
    ->where('etudiant_id', $user->id)
    ->where('statut', 'valide')
    ->pluck('formation_id')
    ->unique()
    ->toArray();

        $examens = Examen::where('statut', '!=', 'brouillon')
            ->where(function ($q) use ($formationsIds) {
                $q->whereIn('formation_id', $formationsIds)
                  ->orWhereNull('formation_id');
            })
            ->with(['formation:id,titre'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($examen) use ($user) {
                $examen->nb_questions = $examen->questions_count;
                $examen->formation_nom = $examen->formation->titre ?? null;
                $examen->statut = $this->statutEffectif($examen, $user->id);

                // Ajoute le score si déjà passé
                $reponse = ReponseExamen::where('user_id', $user->id)
                    ->where('examen_id', $examen->id)
                    ->latest()
                    ->first();

                $examen->score = $reponse?->score;
                $examen->temps_ecoule = $reponse?->temps_ecoule;

                return $examen;
            });

        return response()->json([
            'success' => true,
            'data' => $examens,
        ]);
    }

    /**
     * Détail d'un examen pour le passer.
     * GET /etudiant/examens/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $examen = Examen::with(['formation:id,titre'])
            ->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        $statut = $this->statutEffectif($examen, $user->id);

        if ($statut !== 'disponible') {
            return response()->json([
                'success' => false,
                'message' => match ($statut) {
                    'a_venir' => 'Cet examen n\'est pas encore disponible.',
                    'ferme' => 'Cet examen est fermé.',
                    'termine' => 'Vous avez déjà passé cet examen.',
                    default => 'Cet examen n\'est pas disponible.',
                },
            ], 403);
        }

        // Charge les questions SANS la bonne réponse
        $questions = $examen->questions()
            ->select('id', 'examen_id', 'question', 'reponses', 'points', 'ordre')
            ->get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question' => $q->question,
                    'reponses' => $q->reponses,
                    'points' => $q->points,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $examen->id,
                'titre' => $examen->titre,
                'description' => $examen->description,
                'duree_minutes' => $examen->duree_minutes,
                'note_sur' => $examen->note_sur,
                'formation_nom' => $examen->formation->titre ?? null,
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * Soumettre les réponses d'un examen.
     * POST /etudiant/examens/{id}/soumettre
     */
    public function soumettre(Request $request, $id)
    {
        $user = $request->user();

        $request->validate([
            'reponses' => 'required|array',
            'reponses.*.question_id' => 'required|integer',
            'reponses.*.reponse_index' => 'required|integer|min:0',
            'temps_ecoule' => 'required|integer|min:0',
        ]);

        $examen = Examen::with('questions')->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        // Vérifier que l'étudiant n'a pas déjà soumis
        $dejaPasse = ReponseExamen::where('user_id', $user->id)
            ->where('examen_id', $examen->id)
            ->exists();

        if ($dejaPasse && $examen->tentatives_max === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà passé cet examen.',
            ], 422);
        }

        // Index des réponses données par question_id
        $reponsesDonnees = collect($request->reponses)
            ->keyBy('question_id')
            ->map(fn ($r) => (int) $r['reponse_index'])
            ->toArray();

        // Calcul du score
        $bonnesReponses = 0;
        $mauvaisesReponses = 0;
        $totalPoints = 0;
        $pointsObtenus = 0;

        foreach ($examen->questions as $question) {
            $points = $question->points ?: 1;
            $totalPoints += $points;

            $reponseDonnee = $reponsesDonnees[$question->id] ?? null;

            if ($reponseDonnee !== null && $reponseDonnee === $question->bonne_reponse) {
                $bonnesReponses++;
                $pointsObtenus += $points;
            } else {
                $mauvaisesReponses++;
            }
        }

        // Normalise le score sur note_sur
        $noteSur = $examen->note_sur ?: 20;
        $scoreFinal = $totalPoints > 0
            ? round(($pointsObtenus / $totalPoints) * $noteSur, 2)
            : 0;

        // Enregistrer la réponse
        $reponseExamen = ReponseExamen::create([
            'user_id' => $user->id,
            'examen_id' => $examen->id,
            'reponses' => $reponsesDonnees,
            'score' => $scoreFinal,
            'bonnes_reponses' => $bonnesReponses,
            'mauvaises_reponses' => $mauvaisesReponses,
            'temps_ecoule' => $request->temps_ecoule,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Examen soumis avec succès.',
            'data' => [
                'score' => $scoreFinal,
                'bonnes_reponses' => $bonnesReponses,
                'mauvaises_reponses' => $mauvaisesReponses,
            ],
        ]);
    }

    /**
     * Résultat détaillé d'un examen.
     * GET /etudiant/examens/{id}/resultat
     */
    public function resultat(Request $request, $id)
    {
        $user = $request->user();

        $examen = Examen::with(['questions', 'formation:id,titre'])->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        $reponse = ReponseExamen::where('user_id', $user->id)
            ->where('examen_id', $examen->id)
            ->latest()
            ->first();

        if (!$reponse) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas encore passé cet examen.',
            ], 404);
        }

        $reponsesDonnees = $reponse->reponses ?: [];

        // Détail question par question
        $questions = $examen->questions->map(function ($q) use ($reponsesDonnees) {
            $reponseDonnee = $reponsesDonnees[$q->id] ?? null;

            return [
                'id' => $q->id,
                'question' => $q->question,
                'reponses' => $q->reponses,
                'bonne_reponse' => $q->bonne_reponse,
                'reponse_donnee' => $reponseDonnee,
                'correcte' => $reponseDonnee === $q->bonne_reponse,
                'points' => $q->points,
                'explication' => $q->explication,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'titre' => $examen->titre,
                'formation_nom' => $examen->formation->titre ?? null,
                'score' => (float) $reponse->score,
                'note_sur' => $examen->note_sur,
                'total_questions' => $examen->questions->count(),
                'bonnes_reponses' => $reponse->bonnes_reponses,
                'mauvaises_reponses' => $reponse->mauvaises_reponses,
                'temps_ecoule' => $reponse->temps_ecoule,
                'rang' => null, // à calculer plus tard si besoin
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * Détermine le statut effectif d'un examen pour un étudiant donné.
     */
    protected function statutEffectif(Examen $examen, int $userId): string
    {
        $now = now();

        // Déjà passé ?
        $dejaPasse = ReponseExamen::where('user_id', $userId)
            ->where('examen_id', $examen->id)
            ->exists();

        if ($dejaPasse) {
            return 'termine';
        }

        // Pas encore ouvert ?
        if ($examen->date_debut && $now->lt($examen->date_debut)) {
            return 'a_venir';
        }

        // Fermé manuellement
        if ($examen->statut === 'ferme') {
            return 'ferme';
        }

        // Date de fin dépassée
        if ($examen->date_fin && $now->gt($examen->date_fin)) {
            return 'ferme';
        }

        return 'disponible';
    }
}