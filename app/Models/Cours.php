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
        'ordre',
        'statut',
        'est_visible',
        'date_publication',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'est_visible' => 'boolean',
        'date_publication' => 'datetime',
    ];

    /* ------------------------------------------------------------------
     * Accessors (compatibilité frontend)
     * ------------------------------------------------------------------ */

    public function getDescriptionAttribute()
    {
        return $this->description_courte;
    }

    public function getIsActiveAttribute()
    {
        return (bool) $this->est_visible;
    }

    /**
     * Nombre d'étudiants inscrits à la formation de ce cours.
     */
    public function getEtudiantsCountAttribute(): int
    {
        if (!$this->formation_id) {
            return 0;
        }

        return \App\Models\Inscription::where('formation_id', $this->formation_id)
            ->whereIn('statut', ['valide', 'accepte', 'approuve'])
            ->count();
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