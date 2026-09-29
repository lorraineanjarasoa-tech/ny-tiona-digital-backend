<?php
// app/Http/Controllers/Api/Etudiant/PartageController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Partage;
use App\Models\Like;
use App\Models\Commentaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PartageController extends Controller
{
    /* ==================================================================
     * LISTE DES PARTAGES
     * GET /etudiant/mes-partages
     * ================================================================== */
    public function mesPartages(Request $request)
    {
        try {
            $user = $request->user();

            $partages = Partage::with(['user.profile'])
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('visibilite', 'public')
                      ->orWhere(function ($q2) use ($user) {
                          if ($user->role === 'etudiant') {
                              $q2->where('visibilite', 'etudiants');
                          }
                      })
                      ->orWhere(function ($q2) use ($user) {
                          if ($user->role === 'formateur') {
                              $q2->where('visibilite', 'formateurs');
                          }
                      });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $items = $partages->map(function ($p) use ($user) {
                $data = $p->toArray();

                // Réaction de l'utilisateur courant
                $myLike = Like::where('partage_id', $p->id)
                    ->where('user_id', $user->id)
                    ->first();

                $data['liked'] = $myLike !== null;
                $data['my_reaction'] = $myLike?->reaction_type;
                $data['likes_count'] = Like::where('partage_id', $p->id)->count();
                $data['comments_count'] = Commentaire::where('partage_id', $p->id)->count();
                $data['views_count'] = $p->views_count ?? 0;

                // Répartition des réactions par type
                $data['reactions_breakdown'] = Like::where('partage_id', $p->id)
                    ->selectRaw('reaction_type, COUNT(*) as count')
                    ->groupBy('reaction_type')
                    ->pluck('count', 'reaction_type')
                    ->toArray();

                $data['media_url'] = $p->media_path
                    ? asset('storage/' . $p->media_path)
                    : null;
                $data['auteur'] = $p->user;

                return $data;
            });

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mes partages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==================================================================
     * CRÉER UN PARTAGE
     * POST /etudiant/partage
     * ================================================================== */
    public function store(Request $request)
    {
        try {
            $hasMedia = $request->hasFile('media');

            // Convertir les chaînes vides en null pour la validation
            $request->merge([
                'titre' => $request->filled('titre') ? $request->input('titre') : null,
                'contenu' => $request->filled('contenu') ? $request->input('contenu') : null,
            ]);

            $validated = $request->validate([
                'titre' => $hasMedia
                    ? 'nullable|string|max:150'
                    : 'required|string|max:150',
                'contenu' => $hasMedia
                    ? 'nullable|string|max:5000'
                    : 'required|string|max:5000',
                'visibilite' => 'required|in:public,etudiants,formateurs,ma_formation,prive',
                'media' => 'nullable|file|max:204800',
                'media_type' => 'nullable|in:image,video,document',
            ]);

            $mediaPath = null;
            $mediaMime = null;
            $mediaSize = null;

            if ($hasMedia) {
                $file = $request->file('media');
                $folder = 'partages/' . ($validated['media_type'] ?? 'other');
                $mediaPath = $file->store($folder, 'public');
                $mediaMime = $file->getMimeType();
                $mediaSize = $file->getSize();
            }

            // Titre par défaut si vide et média présent
            $titre = $validated['titre'] ?? null;
            if (empty($titre) && $hasMedia) {
                $typeLabels = [
                    'image' => 'Nouvelle photo partagée',
                    'video' => 'Nouvelle vidéo partagée',
                    'document' => 'Nouveau document partagé',
                ];
                $titre = $typeLabels[$validated['media_type'] ?? ''] ?? 'Nouvelle publication';
            }

            $partage = Partage::create([
                'user_id' => $request->user()->id,
                'titre' => $titre,
                'contenu' => $validated['contenu'] ?? '',
                'visibilite' => $validated['visibilite'],
                'media_path' => $mediaPath,
                'media_type' => $validated['media_type'] ?? null,
                'media_mime' => $mediaMime,
                'media_size' => $mediaSize,
                'likes_count' => 0,
                'views_count' => 0,
            ]);

            $partage->load('user.profile');

            Log::info('Nouveau partage', [
                'user_id' => $request->user()->id,
                'partage_id' => $partage->id,
                'has_media' => $hasMedia,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Partage publié avec succès.',
                'data' => array_merge($partage->toArray(), [
                    'liked' => false,
                    'my_reaction' => null,
                    'likes_count' => 0,
                    'comments_count' => 0,
                    'views_count' => 0,
                    'reactions_breakdown' => [],
                    'media_url' => $mediaPath ? asset('storage/' . $mediaPath) : null,
                    'auteur' => $partage->user,
                ]),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur partage: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du partage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ==================================================================
     * MODIFIER UN PARTAGE
     * PUT /etudiant/partage/{id}
     * ================================================================== */
    public function update(Request $request, $id)
    {
        try {
            $partage = Partage::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$partage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Partage non trouvé ou vous n\'êtes pas l\'auteur.',
                ], 404);
            }

            $hasNewMedia = $request->hasFile('media');
            $hasExistingMedia = !empty($partage->media_path);
            $hasMedia = $hasNewMedia || $hasExistingMedia;

            // Convertir les chaînes vides en null
            $request->merge([
                'titre' => $request->filled('titre') ? $request->input('titre') : null,
                'contenu' => $request->filled('contenu') ? $request->input('contenu') : null,
            ]);

            $validated = $request->validate([
                'titre' => 'nullable|string|max:150',
                'contenu' => 'nullable|string|max:5000',
                'visibilite' => 'sometimes|in:public,etudiants,formateurs,ma_formation,prive',
                'media' => 'nullable|file|max:51200',
                'media_type' => 'nullable|in:image,video,document',
            ]);

            if ($hasNewMedia) {
                if ($partage->media_path) {
                    Storage::disk('public')->delete($partage->media_path);
                }

                $file = $request->file('media');
                $folder = 'partages/' . ($validated['media_type'] ?? 'other');
                $partage->media_path = $file->store($folder, 'public');
                $partage->media_mime = $file->getMimeType();
                $partage->media_size = $file->getSize();
                $partage->media_type = $validated['media_type'] ?? null;
            }

            if ($request->has('titre')) {
                $partage->titre = $validated['titre'] ?? $partage->titre;
            }
            if ($request->has('contenu')) {
                $partage->contenu = $validated['contenu'] ?? $partage->contenu;
            }
            if ($request->has('visibilite')) {
                $partage->visibilite = $validated['visibilite'];
            }

            $partage->save();

            return response()->json([
                'success' => true,
                'message' => 'Partage modifié avec succès.',
                'data' => $partage,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur modification partage: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification.',
            ], 500);
        }
    }

    /* ==================================================================
     * SUPPRIMER UN PARTAGE
     * DELETE /etudiant/partage/{id}
     * ================================================================== */
    public function destroy(Request $request, $id)
    {
        try {
            $partage = Partage::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$partage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Partage non trouvé ou vous n\'êtes pas l\'auteur.',
                ], 404);
            }

            if ($partage->media_path) {
                Storage::disk('public')->delete($partage->media_path);
            }

            Like::where('partage_id', $id)->delete();
            Commentaire::where('partage_id', $id)->delete();
            $partage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Partage supprimé avec succès.',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression partage: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
            ], 500);
        }
    }

    /* ==================================================================
     * RÉACTION (LIKE / LOVE / HAHA / WOW / SAD / ANGRY)
     * POST /partages/{id}/like
     * Body: { reaction_type: 'like'|'love'|'haha'|'wow'|'sad'|'angry' }
     * ================================================================== */
    public function like(Request $request, $id)
    {
        try {
            $partage = Partage::find($id);

            if (!$partage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Partage non trouvé.',
                ], 404);
            }

            $userId = $request->user()->id;
            $reactionType = $request->input('reaction_type', 'like');

            $allowed = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
            if (!in_array($reactionType, $allowed)) {
                $reactionType = 'like';
            }

            $existing = Like::where('partage_id', $id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                if ($existing->reaction_type === $reactionType) {
                    // Même réaction → on retire
                    $existing->delete();
                    $myReaction = null;
                } else {
                    // Réaction différente → on change
                    $existing->update(['reaction_type' => $reactionType]);
                    $myReaction = $reactionType;
                }
            } else {
                // Nouvelle réaction
                Like::create([
                    'partage_id' => $id,
                    'user_id' => $userId,
                    'reaction_type' => $reactionType,
                ]);
                $myReaction = $reactionType;
            }

            $likesCount = Like::where('partage_id', $id)->count();
            $partage->update(['likes_count' => $likesCount]);

            $breakdown = Like::where('partage_id', $id)
                ->selectRaw('reaction_type, COUNT(*) as count')
                ->groupBy('reaction_type')
                ->pluck('count', 'reaction_type')
                ->toArray();

            return response()->json([
                'success' => true,
                'my_reaction' => $myReaction,
                'liked' => $myReaction !== null,
                'likes_count' => $likesCount,
                'reactions_breakdown' => $breakdown,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur like: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réaction.',
            ], 500);
        }
    }

    /**
 * Ajouter un commentaire (ou une réponse).
 * POST /partages/{id}/commenter
 * Body: { contenu: string, parent_id?: int }
 */
public function commenter(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'contenu' => 'required|string|max:500',
            'parent_id' => 'nullable|integer|exists:commentaires,id',
        ]);

        $partage = Partage::find($id);
        if (!$partage) {
            return response()->json(['success' => false, 'message' => 'Partage non trouvé.'], 404);
        }

        $commentaire = Commentaire::create([
            'partage_id' => $id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'contenu' => $validated['contenu'],
        ]);

        $commentaire->load('user.profile');

        return response()->json([
            'success' => true,
            'message' => 'Commentaire ajouté.',
            'data' => [
                'id' => $commentaire->id,
                'contenu' => $commentaire->contenu,
                'parent_id' => $commentaire->parent_id,
                'created_at' => $commentaire->created_at,
                'user' => $commentaire->user,
                'replies' => [],
            ],
            'comments_count' => Commentaire::where('partage_id', $id)->count(),
        ], 201);

    } catch (\Exception $e) {
        Log::error('Erreur commentaire: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

   /**
 * Liste des commentaires avec leurs réponses imbriquées.
 * GET /partages/{id}/commentaires
 */
public function commentaires($id)
{
    try {
        // Récupère uniquement les commentaires racine (sans parent)
        // avec leurs réponses préchargées
        $commentaires = Commentaire::with([
                'user.profile',
                'replies.user.profile',
            ])
            ->where('partage_id', $id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($c) {
                return $this->formatCommentaire($c);
            });

        return response()->json([
            'success' => true,
            'data' => $commentaires,
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur liste commentaires: ' . $e->getMessage());
        return response()->json(['success' => false, 'data' => []], 500);
    }
}

/**
 * Formate un commentaire (récursif pour les réponses).
 */
private function formatCommentaire($commentaire)
{
    return [
        'id' => $commentaire->id,
        'contenu' => $commentaire->contenu,
        'parent_id' => $commentaire->parent_id,
        'created_at' => $commentaire->created_at,
        'user' => $commentaire->user,
        'replies' => $commentaire->replies->map(function ($reply) {
            return $this->formatCommentaire($reply);
        })->values(),
    ];
}

    /* ==================================================================
     * INCRÉMENTER LE NOMBRE DE VUES
     * POST /partages/{id}/view
     * ================================================================== */
    public function view(Request $request, $id)
    {
        try {
            $partage = Partage::find($id);

            if (!$partage) {
                return response()->json(['success' => false], 404);
            }

            $partage->increment('views_count');

            return response()->json([
                'success' => true,
                'views_count' => $partage->fresh()->views_count,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur view: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
}