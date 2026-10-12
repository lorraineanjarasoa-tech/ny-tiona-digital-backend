<?php

namespace App\Http\Controllers\Api\Formateur;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Inscription;
use App\Models\Message;
use App\Models\Partage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Statistiques du formateur connecte.
     * GET /formateur/dashboard/stats
     */
    public function stats(Request $request)
{
    try {
        $user = $request->user();

        // 1. Nombre de cours crees par ce formateur
        $coursCount = Cours::where('formateur_id', $user->id)->count();

        // 2. Nombre d'etudiants uniques (via les formations ou le formateur a des cours)
        $formationIds = Cours::where('formateur_id', $user->id)
            ->whereNotNull('formation_id')
            ->pluck('formation_id')
            ->unique()
            ->values();

        $etudiantsCount = 0;
        if ($formationIds->isNotEmpty()) {
            $etudiantsCount = Inscription::whereIn('formation_id', $formationIds)
                ->where('statut', 'valide')
                ->distinct('etudiant_id')
                ->count('etudiant_id');
        }

        // 3. Nombre de reunions du formateur (a venir + en cours + passees)
        $meetingsCount = \App\Models\Meeting::where('created_by', $user->id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'cours' => $coursCount,
                'etudiants' => $etudiantsCount,
                'meetings' => $meetingsCount,
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('DashboardController@stats : ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'data' => [
                'cours' => 0,
                'etudiants' => 0,
                'meetings' => 0,
            ],
            'message' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Activites recentes du formateur.
     * GET /formateur/dashboard/activites
     */
    public function activites(Request $request)
    {
        try {
            $user = $request->user();
            $activites = [];

            // 1. Dernieres inscriptions aux formations du formateur
            $formationIds = Cours::where('formateur_id', $user->id)
                ->whereNotNull('formation_id')
                ->pluck('formation_id')
                ->unique()
                ->values();

            if ($formationIds->isNotEmpty()) {
                $inscriptions = Inscription::whereIn('formation_id', $formationIds)
                    ->with(['user.profile', 'formation'])
                    ->latest()
                    ->limit(5)
                    ->get();

                foreach ($inscriptions as $ins) {
                    $nom = $ins->user?->profile?->nom_complet
                        ?? $ins->user?->email
                        ?? 'Un etudiant';

                    $titreFormation = $ins->formation?->titre ?? $ins->formation?->nom ?? 'une formation';

                    $activites[] = [
                        'id' => 'inscription-' . $ins->id,
                        'type' => 'inscription',
                        'message' => "{$nom} s'est inscrit a \"{$titreFormation}\"",
                        'url' => '/formateur/etudiants',
                        'created_at' => $ins->created_at,
                    ];
                }
            }

            // 2. Derniers cours crees/mis a jour
            $cours = Cours::where('formateur_id', $user->id)
                ->latest('updated_at')
                ->limit(3)
                ->get();

            foreach ($cours as $c) {
                $activites[] = [
                    'id' => 'cours-' . $c->id,
                    'type' => 'cours',
                    'message' => "Cours \"{$c->titre}\" mis a jour",
                    'url' => "/formateur/mes-cours/{$c->id}",
                    'created_at' => $c->updated_at,
                ];
            }

            // 3. Dernieres publications
            $publications = Partage::where('user_id', $user->id)
                ->latest()
                ->limit(3)
                ->get();

            foreach ($publications as $p) {
                $activites[] = [
                    'id' => 'publication-' . $p->id,
                    'type' => 'publication',
                    'message' => "Publication \"{$p->titre}\" creee",
                    'url' => '/formateur/publications',
                    'created_at' => $p->created_at,
                ];
            }

            // 4. Derniers messages recus
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

            // Tri par date decroissante
            usort($activites, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

            return response()->json([
                'success' => true,
                'data' => array_slice($activites, 0, 10),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur activites formateur : ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
    }
}