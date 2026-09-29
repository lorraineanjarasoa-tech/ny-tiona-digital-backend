<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Formation;
use App\Models\Inscription;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index()
    {
        $totalEtudiants = User::where('role', 'etudiant')->count();
        $totalFormations = Formation::where('statut', 'ouverte')->count();
        $tauxReussite = $this->calculerTauxReussite();
        $satisfaction = $this->calculerSatisfaction();

        return response()->json([
            'taux_reussite' => $tauxReussite,
            'total_etudiants' => $totalEtudiants,
            'total_formations' => $totalFormations,
            'satisfaction' => $satisfaction
        ]);
    }
    /**
     * Récupérer la prochaine vague disponible
     */
    public function nextVague()
    {
        try {
            // Récupérer la vague la plus proche (date de début >= aujourd'hui)
            $vague = \DB::table('vagues')
                ->where('date_debut', '>=', now()->toDateString())
                ->where('statut', 'ouverte')
                ->orderBy('date_debut', 'asc')
                ->first();

            // Si aucune vague à venir, prendre la plus récente
            if (!$vague) {
                $vague = \DB::table('vagues')
                    ->orderBy('date_debut', 'desc')
                    ->first();
            }

            if (!$vague) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune vague disponible pour le moment.'
                ], 404);
            }

            // Récupérer les informations de la formation liée
            $formation = \DB::table('formations')
                ->where('id', $vague->formation_id)
                ->first();

            // Compter le nombre d'inscrits (sécurisé)
            $inscrits = 0;
            try {
                $inscrits = \DB::table('inscriptions')
                    ->where('vague_id', $vague->id)
                    ->count();
            } catch (\Exception $e) {
                $inscrits = 0;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $vague->id,
                    'num_vague' => $vague->vague,
                    'date_debut' => $vague->date_debut,
                    'date_fin' => $vague->date_fin,
                    'capacite' => $vague->capacite,
                    'inscrits' => $inscrits,
                    'places_restantes' => max(0, $vague->capacite - $inscrits),
                    'formation' => $formation ? [
                        'titre' => $formation->titre ?? null,
                        'domaine' => $formation->domaine ?? null,
                        'prix' => $formation->prix ?? 0,
                        'duree' => $formation->duree ?? null,
                        'unite_duree' => $formation->unite_duree ?? null,
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur nextVague: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la prochaine vague.'
            ], 500);
        }
    }
    
    private function calculerTauxReussite()
    {
        $totalInscriptions = Inscription::count();
        $inscriptionsValides = Inscription::where('statut', 'valide')->count();
        
        if ($totalInscriptions === 0) {
            return 94; // Valeur par défaut
        }
        
        return round(($inscriptionsValides / $totalInscriptions) * 100);
    }

    private function calculerSatisfaction()
    {
        // À implémenter avec de vraies données d'enquête
        return 100;
    }
}
