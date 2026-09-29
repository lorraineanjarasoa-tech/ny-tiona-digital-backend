<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Formation;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * ============================================================
     * DASHBOARD ADMIN
     * ============================================================
     */

    /**
     * Statistiques principales du dashboard
     */
    public function stats()
    {
        try {
            // Total étudiants
            $totalEtudiants = User::where('role', 'etudiant')->count();

            // Étudiants validés
            $etudiantsValides = User::where('role', 'etudiant')
                ->where('is_validated', true)
                ->count();

            // Total formateurs
            $totalFormateurs = User::where('role', 'formateur')->count();

            // Formations actives
            $totalFormations = Formation::where('is_active', true)->count();

            // Si tu veux toutes les formations au lieu des actives :
            // $totalFormations = Formation::count();

            return response()->json([
                'success' => true,

                'total_etudiants' => $totalEtudiants,

                'etudiants_valides' => $etudiantsValides,

                'total_formateurs' => $totalFormateurs,

                'total_formations' => $totalFormations,
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Erreur dashboard admin - statistiques', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de charger les statistiques.',

                'total_etudiants' => 0,
                'etudiants_valides' => 0,
                'total_formateurs' => 0,
                'total_formations' => 0,
            ], 500);
        }
    }


    /**
     * ============================================================
     * INSCRIPTIONS RÉCENTES
     * ============================================================
     */

    public function recentes()
    {
        try {

            $inscriptions = Inscription::with([
                'etudiant.profile',
                'formation'
            ])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function ($inscription) {

                    return [
                        'id' => $inscription->id,

                        'etudiant_nom' =>
                            optional(optional($inscription->etudiant)->profile)
                                ->nom_complet
                            ?? optional($inscription->etudiant)->name
                            ?? 'Inconnu',

                        'etudiant_email' =>
                            optional($inscription->etudiant)->email
                            ?? '—',

                        'formation_nom' =>
                            optional($inscription->formation)->titre
                            ?? optional($inscription->formation)->nom
                            ?? 'Formation',

                        'date' => $inscription->created_at,

                        'statut' => $inscription->statut,
                    ];
                })
                ->values();

            return response()->json(
                $inscriptions,
                200
            );

        } catch (\Throwable $e) {

            Log::error('Erreur dashboard admin - inscriptions récentes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([], 200);
        }
    }


    /**
     * ============================================================
     * INSCRIPTIONS EN ATTENTE
     * ============================================================
     */

    public function enAttente()
    {
        try {

            $inscriptions = Inscription::with([
                'etudiant.profile',
                'formation'
            ])
                ->where('statut', 'en_attente')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($inscription) {

                    return [
                        'id' => $inscription->id,

                        'etudiant_nom' =>
                            optional(optional($inscription->etudiant)->profile)
                                ->nom_complet
                            ?? optional($inscription->etudiant)->name
                            ?? 'Inconnu',

                        'etudiant_email' =>
                            optional($inscription->etudiant)->email
                            ?? '—',

                        'formation_nom' =>
                            optional($inscription->formation)->titre
                            ?? optional($inscription->formation)->nom
                            ?? 'Formation',

                        'date_inscription' => $inscription->created_at,

                        'statut' => $inscription->statut,
                    ];
                })
                ->values();

            return response()->json(
                $inscriptions,
                200
            );

        } catch (\Throwable $e) {

            Log::error('Erreur dashboard admin - inscriptions en attente', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([], 200);
        }
    }


    /**
     * ============================================================
     * VALIDER UNE INSCRIPTION
     * ============================================================
     */

    public function validerInscription(Request $request, $id)
    {
        try {

            $inscription = Inscription::find($id);

            if (!$inscription) {

                return response()->json([
                    'success' => false,
                    'message' => 'Inscription introuvable.'
                ], 404);
            }

            if ($inscription->statut === 'valide') {

                return response()->json([
                    'success' => false,
                    'message' => 'Cette inscription est déjà validée.'
                ], 400);
            }

            $data = [
                'statut' => 'valide',
            ];

            /*
             * Ces colonnes sont ajoutées seulement si elles existent.
             * Cela évite une erreur SQL si ton ancienne migration
             * ne les possède pas.
             */
            if (Schema::hasColumn('inscriptions', 'date_validation')) {
                $data['date_validation'] = now();
            }

            if (
                Schema::hasColumn('inscriptions', 'valide_par')
                && auth()->check()
            ) {
                $data['valide_par'] = auth()->id();
            }

            DB::table('inscriptions')
                ->where('id', $id)
                ->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Inscription validée avec succès.'
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Erreur validation inscription dashboard', [
                'inscription_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null
            ], 500);
        }
    }


    /**
     * ============================================================
     * REJETER UNE INSCRIPTION
     * ============================================================
     */

    public function rejeterInscription(Request $request, $id)
    {
        try {

            $inscription = Inscription::find($id);

            if (!$inscription) {

                return response()->json([
                    'success' => false,
                    'message' => 'Inscription introuvable.'
                ], 404);
            }

            if ($inscription->statut === 'rejete') {

                return response()->json([
                    'success' => false,
                    'message' => 'Cette inscription est déjà rejetée.'
                ], 400);
            }

            $data = [
                'statut' => 'rejete',
            ];

            if (Schema::hasColumn('inscriptions', 'date_validation')) {
                $data['date_validation'] = now();
            }

            if (
                Schema::hasColumn('inscriptions', 'valide_par')
                && auth()->check()
            ) {
                $data['valide_par'] = auth()->id();
            }

            DB::table('inscriptions')
                ->where('id', $id)
                ->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Inscription rejetée avec succès.'
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Erreur rejet inscription dashboard', [
                'inscription_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null
            ], 500);
        }
    }
}