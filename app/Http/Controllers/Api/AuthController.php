<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * =====================================================
     * INSCRIPTION
     * =====================================================
     */
    public function register(Request $request)
    {
        try {
            Log::info('=== DÉBUT INSCRIPTION ===');

            $validated = $request->validate([
                'role' => 'required|in:etudiant,formateur',
                'nom_complet' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'contact' => 'required|string|max:20',
                'adresse' => 'nullable|string|max:255',
                'password' => 'required|string|min:8|confirmed',
                'cours_enseignes' => 'nullable|string|max:255',

                // Nouveaux champs pour les étudiants
                'date_naissance' => 'nullable|date',
                'sexe' => 'nullable|in:M,F',
                'cin' => 'nullable|string|max:20',
                'derniere_etude' => 'nullable|string|max:255',
                'preference_cours' => 'nullable|string|max:255',
                'reference_bancaire' => 'nullable|string|max:255',

                // Fichiers (optionnels mais traités)
                'cin_recto' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'cin_verso' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'diplome' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'preuve_paiement' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            /* ============================================
             * CRÉATION UTILISATEUR
             * ============================================ */
            $user = User::create([
                'name' => $validated['nom_complet'],
                'email' => strtolower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_validated' => false,
            ]);

            /* ============================================
             * CONSTRUCTION DU PROFIL
             * ============================================ */
            $profileData = [
                'nom_complet' => $validated['nom_complet'],
                'contact' => $validated['contact'],
                'adresse' => $validated['adresse'] ?? null,
            ];

            /* ----- Formateur ----- */
            if ($validated['role'] === 'formateur' && !empty($validated['cours_enseignes'])) {
                $profileData['specialite'] = $validated['cours_enseignes'];
                $profileData['cours_enseignes'] = $validated['cours_enseignes'];
            }

            /* ----- Étudiant : champs académiques ----- */
            if ($validated['role'] === 'etudiant') {
                foreach ([
                    'date_naissance',
                    'sexe',
                    'cin',
                    'derniere_etude',
                    'preference_cours',
                    'reference_bancaire',
                ] as $field) {
                    if (!empty($validated[$field])) {
                        $profileData[$field] = $validated[$field];
                    }
                }
            }

            /* ============================================
             * SAUVEGARDE DES FICHIERS
             * ============================================ */
            if ($request->hasFile('cin_recto')) {
                $profileData['cin_recto'] = $request->file('cin_recto')->store('cin_recto', 'public');
            }
            if ($request->hasFile('cin_verso')) {
                $profileData['cin_verso'] = $request->file('cin_verso')->store('cin_verso', 'public');
            }
            if ($request->hasFile('diplome')) {
                $profileData['diplomes'] = [
                    $request->file('diplome')->store('diplomes', 'public'),
                ];
            }
            if ($request->hasFile('preuve_paiement')) {
                $profileData['preuve_paiement'] = $request->file('preuve_paiement')->store('preuves_paiement', 'public');
            }

            /* ============================================
             * CRÉATION DU PROFIL
             * ============================================ */
            $profile = $user->profile()->create($profileData);

            Log::info('Inscription réussie', [
                'user_id' => $user->id,
                'profile_id' => $profile->id,
                'has_cin_recto' => $request->hasFile('cin_recto'),
                'has_cin_verso' => $request->hasFile('cin_verso'),
                'has_diplome' => $request->hasFile('diplome'),
                'has_preuve_paiement' => $request->hasFile('preuve_paiement'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie. Veuillez attendre la validation.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
    Log::error('Erreur inscription', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Erreur lors de l\'inscription.',
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ],
    ], 500);
            }
    }

    /**
     * =====================================================
     * LOGIN
     * =====================================================
     *
     * Champs attendus :
     *  - login_method : email ou telephone
     *  - identifier   : email ou numéro
     *  - password     : mot de passe
     *  - role         : admin, formateur ou etudiant
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'login_method' => ['required', 'in:email,telephone'],
                'identifier' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', 'in:admin,formateur,etudiant'],
            ]);

            $identifier = trim($validated['identifier']);

            // Recherche par email ou téléphone
            if ($validated['login_method'] === 'email') {
                $user = User::where('email', strtolower($identifier))->first();
            } else {
                $user = User::whereHas('profile', function ($query) use ($identifier) {
                    $query->where('contact', $identifier);
                })->first();
            }

            // Compte introuvable ou mauvais mot de passe
            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant ou mot de passe incorrect.',
                ], 401);
            }

            // Vérification du rôle
            if ($user->role !== $validated['role']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le rôle sélectionné ne correspond pas à votre compte.',
                ], 403);
            }

            // Admin : connexion directe. Autres : doivent être validés.
            if ($user->role !== 'admin' && !$user->is_validated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est en attente de validation.',
                ], 403);
            }

            $token = auth()->login($user);

            $user->update(['last_login_at' => now()]);
            $user->load('profile');

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie.',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => $user,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Les informations fournies sont invalides.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur LOGIN', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * UTILISATEUR CONNECTÉ
     * =====================================================
     */
    public function me(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié',
                ], 401);
            }

            $user->load('profile');

            return response()->json([
                'success' => true,
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération utilisateur', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du profil.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * LOGOUT
     * =====================================================
     */
    public function logout()
    {
        try {
            auth()->logout();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie.',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur logout', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * REFRESH TOKEN
     * =====================================================
     */
    public function refresh()
    {
        try {
            $token = auth()->refresh();

            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur refresh token', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement du token.',
            ], 401);
        }
    }

    /**
     * =====================================================
     * MOT DE PASSE OUBLIÉ
     * =====================================================
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'method' => ['required', 'in:email,telephone'],
                'identifier' => ['required', 'string', 'max:255'],
                'role' => ['required', 'in:admin,formateur,etudiant'],
            ]);

            $identifier = trim($validated['identifier']);

            if ($validated['method'] === 'email') {
                $user = User::where('email', strtolower($identifier))
                    ->where('role', $validated['role'])
                    ->first();
            } else {
                $user = User::where('role', $validated['role'])
                    ->whereHas('profile', function ($query) use ($identifier) {
                        $query->where('contact', $identifier);
                    })
                    ->first();
            }

            // Ne pas révéler si le compte existe
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'Si les informations correspondent à un compte, un code de récupération sera envoyé.',
                ]);
            }

            // Supprimer les anciens codes non utilisés
            PasswordResetCode::where('user_id', $user->id)
                ->whereNull('used_at')
                ->delete();

            $code = (string) random_int(100000, 999999);

            PasswordResetCode::create([
                'user_id' => $user->id,
                'identifier' => $identifier,
                'method' => $validated['method'],
                'code' => $code,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'used_at' => null,
            ]);

            if ($validated['method'] === 'email') {
                Mail::raw(
                    "Votre code de récupération Ny Tiona Digital est : {$code}\n\n" .
                    "Ce code est valable pendant 10 minutes.\n" .
                    "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.",
                    function ($message) use ($user) {
                        $message->to($user->email)
                            ->subject('Code de récupération - Ny Tiona Digital');
                    }
                );
            } else {
                // SMS pas encore connecté : log pour dev
                Log::info('Code récupération téléphone généré', [
                    'user_id' => $user->id,
                    'telephone' => $identifier,
                    'code' => $code,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $validated['method'] === 'email'
                    ? 'Un code de vérification a été envoyé à votre adresse email.'
                    : 'Un code de vérification a été envoyé à votre numéro de téléphone.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur forgot password', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de traiter votre demande pour le moment.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * VÉRIFICATION DU CODE
     * =====================================================
     */
    public function verifyResetCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'method' => ['required', 'in:email,telephone'],
                'identifier' => ['required', 'string', 'max:255'],
                'code' => ['required', 'digits:6'],
                'role' => ['required', 'in:admin,formateur,etudiant'],
            ]);

            $identifier = trim($validated['identifier']);

            $resetCode = PasswordResetCode::where('identifier', $identifier)
                ->where('method', $validated['method'])
                ->whereHas('user', function ($query) use ($validated) {
                    $query->where('role', $validated['role']);
                })
                ->latest()
                ->first();

            if (!$resetCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code incorrect ou expiré.',
                ], 422);
            }

            if ($resetCode->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce code a expiré. Veuillez demander un nouveau code.',
                ], 422);
            }

            if ($resetCode->isUsed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce code a déjà été utilisé.',
                ], 422);
            }

            if ($resetCode->attempts >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nombre maximal de tentatives atteint. Veuillez demander un nouveau code.',
                ], 429);
            }

            if ($resetCode->code !== $validated['code']) {
                $resetCode->increment('attempts');

                return response()->json([
                    'success' => false,
                    'message' => 'Code de vérification incorrect.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Code vérifié avec succès.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Code invalide.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur vérification code reset', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * RÉINITIALISATION DU MOT DE PASSE
     * =====================================================
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'method' => ['required', 'in:email,telephone'],
                'identifier' => ['required', 'string', 'max:255'],
                'code' => ['required', 'digits:6'],
                'role' => ['required', 'in:admin,formateur,etudiant'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $identifier = trim($validated['identifier']);

            $resetCode = PasswordResetCode::where('identifier', $identifier)
                ->where('method', $validated['method'])
                ->where('code', $validated['code'])
                ->whereNull('used_at')
                ->latest()
                ->first();

            if (!$resetCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code incorrect ou invalide.',
                ], 422);
            }

            if ($resetCode->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce code a expiré. Veuillez recommencer la procédure.',
                ], 422);
            }

            if ($resetCode->attempts >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nombre maximal de tentatives atteint.',
                ], 429);
            }

            $user = User::where('id', $resetCode->user_id)
                ->where('role', $validated['role'])
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte introuvable.',
                ], 404);
            }

            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            $resetCode->update(['used_at' => now()]);

            PasswordResetCode::where('user_id', $user->id)
                ->whereNull('used_at')
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur reset password', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier le mot de passe.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * VÉRIFICATION EMAIL
     * =====================================================
     */
    public function verifyEmail($token)
    {
        try {
            $user = User::where('email_verification_token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de vérification invalide ou expiré.',
                ], 404);
            }

            $user->update([
                'email_verified_at' => now(),
                'email_verification_token' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre adresse email a été vérifiée avec succès.',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification email', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de votre email.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * CHECK AUTHENTIFICATION
     * =====================================================
     */
    public function check()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'authenticated' => false,
                ], 401);
            }

            $user->load('profile');

            return response()->json([
                'authenticated' => true,
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur check authentication', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'authenticated' => false,
            ], 401);
        }
    }
}