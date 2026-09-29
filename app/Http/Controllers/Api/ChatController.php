<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Liste des conversations (interlocuteurs distincts).
     * GET /chat/conversations
     */
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $messages = Message::where('expediteur_id', $userId)
            ->orWhere('destinataire_id', $userId)
            ->with(['expediteur.profile', 'destinataire.profile'])
            ->latest()
            ->get();

        $conversations = [];
        $seen = [];

        foreach ($messages as $message) {
            $otherId = $message->expediteur_id === $userId
                ? $message->destinataire_id
                : $message->expediteur_id;

            if (isset($seen[$otherId])) {
                continue;
            }

            $seen[$otherId] = true;

            $other = $message->expediteur_id === $userId
                ? $message->destinataire
                : $message->expediteur;

            $unreadCount = Message::where('expediteur_id', $otherId)
                ->where('destinataire_id', $userId)
                ->where('lu', false)
                ->count();

            $conversations[] = [
                'id' => $otherId,
                'user_id' => $otherId,
                'user' => $other,
                'last_message' => $message->contenu,
                'last_message_at' => $message->created_at,
                'unread_count' => $unreadCount,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * Messages entre l'utilisateur connecte et un autre utilisateur.
     * GET /chat/messages/{userId}
     */
    public function messages(Request $request, $userId)
    {
        $currentUserId = $request->user()->id;

        $messages = Message::where(function ($query) use ($currentUserId, $userId) {
                $query->where('expediteur_id', $currentUserId)
                      ->where('destinataire_id', $userId);
            })
            ->orWhere(function ($query) use ($currentUserId, $userId) {
                $query->where('expediteur_id', $userId)
                      ->where('destinataire_id', $currentUserId);
            })
            ->orderBy('created_at')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'sender_id' => $m->expediteur_id,
                    'receiver_id' => $m->destinataire_id,
                    'content' => $m->contenu,
                    'read' => (bool) $m->lu,
                    'created_at' => $m->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Envoyer un message.
     * POST /chat/send
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'destinataire_id' => 'required|exists:users,id',
            'contenu' => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'expediteur_id' => $request->user()->id,
            'destinataire_id' => $validated['destinataire_id'],
            'contenu' => $validated['contenu'],
            'lu' => false,
        ]);

        // Notification pour le destinataire
        $senderName = $request->user()->profile?->nom_complet
            ?? $request->user()->email;

        Notification::notify(
            $validated['destinataire_id'],
            'message',
            'Nouveau message',
            $senderName . ' vous a envoye un message.',
            '/etudiant/chat',
            ['sender_id' => $request->user()->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Message envoye avec succes.',
            'data' => [
                'id' => $message->id,
                'sender_id' => $message->expediteur_id,
                'receiver_id' => $message->destinataire_id,
                'content' => $message->contenu,
                'read' => false,
                'created_at' => $message->created_at,
            ],
        ], 201);
    }

    /**
 * Marquer tous les messages d'un utilisateur comme lus.
 * PUT /chat/message/{userId}/read
 *
 * ⚠️ Le paramètre est un USER ID, pas un MESSAGE ID.
 */
public function markAsRead(Request $request, $userId)
{
    try {
        $currentUserId = $request->user()->id;

        // Marquer tous les messages envoyés par $userId et reçus par l'utilisateur connecté
        $updated = Message::where('expediteur_id', $userId)
            ->where('destinataire_id', $currentUserId)
            ->where('lu', false)
            ->update([
                'lu' => true,
                // ⚠️ Décommente cette ligne UNIQUEMENT si ta colonne lu_at existe :
                // 'lu_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Messages marqués comme lus.',
            'count'   => $updated,
        ]);

    } catch (\Exception $e) {
        \Log::error('markAsRead error: ' . $e->getMessage(), [
            'user_id' => $userId,
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du marquage : ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * Compteur de messages non lus.
     * GET /chat/non-lus
     */
    public function messagesNonLus(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['success' => true, 'data' => 0]);
            }

            $count = Message::where('destinataire_id', $user->id)
                ->where('lu', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $count,
            ]);
        } catch (\Exception $e) {
            \Log::error('messagesNonLus error: ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'data' => 0,
            ]);
        }
    }
}