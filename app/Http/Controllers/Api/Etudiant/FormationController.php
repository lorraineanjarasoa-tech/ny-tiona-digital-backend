<?php
// app/Http/Controllers/Api/Etudiant/FormationController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
                ->with('formation')
                ->get();
            
            $formations = $inscriptions->map(function($inscription) {
                return [
                    'id' => $inscription->formation->id,
                    'nom' => $inscription->formation->nom,
                    'titre' => $inscription->formation->titre,
                    'description' => $inscription->formation->description,
                    'statut' => $inscription->statut,
                    'progression' => $inscription->progression ?? 0,
                    'date_debut' => $inscription->created_at,
                    'duree' => $inscription->formation->duree,
                    'unite_duree' => $inscription->formation->unite_duree,
                    'niveau' => $inscription->formation->niveau,
                    'vague' => $inscription->formation->vague,
                    'date_fin' => $inscription->formation->date_fin,
                ];
            });
            
            return response()->json($formations);
            
        } catch (\Exception $e) {
            Log::error('Erreur mes formations: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}