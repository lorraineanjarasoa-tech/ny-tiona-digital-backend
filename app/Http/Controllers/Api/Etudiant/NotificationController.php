<?php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Liste des notifications de l'étudiant connecté.
     * GET /etudiant/notifications
     */
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 30);

        $notifications = Notification::forUser($request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $unreadCount = Notification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Compteur de notifications non lues.
     * GET /etudiant/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => $count,
        ]);
    }

    /**
     * Marquer une notification comme lue.
     * POST /etudiant/notifications/{id}/read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::forUser($request->user()->id)
            ->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification introuvable.',
            ], 404);
        }

        $notification->update(['read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue.',
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues.
     * POST /etudiant/notifications/read-all
     */
    public function markAllAsRead(Request $request)
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update(['read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications sont marquées comme lues.',
        ]);
    }

    /**
     * Supprimer une notification.
     * DELETE /etudiant/notifications/{id}
     */
    public function destroy(Request $request, $id)
    {
        $notification = Notification::forUser($request->user()->id)
            ->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification introuvable.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée.',
        ]);
    }
}