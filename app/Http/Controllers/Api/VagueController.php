<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\Vague;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VagueController extends Controller
{
    // ==========================================
    // LISTE DES VAGUES
    // ==========================================

    public function index()
    {
        try {

            $vagues = Vague::with('formation')
                ->withCount('inscriptions')
                ->orderByDesc('date_debut')
                ->orderBy('vague')
                ->get()
                ->map(function ($vague) {

                    return [
                        'id' => $vague->id,

                        'vague' => $vague->vague,

                        'formation_id' => $vague->formation_id,

                        'formation_nom' =>
                            $vague->formation?->titre
                            ?? $vague->formation?->nom
                            ?? 'Formation non définie',

                        'formation' => $vague->formation,

                        'date_debut' => $vague->date_debut,

                        'date_fin' => $vague->date_fin,

                        'capacite' => $vague->capacite,

                        'statut' => $vague->statut,

                        'nb_etudiants' =>
                            $vague->inscriptions_count ?? 0,

                        'annee' =>
                            $vague->date_debut?->year,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Vagues récupérées avec succès.',
                'data' => $vagues,
            ]);

        } catch (\Throwable $e) {

            Log::error('Erreur liste vagues', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des vagues.',
            ], 500);
        }
    }


    // ==========================================
    // CRÉER UNE VAGUE
    // ==========================================

    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'vague' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'formation_id' => [
                    'required',
                    'exists:formations,id',
                ],

                'date_debut' => [
                    'required',
                    'date',
                ],

                'date_fin' => [
                    'required',
                    'date',
                    'after:date_debut',
                ],

                'capacite' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'statut' => [
                    'required',
                    'in:ouverte,fermee,terminee',
                ],
            ]);

            $formation = Formation::findOrFail(
                $validated['formation_id']
            );

            $annee = date(
                'Y',
                strtotime($validated['date_debut'])
            );

            $vagueExiste = Vague::where(
                'formation_id',
                $validated['formation_id']
            )
                ->where(
                    'vague',
                    $validated['vague']
                )
                ->whereYear(
                    'date_debut',
                    $annee
                )
                ->exists();

            if ($vagueExiste) {

                return response()->json([
                    'success' => false,

                    'message' =>
                        "La vague {$validated['vague']} de la formation "
                        . "\"{$formation->titre}\" existe déjà pour l'année {$annee}.",
                ], 422);
            }

            $vague = Vague::create($validated);

            $vague->load('formation');

            return response()->json([
                'success' => true,
                'message' => 'Vague créée avec succès.',
                'data' => $vague,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Veuillez corriger les erreurs du formulaire.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            Log::error('Erreur création vague', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la vague.',
            ], 500);
        }
    }


    // ==========================================
    // MODIFIER UNE VAGUE
    // ==========================================

    public function update(Request $request, $id)
    {
        try {

            $vague = Vague::findOrFail($id);

            $validated = $request->validate([
                'vague' => 'sometimes|integer|min:1',

                'formation_id' =>
                    'sometimes|exists:formations,id',

                'date_debut' =>
                    'sometimes|date',

                'date_fin' =>
                    'sometimes|date',

                'capacite' =>
                    'sometimes|integer|min:1',

                'statut' =>
                    'sometimes|in:ouverte,fermee,terminee',
            ]);

            $dateDebut =
                $validated['date_debut']
                ?? $vague->date_debut->format('Y-m-d');

            $dateFin =
                $validated['date_fin']
                ?? $vague->date_fin->format('Y-m-d');

            if (strtotime($dateFin) <= strtotime($dateDebut)) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'La date de fin doit être après la date de début.',
                ], 422);
            }

            $vague->update($validated);

            $vague->load('formation');

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

        } catch (\Throwable $e) {

            Log::error('Erreur modification vague', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification.',
            ], 500);
        }
    }


    // ==========================================
    // SUPPRIMER UNE VAGUE
    // ==========================================

    public function destroy($id)
    {
        try {

            $vague = Vague::findOrFail($id);

            if ($vague->inscriptions()->exists()) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Impossible de supprimer cette vague car elle possède déjà des inscriptions.',
                ], 422);
            }

            $vague->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vague supprimée avec succès.',
            ]);

        } catch (\Throwable $e) {

            Log::error('Erreur suppression vague', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' =>
                    'Erreur lors de la suppression de la vague.',
            ], 500);
        }
    }
}