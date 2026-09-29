<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cours extends Model
{
    use HasFactory;

    protected $table = 'cours';

    protected $fillable = [
        'formateur_id',
        'formation_id',
        'titre',
        'description_courte',
        'description_longue',
        'video_url',
        'document',
        'image',
        'niveau',
        'duree_heures',
        'ordre',
        'statut',
        'est_visible',
        'date_publication',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'duree_heures' => 'integer',
        'est_visible' => 'boolean',
        'date_publication' => 'datetime',
    ];

    /* ------------------------------------------------------------------
     * Accessors (compatibilité frontend)
     * ------------------------------------------------------------------
     * Le frontend utilise `description`, `duree`, `is_active`
     * → on les mappe automatiquement vers les vraies colonnes.
     */

    public function getDescriptionAttribute()
    {
        return $this->description_courte;
    }

    public function getDureeAttribute()
    {
        return $this->duree_heures;
    }

    public function getIsActiveAttribute()
    {
        return (bool) $this->est_visible;
    }

    /* ------------------------------------------------------------------
     * Relations
     * ------------------------------------------------------------------ */

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function formateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }
}