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
        'prix' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function vagues(): HasMany
    {
        return $this->hasMany(Vague::class);
    }
}