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

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }
}