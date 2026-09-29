<?php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $friendships = Friendship::accepted()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('friend_id', $userId);
            })
            ->with(['user.profile', 'friend.profile'])
            ->get();

        $amis = $friendships->map(function ($f) use ($userId) {
            return $f->user_id === $userId ? $f->friend : $f->user;
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $amis,
        ]);
    }

    public function suggestions(Request $request)
    {
        $userId = $request->user()->id;
        $limit = (int) $request->query('limit', 12);

        $relatedIds = Friendship::where('user_id', $userId)
            ->orWhere('friend_id', $userId)
            ->get()
            ->flatMap(function ($f) use ($userId) {
                return $f->user_id === $userId ? [$f->friend_id] : [$f->user_id];
            })
            ->unique()
            ->push($userId)
            ->values()
            ->all();

        $suggestions = User::whereIn('role', ['etudiant', 'formateur'])
            ->whereNotIn('id', $relatedIds)
            ->with(['profile'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    public function demandes(Request $request)
    {
        $userId = $request->user()->id;

        $demandes = Friendship::pending()
            ->where('friend_id', $userId)
            ->with(['user.profile'])
            ->latest()
            ->get()
            ->map(fn ($f) => $f->user)
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $demandes,
        ]);
    }

    public function envoyees(Request $request)
    {
        $userId = $request->user()->id;

        $envoyees = Friendship::pending()
            ->where('user_id', $userId)
            ->with(['friend.profile'])
            ->latest()
            ->get()
            ->map(fn ($f) => $f->friend)
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $envoyees,
        ]);
    }

    public function demander(Request $request, $userId)
    {
        $currentId = $request->user()->id;

        if ($currentId === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous ajouter vous-meme.',
            ], 422);
        }

        $target = User::find($userId);

        if (!$target) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        if (Friendship::existsBetween($currentId, $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Une demande existe deja avec cet utilisateur.',
            ], 422);
        }

        Friendship::create([
            'user_id' => $currentId,
            'friend_id' => $userId,
            'status' => 'pending',
        ]);

        $senderName = $request->user()->profile?->nom_complet
            ?? $request->user()->email;

        Notification::notify(
            $userId,
            'ami',
            'Nouvelle demande d\'ami',
            $senderName . ' souhaite devenir votre ami.',
            '/etudiant/amis',
            ['sender_id' => $currentId]
        );

        return response()->json([
            'success' => true,
            'message' => 'Demande envoyee.',
        ]);
    }

    public function accepter(Request $request, $userId)
    {
        $currentId = $request->user()->id;

        $friendship = Friendship::pending()
            ->where('user_id', $userId)
            ->where('friend_id', $currentId)
            ->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'Demande introuvable.',
            ], 404);
        }

        $friendship->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $accepterName = $request->user()->profile?->nom_complet
            ?? $request->user()->email;

        Notification::notify(
            $friendship->user_id,
            'ami',
            'Demande acceptee',
            $accepterName . ' a accepte votre demande d\'ami.',
            '/etudiant/amis'
        );

        return response()->json([
            'success' => true,
            'message' => 'Ami ajoute.',
        ]);
    }

    public function refuser(Request $request, $userId)
    {
        $currentId = $request->user()->id;

        $friendship = Friendship::pending()
            ->where('user_id', $userId)
            ->where('friend_id', $currentId)
            ->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'Demande introuvable.',
            ], 404);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Demande refusee.',
        ]);
    }

    public function annuler(Request $request, $userId)
    {
        $currentId = $request->user()->id;

        $friendship = Friendship::pending()
            ->where('user_id', $currentId)
            ->where('friend_id', $userId)
            ->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'Demande introuvable.',
            ], 404);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Demande annulee.',
        ]);
    }

    public function retirer(Request $request, $userId)
    {
        $currentId = $request->user()->id;

        $deleted = Friendship::where('status', 'accepted')
            ->where(function ($q) use ($currentId, $userId) {
                $q->where('user_id', $currentId)->where('friend_id', $userId);
            })
            ->orWhere(function ($q) use ($currentId, $userId) {
                $q->where('user_id', $userId)->where('friend_id', $currentId);
            })
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Amitie introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ami retire.',
        ]);
    }
}