<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commentaire extends Model
{
    protected $table = 'commentaires';

    protected $fillable = ['partage_id', 'user_id', 'parent_id', 'contenu'];

    public function partage()
    {
        return $this->belongsTo(Partage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Commentaire parent (si c'est une réponse).
     */
    public function parent()
    {
        return $this->belongsTo(Commentaire::class, 'parent_id');
    }

    /**
     * Réponses à ce commentaire.
     */
    public function replies()
    {
        return $this->hasMany(Commentaire::class, 'parent_id')->orderBy('created_at', 'asc');
    }
}