<?php

namespace App\Http\Controllers\Api\Formateur;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CoursController extends Controller
{
    /* ==============================================================
     * LISTE
     * GET /formateur/mes-cours
     * ============================================================== */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $cours = Cours::where('formateur_id', $user->id)
                ->with('formation:id,titre')
                ->orderBy('ordre')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'titre' => $c->titre,
                        'description' => $c->description_courte,
                        'description_longue' => $c->description_longue,
                        'video_url' => $c->video_url,
                        'document' => $c->document,
                        'image' => $c->image,
                        'niveau' => $c->niveau ?? 'debutant',
                        'ordre' => $c->ordre ?? 0,
                        'statut' => $c->statut ?? 'brouillon',
                        'est_visible' => (bool) $c->est_visible,
                        'is_active' => (bool) $c->est_visible,
                        'etudiants_count' => $c->etudiants_count,
                        'formation_id' => $c->formation_id,
                        'formation' => $c->formation,
                        'date_publication' => $c->date_publication,
                        'created_at' => $c->created_at,
                    ];
                });

            return response()->json($cours);
        } catch (\Exception $e) {
            Log::error('CoursController@index : ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * AFFICHER UN COURS
     * GET /formateur/cours/{id}
     * ============================================================== */
    public function show(Request $request, $id)
    {
        try {
            $cours = Cours::where('id', $id)
                ->where('formateur_id', $request->user()->id)
                ->with('formation:id,titre')
                ->first();

            if (!$cours) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cours introuvable ou accès non autorisé.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cours->id,
                    'titre' => $cours->titre,
                    'description' => $cours->description_courte,
                    'description_longue' => $cours->description_longue,
                    'video_url' => $cours->video_url,
                    'document' => $cours->document,
                    'image' => $cours->image,
                    'niveau' => $cours->niveau ?? 'debutant',
                    'ordre' => $cours->ordre ?? 0,
                    'statut' => $cours->statut ?? 'brouillon',
                    'est_visible' => (bool) $cours->est_visible,
                    'is_active' => (bool) $cours->est_visible,
                    'etudiants_count' => $cours->etudiants_count,
                    'formation_id' => $cours->formation_id,
                    'formation' => $cours->formation,
                    'date_publication' => $cours->date_publication,
                    'created_at' => $cours->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CoursController@show : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * CRÉER
     * POST /formateur/cours
     * ============================================================== */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'titre' => 'required|string|max:150',
                'description' => 'nullable|string|max:1000',
                'description_longue' => 'nullable|string',
                'video_url' => 'nullable|string|max:500',
                'video_file' => 'nullable|file|mimes:mp4,webm,mov|max:204800',
                'document' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx|max:20480',
                'formation_id' => 'nullable|exists:formations,id',
                'niveau' => 'nullable|in:debutant,intermediaire,avance',
                'ordre' => 'nullable|integer|min:0',
                'statut' => 'nullable|in:brouillon,actif,en_attente,termine',
                'is_active' => 'nullable',
            ]);

            $videoUrl = $validated['video_url'] ?? null;

            if ($request->hasFile('video_file')) {
                $videoUrl = $request->file('video_file')->store('cours/videos', 'public');
            }

            $documentPath = null;
            if ($request->hasFile('document')) {
                $documentPath = $request->file('document')->store('cours/documents', 'public');
            }

            $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);
            $statut = $validated['statut'] ?? ($isActive ? 'actif' : 'brouillon');
            if ($isActive && $statut === 'brouillon') {
                $statut = 'actif';
            }

            $cours = Cours::create([
                'formateur_id' => $request->user()->id,
                'formation_id' => $validated['formation_id'] ?? null,
                'titre' => $validated['titre'],
                'description_courte' => $validated['description'] ?? null,
                'description_longue' => $validated['description_longue'] ?? null,
                'video_url' => $videoUrl,
                'document' => $documentPath,
                'niveau' => $validated['niveau'] ?? 'debutant',
                'ordre' => $validated['ordre'] ?? 0,
                'statut' => $statut,
                'est_visible' => $isActive,
                'date_publication' => $isActive ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cours créé avec succès.',
                'data' => $cours,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CoursController@store : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * MODIFIER
     * PUT /formateur/cours/{id}
     * ============================================================== */
    public function update(Request $request, $id)
    {
        try {
            $cours = Cours::where('id', $id)
                ->where('formateur_id', $request->user()->id)
                ->firstOrFail();

            $validated = $request->validate([
                'titre' => 'sometimes|required|string|max:150',
                'description' => 'nullable|string|max:1000',
                'description_longue' => 'nullable|string',
                'video_url' => 'nullable|string|max:500',
                'video_file' => 'nullable|file|mimes:mp4,webm,mov|max:204800',
                'document' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx|max:20480',
                'formation_id' => 'nullable|exists:formations,id',
                'niveau' => 'nullable|in:debutant,intermediaire,avance',
                'ordre' => 'nullable|integer|min:0',
                'statut' => 'nullable|in:brouillon,actif,en_attente,termine',
                'is_active' => 'nullable',
            ]);

            if ($request->hasFile('video_file')) {
                if ($cours->video_url && !str_starts_with($cours->video_url, 'http')) {
                    Storage::disk('public')->delete($cours->video_url);
                }
                $cours->video_url = $request->file('video_file')->store('cours/videos', 'public');
            } elseif (array_key_exists('video_url', $validated)) {
                $cours->video_url = $validated['video_url'];
            }

            if ($request->hasFile('document')) {
                if ($cours->document) {
                    Storage::disk('public')->delete($cours->document);
                }
                $cours->document = $request->file('document')->store('cours/documents', 'public');
            }

            if (isset($validated['titre'])) {
                $cours->titre = $validated['titre'];
            }
            if (array_key_exists('description', $validated)) {
                $cours->description_courte = $validated['description'];
            }
            if (array_key_exists('description_longue', $validated)) {
                $cours->description_longue = $validated['description_longue'];
            }
            if (array_key_exists('formation_id', $validated)) {
                $cours->formation_id = $validated['formation_id'];
            }
            if (isset($validated['niveau'])) {
                $cours->niveau = $validated['niveau'];
            }
            if (isset($validated['ordre'])) {
                $cours->ordre = $validated['ordre'];
            }

            if ($request->has('is_active')) {
                $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
                $cours->est_visible = $isActive;
                $cours->statut = $isActive ? 'actif' : 'brouillon';
                if ($isActive && !$cours->date_publication) {
                    $cours->date_publication = now();
                }
            }
            if (isset($validated['statut'])) {
                $cours->statut = $validated['statut'];
                $cours->est_visible = $validated['statut'] === 'actif';
            }

            $cours->save();

            return response()->json([
                'success' => true,
                'message' => 'Cours modifié avec succès.',
                'data' => $cours,
            ]);

        } catch (\Exception $e) {
            Log::error('CoursController@update : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * SUPPRIMER
     * DELETE /formateur/cours/{id}
     * ============================================================== */
    public function destroy(Request $request, $id)
    {
        try {
            $cours = Cours::where('id', $id)
                ->where('formateur_id', $request->user()->id)
                ->firstOrFail();

            if ($cours->video_url && !str_starts_with($cours->video_url, 'http')) {
                Storage::disk('public')->delete($cours->video_url);
            }
            if ($cours->document) {
                Storage::disk('public')->delete($cours->document);
            }

            $cours->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cours supprimé.',
            ]);
        } catch (\Exception $e) {
            Log::error('CoursController@destroy : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}