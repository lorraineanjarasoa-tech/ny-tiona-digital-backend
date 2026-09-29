<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeetingController extends Controller
{
    /**
     * Liste des meetings
     */
    public function index(Request $request)
    {
        try {
            $meetings = Meeting::latest()->get();

            return response()->json([
                'success' => true,
                'data' => $meetings,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur liste meetings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des meetings.'
            ], 500);
        }
    }

    /**
     * Créer un meeting
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_heure' => 'required|date',
                'duree_minutes' => 'nullable|integer|min:15',
                'lien_meeting' => 'nullable|url',
                'plateforme' => 'nullable|string|in:google_meet,zoom,jitsi',
                'max_participants' => 'nullable|integer|min:1',
                'statut' => 'nullable|in:planifie,en_cours,termine,annule',
            ]);

            // Récupérer les colonnes de la table
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('meetings');
            
            // Construire les données
            $data = [
                'createur_id' => $request->user()->id,
            ];

            // Mapper les champs du formulaire vers les colonnes de la base
            $fieldMapping = [
                'titre' => ['titre', 'title'],
                'description' => ['description'],
                'date_heure' => ['date_heure', 'date_debut', 'scheduled_at'],
                'duree_minutes' => ['duree_minutes', 'duration_minutes'],
                'lien_meeting' => ['lien_meeting', 'meeting_url'],
                'plateforme' => ['plateforme', 'platform'],
                'max_participants' => ['max_participants', 'participants_max'],
                'statut' => ['statut', 'status'],
            ];

            // Mapper les champs
            foreach ($fieldMapping as $formField => $dbFields) {
                if (isset($validated[$formField])) {
                    foreach ($dbFields as $dbField) {
                        if (in_array($dbField, $columns)) {
                            $data[$dbField] = $validated[$formField];
                            break;
                        }
                    }
                }
            }

            // Si date_heure n'a pas été mappé mais qu'il y a date_debut
            if (isset($validated['date_heure']) && !isset($data['date_heure']) && in_array('date_debut', $columns)) {
                $data['date_debut'] = $validated['date_heure'];
            }

            Log::info('Création meeting avec les données:', $data);

            $meeting = Meeting::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Meeting créé avec succès.',
                'data' => $meeting,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur création meeting: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du meeting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un meeting
     */
    public function show($id)
    {
        try {
            $meeting = Meeting::with('createur')->find($id);

            if (!$meeting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting introuvable.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $meeting,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur affichage meeting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du meeting.'
            ], 500);
        }
    }

    /**
     * Modifier un meeting
     */
    public function update(Request $request, $id)
    {
        try {
            $meeting = Meeting::find($id);

            if (!$meeting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting introuvable.',
                ], 404);
            }

            $validated = $request->validate([
                'titre' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'date_heure' => 'sometimes|required|date',
                'duree_minutes' => 'nullable|integer|min:15',
                'lien_meeting' => 'nullable|url',
                'plateforme' => 'nullable|string|in:google_meet,zoom,jitsi',
                'max_participants' => 'nullable|integer|min:1',
                'statut' => 'nullable|in:planifie,en_cours,termine,annule',
            ]);

            // Mapper les champs pour la mise à jour
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('meetings');
            
            $data = [];
            
            $fieldMapping = [
                'titre' => ['titre', 'title'],
                'description' => ['description'],
                'date_heure' => ['date_heure', 'date_debut', 'scheduled_at'],
                'duree_minutes' => ['duree_minutes', 'duration_minutes'],
                'lien_meeting' => ['lien_meeting', 'meeting_url'],
                'plateforme' => ['plateforme', 'platform'],
                'max_participants' => ['max_participants', 'participants_max'],
                'statut' => ['statut', 'status'],
            ];

            foreach ($fieldMapping as $formField => $dbFields) {
                if (isset($validated[$formField])) {
                    foreach ($dbFields as $dbField) {
                        if (in_array($dbField, $columns)) {
                            $data[$dbField] = $validated[$formField];
                            break;
                        }
                    }
                }
            }

            $meeting->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Meeting modifié avec succès.',
                'data' => $meeting,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur modification meeting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du meeting.'
            ], 500);
        }
    }

    /**
     * Supprimer un meeting
     */
    public function destroy($id)
    {
        try {
            $meeting = Meeting::find($id);

            if (!$meeting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting introuvable.',
                ], 404);
            }

            $meeting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Meeting supprimé avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur suppression meeting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du meeting.'
            ], 500);
        }
    }

    /**
     * Inviter des participants
     */
    public function inviter(Request $request, $id)
    {
        try {
            $meeting = Meeting::find($id);

            if (!$meeting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting introuvable.',
                ], 404);
            }

            $validated = $request->validate([
                'participants' => 'required|array',
                'participants.*' => 'exists:users,id',
            ]);

            // Logique d'invitation à implémenter
            // $meeting->participants()->attach($validated['participants']);

            return response()->json([
                'success' => true,
                'message' => 'Invitations envoyées avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur invitation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des invitations.'
            ], 500);
        }
    }

    /**
     * Répondre à une invitation
     */
    public function repondreInvitation(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'reponse' => 'required|in:accepte,refuse,peut_etre',
            ]);

            // Logique de réponse à implémenter
            // $meeting = Meeting::find($id);
            // $meeting->participants()->updateExistingPivot(auth()->id(), ['reponse' => $validated['reponse']]);

            return response()->json([
                'success' => true,
                'message' => 'Réponse enregistrée avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur réponse invitation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la réponse.'
            ], 500);
        }
    }
}