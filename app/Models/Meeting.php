<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'titre',
        'description',
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

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}