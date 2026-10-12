<?php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Progression;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema; 

class CoursController extends Controller
{
    /**
     * Voir le contenu d'une formation (tous ses cours)
     * GET /api/etudiant/cours/{formationId}
     */
    public function show($id)
    {
        try {
            $user = auth()->user();

            // 1. Vérifier l'inscription à cette formation
            $inscription = Inscription::where('etudiant_id', $user->id)
                ->where('formation_id', $id)
                ->first();

            // Cas 403 : pas inscrit
            if (!$inscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas inscrit à cette formation.',
                ], 403);
            }

            // Cas 403 : inscription non validée
            if ($inscription->statut !== 'valide') {
                return response()->json([
                    'success' => false,
                    'message' => $inscription->statut === 'rejete'
                        ? 'Votre inscription a été rejetée.'
                        : 'Votre inscription est en attente de validation.',
                    'statut_inscription' => $inscription->statut,
                ], 403);
            }

            // 2. Vérifier les paiements en retard
            $paymentBlocked = $this->isPaymentBlocked($user, $inscription);

            if ($paymentBlocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'payment_required',
                    'access_blocked' => true,
                    'reason' => 'Écolage en retard',
                ], 403);
            }

            // 3. Charger la formation
            $formation = Formation::with(['cours', 'vagues'])->find($id);

            if (!$formation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formation non trouvée.',
                    'no_cours' => true,
                ], 404);
            }

            // 4. Charger les cours de la formation
            $coursList = Cours::where('formation_id', $id)
                ->orderBy('id')
                ->get();

            // Cas 2 : aucun cours disponible
            if ($coursList->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'no_cours' => true,
                    'message' => 'Aucun cours disponible pour le moment.',
                    'data' => [
                        'formation_id' => $formation->id,
                        'formation_titre' => $formation->titre,
                        'empty' => true,
                    ],
                ]);
            }

            // 5. Charger les progressions de l'étudiant pour ces cours
            $progressions = Progression::where('user_id', $user->id)
                ->whereIn('cours_id', $coursList->pluck('id'))
                ->get()
                ->keyBy('cours_id');

            // 6. Calculer la progression globale
            $nbCoursTotal = $coursList->count();
            $nbCoursTermines = 0;

            foreach ($coursList as $c) {
                $p = $progressions->get($c->id);
                if ($p && ($p->statut === 'termine' || $p->pourcentage >= 100)) {
                    $nbCoursTermines++;
                }
            }

            $progression = $nbCoursTotal > 0
                ? round(($nbCoursTermines / $nbCoursTotal) * 100)
                : 0;

            // 7. Trouver le premier cours non terminé (pour "next cours")
            $nextCours = null;
            foreach ($coursList as $c) {
                $p = $progressions->get($c->id);
                if (!$p || $p->statut !== 'termine') {
                    $nextCours = [
                        'id' => $c->id,
                        'titre' => $c->titre,
                        'description' => $c->description,
                    ];
                    break;
                }
            }

            // 8. Construire la liste des cours avec statut
            $coursAvecStatut = $coursList->map(function ($c) use ($progressions) {
                $p = $progressions->get($c->id);

                return [
                    'id' => $c->id,
                    'titre' => $c->titre,
                    'description' => $c->description,
                    'video_url' => $c->video_url ?? null,
                    'duree' => $c->duree ?? null,
                    'unite_duree' => $c->unite_duree ?? null,
                    'formateur_nom' => $c->formateur_nom ?? null,
                    'statut' => $p?->statut ?? 'non_commence',
                    'pourcentage' => $p?->pourcentage ?? 0,
                ];
            });

            // 9. Retourner la structure attendue par le frontend
            // Le frontend attend un objet avec `titre`, `description`, `progression`, etc.
            // On prend le PREMIER cours non terminé comme "cours courant"
            $courant = null;
            foreach ($coursAvecStatut as $c) {
                if ($c['statut'] !== 'termine') {
                    $courant = $c;
                    break;
                }
            }

            // Si tous les cours sont terminés → prendre le dernier
            if (!$courant) {
                $courant = $coursAvecStatut->last();
            }

            return response()->json([
                'success' => true,
                'data' => array_merge($courant ?? [], [
                    'id' => $courant['id'] ?? null,
                    'formation_id' => $formation->id,
                    'formation_titre' => $formation->titre,
                    'formation_description' => $formation->description,
                    'domaine' => $formation->domaine,

                    // Progression globale de la formation
                    'progression' => $progression,
                    'nb_cours_total' => $nbCoursTotal,
                    'nb_cours_termines' => $nbCoursTermines,

                    // Liste complète des cours
                    'cours' => $coursAvecStatut,

                    // Prochain cours
                    'next_cours' => $nextCours,
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur voir cours', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'formation_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du cours.',
            ], 500);
        }
    }

    /**
     * Vérifie si l'accès aux cours est bloqué à cause d'un paiement en retard
     *
     * Logique : l'étudiant doit avoir réglé son écolage du mois en cours.
     * L'écolage est dû entre le 1er et le 5 du mois.
     * Passé le 5, si aucun paiement n'a été enregistré pour ce mois → bloqué.
     */
    private function isPaymentBlocked($user, $inscription): bool
{
    try {
        if (!Schema::hasTable('paiements')) return false;

        $dateDebut = $inscription->date_validation
            ?? $inscription->date_inscription
            ?? $inscription->created_at;

        if (!$dateDebut) return false;

        $start = Carbon::parse($dateDebut)->startOfMonth();
        $now = Carbon::now()->startOfMonth();
        $nbMois = $start->diffInMonths($now) + 1;

        $nbPaiementsValides = Paiement::where('etudiant_id', $user->id)
            ->where('statut', 'paye')   // ⚠️ 'paye', pas 'valide'
            ->count();

        return $nbPaiementsValides < $nbMois;

    } catch (\Exception $e) {
        Log::warning('Erreur isPaymentBlocked', ['message' => $e->getMessage()]);
        return false;
    }
}
}