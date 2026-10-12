<?php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Partage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicProfileController extends Controller
{
    /**
     * Voir le profil public d'un utilisateur
     * GET /api/utilisateur/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $currentUser = auth()->user();

            // Charger l'utilisateur + profil
            $user = User::with('profile')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable.',
                ], 404);
            }

            // Interdire de voir le profil si non validé (sauf si c'est nous)
            if (!$user->is_validated && $user->id !== $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce profil n\'est pas encore disponible.',
                ], 403);
            }

            // Récupérer les formations de l'utilisateur
            $formations = [];

            if ($user->role === 'etudiant') {
                $formations = Inscription::with('formation')
                    ->where('etudiant_id', $user->id)
                    ->where('statut', 'valide')
                    ->get()
                    ->map(fn($i) => [
                        'id' => $i->formation?->id,
                        'titre' => $i->formation?->titre,
                        'domaine' => $i->formation?->domaine,
                    ])
                    ->filter(fn($f) => $f['id'] !== null)
                    ->values()
                    ->toArray();
            }

            if ($user->role === 'formateur') {
                try {
                    $cours = \App\Models\Cours::where('formateur_id', $user->id)->get();
                    $formations = $cours->map(fn($c) => [
                        'id' => $c->id,
                        'titre' => $c->titre,
                        'domaine' => $c->domaine ?? null,
                    ])->toArray();
                } catch (\Exception $e) {
                    $formations = [];
                }
            }

            // Statistiques publiques (partages)
            $totalPartages = 0;
            $totalLikes = 0;
            try {
                $totalPartages = Partage::where('user_id', $user->id)->count();
                $totalLikes = Partage::where('user_id', $user->id)->sum('likes_count') ?? 0;
            } catch (\Exception $e) {
                // Table partages pas accessible
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_validated' => $user->is_validated,
                    'created_at' => $user->created_at,
                    'last_login_at' => $user->last_login_at,
                    'profile' => [
                        'nom_complet' => $user->profile?->nom_complet,
                        'contact' => $user->profile?->contact,
                        'adresse' => $user->profile?->adresse,
                        'bio' => $user->profile?->bio,
                        'photo_profil' => $user->profile?->photo_profil,
                        'photo_profil_url' => $user->profile?->photo_profil_url,
                        'specialite' => $user->profile?->specialite,
                        'date_naissance' => $user->profile?->date_naissance,
                        'sexe' => $user->profile?->sexe,
                        'derniere_etude' => $user->profile?->derniere_etude,
                    ],
                    'formations' => $formations,
                    'stats' => [
                        'total_partages' => $totalPartages,
                        'total_likes' => $totalLikes,
                    ],
                    'is_me' => $user->id === $currentUser->id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur public profile', [
                'message' => $e->getMessage(),
                'user_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du profil.',
            ], 500);
        }
    }
}