<?php
// app/Http/Controllers/Api/ForgotPasswordController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    /**
     * Envoyer un code de récupération.
     */
    public function sendCode(Request $request)
    {
        try {
            $request->validate([
                'method' => 'required|in:email,telephone',
                'identifier' => 'required|string',
                'role' => 'required|in:etudiant,formateur,admin',
            ]);

            $loginMethod = $request->method;
            $identifier = trim($request->identifier);
            $role = $request->role;

            // Recherche de l'utilisateur (CORRIGÉ : Uniquement par email)
            $query = User::query()->where('role', $role);

            if ($loginMethod === 'email') {
                $query->where('email', strtolower($identifier));
            } else {
                // Si le téléphone n'est pas supporté par la base de données, on renvoie une erreur claire
                return response()->json([
                    'success' => false,
                    'message' => 'La récupération par téléphone n\'est pas encore disponible. Utilisez votre email.'
                ], 422);
            }

            $user = $query->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun compte correspondant à ces informations.'
                ], 404);
            }

            // Génération du code
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Sauvegarde temporaire du code (expire après 10 minutes)
            Cache::put(
                'password_reset_' . $user->id,
                [
                    'code' => Hash::make($code),
                    'user_id' => $user->id,
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ],
                now()->addMinutes(10)
            );

            // Envoi par email
            if ($loginMethod === 'email') {
                try {
                    Mail::to($user->email)->send(new PasswordResetCodeMail($user, $code));
                    
                    Log::info('Code de récupération envoyé', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Un code de récupération a été envoyé à votre adresse email.'
                    ]);

                } catch (\Throwable $e) {
                    Log::error('Erreur envoi code récupération', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible d\'envoyer le code par email.'
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Un code de récupération a été envoyé.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur sendCode: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.'
            ], 500);
        }
    }

    /**
     * Vérifier le code de récupération.
     */
    public function verifyCode(Request $request)
    {
        try {
            $request->validate([
                'identifier' => 'required|string',
                'code' => 'required|string|size:6',
                'role' => 'required|in:etudiant,formateur,admin',
            ]);

            $identifier = trim($request->identifier);
            $code = trim($request->code);
            $role = $request->role;

            // Recherche utilisateur (CORRIGÉ : Uniquement par email pour éviter l'erreur SQL colonne "telephone")
            $user = User::query()
                ->where('role', $role)
                ->where('email', strtolower($identifier))
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte introuvable.'
                ], 404);
            }

            // Récupération du code dans le cache
            $resetData = Cache::get('password_reset_' . $user->id);

            // Vérification stricte de l'existence des données AVANT le Hash::check
            if (!$resetData || !isset($resetData['code'])) {
                Cache::forget('password_reset_' . $user->id);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Le code a expiré ou est introuvable. Veuillez demander un nouveau code.'
                ], 422);
            }

            // Vérification expiration
            if (isset($resetData['expires_at']) && now()->timestamp > $resetData['expires_at']) {
                Cache::forget('password_reset_' . $user->id);
                return response()->json([
                    'success' => false,
                    'message' => 'Le code a expiré. Veuillez demander un nouveau code.'
                ], 422);
            }

            // Vérification du code
            if (!Hash::check($code, $resetData['code'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le code de récupération est incorrect.'
                ], 422);
            }

            // Génération d'un token temporaire pour l'étape suivante
            $resetToken = Str::random(64);

            Cache::put(
                'password_reset_verified_' . $resetToken,
                [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'verified_at' => now()->timestamp,
                ],
                now()->addMinutes(15)
            );

            // On supprime le code de vérification utilisé
            Cache::forget('password_reset_' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Code vérifié avec succès.',
                'reset_token' => $resetToken, // C'est ce token que le frontend doit garder
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur verifyCode: ' . $e->getMessage() . ' | Ligne: ' . $e->getLine());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue.'
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe.
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'reset_token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:etudiant,formateur,admin',
            ]);

            $resetToken = $request->reset_token;

            // Vérification du token
            $resetData = Cache::get('password_reset_verified_' . $resetToken);

            if (!$resetData) {
                return response()->json([
                    'success' => false,
                    'message' => 'La session de récupération a expiré. Veuillez recommencer la procédure.'
                ], 422);
            }

            // Vérification utilisateur
            $user = User::find($resetData['user_id']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable.'
                ], 404);
            }

            // Vérification du rôle
            if ($user->role !== $request->role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le rôle ne correspond pas.'
                ], 422);
            }

            // Nouveau mot de passe
            $user->password = Hash::make($request->password);
            $user->save();

            // Invalidation du token
            Cache::forget('password_reset_verified_' . $resetToken);

            Log::info('Mot de passe réinitialisé', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre mot de passe a été réinitialisé avec succès.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur resetPassword: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ], 500);
        }
    }
}