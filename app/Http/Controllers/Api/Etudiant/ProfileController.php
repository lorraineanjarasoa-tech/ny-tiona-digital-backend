<?php
// app/Http/Controllers/Api/Etudiant/ProfileController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Récupérer le profil de l'étudiant
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user()->load('profile');
            return response()->json($user);
        } catch (\Exception $e) {
            Log::error('Erreur profil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du profil.'
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom_complet' => 'sometimes|string|max:255',
                'contact' => 'sometimes|string|max:20',
                'adresse' => 'sometimes|string|max:255',
                'bio' => 'nullable|string',
                'photo_profil' => 'nullable|image|max:2048',
            ]);

            $user = $request->user();
            
            // Mettre à jour le profil
            if ($user->profile) {
                $user->profile->update($validated);
            } else {
                $user->profile()->create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès.',
                'user' => $user->load('profile')
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour profil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil.'
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe actuel incorrect.'
                ], 400);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès.'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur changement mot de passe: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe.'
            ], 500);
        }
    }
}