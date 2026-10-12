<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    use HasFactory;

    protected $table = 'meetings';

    protected $fillable = [
        'created_by',
        'titre',
        'description',
        'audience',           // ✅ AJOUT
        'date_heure',
        'duree_minutes',
        'lien_meeting',
        'plateforme',
        'max_participants',
        'statut',
        'settings',
    ];

    protected $casts = [
        'date_heure' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Valeurs par défaut des attributs.
     */
    protected $attributes = [
        'duree_minutes' => 60,
        'max_participants' => 100,
        'statut' => 'planifie',
        'plateforme' => 'google_meet',
        'audience' => 'public',    // ✅ AJOUT
    ];

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}