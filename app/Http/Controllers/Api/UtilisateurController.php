<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UtilisateurController extends Controller
{
    /**
     * Afficher un utilisateur spécifique
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Log pour déboguer
            Log::info('Recherche utilisateur avec ID: ' . $id);
            
            // Chercher l'utilisateur par ID
            $user = User::with('profile')->find($id);
            
            if (!$user) {
                // Si non trouvé, essayer de chercher par email ou autre
                $user = User::where('email', $id)->orWhere('id', $id)->first();
                
                if (!$user) {
                    Log::warning('Utilisateur non trouvé', ['id' => $id]);
                    return response()->json([
                        'message' => 'Utilisateur non trouvé',
                        'id' => $id
                    ], 404);
                }
            }

            return response()->json($user, 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche de l\'utilisateur', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider un utilisateur
     */
    public function valider(Request $request, string $id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $user->is_validated = true;
            $user->validated_at = now();
            $user->save();

            return response()->json([
                'message' => 'Utilisateur validé avec succès',
                'user' => $user
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la validation'
            ], 500);
        }
    }
}