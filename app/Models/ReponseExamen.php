<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReponseExamen extends Model
{
    use HasFactory;

    protected $table = 'reponses_examens';

    protected $fillable = [
        'user_id',
        'examen_id',
        'reponses',
        'score',
        'bonnes_reponses',
        'mauvaises_reponses',
        'temps_ecoule',
    ];

    protected $casts = [
        'reponses' => 'array',
        'score' => 'decimal:2',
        'bonnes_reponses' => 'integer',
        'mauvaises_reponses' => 'integer',
        'temps_ecoule' => 'integer',
    ];

    /* ------------------------------------------------------------------
     * RELATIONS
     * ------------------------------------------------------------------ */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class);
    }
}