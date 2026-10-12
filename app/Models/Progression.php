<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Progression extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cours_id',
        'pourcentage',
        'statut',
        'temps_passe_minutes',
        'started_at',
        'completed_at',
        'last_activity_at',
    ];

    protected $casts = [
        'pourcentage' => 'integer',
        'temps_passe_minutes' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    /* ============================================================
     * RELATIONS
     * ============================================================ */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cours(): BelongsTo
    {
        return $this->belongsTo(Cours::class);
    }

    /* ============================================================
     * HELPERS
     * ============================================================ */

    public function isCompleted(): bool
    {
        return $this->statut === 'termine' || $this->pourcentage >= 100;
    }

    public function isInProgress(): bool
    {
        return $this->statut === 'en_cours';
    }
}