<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'formation_id',
        'vague_id',
        'preuve_paiement',
        'reference_bancaire',
        'statut',
        'motif_rejet',
        'valide_par',
        'date_validation',
        'date_inscription',
        'documents_justificatifs',
    ];

    protected $casts = [
        'documents_justificatifs' => 'array',
        'date_validation' => 'datetime',
        'date_inscription' => 'datetime',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'etudiant_id'
        );
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(
            Formation::class
        );
    }

    public function vague(): BelongsTo
    {
        return $this->belongsTo(
            Vague::class
        );
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'valide_par'
        );
    }
}