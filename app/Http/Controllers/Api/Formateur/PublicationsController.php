<?php

namespace App\Http\Controllers\Api\Formateur;

use App\Http\Controllers\Controller;
use App\Models\Partage;
use App\Models\Like;
use App\Models\Commentaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicationsController extends Controller
{
    /**
     * Liste des publications du formateur connecté.
     * GET /formateur/publications
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $publications = Partage::with(['user.profile'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $items = $publications->map(function ($p) use ($user) {
                $data = $p->toArray();

                $myLike = Like::where('partage_id', $p->id)
                    ->where('user_id', $user->id)
                    ->first();

                $data['liked'] = $myLike !== null;
                $data['my_reaction'] = $myLike?->reaction_type;
                $data['likes_count'] = Like::where('partage_id', $p->id)->count();
                $data['comments_count'] = Commentaire::where('partage_id', $p->id)->count();
                $data['views_count'] = $p->views_count ?? 0;

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
            Log::error('PublicationsController@index : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer une publication.
     * POST /formateur/publications
     */
    public function store(Request $request)
    {
        try {
            $hasMedia = $request->hasFile('media');

            $request->merge([
                'titre' => $request->filled('titre') ? $request->input('titre') : null,
                'contenu' => $request->filled('contenu') ? $request->input('contenu') : null,
            ]);

            $validated = $request->validate([
                'titre' => $hasMedia ? 'nullable|string|max:150' : 'required|string|max:150',
                'contenu' => $hasMedia ? 'nullable|string|max:5000' : 'required|string|max:5000',
                'visibilite' => 'required|in:public,etudiants,formateurs,ma_formation,prive',
                'media' => 'nullable|file|max:51200',
                'media_type' => 'nullable|in:image,video,document',
            ]);

            $mediaPath = null;
            $mediaMime = null;
            $mediaSize = null;

            if ($hasMedia) {
                $file = $request->file('media');
                $folder = 'publications/' . ($validated['media_type'] ?? 'other');
                $mediaPath = $file->store($folder, 'public');
                $mediaMime = $file->getMimeType();
                $mediaSize = $file->getSize();
            }

            $titre = $validated['titre'] ?? null;
            if (empty($titre) && $hasMedia) {
                $labels = [
                    'image' => 'Nouvelle image partagée',
                    'video' => 'Nouvelle vidéo partagée',
                    'document' => 'Nouveau document partagé',
                ];
                $titre = $labels[$validated['media_type'] ?? ''] ?? 'Nouvelle publication';
            }

            $publication = Partage::create([
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

            $publication->load('user.profile');

            return response()->json([
                'success' => true,
                'message' => 'Publication créée.',
                'data' => array_merge($publication->toArray(), [
                    'liked' => false,
                    'my_reaction' => null,
                    'likes_count' => 0,
                    'comments_count' => 0,
                    'views_count' => 0,
                    'reactions_breakdown' => [],
                    'media_url' => $mediaPath ? asset('storage/' . $mediaPath) : null,
                    'auteur' => $publication->user,
                ]),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('PublicationsController@store : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Modifier une publication.
     * PUT /formateur/publications/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $publication = Partage::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $hasNewMedia = $request->hasFile('media');

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
                if ($publication->media_path) {
                    Storage::disk('public')->delete($publication->media_path);
                }
                $file = $request->file('media');
                $folder = 'publications/' . ($validated['media_type'] ?? 'other');
                $publication->media_path = $file->store($folder, 'public');
                $publication->media_mime = $file->getMimeType();
                $publication->media_size = $file->getSize();
                $publication->media_type = $validated['media_type'] ?? null;
            }

            if ($request->has('titre')) {
                $publication->titre = $validated['titre'] ?? $publication->titre;
            }
            if ($request->has('contenu')) {
                $publication->contenu = $validated['contenu'] ?? $publication->contenu;
            }
            if ($request->has('visibilite')) {
                $publication->visibilite = $validated['visibilite'];
            }

            $publication->save();

            return response()->json([
                'success' => true,
                'message' => 'Publication modifiée.',
                'data' => $publication,
            ]);
        } catch (\Exception $e) {
            Log::error('PublicationsController@update : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une publication.
     * DELETE /formateur/publications/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $publication = Partage::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            if ($publication->media_path) {
                Storage::disk('public')->delete($publication->media_path);
            }

            Like::where('partage_id', $id)->delete();
            Commentaire::where('partage_id', $id)->delete();
            $publication->delete();

            return response()->json([
                'success' => true,
                'message' => 'Publication supprimée.',
            ]);
        } catch (\Exception $e) {
            Log::error('PublicationsController@destroy : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}