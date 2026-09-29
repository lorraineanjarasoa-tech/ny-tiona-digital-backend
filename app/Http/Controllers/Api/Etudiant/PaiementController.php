<?php
// app/Http/Controllers/Api/Etudiant/PaiementController.php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaiementController extends Controller
{
    /**
     * Récupérer les paiements de l'étudiant
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Vérifier si la table existe
            if (!Schema::hasTable('paiements')) {
                return response()->json([
                    'has_late' => false,
                    'message' => 'Système de paiement en cours de configuration',
                    'current_paid' => false,
                    'history' => []
                ]);
            }

            $payments = Paiement::where('etudiant_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Vérifier le statut du paiement du mois en cours
            $now = Carbon::now();
            $currentMonth = $now->month;
            $currentYear = $now->year;
            
            $currentPayment = Paiement::where('etudiant_id', $user->id)
                ->where('mois', $currentMonth)
                ->where('annee', $currentYear)
                ->where('statut', 'paye')
                ->first();

            $hasLate = false;
            $message = '';

            // Vérifier si le paiement est en retard (entre le 1er et le 10 du mois)
            $day = $now->day;
            if ($day >= 1 && $day <= 10) {
                if (!$currentPayment) {
                    $hasLate = true;
                    $message = 'Votre paiement du mois ' . $now->translatedFormat('F') . ' est dû. Veuillez effectuer le paiement avant le 10 du mois.';
                }
            }

            return response()->json([
                'has_late' => $hasLate,
                'message' => $message,
                'current_paid' => (bool) $currentPayment,
                'history' => $payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'mois' => Carbon::create($payment->annee, $payment->mois, 1)->translatedFormat('F Y'),
                        'montant' => number_format($payment->montant, 0, ',', ' ') . ' Ar',
                        'date_paiement' => $payment->date_paiement ? $payment->date_paiement->format('d/m/Y') : null,
                        'statut' => $payment->statut,
                        'reference' => $payment->reference,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération paiements: ' . $e->getMessage());
            return response()->json([
                'has_late' => false,
                'message' => 'Erreur lors du chargement des paiements',
                'current_paid' => false,
                'history' => []
            ]);
        }
    }

    /**
     * Vérifier le statut du paiement (pour la navbar)
     */
    public function statut(Request $request)
    {
        try {
            $user = $request->user();
            
            // Vérifier si la table existe
            if (!Schema::hasTable('paiements')) {
                return response()->json([
                    'has_late' => false,
                    'notifications' => 0
                ]);
            }

            $now = Carbon::now();
            $currentMonth = $now->month;
            $currentYear = $now->year;
            
            $currentPayment = Paiement::where('etudiant_id', $user->id)
                ->where('mois', $currentMonth)
                ->where('annee', $currentYear)
                ->where('statut', 'paye')
                ->first();

            $hasLate = false;
            $day = $now->day;
            
            if ($day >= 1 && $day <= 10 && !$currentPayment) {
                $hasLate = true;
            }

            // Compter les notifications
            $notifications = $hasLate ? 1 : 0;

            return response()->json([
                'has_late' => $hasLate,
                'notifications' => $notifications,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur statut paiement: ' . $e->getMessage());
            return response()->json([
                'has_late' => false,
                'notifications' => 0
            ]);
        }
    }

    /**
     * Effectuer un paiement
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'montant' => 'required|numeric|min:0',
                'reference' => 'nullable|string|max:255',
                'date_paiement' => 'required|date',
            ]);

            $user = $request->user();
            $now = Carbon::now();

            // Vérifier si le paiement du mois existe déjà
            $existing = Paiement::where('etudiant_id', $user->id)
                ->where('mois', $now->month)
                ->where('annee', $now->year)
                ->first();

            if ($existing && $existing->statut === 'paye') {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez déjà payé pour ce mois.'
                ], 400);
            }

            $payment = Paiement::create([
                'etudiant_id' => $user->id,
                'mois' => $now->month,
                'annee' => $now->year,
                'montant' => $validated['montant'],
                'date_paiement' => $validated['date_paiement'],
                'statut' => 'paye',
                'reference' => $validated['reference'] ?? 'PAY-' . strtoupper(uniqid()),
            ]);

            Log::info('Nouveau paiement enregistré', [
                'user_id' => $user->id,
                'montant' => $validated['montant'],
                'reference' => $payment->reference
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès.',
                'data' => $payment
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur paiement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du paiement: ' . $e->getMessage()
            ], 500);
        }
    }
}