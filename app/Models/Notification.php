<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'url',
        'read',
        'data',
    ];

    protected $casts = [
        'read' => 'boolean',
        'data' => 'array',
    ];

    /* ------------------------------------------------------------------
     * RELATIONS
     * ------------------------------------------------------------------ */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ------------------------------------------------------------------
     * SCOPES
     * ------------------------------------------------------------------ */

    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /* ------------------------------------------------------------------
     * STATIC HELPERS
     * ------------------------------------------------------------------ */

    /**
     * Créer une notification pour un utilisateur.
     */
    public static function notify(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $url = null,
        ?array $data = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'read' => false,
            'data' => $data,
        ]);
    }

    /**
     * Envoyer à plusieurs utilisateurs.
     */
    public static function notifyMany(
        array $userIds,
        string $type,
        string $title,
        ?string $message = null,
        ?string $url = null,
        ?array $data = null
    ): int {
        $rows = [];
        $now = now();

        foreach ($userIds as $id) {
            $rows[] = [
                'user_id' => $id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'url' => $url,
                'read' => false,
                'data' => $data ? json_encode($data) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return static::insert($rows) ? count($rows) : 0;
    }
}