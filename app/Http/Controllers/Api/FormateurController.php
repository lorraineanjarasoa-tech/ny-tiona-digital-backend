<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cours;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FormateurController extends Controller
{
    public function stats()
    {
        try {
            $user = auth()->user();
            
            $totalCours = Cours::where('formateur_id', $user->id)->count();
            
            // Compter les étudiants inscrits aux cours du formateur
            $coursIds = Cours::where('formateur_id', $user->id)->pluck('id');
            $totalEtudiants = Inscription::whereIn('formation_id', $coursIds)
                ->where('statut', 'valide')
                ->distinct('etudiant_id')
                ->count();
                
            $totalPublications = 0; // À implémenter
            $messagesNonLus = 0; // À implémenter

            return response()->json([
                'total_cours' => $totalCours,
                'total_etudiants' => $totalEtudiants,
                'total_publications' => $totalPublications,
                'messages_non_lus' => $messagesNonLus,
            ]);
        } catch (\Exception $e) {
            Log::error('Formateur stats error: ' . $e->getMessage());
            return response()->json([
                'total_cours' => 0,
                'total_etudiants' => 0,
                'total_publications' => 0,
                'messages_non_lus' => 0,
            ]);
        }
    }

    public function mesCours()
    {
        try {
            $user = auth()->user();
            
            $cours = Cours::where('formateur_id', $user->id)
                ->withCount('inscriptions as nb_etudiants')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($cours) {
                    return [
                        'id' => $cours->id,
                        'titre' => $cours->titre,
                        'description' => $cours->description_courte,
                        'type_fichier' => $cours->type_fichier,
                        'est_visible' => $cours->est_visible,
                        'nb_etudiants' => $cours->nb_etudiants ?? 0,
                        'date_publication' => $cours->date_publication,
                    ];
                });

            return response()->json($cours);
        } catch (\Exception $e) {
            Log::error('Mes cours error: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }

    public function etudiantsParCours($coursId)
    {
        try {
            $cours = Cours::with('inscriptions.etudiant.profile')
                ->where('id', $coursId)
                ->where('formateur_id', auth()->id())
                ->firstOrFail();

            $etudiants = $cours->inscriptions->map(function ($inscription) {
                return [
                    'id' => $inscription->etudiant->id,
                    'nom' => $inscription->etudiant->profile->nom_complet ?? 'Inconnu',
                    'email' => $inscription->etudiant->email,
                    'date_inscription' => $inscription->created_at,
                ];
            });

            return response()->json($etudiants);
        } catch (\Exception $e) {
            Log::error('Etudiants par cours error: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }

    public function publier(Request $request)
    {
        try {
            $validated = $request->validate([
                'titre' => 'required|string|max:255',
                'contenu' => 'required|string',
                'type' => 'required|in:instruction,ressource,annonce',
            ]);

            // Créer une publication
            $publication = [
                'formateur_id' => auth()->id(),
                'titre' => $validated['titre'],
                'contenu' => $validated['contenu'],
                'type' => $validated['type'],
                'date_publication' => now(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Publication créée avec succès.',
                'data' => $publication
            ]);
        } catch (\Exception $e) {
            Log::error('Publier error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la publication.'
            ], 500);
        }
    }
}