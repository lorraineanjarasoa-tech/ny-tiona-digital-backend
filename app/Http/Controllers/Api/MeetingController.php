<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeetingController extends Controller
{
    /* ==============================================================
     * LISTE
     * GET /meetings
     * ============================================================== */
    public function index(Request $request)
    {
        try {
            $meetings = Meeting::where('created_by', $request->user()->id)
                ->orderBy('date_heure', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $meetings,
            ]);
        } catch (\Exception $e) {
            Log::error('MeetingController@index : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * CRÉER
     * POST /meetings
     * ============================================================== */
    public function store(Request $request)
    {
        try {
            // ✅ On valide UNIQUEMENT les 4 champs du formulaire
            $validated = $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_heure' => 'required|date',
                'lien_meeting' => 'required|url|max:500',
            ]);

            $meeting = Meeting::create([
                'created_by' => $request->user()->id,
                'titre' => $validated['titre'],
                'description' => $validated['description'] ?? null,
                'date_heure' => $validated['date_heure'],
                'lien_meeting' => $validated['lien_meeting'],
                'duree_minutes' => 60,
                'plateforme' => $this->detectPlateforme($validated['lien_meeting']),
                'max_participants' => 100,
                'statut' => 'planifie',   // ✅ Sans le "e" final
                'settings' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réunion créée avec succès.',
                'data' => $meeting,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('MeetingController@store : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * AFFICHER
     * GET /meetings/{id}
     * ============================================================== */
    public function show(Request $request, $id)
    {
        try {
            $meeting = Meeting::where('id', $id)
                ->where('created_by', $request->user()->id)
                ->first();

            if (!$meeting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Réunion introuvable.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $meeting,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * MODIFIER
     * PUT /meetings/{id}
     * ============================================================== */
    public function update(Request $request, $id)
    {
        try {
            $meeting = Meeting::where('id', $id)
                ->where('created_by', $request->user()->id)
                ->firstOrFail();

            // ✅ On valide UNIQUEMENT les 4 champs du formulaire
            $validated = $request->validate([
                'titre' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'date_heure' => 'sometimes|required|date',
                'lien_meeting' => 'sometimes|required|url|max:500',
            ]);

            // Si le lien change, on re-détecte la plateforme
            if (isset($validated['lien_meeting'])) {
                $validated['plateforme'] = $this->detectPlateforme($validated['lien_meeting']);
            }

            $meeting->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Réunion modifiée avec succès.',
                'data' => $meeting,
            ]);
        } catch (\Exception $e) {
            Log::error('MeetingController@update : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * SUPPRIMER
     * DELETE /meetings/{id}
     * ============================================================== */
    public function destroy(Request $request, $id)
    {
        try {
            $meeting = Meeting::where('id', $id)
                ->where('created_by', $request->user()->id)
                ->firstOrFail();

            $meeting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Réunion supprimée.',
            ]);
        } catch (\Exception $e) {
            Log::error('MeetingController@destroy : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * INVITER
     * POST /meetings/{id}/inviter
     * ============================================================== */
    public function inviter(Request $request, $id)
    {
        try {
            $meeting = Meeting::where('id', $id)
                ->where('created_by', $request->user()->id)
                ->firstOrFail();

            $validated = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitations envoyées.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
 * Retourne les reunions qui doivent etre notifiees :
 * - Reunions en cours
 * - Reunions qui commencent dans les prochaines 24h
 */
public function notifications(Request $request)
{
    try {
        $now = now();
        $in24h = now()->addHours(24);

        $meetings = Meeting::where('statut', '!=', 'annule')
            ->where('date_heure', '<=', $in24h)
            ->where('date_heure', '>=', $now->copy()->subMinutes(180)) // Pas plus de 3h de retard
            ->orderBy('date_heure')
            ->get();

        $items = $meetings->map(function ($m) use ($now) {
            $start = \Carbon\Carbon::parse($m->date_heure);
            $end = $start->copy()->addMinutes($m->duree_minutes ?? 60);

            $isLive = $now->between($start, $end);

            return [
                'id' => $m->id,
                'titre' => $m->titre,
                'description' => $m->description,
                'date_heure' => $m->date_heure,
                'duree_minutes' => $m->duree_minutes,
                'lien_meeting' => $m->lien_meeting,
                'audience' => $m->audience,
                'is_live' => $isLive,
                'starts_in_minutes' => $isLive ? 0 : $now->diffInMinutes($start, false),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    } catch (\Exception $e) {
        Log::error('MeetingController@notifications : ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'data' => [],
        ], 500);
    }
}

    /* ==============================================================
     * RÉPONDRE À UNE INVITATION
     * POST /meetings/{id}/repondre
     * ============================================================== */
    public function repondre(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'reponse' => 'required|in:accepte,refuse',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réponse enregistrée.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ==============================================================
     * HELPER : Détecter la plateforme depuis l'URL
     * ============================================================== */
    private function detectPlateforme($url)
    {
        $url = strtolower($url);
        if (str_contains($url, 'meet.google')) return 'google_meet';
        if (str_contains($url, 'zoom.us')) return 'zoom';
        if (str_contains($url, 'teams.microsoft')) return 'teams';
        if (str_contains($url, 'jitsi')) return 'jitsi';
        if (str_contains($url, 'whereby')) return 'whereby';
        return 'google_meet';
    }
}