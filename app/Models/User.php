<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids, Notifiable;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'is_validated',
        'email_verified_at',
        'is_active',
        'last_login_at',
        'remember_token',
        'verification_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_validated' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function profile(): HasOne
    {
         return $this->hasOne(\App\Models\UserProfile::class, 'user_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'etudiant_id');
    }

    public function coursEnseignes(): HasMany
    {
        return $this->hasMany(Cours::class, 'formateur_id');
    }

    public function messagesEnvoyes(): HasMany
    {
        return $this->hasMany(Message::class, 'expediteur_id');
    }

    public function messagesRecus(): HasMany
    {
        return $this->hasMany(Message::class, 'destinataire_id');
    }

    public function meetingsCrees(): HasMany
    {
        return $this->hasMany(Meeting::class, 'createur_id');
    }

    public function partages(): HasMany
    {
        return $this->hasMany(Partage::class);
    }
    /**
     * Amitiés (en tant qu'expéditeur ou destinataire)
     */
    public function amisEnvoyes(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function amisRecus(): HasMany
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Examens créés (si formateur)
     */
    public function examensCrees(): HasMany
    {
        return $this->hasMany(Examen::class, 'created_by');
    }

    /**
     * Réponses aux examens (si étudiant)
     */
    public function reponsesExamens(): HasMany
    {
        return $this->hasMany(ReponseExamen::class, 'user_id');
    }
    
    /*
    |--------------------------------------------------------------------------
    | JWT
    |--------------------------------------------------------------------------
    */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFormateur(): bool
    {
        return $this->role === 'formateur';
    }

    public function isEtudiant(): bool
    {
        return $this->role === 'etudiant';
    }
}