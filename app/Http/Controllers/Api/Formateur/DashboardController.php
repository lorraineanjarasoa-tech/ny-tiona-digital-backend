<?php

namespace App\Http\Controllers\Api\Formateur;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Inscription;
use App\Models\Message;
use App\Models\Partage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Statistiques du formateur connecté.
     * GET /formateur/dashboard/stats
     */
    public function stats(Request $request)
{
    try {
        $user = $request->user();

        // 1. Nombre de cours créés par ce formateur
        $coursCount = \App\Models\Cours::where('formateur_id', $user->id)->count();

        // 2. Nombre d'étudiants uniques (via les formations où le formateur a des cours)
        $formationIds = \App\Models\Cours::where('formateur_id', $user->id)
            ->whereNotNull('formation_id')
            ->pluck('formation_id')
            ->unique()
            ->values();

        $etudiantsCount = 0;
        if ($formationIds->isNotEmpty()) {
            $etudiantsCount = \App\Models\Inscription::whereIn('formation_id', $formationIds)
                ->distinct('etudiant_id')
                ->count('etudiant_id');
        }

        // 3. Publications (pas de table dédiée pour le formateur → 0)
        $publicationsCount = 0;

        // 4. Messages non lus
        $messagesCount = \DB::table('messages')
            ->where('destinataire_id', $user->id)
            ->where('lu', false)
            ->count();

        return response()->json([
            'success' => true,
            'cours' => $coursCount,
            'etudiants' => $etudiantsCount,
            'publications' => $publicationsCount,
            'messages' => $messagesCount,
        ]);
    } catch (\Exception $e) {
        \Log::error('DashboardController@stats : ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'cours' => 0,
            'etudiants' => 0,
            'publications' => 0,
            'messages' => 0,
            'message' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Activités récentes du formateur.
     * GET /formateur/dashboard/activites
     */
    public function activites(Request $request)
    {
        try {
            $user = $request->user();
            $activites = [];

            // 1. Dernières inscriptions aux cours du formateur
            $inscriptions = Inscription::whereHas('cours', function ($q) use ($user) {
                $q->where('formateur_id', $user->id);
            })
                ->with(['user.profile', 'cours'])
                ->latest()
                ->limit(5)
                ->get();

            foreach ($inscriptions as $ins) {
                $nom = $ins->user?->profile?->nom_complet
                    ?? $ins->user?->email
                    ?? 'Un étudiant';

                $activites[] = [
                    'id' => 'inscription-' . $ins->id,
                    'type' => 'inscription',
                    'message' => "{$nom} s'est inscrit à \"{$ins->cours?->titre}\"",
                    'url' => '/formateur/etudiants',
                    'created_at' => $ins->created_at,
                ];
            }

            // 2. Derniers cours créés/mis à jour
            $cours = Cours::where('formateur_id', $user->id)
                ->latest('updated_at')
                ->limit(3)
                ->get();

            foreach ($cours as $c) {
                $activites[] = [
                    'id' => 'cours-' . $c->id,
                    'type' => 'cours',
                    'message' => "Cours \"{$c->titre}\" mis à jour",
                    'url' => "/formateur/mes-cours/{$c->id}",
                    'created_at' => $c->updated_at,
                ];
            }

            // 3. Derniers messages reçus
            $messages = Message::where('destinataire_id', $user->id)
                ->with('expediteur.profile')
                ->latest()
                ->limit(3)
                ->get();

            foreach ($messages as $m) {
                $nom = $m->expediteur?->profile?->nom_complet
                    ?? $m->expediteur?->email
                    ?? 'Quelqu\'un';

                $activites[] = [
                    'id' => 'message-' . $m->id,
                    'type' => 'message',
                    'message' => "Nouveau message de {$nom}",
                    'url' => '/formateur/chat',
                    'created_at' => $m->created_at,
                ];
            }

            // Tri par date décroissante
            usort($activites, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

            return response()->json([
                'success' => true,
                'data' => array_slice($activites, 0, 10),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur activités formateur : ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
    }
}