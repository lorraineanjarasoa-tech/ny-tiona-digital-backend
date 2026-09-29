<?php
// app/Http/Controllers/Api/Etudiant/DashboardController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Statistiques du dashboard étudiant
     */
    public function stats(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer les formations de l'étudiant (inscriptions validées)
            $inscriptions = Inscription::where('etudiant_id', $user->id)
                ->where('statut', 'valide')
                ->with('formation')
                ->get();
            
            $totalFormations = $inscriptions->count();
            
            // Cours terminés (progression = 100%)
            $coursTermines = $inscriptions->filter(function($inscription) {
                return ($inscription->progression ?? 0) >= 100;
            })->count();
            
            // Total des meetings à venir
            $totalMeetings = Meeting::where('statut', 'planifie')
                ->orWhere('statut', 'en_cours')
                ->count();
            
            // Messages non lus (à implémenter avec la table messages)
            $messagesNonLus = 0;
            
            return response()->json([
                'total_formations' => $totalFormations,
                'total_meetings' => $totalMeetings,
                'messages_non_lus' => $messagesNonLus,
                'cours_termines' => $coursTermines,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur stats étudiant: ' . $e->getMessage());
            return response()->json([
                'total_formations' => 0,
                'total_meetings' => 0,
                'messages_non_lus' => 0,
                'cours_termines' => 0,
            ]);
        }
    }
}