<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * GET /api/profile
     */
    public function show()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié.',
                ], 401);
            }

            $user->load('profile');

            return response()->json([
                'success' => true,
                'user' => $user,
                'data' => $user,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur show profile', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de charger le profil.',
            ], 500);
        }
    }

    /**
     * POST /api/profile/photo
     */
    public function uploadPhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $user = auth()->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil introuvable.',
                ], 404);
            }

            // Supprimer l'ancienne photo
            if ($profile->photo_profil && Storage::disk('public')->exists($profile->photo_profil)) {
                Storage::disk('public')->delete($profile->photo_profil);
            }

            $path = $request->file('photo')->store('photos_profil', 'public');

            $profile->update(['photo_profil' => $path]);

            Log::info('Photo de profil mise à jour', [
                'user_id' => $user->id,
                'path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo mise à jour.',
                'photo_profil' => $path,
                'photo_profil_url' => asset('storage/' . $path),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur upload photo profil', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de mettre à jour la photo.',
            ], 500);
        }
    }

    /**
     * DELETE /api/profile/photo
     */
    public function deletePhoto()
    {
        try {
            $user = auth()->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil introuvable.',
                ], 404);
            }

            if ($profile->photo_profil && Storage::disk('public')->exists($profile->photo_profil)) {
                Storage::disk('public')->delete($profile->photo_profil);
            }

            $profile->update(['photo_profil' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Photo supprimée.',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression photo', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer la photo.',
            ], 500);
        }
    }

    /**
     * PUT /api/profile
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom_complet' => 'sometimes|string|max:255',
                'adresse' => 'nullable|string|max:255',
                'contact' => 'sometimes|string|max:20',
                'bio' => 'nullable|string|max:500',
                'date_naissance' => 'nullable|date',
                'sexe' => 'nullable|in:M,F',
                'cin' => 'nullable|string|max:20',
                'derniere_etude' => 'nullable|string|max:255',
                'preference_cours' => 'nullable|string|max:255',
                'reference_bancaire' => 'nullable|string|max:255',
                'specialite' => 'nullable|string|max:255',
                'cours_enseignes' => 'nullable|string|max:255',
            ]);

            $user = auth()->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil introuvable.',
                ], 404);
            }

            $profile->update($validated);

            if (isset($validated['nom_complet'])) {
                $user->update(['name' => $validated['nom_complet']]);
            }

            $profile->refresh();
            $user->refresh();
            $user->load('profile');

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour.',
                'user' => $user,
                'profile' => $profile,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur update profil', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de mettre à jour le profil.',
            ], 500);
        }
    }

    /**
     * PUT /api/profile/password
     */
    public function updatePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = auth()->user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect.',
                ], 422);
            }

            $user->update([
                'password' => Hash::make($validated['new_password']),
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
            Log::error('Erreur update password', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier le mot de passe.',
            ], 500);
        }
    }
}