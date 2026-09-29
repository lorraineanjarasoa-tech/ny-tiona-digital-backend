<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Examen extends Model
{
    use HasFactory;

    protected $table = 'examens';

    protected $fillable = [
        'titre',
        'description',
        'formation_id',
        'created_by',
        'duree_minutes',
        'note_sur',
        'date_debut',
        'date_fin',
        'statut',
        'tentatives_max',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    /* ------------------------------------------------------------------
     * RELATIONS
     * ------------------------------------------------------------------ */

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('ordre');
    }

    public function reponses()
    {
        return $this->hasMany(ReponseExamen::class);
    }

    /* ------------------------------------------------------------------
     * ACCESSORS
     * ------------------------------------------------------------------ */

    public function getNbQuestionsAttribute()
    {
        return $this->questions()->count();
    }

    /**
     * Calcule le statut effectif d'un examen pour un étudiant donné.
     * Retourne : disponible | a_venir | termine | ferme
     */
    public function getStatutEffectifAttribute()
    {
        $now = now();

        // Déjà passé ?
        $dejaPasse = $this->reponses()
            ->where('user_id', auth()->id())
            ->exists();

        if ($dejaPasse) {
            return 'termine';
        }

        // Pas encore ouvert ?
        if ($this->date_debut && $now->lt($this->date_debut)) {
            return 'a_venir';
        }

        // Fermé (statut manuel ou date de fin dépassée)
        if ($this->statut === 'ferme') {
            return 'ferme';
        }

        if ($this->date_fin && $now->gt($this->date_fin)) {
            return 'ferme';
        }

        // Sinon, disponible
        return 'disponible';
    }
}