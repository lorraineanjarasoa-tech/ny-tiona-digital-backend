<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'formation_id',
        'mois',
        'annee',
        'montant',
        'date_paiement',
        'statut',
        'reference',
        'preuve_paiement',
        'motif_rejet',
        'valide_par',
        'date_validation',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'mois' => 'integer',
        'annee' => 'integer',
        'date_paiement' => 'datetime',
        'date_validation' => 'datetime',
    ];

    /* ============================================================
     * RELATIONS
     * ============================================================ */

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /* ============================================================
     * ACCESSORS
     * ============================================================ */

    /**
     * URL complète de la preuve de paiement
     */
    public function getPreuvePaiementUrlAttribute(): ?string
    {
        return $this->preuve_paiement
            ? asset('storage/' . $this->preuve_paiement)
            : null;
    }

    /**
     * Libellé du mois : "Octobre 2026"
     */
    public function getMoisLabelAttribute(): string
    {
        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return ($moisNoms[$this->mois] ?? 'Mois ' . $this->mois) . ' ' . $this->annee;
    }

    /**
     * Clé unique du mois : "2026-10"
     */
    public function getMoisKeyAttribute(): string
    {
        return sprintf('%04d-%02d', $this->annee, $this->mois);
    }

    /* ============================================================
     * SCOPES
     * ============================================================ */

    public function scopeValides($query)
    {
        return $query->where('statut', 'paye');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeRejetes($query)
    {
        return $query->where('statut', 'annule');
    }
}