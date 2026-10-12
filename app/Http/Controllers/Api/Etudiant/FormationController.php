<?php
// app/Http/Controllers/Api/Etudiant/FormationController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Progression;

class FormationController extends Controller
{
    /**
     * Récupérer les formations de l'étudiant
     */
    public function mesFormations(Request $request)
{
    try {
        $user = $request->user();

        $inscriptions = Inscription::where('etudiant_id', $user->id)
            ->with(['formation', 'vague'])
            ->latest()
            ->get();

        // Charger TOUTES les progressions en une seule requête
        $allProgressions = Progression::where('user_id', $user->id)
            ->get()
            ->keyBy('cours_id');

        $formations = $inscriptions->map(function ($inscription) use ($allProgressions) {
            $formation = $inscription->formation;
            $vague = $inscription->vague;

            // Cours de cette formation
            $cours = $formation
                ? \App\Models\Cours::where('formation_id', $formation->id)->get()
                : collect();

            $nbCoursTotal = $cours->count();
            $nbCoursTermines = 0;
            $nbCoursEnCours = 0;

            foreach ($cours as $c) {
                $p = $allProgressions->get($c->id);
                if ($p) {
                    if ($p->statut === 'termine' || $p->pourcentage >= 100) {
                        $nbCoursTermines++;
                    } elseif ($p->statut === 'en_cours') {
                        $nbCoursEnCours++;
                    }
                }
            }

            $progression = $nbCoursTotal > 0
                ? round(($nbCoursTermines / $nbCoursTotal) * 100)
                : 0;

            return [
                'id' => $inscription->id,
                'formation_id' => $formation?->id,
                'titre' => $formation?->titre ?? 'Formation',
                'description' => $formation?->description,
                'domaine' => $formation?->domaine,
                'image' => $formation?->image,
                'prix' => $formation?->prix,
                'niveau' => $formation?->niveau,
                'duree' => $formation?->duree,
                'unite_duree' => $formation?->unite_duree,

                'statut' => $inscription->statut,
                'date_inscription' => $inscription->date_inscription ?? $inscription->created_at,
                'date_validation' => $inscription->date_validation,
                'motif_rejet' => $inscription->motif_rejet,

                'vague' => $vague?->vague,
                'vague_id' => $vague?->id,
                'date_debut' => $vague?->date_debut,
                'date_fin' => $vague?->date_fin,

                // ✅ Progression RÉELLE
                'progression' => $progression,
                'nb_cours_total' => $nbCoursTotal,
                'nb_cours_termines' => $nbCoursTermines,
                'nb_cours_en_cours' => $nbCoursEnCours,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formations,
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur mes formations: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des formations.',
            'data' => [],
        ], 500);
    }
}
}