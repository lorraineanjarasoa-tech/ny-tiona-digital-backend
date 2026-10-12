<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PublicationController extends Controller
{
    /**
     * =====================================================
     * LISTE DES PUBLICATIONS
     * GET /api/admin/publications
     * =====================================================
     */
    public function index(Request $request)
    {
        try {
            $query = Partage::with('user.profile')->latest();

            // Filtre par type de média
            if ($request->filled('media_type') && $request->media_type !== 'all') {
                $query->where('media_type', $request->media_type);
            }

            // Filtre : uniquement mes publications
            if ($request->boolean('mine')) {
                $query->where('user_id', $request->user()->id);
            }

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('titre', 'ILIKE', "%{$search}%")
                      ->orWhere('contenu', 'ILIKE', "%{$search}%");
                });
            }

            $publications = $query->paginate(20);

            // Formater chaque publication
            $publications->getCollection()->transform(
                fn($p) => $this->formatPublication($p)
            );

            return response()->json([
                'success' => true,
                'data' => $publications,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur index publications admin', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de charger les publications.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * CRÉER UNE PUBLICATION
     * POST /api/admin/publications
     * =====================================================
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'titre' => 'nullable|string|max:255',
                'contenu' => 'nullable|string|max:5000',
                'visibilite' => 'required|in:public,etudiants,formateurs,prive',
                'media' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi,pdf,doc,docx,xls,xlsx,ppt,pptx,txt|max:51200',
                'media_type' => 'nullable|in:image,video,document',
            ]);

            // Validation : au moins un titre ou un média
            if (empty($validated['titre']) && !$request->hasFile('media')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le titre ou un média est requis.',
                ], 422);
            }

            // Sauvegarde du média
            $mediaPath = null;
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $folder = match ($validated['media_type'] ?? 'document') {
                    'image' => 'publications/images',
                    'video' => 'publications/videos',
                    default => 'publications/documents',
                };
                $mediaPath = $file->store($folder, 'public');
            }

            $titre = $validated['titre'] ?? 'Nouvelle publication';

            $publication = Partage::create([
                'user_id' => $request->user()->id,
                'titre' => $titre,
                'contenu' => $validated['contenu'] ?? '',
                'visibilite' => $validated['visibilite'],
                'media_path' => $mediaPath,
                'media_type' => $validated['media_type'] ?? null,
            ]);

            // Recharger avec les relations
            $publication->load('user.profile');

            return response()->json([
                'success' => true,
                'message' => 'Publication créée avec succès.',
                'data' => $this->formatPublication($publication),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur store publication admin', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de créer la publication.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * MODIFIER UNE PUBLICATION
     * PUT /api/admin/publications/{id}
     * Seul le propriétaire peut modifier
     * =====================================================
     */
    public function update(Request $request, $id)
    {
        try {
            $publication = Partage::findOrFail($id);

            // 🔒 Vérifier que l'utilisateur est bien le propriétaire
            if ($publication->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez modifier que vos propres publications.',
                ], 403);
            }

            $validated = $request->validate([
                'titre' => 'sometimes|nullable|string|max:255',
                'contenu' => 'sometimes|nullable|string|max:5000',
                'visibilite' => 'sometimes|required|in:public,etudiants,formateurs,prive',
                'media' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi,pdf,doc,docx,xls,xlsx,ppt,pptx,txt|max:51200',
                'media_type' => 'nullable|in:image,video,document',
            ]);

            // Nouveau média ?
            if ($request->hasFile('media')) {
                // Supprimer l'ancien
                if (
                    $publication->media_path &&
                    Storage::disk('public')->exists($publication->media_path)
                ) {
                    Storage::disk('public')->delete($publication->media_path);
                }

                $file = $request->file('media');
                $folder = match ($validated['media_type'] ?? 'document') {
                    'image' => 'publications/images',
                    'video' => 'publications/videos',
                    default => 'publications/documents',
                };
                $publication->media_path = $file->store($folder, 'public');
                $publication->media_type = $validated['media_type'] ?? null;
            }

            if (isset($validated['titre'])) {
                $publication->titre = $validated['titre'];
            }
            if (isset($validated['contenu'])) {
                $publication->contenu = $validated['contenu'];
            }
            if (isset($validated['visibilite'])) {
                $publication->visibilite = $validated['visibilite'];
            }

            $publication->save();
            $publication->load('user.profile');

            return response()->json([
                'success' => true,
                'message' => 'Publication modifiée.',
                'data' => $this->formatPublication($publication),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Publication introuvable.',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur update publication admin', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier la publication.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * SUPPRIMER UNE PUBLICATION
     * DELETE /api/admin/publications/{id}
     * Seul le propriétaire peut supprimer
     * =====================================================
     */
    public function destroy(Request $request, $id)
    {
        try {
            $publication = Partage::findOrFail($id);

            // 🔒 Vérifier que l'utilisateur est bien le propriétaire
            if ($publication->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez supprimer que vos propres publications.',
                ], 403);
            }

            // Supprimer le média
            if (
                $publication->media_path &&
                Storage::disk('public')->exists($publication->media_path)
            ) {
                Storage::disk('public')->delete($publication->media_path);
            }

            $publication->delete();

            return response()->json([
                'success' => true,
                'message' => 'Publication supprimée.',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Publication introuvable.',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur destroy publication admin', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer la publication.',
            ], 500);
        }
    }

    /**
     * =====================================================
     * FORMATER UNE PUBLICATION
     * Inclut les infos de l'auteur + is_owner
     * =====================================================
     */
    private function formatPublication(Partage $p): array
    {
        // Charger les relations si pas déjà fait
        if (!$p->relationLoaded('user')) {
            $p->load('user.profile');
        }

        $user = $p->user;
        $profile = $user?->profile;

        // URL de la photo de profil
        $photoProfilUrl = null;
        if ($profile?->photo_profil) {
            $photoProfilUrl = asset('storage/' . $profile->photo_profil);
        }

        return [
            'id' => $p->id,
            'titre' => $p->titre,
            'contenu' => $p->contenu,
            'visibilite' => $p->visibilite,
            'media_type' => $p->media_type,
            'media_path' => $p->media_path,
            'media_url' => $p->media_path
                ? asset('storage/' . $p->media_path)
                : null,
            'created_at' => $p->created_at,
            'updated_at' => $p->updated_at,

            // Propriétaire
            'user_id' => $p->user_id,
            'is_owner' => auth()->id() === $p->user_id,

            // Infos auteur
            'author' => [
                'id' => $user?->id,
                'name' => $profile?->nom_complet ?? $user?->name ?? 'Utilisateur',
                'role' => $user?->role,
                'photo_profil' => $profile?->photo_profil,
                'photo_profil_url' => $photoProfilUrl,
            ],
        ];
    }
}