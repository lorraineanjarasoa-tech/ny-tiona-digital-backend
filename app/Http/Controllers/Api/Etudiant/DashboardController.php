<?php
// app/Http/Controllers/Api/Etudiant/DashboardController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Inscription;
use App\Models\Message;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Progression;

class DashboardController extends Controller
{
    /**
     * Statistiques du dashboard étudiant
     */
    public function stats(Request $request)
    {
        try {
        $user = $request->user();

        // 1. Formations validées
        $inscriptions = Inscription::where('etudiant_id', $user->id)
            ->where('statut', 'valide')
            ->with('formation')
            ->get();

        $totalFormations = $inscriptions->count();

        // 2. Cours accessibles + cours terminés
        $formationIds = $inscriptions->pluck('formation_id')->filter()->unique()->toArray();
        $totalCours = count($formationIds) > 0
            ? \App\Models\Cours::whereIn('formation_id', $formationIds)->count()
            : 0;

        $coursTermines = Progression::where('user_id', $user->id)
            ->where('statut', 'termine')
            ->count();

        // 3. Paiements en attente
        $paiementsEnAttente = 0;
        try {
            $paiementsEnAttente = \App\Models\Paiement::where('user_id', $user->id)
                ->where('statut', 'en_attente')
                ->count();
        } catch (\Exception $e) {}

        // 4. Messages non lus
        $messagesNonLus = 0;
        try {
            $messagesNonLus = \App\Models\Message::where('destinataire_id', $user->id)
                ->whereNull('read_at')
                ->count();
        } catch (\Exception $e) {}

        // 5. Progression globale
        $progressionGlobale = $totalCours > 0
            ? round(($coursTermines / $totalCours) * 100)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'formations' => $totalFormations,
                'cours' => $totalCours,
                'cours_termines' => $coursTermines,
                'progression_globale' => $progressionGlobale,
                'paiements_en_attente' => $paiementsEnAttente,
                'messages_non_lus' => $messagesNonLus,
            ],
            // Compatibilité
            'total_formations' => $totalFormations,
            'total_cours' => $totalCours,
            'cours_termines' => $coursTermines,
            'paiements_en_attente' => $paiementsEnAttente,
            'messages_non_lus' => $messagesNonLus,
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur stats étudiant: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'data' => [
                'formations' => 0,
                'cours' => 0,
                'cours_termines' => 0,
                'progression_globale' => 0,
                'paiements_en_attente' => 0,
                'messages_non_lus' => 0,
                ],
            ]);
        }
    }
}