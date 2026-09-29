<?php
// app/Models/Paiement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $table = 'paiements';

    protected $fillable = [
        'etudiant_id',
        'mois',
        'annee',
        'montant',
        'date_paiement',
        'statut',
        'reference',
    ];

    protected $casts = [
        'date_paiement' => 'datetime',
        'montant' => 'float',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }
}