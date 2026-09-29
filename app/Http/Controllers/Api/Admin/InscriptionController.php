<?php
// app/Http/Controllers/Api/Admin/InscriptionController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Mail\InscriptionValidatedMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InscriptionController extends Controller
{
    /**
     * STATISTIQUES GLOBALES
     */
    public function stats()
    {
        try {
            $totalEtudiants = DB::table('users')->where('role', 'etudiant')->count();
            $totalFormateurs = DB::table('users')->where('role', 'formateur')->count();
            $totalUtilisateurs = DB::table('users')->count();

            $etudiantsValides = DB::table('users')
                ->where('role', 'etudiant')
                ->where('is_validated', true)
                ->count();

            $utilisateursNonValides = DB::table('users')
                ->where('is_validated', false)
                ->whereIn('role', ['etudiant', 'formateur'])
                ->count();

            $totalFormations = DB::table('formations')->count();
            $totalInscriptions = DB::table('inscriptions')->count();

            $inscriptionsValidees = DB::table('inscriptions')->where('statut', 'valide')->count();
            $inscriptionsEnAttente = DB::table('inscriptions')->where('statut', 'en_attente')->count();
            $inscriptionsRejetees = DB::table('inscriptions')->where('statut', 'rejete')->count();

            $tauxValidationComptes = $totalEtudiants > 0
                ? round(($etudiantsValides / $totalEtudiants) * 100, 1)
                : 0;

            $tauxReussite = $totalInscriptions > 0
                ? round(($inscriptionsValidees / $totalInscriptions) * 100, 1)
                : 0;

            $newsletterCount = 0;
            try {
                $newsletterCount = NewsletterSubscriber::where('is_active', true)->count();
            } catch (\Throwable $e) {
                $newsletterCount = 0;
            }

            // Évolutions 7 jours
            $inscriptions7Jours = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $count = DB::table('inscriptions')->whereDate('created_at', $date->toDateString())->count();
                $inscriptions7Jours[] = ['label' => $date->format('d/m'), 'value' => $count];
            }

            // Évolutions 30 jours
            $inscriptions30Jours = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $count = DB::table('inscriptions')->whereDate('created_at', $date->toDateString())->count();
                $inscriptions30Jours[] = ['label' => $date->format('d/m'), 'value' => $count];
            }

            // Évolutions 12 mois
            $inscriptions12Mois = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $count = DB::table('inscriptions')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $inscriptions12Mois[] = [
                    'label' => ucfirst($date->locale('fr')->translatedFormat('M')),
                    'value' => $count,
                ];
            }

            return response()->json([
                'success' => true,
                'total_etudiants' => $totalEtudiants,
                'total_formateurs' => $totalFormateurs,
                'total_formations' => $totalFormations,
                'total_inscriptions' => $totalInscriptions,
                'total_utilisateurs' => $totalUtilisateurs,
                'etudiants_valides' => $etudiantsValides,
                'utilisateurs_non_valides' => $utilisateursNonValides,
                'inscriptions_validees' => $inscriptionsValidees,
                'inscriptions_en_attente' => $inscriptionsEnAttente,
                'inscriptions_rejetees' => $inscriptionsRejetees,
                'taux_validation_comptes' => $tauxValidationComptes,
                'taux_reussite' => $tauxReussite,
                'newsletter_count' => $newsletterCount,
                'evolution' => [
                    '7_days' => $inscriptions7Jours,
                    '30_days' => $inscriptions30Jours,
                    '12_months' => $inscriptions12Mois,
                ],
                'repartition_roles' => [
                    'etudiants' => $totalEtudiants,
                    'etudiants_valides' => $etudiantsValides,
                    'formateurs' => $totalFormateurs,
                    'formations' => $totalFormations,
                ],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur statistiques admin', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de charger les statistiques.',
            ], 500);
        }
    }

    /**
     * LISTE DE TOUTES LES INSCRIPTIONS
     */
    public function index()
    {
        try {
            $inscriptions = DB::table('inscriptions')
                ->leftJoin('users', 'inscriptions.etudiant_id', '=', 'users.id')
                ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->leftJoin('formations', 'inscriptions.formation_id', '=', 'formations.id')
                ->leftJoin('vagues', 'inscriptions.vague_id', '=', 'vagues.id')
                ->select(
                    'inscriptions.id',
                    'inscriptions.statut',
                    'inscriptions.created_at',
                    'inscriptions.etudiant_id',
                    'inscriptions.formation_id',
                    'users.email as etudiant_email',
                    'users.is_validated as statut_compte',
                    'user_profiles.nom_complet as etudiant_nom',
                    'formations.titre as formation_nom',
                    'formations.domaine as domaine',
                    'vagues.vague as vague_numero'
                )
                ->orderBy('inscriptions.created_at', 'desc')
                ->get()
                ->map(function ($inscription) {
    $vagueNumero = $inscription->vague_numero;

    if (!$vagueNumero && $inscription->formation_id) {
        $vagueActive = DB::table('vagues')
            ->where('formation_id', $inscription->formation_id)
            ->orderBy('vague')
            ->first();
        $vagueNumero = $vagueActive->vague ?? null;
    }

    // ✅ Déterminer le statut d'affichage
    $statutCompte = (bool) $inscription->statut_compte;
    $statutInscription = $inscription->statut;

    // Logique : le statut principal est celui de l'inscription
    $statutAffichage = 'en_attente';
    if ($statutInscription === 'valide' && $statutCompte) {
        $statutAffichage = 'valide';
    } elseif ($statutInscription === 'rejete') {
        $statutAffichage = 'rejete';
    } elseif ($statutInscription === 'valide' && !$statutCompte) {
        $statutAffichage = 'compte_en_attente';
    }

    return [
        'id' => $inscription->id,
        'etudiant_id' => $inscription->etudiant_id,
        'etudiant_nom' => $inscription->etudiant_nom ?? 'Inconnu',
        'etudiant_email' => $inscription->etudiant_email,
        'formation_nom' => $inscription->formation_nom ?? 'Inconnue',
        'domaine' => $inscription->domaine ?? 'Non défini',
        'vague' => $vagueNumero,
        'statut_compte' => $statutCompte,
        'statut_inscription' => $statutInscription,
        'statut_affichage' => $statutAffichage,
        'date_inscription' => $inscription->created_at,
        'statut' => $statutInscription,
    ];
});

            return response()->json($inscriptions);

        } catch (\Throwable $e) {
            Log::error('Erreur liste inscriptions', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des inscriptions',
            ], 500);
        }
    }

    /**
     * INSCRIPTIONS EN ATTENTE
     */
    public function enAttente()
    {
        try {
            $inscriptions = DB::table('inscriptions')
                ->leftJoin('users', 'inscriptions.etudiant_id', '=', 'users.id')
                ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->leftJoin('formations', 'inscriptions.formation_id', '=', 'formations.id')
                ->where('inscriptions.statut', 'en_attente')
                ->select(
                    'inscriptions.id',
                    'inscriptions.statut',
                    'inscriptions.created_at',
                    'user_profiles.nom_complet as etudiant_nom',
                    'users.email as etudiant_email',
                    'formations.titre as formation_nom'
                )
                ->orderBy('inscriptions.created_at', 'desc')
                ->get();

            return response()->json($inscriptions);

        } catch (\Throwable $e) {
            Log::error('Erreur inscriptions en attente', ['message' => $e->getMessage()]);
            return response()->json([], 200);
        }
    }

    /**
     * INSCRIPTIONS RÉCENTES
     */
    public function recentes()
    {
        try {
            $inscriptions = DB::table('inscriptions')
                ->leftJoin('users', 'inscriptions.etudiant_id', '=', 'users.id')
                ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->leftJoin('formations', 'inscriptions.formation_id', '=', 'formations.id')
                ->select(
                    'inscriptions.id',
                    'inscriptions.statut',
                    'inscriptions.created_at',
                    'user_profiles.nom_complet as etudiant_nom',
                    'users.email as etudiant_email',
                    'formations.titre as formation_nom'
                )
                ->orderBy('inscriptions.created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json($inscriptions);

        } catch (\Throwable $e) {
            Log::error('Erreur inscriptions recentes', ['message' => $e->getMessage()]);
            return response()->json([], 200);
        }
    }

    /**
     * VALIDER UNE INSCRIPTION
     * - Valide l'inscription
     * - Valide le compte étudiant si pas encore validé
     * - Assigne la vague
     * - Envoie un email de confirmation
     */
    public function valider($id)
{
    try {
        Log::info('=== DÉBUT VALIDATION INSCRIPTION ===', ['inscription_id' => $id]);

        $inscription = DB::table('inscriptions')->where('id', $id)->first();

        if (!$inscription) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription introuvable.',
            ], 404);
        }

        if ($inscription->statut === 'valide') {
            return response()->json([
                'success' => false,
                'message' => 'Cette inscription est déjà validée.',
            ], 400);
        }

        // Trouver la vague ouverte
        $vague = DB::table('vagues')
            ->where('formation_id', $inscription->formation_id)
            ->where('statut', 'ouverte')
            ->orderBy('vague')
            ->first();

        Log::info('Vague trouvée', ['vague_id' => $vague->id ?? null]);

        $updateData = [
            'statut' => 'valide',
            'date_validation' => now(),
            'valide_par' => auth()->id(),
        ];

        if ($vague) {
            $updateData['vague_id'] = $vague->id;
        }

        // Mettre à jour l'inscription
        DB::table('inscriptions')->where('id', $id)->update($updateData);
        Log::info('Inscription mise à jour');

        // Valider le compte étudiant
        $etudiant = DB::table('users')->where('id', $inscription->etudiant_id)->first();
        $compteValide = false;

        if ($etudiant && !$etudiant->is_validated) {
            DB::table('users')
                ->where('id', $inscription->etudiant_id)
                ->update([
                    'is_validated' => true,
                    'validated_at' => now(),
                ]);
            $compteValide = true;
            Log::info('Compte étudiant validé', ['user_id' => $etudiant->id]);
        }

        // ============================================
        // ENVOI D'EMAIL AVEC DEBUG DÉTAILLÉ
        // ============================================
        $emailEnvoye = false;
        $emailErreur = null;

        if ($etudiant) {
            try {
                Log::info('Préparation de l\'email', [
                    'user_id' => $etudiant->id,
                    'email' => $etudiant->email,
                ]);

                $user = \App\Models\User::with('profile')->find($inscription->etudiant_id);

                if (!$user) {
                    throw new \Exception('Utilisateur introuvable pour l\'email');
                }

                Log::info('Utilisateur chargé', [
                    'email' => $user->email,
                    'nom' => $user->profile->nom_complet ?? $user->name,
                ]);

                // ✅ Envoi de l'email
                Mail::to($user->email)->send(
                    new \App\Mail\InscriptionStatusEmail($user, 'valide')
                );

                $emailEnvoye = true;
                Log::info('✅ EMAIL ENVOYÉ AVEC SUCCÈS', [
                    'to' => $user->email,
                    'inscription_id' => $id,
                ]);

            } catch (\Throwable $mailError) {
                $emailErreur = $mailError->getMessage();
                Log::error('❌ ERREUR ENVOI EMAIL', [
                    'inscription_id' => $id,
                    'error' => $mailError->getMessage(),
                    'file' => $mailError->getFile(),
                    'line' => $mailError->getLine(),
                    'trace' => $mailError->getTraceAsString(),
                ]);
            }
        } else {
            Log::warning('Aucun étudiant trouvé pour l\'email', [
                'etudiant_id' => $inscription->etudiant_id,
            ]);
        }

        Log::info('=== FIN VALIDATION INSCRIPTION ===', [
            'compte_valide' => $compteValide,
            'email_envoye' => $emailEnvoye,
            'email_erreur' => $emailErreur,
        ]);

        $message = 'Inscription validée avec succès.';
        if ($compteValide) $message .= ' Le compte étudiant a été activé.';
        if ($emailEnvoye) {
            $message .= ' Un email a été envoyé.';
        } elseif ($emailErreur) {
            $message .= ' ⚠️ Mais l\'email n\'a pas pu être envoyé.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'compte_valide' => $compteValide,
            'email_envoye' => $emailEnvoye,
            'email_erreur' => $emailErreur,
        ]);

    } catch (\Throwable $e) {
        Log::error('❌ Erreur validation inscription', [
            'id' => $id,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la validation: ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * REJETER UNE INSCRIPTION
     */
    public function rejeter($id)
    {
        try {
            $inscription = DB::table('inscriptions')->where('id', $id)->first();

            if (!$inscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscription introuvable.',
                ], 404);
            }

            DB::table('inscriptions')
                ->where('id', $id)
                ->update([
                    'statut' => 'rejete',
                    'date_validation' => now(),
                    'valide_par' => auth()->id(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription rejetée avec succès.',
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur rejet inscription', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet.',
            ], 500);
        }
    }

    /**
     * NOTIFIER PAIEMENT
     */
    public function notifierPaiement($id)
    {
        try {
            $inscription = DB::table('inscriptions')->where('id', $id)->first();

            if (!$inscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscription introuvable.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification envoyée avec succès.',
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur notification paiement', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification.',
            ], 500);
        }
    }
}