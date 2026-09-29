<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema; 
use App\Mail\AccountActivatedMail;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    /**
     * ============================================================
     * UTILISATEURS
     * ============================================================
     */

        public function listeUtilisateurs(Request $request)
    {
        try {
            $query = User::with('profile');

            /*
             * Recherche
             */
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhereHas('profile', function ($profile) use ($search) {
                            $profile->where('nom_complet', 'LIKE', "%{$search}%");
                        });
                });
            }

            /*
             * Filtre rôle
             */
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            /*
             * Filtre validation
             */
            if ($request->filled('is_validated')) {
                $query->where('is_validated', filter_var($request->is_validated, FILTER_VALIDATE_BOOLEAN));
            }

            // Récupérer tous les utilisateurs sans pagination pour simplifier le frontend
            $utilisateurs = $query->latest()->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_validated' => $user->is_validated,
                    'created_at' => $user->created_at,
                    'profile' => $user->profile ? [
                        'nom_complet' => $user->profile->nom_complet ?? null
                    ] : null
                ];
            });

            // Renvoyer un tableau simple directement dans "data"
            return response()->json([
                'success' => true,
                'data' => $utilisateurs
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste utilisateurs admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des utilisateurs.',
            ], 500);
        }
    }


    /**
     * Afficher un utilisateur
     */
    public function showUtilisateur($id)
    {
        try {

            $user = User::with('profile')
                ->find($id);

            if (!$user) {

                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur détail utilisateur admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de l’utilisateur.',
            ], 500);
        }
    }


    /**
     * Utilisateurs non validés
     */
    public function utilisateursNonValides()
    {
        try {

            $utilisateurs = User::with('profile')
                ->whereIn('role', [
                    'etudiant',
                    'formateur'
                ])
                ->where('is_validated', false)
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $utilisateurs,
                'count' => $utilisateurs->count(),
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur utilisateurs non validés', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des utilisateurs non validés.',
            ], 500);
        }
    }


public function validerUtilisateur(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);

        // Empêche de revalider un compte déjà validé
        if ($user->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est déjà validé.',
            ], 409);
        }

        // Marquer comme validé
        $user->is_validated = true;
        $user->validated_at = now();
        $user->is_active = true;
        $user->save();

        // Envoyer l'email de confirmation
        $emailSent = false;
        try {
            Mail::to($user->email)->send(
                new AccountActivatedMail($user, $user->role)
            );
            $emailSent = true;
        } catch (\Exception $mailError) {
            Log::error('[AdminController@validerUtilisateur] Email non envoyé', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $mailError->getMessage(),
            ]);
            // On ne bloque PAS la validation si l'email échoue
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Compte validé avec succès.' . ($emailSent ? ' Un email a été envoyé à l\'utilisateur.' : ''),
            'email_envoye' => $emailSent,
            'data'         => $user,
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Utilisateur introuvable.',
        ], 404);
    } catch (\Exception $e) {
        Log::error('[AdminController@validerUtilisateur] Erreur', [
            'user_id' => $id,
            'error'   => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la validation : ' . $e->getMessage(),
        ], 500);
    }
}


    /**
     * Supprimer un utilisateur
     */
    public function supprimerUtilisateur($id)
    {
        try {

            $user = User::find($id);

            if (!$user) {

                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable.',
                ], 404);
            }

            /*
             * Empêcher la suppression d'un admin
             */
            if ($user->role === 'admin') {

                return response()->json([
                    'success' => false,
                    'message' => 'La suppression d’un administrateur est interdite.',
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès.',
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur suppression utilisateur', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * VAGUES
     * ============================================================
     */

           // ============================================================
    // VAGUES
    // ============================================================

    /**
     * Fermer automatiquement les vagues
     */
    private function autoFermerVagues()
    {
        try {
            $today = now()->toDateString();

            $vaguesOuvertes = DB::table('vagues')
                ->where('statut', 'ouverte')
                ->get();

            foreach ($vaguesOuvertes as $vague) {
                $raison = null;

                // 1. Date de fin dépassée
                if ($vague->date_fin && $vague->date_fin < $today) {
                    $raison = 'Date de fin dépassée';
                }

                // 2. Capacité atteinte
                $nbInscrits = DB::table('inscriptions')
                    ->where('vague_id', $vague->id)
                    ->where('statut', 'valide')
                    ->count();

                $capacite = (int) ($vague->capacite ?? 0);

                if ($nbInscrits >= $capacite && $capacite > 0) {
                    $raison = 'Capacité atteinte';
                }

                if ($raison) {
                    DB::table('vagues')
                        ->where('id', $vague->id)
                        ->update([
                            'statut' => 'terminee',
                            'updated_at' => now(),
                        ]);

                    Log::info('Vague terminée automatiquement', [
                        'vague_id' => $vague->id,
                        'vague' => $vague->vague,
                        'raison' => $raison,
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur auto-fermeture vagues', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Liste des vagues avec calcul des places restantes
     */
    public function listeVagues()
    {
        try {
            // ÉTAPE 1 : Fermer automatiquement les vagues concernées
            $this->autoFermerVagues();

            // ÉTAPE 2 : Récupérer les vagues
            $vagues = DB::table('vagues')->orderByDesc('id')->get();

            $formations = DB::table('formations')->get()->keyBy('id');

            $today = now()->toDateString();

            $vagues->transform(function ($vague) use ($formations, $today) {
                $formation = $formations->get($vague->formation_id);

                $vague->formation_titre = $formation->titre ?? null;
                $vague->formation_nom = $formation->nom ?? null;

                // Compter les inscriptions VALIDÉES par vague_id
                $nbInscrits = DB::table('inscriptions')
                    ->where('vague_id', $vague->id)
                    ->where('statut', 'valide')
                    ->count();

                // Fallback : si aucune inscription liée à la vague_id,
                // compter les inscriptions validées de la même formation sans vague_id
                if ($nbInscrits === 0) {
                    $nbInscrits = DB::table('inscriptions')
                        ->where('formation_id', $vague->formation_id)
                        ->whereNull('vague_id')
                        ->where('statut', 'valide')
                        ->count();
                }

                $vague->nb_inscrits = $nbInscrits;
                $vague->nb_etudiants = $nbInscrits;

                $capacite = (int) ($vague->capacite ?? 0);
                $placesRestantes = max(0, $capacite - $nbInscrits);

                $vague->places_restantes = $placesRestantes;
                $vague->est_complet = $placesRestantes <= 0;

                // Vérifier expiration
                $vague->est_expiree = $vague->date_fin && $vague->date_fin < $today;

                // Raison de fermeture
                $raisonFermeture = null;
                if (in_array($vague->statut, ['fermee', 'terminee'])) {
                    if ($vague->est_expiree) {
                        $raisonFermeture = 'Date de fin dépassée';
                    } elseif ($vague->est_complet) {
                        $raisonFermeture = 'Capacité atteinte';
                    } elseif ($vague->statut === 'fermee') {
                        $raisonFermeture = 'Fermée manuellement';
                    } else {
                        $raisonFermeture = 'Terminée';
                    }
                }
                $vague->raison_fermeture = $raisonFermeture;

                return $vague;
            });

            return response()->json([
                'success' => true,
                'data' => $vagues,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur liste vagues', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des vagues.',
            ], 500);
        }
    }

    /**
     * Créer une vague
     */
    public function creerVague(Request $request)
    {
        try {
            $validated = $request->validate([
                'vague' => 'required|integer|min:1',
                'formation_id' => 'required|exists:formations,id',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after:date_debut',
                'capacite' => 'required|integer|min:1',
                'statut' => 'required|in:ouverte,fermee,terminee',
            ]);

            $exists = DB::table('vagues')
                ->where('formation_id', $validated['formation_id'])
                ->where('vague', $validated['vague'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette vague existe déjà pour cette formation.',
                ], 422);
            }

            $id = DB::table('vagues')->insertGetId([
                'vague' => $validated['vague'],
                'formation_id' => $validated['formation_id'],
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'capacite' => $validated['capacite'],
                'statut' => $validated['statut'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $vague = DB::table('vagues')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Vague créée avec succès.',
                'data' => $vague,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur création vague', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la vague.',
            ], 500);
        }
    }

    /**
     * Modifier une vague
     */
    public function modifierVague(Request $request, $id)
    {
        try {
            $vague = DB::table('vagues')->where('id', $id)->first();

            if (!$vague) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vague introuvable.',
                ], 404);
            }

            $validated = $request->validate([
                'vague' => 'sometimes|integer|min:1',
                'formation_id' => 'sometimes|exists:formations,id',
                'date_debut' => 'sometimes|date',
                'date_fin' => 'sometimes|date|after:date_debut',
                'capacite' => 'sometimes|integer|min:1',
                'statut' => 'sometimes|in:ouverte,fermee,terminee',
            ]);

            $validated['updated_at'] = now();

            DB::table('vagues')->where('id', $id)->update($validated);

            $vague = DB::table('vagues')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Vague modifiée avec succès.',
                'data' => $vague,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur modification vague', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la vague.',
            ], 500);
        }
    }

    /**
     * Supprimer une vague
     */
    public function supprimerVague($id)
    {
        try {
            $vague = DB::table('vagues')->where('id', $id)->first();

            if (!$vague) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vague introuvable.',
                ], 404);
            }

            $nbInscriptions = DB::table('inscriptions')
                ->where('vague_id', $id)
                ->count();

            if ($nbInscriptions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de supprimer : $nbInscriptions inscription(s) liée(s).",
                ], 400);
            }

            DB::table('vagues')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vague supprimée avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur suppression vague', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la vague.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * FORMATIONS
     * ============================================================
     */

        public function listeFormations()
{
    try {

        $formations = DB::table('formations')
            ->select([
                'id',
                'titre',
                'domaine',
                'description',
                'image',
                'prix',
                'duree',
                'unite_duree',
                'niveau',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $formations,
        ]);

    } catch (\Throwable $e) {

        Log::error('Erreur liste formations admin', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des formations.',
            'error' => config('app.debug')
                ? $e->getMessage()
                : null,
        ], 500);
    }
}


    /**
     * Créer une formation
     */
    public function creerFormation(Request $request)
{
    try {

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:1000',
            'prix' => 'nullable|numeric|min:0',
            'duree' => 'nullable|string|max:255',
            'unite_duree' => 'nullable|string|max:50',
            'niveau' => 'nullable|string|max:100',
            'domaine' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $id = DB::table('formations')->insertGetId([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'image' => $validated['image'] ?? null,
            'prix' => $validated['prix'] ?? 0,
            'duree' => $validated['duree'] ?? null,
            'unite_duree' => $validated['unite_duree'] ?? null,
            'niveau' => $validated['niveau'] ?? null,
            'domaine' => $validated['domaine'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $formation = DB::table('formations')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Formation créée avec succès.',
            'data' => $formation,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {

        Log::error('Erreur création formation admin', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création de la formation.',
            'error' => config('app.debug')
                ? $e->getMessage()
                : null,
        ], 500);
    }
}


    /**
     * Modifier une formation
     */
    public function modifierFormation(Request $request, $id)
{
    try {

        $formation = DB::table('formations')
            ->where('id', $id)
            ->first();

        if (!$formation) {

            return response()->json([
                'success' => false,
                'message' => 'Formation introuvable.',
            ], 404);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:1000',
            'prix' => 'nullable|numeric|min:0',
            'duree' => 'nullable|string|max:255',
            'unite_duree' => 'nullable|string|max:50',
            'niveau' => 'nullable|string|max:100',
            'domaine' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['updated_at'] = now();

        DB::table('formations')
            ->where('id', $id)
            ->update($validated);

        $formation = DB::table('formations')
            ->where('id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Formation modifiée avec succès.',
            'data' => $formation,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {

        Log::error('Erreur modification formation', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la modification de la formation.',
            'error' => config('app.debug')
                ? $e->getMessage()
                : null,
        ], 500);
    }
}


    /**
     * Supprimer une formation
     */
    public function supprimerFormation($id)
    {
        try {

            $formation = DB::table('formations')
                ->where('id', $id)
                ->first();

            if (!$formation) {

                return response()->json([
                    'success' => false,
                    'message' => 'Formation introuvable.',
                ], 404);
            }

            DB::table('formations')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Formation supprimée avec succès.',
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur suppression formation admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la formation.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * PROFIL ADMIN
     * ============================================================
     */
    /**
 * Afficher le profil administrateur connecté
 */
public function getProfil(Request $request): JsonResponse
{
    try {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        // Charger le profil associé
        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Profil administrateur récupéré avec succès.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_validated' => $user->is_validated,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,

                'profile' => $user->profile ? [
                    'id' => $user->profile->id,
                    'nom_complet' => $user->profile->nom_complet,
                    'adresse' => $user->profile->adresse,
                    'telephone' => $user->profile->telephone ?? null,
                    'contact' => $user->profile->contact ?? null,
                    'bio' => $user->profile->bio ?? null,
                    'photo_profil' => $user->profile->photo_profil ?? null,
                ] : null,
            ],
        ]);

    } catch (\Throwable $e) {

        Log::error('Erreur récupération profil admin', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement du profil administrateur.',
            'error' => config('app.debug')
                ? $e->getMessage()
                : null,
        ], 500);
    }
}

    public function updateProfil(Request $request)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié.',
                ], 401);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
                'nom_complet' => 'sometimes|required|string|max:255',
                'adresse' => 'nullable|string|max:255',
            ]);

            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            if (isset($validated['email'])) {
                $user->email = strtolower($validated['email']);
            }

            $user->save();

            if ($user->profile) {

                $profileData = [];

                if (isset($validated['nom_complet'])) {
                    $profileData['nom_complet'] = $validated['nom_complet'];
                }

                if (array_key_exists('adresse', $validated)) {
                    $profileData['adresse'] = $validated['adresse'];
                }

                if (!empty($profileData)) {
                    $user->profile()->update($profileData);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Profil administrateur mis à jour.',
                'user' => $user->load('profile'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            Log::error('Erreur modification profil admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du profil.',
            ], 500);
        }
    }


    /**
     * Modifier mot de passe admin
     */
    public function updatePassword(Request $request)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié.',
                ], 401);
            }

            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if (!Hash::check(
                $validated['current_password'],
                $user->password
            )) {

                return response()->json([
                    'success' => false,
                    'message' => 'L’ancien mot de passe est incorrect.',
                ], 422);
            }

            $user->update([
                'password' => Hash::make(
                    $validated['password']
                ),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            Log::error('Erreur modification mot de passe admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du mot de passe.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * NOTIFICATIONS (SÉCURISÉ CONTRE LES ERREURS 500)
     * ============================================================
     */

    public function notifications(Request $request)
    {
        try {
            // Vérifie si la table notifications existe, sinon renvoie un tableau vide
            if (!Schema::hasTable('notifications')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Aucune notification disponible.'
                ]);
            }

            $user = $request->user();

            $notifications = $user->notifications()
                ->latest()
                ->paginate(
                    $request->get('per_page', 20)
                );

            return response()->json([
                'success' => true,
                'data' => $notifications,
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur notifications admin', [
                'message' => $e->getMessage(),
            ]);

            // On renvoie une réponse vide avec succès pour ne pas bloquer l'interface
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Aucune notification disponible.'
            ]);
        }
    }


    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        try {
            if (!Schema::hasTable('notifications')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucune notification à mettre à jour.'
                ]);
            }

            $user = $request->user();

            $user->unreadNotifications->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Toutes les notifications ont été marquées comme lues.',
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur lecture notifications admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aucune notification à mettre à jour.'
            ]);
        }
    }


    /**
     * Marquer une notification comme lue
     */
    public function markNotificationAsRead(Request $request, $id)
    {
        try {
            if (!Schema::hasTable('notifications')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification introuvable.'
                ]);
            }

            $user = $request->user();

            $notification = $user->notifications()
                ->where('id', $id)
                ->first();

            if (!$notification) {

                return response()->json([
                    'success' => false,
                    'message' => 'Notification introuvable.',
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue.',
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur notification read admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la notification.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * SETTINGS
     * ============================================================
     */

    public function getSettings()
    {
        try {

            $settings = DB::table('settings')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur récupération settings admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des paramètres.',
            ], 500);
        }
    }


    /**
     * Modifier les paramètres
     */
    public function updateSettings(Request $request)
    {
        try {

            $settings = $request->all();

            foreach ($settings as $key => $value) {

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                DB::table('settings')->updateOrInsert(
                    [
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                        'updated_at' => now(),
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès.',
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur modification settings admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification des paramètres.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * MEETINGS ADMIN
     * ============================================================
     */

    public function meetings()
    {
        try {

            $meetings = DB::table('meetings')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $meetings,
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur meetings admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des réunions.',
            ], 500);
        }
    }


    /**
     * Créer une réunion
     */
    public function createMeeting(Request $request)
    {
        try {

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date' => 'required|date',
                'link' => 'nullable|string|max:1000',
            ]);

            $data = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'date' => $validated['date'],
                'link' => $validated['link'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $id = DB::table('meetings')
                ->insertGetId($data);

            $meeting = DB::table('meetings')
                ->where('id', $id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Réunion créée avec succès.',
                'data' => $meeting,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            Log::error('Erreur création meeting admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réunion.',
            ], 500);
        }
    }


    /**
     * Supprimer une réunion
     */
    public function deleteMeeting($id)
    {
        try {

            $meeting = DB::table('meetings')
                ->where('id', $id)
                ->first();

            if (!$meeting) {

                return response()->json([
                    'success' => false,
                    'message' => 'Réunion introuvable.',
                ], 404);
            }

            DB::table('meetings')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Réunion supprimée avec succès.',
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur suppression meeting admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la réunion.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * REPORTS
     * ============================================================
     */

    public function reports()
    {
        try {

            $reports = DB::table('reports')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reports,
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur reports admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des rapports.',
            ], 500);
        }
    }


    /**
     * Modifier le statut d'un rapport
     */
    public function updateReport(Request $request, $id)
    {
        try {

            $report = DB::table('reports')
                ->where('id', $id)
                ->first();

            if (!$report) {

                return response()->json([
                    'success' => false,
                    'message' => 'Rapport introuvable.',
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'required|string|max:50',
                'commentaire' => 'nullable|string',
            ]);

            $data = [
                'status' => $validated['status'],
                'updated_at' => now(),
            ];

            if (array_key_exists('commentaire', $validated)) {
                $data['commentaire'] = $validated['commentaire'];
            }

            DB::table('reports')
                ->where('id', $id)
                ->update($data);

            $report = DB::table('reports')
                ->where('id', $id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Rapport mis à jour avec succès.',
                'data' => $report,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            Log::error('Erreur update report admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du rapport.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * CACHE
     * ============================================================
     */

    public function clearCache()
    {
        try {

            \Artisan::call('optimize:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache Laravel vidé avec succès.',
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur nettoyage cache admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage du cache.',
            ], 500);
        }
    }


    /**
     * ============================================================
     * EXPORT
     * ============================================================
     */

    public function exportData()
    {
        try {

            $users = User::with('profile')
                ->get()
                ->map(function ($user) {

                    return [
                        'id' => $user->id,
                        'nom' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_validated' => $user->is_validated,
                        'created_at' => $user->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Export généré avec succès.',
                'data' => $users,
            ]);

        } catch (\Exception $e) {

            Log::error('Erreur export admin', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’export des données.',
            ], 500);
        }
    }
}