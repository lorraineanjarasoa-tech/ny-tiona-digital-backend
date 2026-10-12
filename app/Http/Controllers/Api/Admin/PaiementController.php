<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    /**
     * Liste de tous les paiements.
     * GET /admin/paiements
     */
    public function index(Request $request)
    {
        try {
            $query = Paiement::with(['etudiant.profile', 'formation'])
                ->orderByDesc('created_at');

            // Filtre par statut
            if ($request->filled('statut') && $request->statut !== 'all') {
                $query->where('statut', $request->statut);
            }

            // Filtre par mois
            if ($request->filled('mois')) {
                $query->where('mois', $request->mois);
            }

            // Filtre par annee
            if ($request->filled('annee')) {
                $query->where('annee', $request->annee);
            }

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'ILIKE', "%{$search}%")
                      ->orWhereHas('etudiant', function ($sub) use ($search) {
                          $sub->where('email', 'ILIKE', "%{$search}%")
                              ->orWhereHas('profile', function ($p) use ($search) {
                                  $p->where('nom_complet', 'ILIKE', "%{$search}%");
                              });
                      });
                });
            }

            $paiements = $query->get()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'mois' => $p->mois,
                    'annee' => $p->annee,
                    'montant' => (float) $p->montant,
                    'statut' => $p->statut,
                    'reference' => $p->reference,
                    'preuve_paiement' => $p->preuve_paiement,
                    'preuve_url' => $p->preuve_paiement_url, // ✅ Utilise l'accessor
                    'motif_rejet' => $p->motif_rejet,
                    'date_paiement' => $p->date_paiement,
                    'date_validation' => $p->date_validation,
                    'created_at' => $p->created_at,
                    'etudiant' => [
                        'id' => $p->etudiant?->id,
                        'nom_complet' => $p->etudiant?->profile?->nom_complet
                            ?? $p->etudiant?->name
                            ?? 'Inconnu',
                        'email' => $p->etudiant?->email,
                        'photo_profil_url' => $p->etudiant?->profile?->photo_profil
                            ? asset('storage/' . $p->etudiant->profile->photo_profil)
                            : null,
                    ],
                    'formation' => [
                        'id' => $p->formation?->id,
                        'titre' => $p->formation?->titre ?? 'Formation',
                    ],
                ];
            });

            // Stats
            $stats = [
                'total' => Paiement::count(),
                'en_attente' => Paiement::where('statut', 'en_attente')->count(),
                'valides' => Paiement::where('statut', 'paye')->count(),
                'rejetes' => Paiement::where('statut', 'annule')->count(),
                'montant_total' => (float) Paiement::where('statut', 'paye')->sum('montant'),
            ];

            return response()->json([
                'success' => true,
                'data' => $paiements,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('PaiementController@index : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Impossible de charger les paiements.',
                'error' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Valider un paiement.
     * PUT /admin/paiements/{id}/valider
     */
    public function valider(Request $request, $id)
    {
        try {
            $paiement = Paiement::findOrFail($id);
            $paiement->statut = 'paye';
            $paiement->date_validation = now();
            $paiement->valide_par = $request->user()->id;
            $paiement->motif_rejet = null;
            $paiement->save();

            return response()->json([
                'success' => true,
                'message' => 'Paiement valide.',
                'data' => $paiement,
            ]);
        } catch (\Exception $e) {
            Log::error('PaiementController@valider : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rejeter un paiement.
     * PUT /admin/paiements/{id}/rejeter
     */
    public function rejeter(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'motif_rejet' => 'required|string|max:500',
            ]);

            $paiement = Paiement::findOrFail($id);
            $paiement->statut = 'annule';
            $paiement->motif_rejet = $validated['motif_rejet'];
            $paiement->date_validation = now();
            $paiement->valide_par = $request->user()->id;
            $paiement->save();

            return response()->json([
                'success' => true,
                'message' => 'Paiement rejete.',
                'data' => $paiement,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Motif requis.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('PaiementController@rejeter : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Voir la preuve.
     * GET /admin/paiements/{id}/preuve
     */
    public function voirPreuve($id)
    {
        try {
            $paiement = Paiement::findOrFail($id);

            if (!$paiement->preuve_paiement) {
                abort(404);
            }

            $path = storage_path('app/public/' . $paiement->preuve_paiement);
            if (!file_exists($path)) {
                abort(404);
            }

            return response()->file($path);
        } catch (\Exception $e) {
            abort(404);
        }
    }
}