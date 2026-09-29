<?php

namespace App\Http\Controllers;

use App\Models\Vague;
use App\Models\Formation;

class PublicController extends Controller
{
    /**
     * Liste des vagues ouvertes (endpoint public).
     * Utilisé par le formulaire d'inscription.
     */
    public function vaguesOuvertes()
    {
        $vagues = Vague::with(['formation'])
            ->where('statut', 'ouverte')
            ->where(function ($query) {
                $query->whereNull('date_fin')
                      ->orWhere('date_fin', '>=', now()->toDateString());
            })
            ->orderBy('vague', 'asc')
            ->get()
            ->map(function ($vague) {
                return [
                    'id' => $vague->id,
                    'vague' => $vague->vague,
                    'formation_id' => $vague->formation_id,
                    'formation_titre' => $vague->formation?->titre
                        ?? $vague->formation?->nom
                        ?? 'Formation #' . $vague->formation_id,
                    'date_debut' => $vague->date_debut,
                    'date_fin' => $vague->date_fin,
                    'capacite' => $vague->capacite,
                    'statut' => $vague->statut,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $vagues,
        ]);
    }

    /**
     * Liste publique des formations (fallback).
     */
    public function formationsPubliques()
    {
        $formations = Formation::orderBy('titre', 'asc')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'titre' => $f->titre ?? $f->nom ?? 'Formation sans titre',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $formations,
        ]);
    }
}