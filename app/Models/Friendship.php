<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Friendship extends Model
{
    use HasFactory;

    protected $table = 'friendships';

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------
     * RELATIONS
     * ------------------------------------------------------------------ */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    /* ------------------------------------------------------------------
     * SCOPES
     * ------------------------------------------------------------------ */

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /* ------------------------------------------------------------------
     * HELPERS
     * ------------------------------------------------------------------ */

        /**
     * Verifie si deux utilisateurs sont amis.
     */
    public static function areFriends(string $userId, string $otherId): bool
    {
        return static::where(function ($q) use ($userId, $otherId) {
            $q->where('user_id', $userId)->where('friend_id', $otherId);
        })->orWhere(function ($q) use ($userId, $otherId) {
            $q->where('user_id', $otherId)->where('friend_id', $userId);
        })
        ->where('status', 'accepted')
        ->exists();
    }

    /**
     * Verifie si une demande existe entre deux utilisateurs.
     */
    public static function existsBetween(string $userId, string $otherId): bool
    {
        return static::where(function ($q) use ($userId, $otherId) {
            $q->where('user_id', $userId)->where('friend_id', $otherId);
        })->orWhere(function ($q) use ($userId, $otherId) {
            $q->where('user_id', $otherId)->where('friend_id', $userId);
        })->exists();
    }
}