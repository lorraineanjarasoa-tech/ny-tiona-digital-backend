<?php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaiementController extends Controller
{
    /**
     * GET /api/etudiant/paiements
     * Liste tous les paiements de l'étudiant + formation active
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Formation active
            $inscription = Inscription::with(['formation', 'vague'])
                ->where('etudiant_id', $user->id)
                ->where('statut', 'valide')
                ->latest()
                ->first();

            $formation = null;
            if ($inscription && $inscription->formation) {
                $f = $inscription->formation;
                $dateDebut = $inscription->date_validation
                    ?? $inscription->date_inscription
                    ?? $inscription->created_at;

                $duree = (int) ($f->duree ?? 5);
                $unite = strtolower($f->unite_duree ?? 'mois');
                $dureeMois = $unite === 'annee' || $unite === 'année' || $unite === 'an'
                    ? $duree * 12
                    : ($unite === 'semaine' ? ceil($duree / 4) : $duree);

                $formation = [
                    'id' => $f->id,
                    'titre' => $f->titre,
                    'domaine' => $f->domaine,
                    'prix' => (float) $f->prix,
                    'montant_mensuel' => $dureeMois > 0
                        ? round((float) $f->prix / $dureeMois)
                        : (float) $f->prix,
                    'date_debut' => $dateDebut,
                    'date_fin' => Carbon::parse($dateDebut)->addMonths($dureeMois)->subDay(),
                    'duree_mois' => $dureeMois,
                    'vague' => $inscription->vague?->vague,
                ];
            }

            // Paiements existants
            $paiements = Paiement::where('etudiant_id', $user->id)
                ->orderBy('annee', 'desc')
                ->orderBy('mois', 'desc')
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'mois' => $p->mois,
                    'annee' => $p->annee,
                    'mois_key' => sprintf('%04d-%02d', $p->annee, $p->mois),
                    'mois_label' => $p->mois_label,
                    'montant' => (float) $p->montant,
                    'statut' => $p->statut,
                    'reference' => $p->reference,
                    'preuve_url' => $p->preuve_paiement_url,
                    'date_paiement' => $p->date_paiement,
                    'date_validation' => $p->date_validation,
                    'motif_rejet' => $p->motif_rejet,
                ]);

            return response()->json([
                'success' => true,
                'formation' => $formation,
                'paiements' => $paiements,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur index paiements', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des paiements.',
            ], 500);
        }
    }

    /**
     * POST /api/etudiant/paiements
     * Upload d'une preuve de paiement
     *
     * Body (multipart/form-data) :
     *  - mois (required, 1-12)
     *  - annee (required, ex: 2026)
     *  - montant (required)
     *  - reference (optional, référence bancaire)
     *  - preuve_paiement (required, file)
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'mois' => 'required|integer|min:1|max:12',
                'annee' => 'required|integer|min:2020|max:2100',
                'montant' => 'required|numeric|min:0',
                'reference' => 'nullable|string|max:255',
                'preuve_paiement' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            // Formation active
            $inscription = Inscription::where('etudiant_id', $user->id)
                ->where('statut', 'valide')
                ->latest()
                ->first();

            if (!$inscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune inscription active. Contactez l\'administration.',
                ], 404);
            }

            // Vérifier qu'un paiement n'existe pas déjà pour ce mois
            $exists = Paiement::where('etudiant_id', $user->id)
                ->where('mois', $validated['mois'])
                ->where('annee', $validated['annee'])
                ->whereIn('statut', ['en_attente', 'paye'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un paiement existe déjà pour ce mois.',
                ], 422);
            }

            // Sauvegarder le fichier
            $path = $request->file('preuve_paiement')
                ->store('preuves_paiement', 'public');

            // Créer le paiement
            $paiement = Paiement::create([
                'etudiant_id' => $user->id,
                'formation_id' => $inscription->formation_id,
                'mois' => $validated['mois'],
                'annee' => $validated['annee'],
                'montant' => $validated['montant'],
                'reference' => $validated['reference'] ?? null,
                'preuve_paiement' => $path,
                'statut' => 'en_attente',
                'date_paiement' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Preuve envoyée. En attente de validation.',
                'paiement' => [
                    'id' => $paiement->id,
                    'mois' => $paiement->mois,
                    'annee' => $paiement->annee,
                    'statut' => $paiement->statut,
                    'preuve_url' => $paiement->preuve_paiement_url,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur store paiement', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi.',
            ], 500);
        }
    }

    /**
     * GET /api/etudiant/paiements/statut
     * Renvoie le statut global : à jour, en retard, mois manquants
     */
    public function statut(Request $request)
    {
        try {
            $user = $request->user();

            $inscription = Inscription::where('etudiant_id', $user->id)
                ->where('statut', 'valide')
                ->latest()
                ->first();

            if (!$inscription) {
                return response()->json([
                    'success' => true,
                    'statut' => 'aucune_formation',
                    'message' => 'Aucune formation active.',
                ]);
            }

            $dateDebut = $inscription->date_validation
                ?? $inscription->date_inscription
                ?? $inscription->created_at;

            $start = Carbon::parse($dateDebut)->startOfMonth();
            $now = Carbon::now()->startOfMonth();
            $nbMois = $start->diffInMonths($now) + 1;

            // Paiements validés
            $paiementsValides = Paiement::where('etudiant_id', $user->id)
                ->where('statut', 'paye')
                ->get();

            $nbPaiementsValides = $paiementsValides->count();

            // Mois manquants
            $moisManquants = [];
            $cursor = $start->copy();

            for ($i = 0; $i < $nbMois; $i++) {
                $mois = (int) $cursor->month;
                $annee = (int) $cursor->year;

                $paye = $paiementsValides->contains(function ($p) use ($mois, $annee) {
                    return (int) $p['mois'] === $mois && (int) $p['annee'] === $annee;
                });

                if (!$paye) {
                    $moisManquants[] = [
                        'mois' => $mois,
                        'annee' => $annee,
                        'label' => Carbon::createFromDate($annee, $mois, 1)->translatedFormat('F Y'),
                    ];
                }

                $cursor->addMonth();
            }

            $enRetard = count($moisManquants) > 0;

            return response()->json([
                'success' => true,
                'statut' => $enRetard ? 'en_retard' : 'a_jour',
                'en_retard' => $enRetard,
                'mois_total' => $nbMois,
                'mois_payes' => $nbPaiementsValides,
                'mois_manquants' => $moisManquants,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur statut paiements', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul.',
            ], 500);
        }
    }
}