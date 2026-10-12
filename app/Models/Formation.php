<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formation extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'image',
        'prix',
        'duree',
        'unite_duree',
        'niveau',
        'domaine',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'prix' => 'decimal:2',
        'duree' => 'integer',
    ];

    /* ============================================================
     * RELATIONS
     * ============================================================ */

    /**
     * ✅ Les cours de cette formation
     */
    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class, 'formation_id');
    }

    /**
     * Les vagues de cette formation
     */
    public function vagues(): HasMany
    {
        return $this->hasMany(Vague::class, 'formation_id');
    }

    /**
     * Les inscriptions à cette formation
     */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'formation_id');
    }
}