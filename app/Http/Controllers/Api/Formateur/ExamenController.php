<?php

namespace App\Http\Controllers\Api\Formateur;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\Question;
use App\Models\ReponseExamen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExamenController extends Controller
{
    /**
     * Liste des examens créés par le formateur connecté.
     * GET /formateur/examens
     */
    public function index(Request $request)
    {
        $examens = Examen::where('created_by', $request->user()->id)
            ->with(['formation:id,titre'])
            ->withCount('questions')
            ->withCount('reponses')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'titre' => $e->titre,
                    'description' => $e->description,
                    'formation_id' => $e->formation_id,
                    'formation_nom' => $e->formation->titre ?? null,
                    'duree_minutes' => $e->duree_minutes,
                    'note_sur' => $e->note_sur,
                    'statut' => $e->statut,
                    'date_debut' => $e->date_debut,
                    'date_fin' => $e->date_fin,
                    'tentatives_max' => $e->tentatives_max,
                    'nb_questions' => $e->questions_count,
                    'nb_participants' => $e->reponses_count,
                    'created_at' => $e->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $examens,
        ]);
    }

    /**
     * Détail d'un examen avec ses questions (formateur).
     * GET /formateur/examens/{id}
     */
    public function show(Request $request, $id)
    {
        $examen = Examen::where('created_by', $request->user()->id)
            ->with(['formation:id,titre', 'questions'])
            ->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $examen,
        ]);
    }

    /**
     * Créer un examen.
     * POST /formateur/examens
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'formation_id' => 'nullable|exists:formations,id',
            'duree_minutes' => 'required|integer|min:5|max:300',
            'note_sur' => 'required|integer|min:1|max:100',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'tentatives_max' => 'nullable|integer|min:1|max:10',
        ]);

        $examen = Examen::create([
            ...$data,
            'created_by' => $request->user()->id,
            'statut' => 'brouillon',
            'tentatives_max' => $data['tentatives_max'] ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Examen créé en brouillon.',
            'data' => $examen,
        ], 201);
    }

    /**
     * Modifier un examen.
     * PUT /formateur/examens/{id}
     */
    public function update(Request $request, $id)
    {
        $examen = Examen::where('created_by', $request->user()->id)->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'formation_id' => 'nullable|exists:formations,id',
            'duree_minutes' => 'required|integer|min:5|max:300',
            'note_sur' => 'required|integer|min:1|max:100',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'tentatives_max' => 'nullable|integer|min:1|max:10',
        ]);

        $examen->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Examen modifié.',
            'data' => $examen->fresh(),
        ]);
    }

    /**
     * Supprimer un examen.
     * DELETE /formateur/examens/{id}
     */
    public function destroy(Request $request, $id)
    {
        $examen = Examen::where('created_by', $request->user()->id)->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        $examen->delete();

        return response()->json([
            'success' => true,
            'message' => 'Examen supprimé.',
        ]);
    }

    /**
     * Publier un examen (le rendre visible aux étudiants).
     * POST /formateur/examens/{id}/publier
     */
    public function publier(Request $request, $id)
    {
        $examen = Examen::where('created_by', $request->user()->id)->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        if ($examen->questions()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de publier un examen sans questions.',
            ], 422);
        }

        $examen->update(['statut' => 'publie']);

        return response()->json([
            'success' => true,
            'message' => 'Examen publié.',
        ]);
    }

    /**
     * Fermer un examen.
     * POST /formateur/examens/{id}/fermer
     */
    public function fermer(Request $request, $id)
    {
        $examen = Examen::where('created_by', $request->user()->id)->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        $examen->update(['statut' => 'ferme']);

        return response()->json([
            'success' => true,
            'message' => 'Examen fermé.',
        ]);
    }

    /**
     * Résultats des étudiants pour cet examen.
     * GET /formateur/examens/{id}/resultats
     */
    public function resultats(Request $request, $id)
    {
        $examen = Examen::where('created_by', $request->user()->id)->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        $resultats = ReponseExamen::where('examen_id', $examen->id)
            ->with(['user:id,email', 'user.profile'])   // ✅ on charge la relation profile
            ->orderBy('score', 'desc')
            ->get()
            ->map(function ($r) {
                $profile = $r->user->profile ?? null;

                return [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'nom' => $profile->nom_complet
                        ?? $r->user->email,
                    'email' => $r->user->email,
                    'score' => (float) $r->score,
                    'bonnes_reponses' => $r->bonnes_reponses,
                    'mauvaises_reponses' => $r->mauvaises_reponses,
                    'temps_ecoule' => $r->temps_ecoule,
                    'date' => $r->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'examen' => [
                    'id' => $examen->id,
                    'titre' => $examen->titre,
                    'note_sur' => $examen->note_sur,
                ],
                'resultats' => $resultats,
                'moyenne' => $resultats->avg('score'),
                'nb_participants' => $resultats->count(),
            ],
        ]);
    }

    /**
     * Ajouter une question à un examen.
     * POST /formateur/examens/{id}/questions
     */
    public function ajouterQuestion(Request $request, $id)
    {
        $examen = Examen::where('created_by', $request->user()->id)->find($id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Examen introuvable.',
            ], 404);
        }

        $data = $request->validate([
            'question' => 'required|string',
            'reponses' => 'required|array|min:2|max:6',
            'reponses.*' => 'required|string|max:500',
            'bonne_reponse' => 'required|integer|min:0',
            'points' => 'nullable|integer|min:1|max:10',
            'explication' => 'nullable|string',
        ]);

        // Vérifie que bonne_reponse est dans les bornes
        if ($data['bonne_reponse'] >= count($data['reponses'])) {
            return response()->json([
                'success' => false,
                'message' => 'L\'index de la bonne réponse est invalide.',
            ], 422);
        }

        $ordre = $examen->questions()->max('ordre') + 1;

        $question = Question::create([
            'examen_id' => $examen->id,
            'question' => $data['question'],
            'reponses' => $data['reponses'],
            'bonne_reponse' => $data['bonne_reponse'],
            'points' => $data['points'] ?? 1,
            'explication' => $data['explication'] ?? null,
            'ordre' => $ordre,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Question ajoutée.',
            'data' => $question,
        ], 201);
    }

    /**
     * Modifier une question.
     * PUT /formateur/examens/questions/{questionId}
     */
    public function updateQuestion(Request $request, $questionId)
    {
        $question = Question::find($questionId);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Question introuvable.',
            ], 404);
        }

        // Vérifier que le formateur est bien le créateur de l'examen
        $examen = Examen::where('created_by', $request->user()->id)
            ->find($question->examen_id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.',
            ], 403);
        }

        $data = $request->validate([
            'question' => 'required|string',
            'reponses' => 'required|array|min:2|max:6',
            'reponses.*' => 'required|string|max:500',
            'bonne_reponse' => 'required|integer|min:0',
            'points' => 'nullable|integer|min:1|max:10',
            'explication' => 'nullable|string',
        ]);

        if ($data['bonne_reponse'] >= count($data['reponses'])) {
            return response()->json([
                'success' => false,
                'message' => 'L\'index de la bonne réponse est invalide.',
            ], 422);
        }

        $question->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Question modifiée.',
            'data' => $question->fresh(),
        ]);
    }

    /**
     * Supprimer une question.
     * DELETE /formateur/examens/questions/{questionId}
     */
    public function deleteQuestion(Request $request, $questionId)
    {
        $question = Question::find($questionId);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Question introuvable.',
            ], 404);
        }

        $examen = Examen::where('created_by', $request->user()->id)
            ->find($question->examen_id);

        if (!$examen) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.',
            ], 403);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question supprimée.',
        ]);
    }
}