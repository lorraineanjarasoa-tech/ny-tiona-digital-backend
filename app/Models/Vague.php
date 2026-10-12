<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vague extends Model
{
    use HasFactory;

    protected $fillable = [
        'vague',
        'formation_id',
        'date_debut',
        'date_fin',
        'capacite',
        'statut',
    ];

    protected $casts = [
        'vague' => 'integer',
        'formation_id' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'capacite' => 'integer',
    ];

    /**
     * Champs calculés ajoutés automatiquement au JSON
     */
    protected $appends = [
        'nb_inscrits',
        'places_restantes',
        'est_complet',
        'est_expiree',
    ];

    /* ============================================================
     * RELATIONS
     * ============================================================ */

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /* ============================================================
     * ACCESSORS — Calculs dynamiques
     * ============================================================ */

    /**
     * Nombre d'inscrits dans cette vague
     */
    public function getNbInscritsAttribute(): int
    {
        // Si withCount('inscriptions') a été utilisé
        if (isset($this->attributes['inscriptions_count'])) {
            return (int) $this->attributes['inscriptions_count'];
        }

        // Sinon, compter dynamiquement
        return $this->inscriptions()->count();
    }

    /**
     * Places restantes
     */
    public function getPlacesRestantesAttribute(): int
    {
        $capacite = (int) ($this->capacite ?? 0);
        $inscrits = $this->getNbInscritsAttribute();
        return max(0, $capacite - $inscrits);
    }

    /**
     * La vague est-elle complète ?
     */
    public function getEstCompletAttribute(): bool
    {
        return $this->getPlacesRestantesAttribute() <= 0;
    }

    /**
     * La vague est-elle expirée ?
     */
    public function getEstExpireeAttribute(): bool
    {
        if (!$this->date_fin) return false;
        return now()->greaterThan($this->date_fin);
    }
}