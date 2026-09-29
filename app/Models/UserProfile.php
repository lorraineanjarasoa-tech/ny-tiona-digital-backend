<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    /**
     * Nom de la table (à ajuster si ta migration utilise 'profiles')
     */
    // protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'nom_complet',
        'adresse',
        'contact',
        'date_naissance',
        'sexe',
        'cin',
        'cin_recto',
        'cin_verso',
        'derniere_etude',
        'preference_cours',
        'photo_profil',
        'bio',
        'specialite',
        'diplomes',
        'certifications',
        'preuve_paiement',
        'reference_bancaire',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'diplomes' => 'array',
        'certifications' => 'array',
    ];

    /**
     * URL complètes ajoutées automatiquement au JSON
     */
    protected $appends = [
        'cin_recto_url',
        'cin_verso_url',
        'diplomes_url',
        'preuve_paiement_url',
        'photo_profil_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ============================================================
     * ACCESSORS : URL complètes des fichiers
     * ============================================================ */

    public function getCinRectoUrlAttribute(): ?string
    {
        return $this->cin_recto
            ? asset('storage/' . $this->cin_recto)
            : null;
    }

    public function getCinVersoUrlAttribute(): ?string
    {
        return $this->cin_verso
            ? asset('storage/' . $this->cin_verso)
            : null;
    }

    public function getDiplomesUrlAttribute(): ?string
    {
        // Si $this->diplomes est un tableau (cast array)
        if (is_array($this->diplomes)) {
            $first = $this->diplomes[0] ?? null;
            return $first ? asset('storage/' . $first) : null;
        }

        return $this->diplomes
            ? asset('storage/' . $this->diplomes)
            : null;
    }

    public function getPreuvePaiementUrlAttribute(): ?string
    {
        return $this->preuve_paiement
            ? asset('storage/' . $this->preuve_paiement)
            : null;
    }

    public function getPhotoProfilUrlAttribute(): ?string
    {
        return $this->photo_profil
            ? asset('storage/' . $this->photo_profil)
            : null;
    }
}