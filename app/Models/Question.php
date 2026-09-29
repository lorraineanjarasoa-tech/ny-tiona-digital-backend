<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions';

    protected $fillable = [
        'examen_id',
        'question',
        'reponses',
        'bonne_reponse',
        'points',
        'explication',
        'ordre',
    ];

    protected $casts = [
        'reponses' => 'array',
        'bonne_reponse' => 'integer',
        'points' => 'integer',
        'ordre' => 'integer',
    ];

    /* ------------------------------------------------------------------
     * RELATIONS
     * ------------------------------------------------------------------ */

    public function examen()
    {
        return $this->belongsTo(Examen::class);
    }
}