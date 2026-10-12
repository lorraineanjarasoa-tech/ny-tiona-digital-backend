<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partage extends Model
{
    use HasFactory;

    protected $table = 'partages';

    protected $fillable = [
        'user_id',
        'titre',
        'contenu',
        'media_path',
        'media_type',
        'media_mime',
        'media_size',
        'visibilite',
        'likes_count',
        'views_count',
    ];

    protected $casts = [
        'media_size' => 'integer',
        'likes_count' => 'integer',
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------
     * Relations
     * ------------------------------------------------------------------ */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Likes de la publication.
     * Utilise le modèle Like qui pointe vers la table `likes`.
     */
    public function likes()
    {
        return $this->hasMany(Like::class, 'partage_id');
    }

    /**
 * Scope : filtre les partages visibles par un utilisateur donné.
 */
public function scopeVisiblePour($query, $user)
{
    if ($user->role === 'admin') {
        return $query;
    }

    return $query->where(function ($q) use ($user) {
        $q->where('visibilite', 'public');

        if ($user->role === 'formateur') {
            $q->orWhere('visibilite', 'formateurs');
        } elseif ($user->role === 'etudiant') {
            $q->orWhere('visibilite', 'etudiants');
        }

        $q->orWhere('user_id', $user->id);
    });
}
    /**
     * Commentaires de la publication.
     */
    public function commentaires()
    {
        return $this->hasMany(Commentaire::class, 'partage_id')->latest();
    }
}