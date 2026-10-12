<?php

namespace App\Services;

use App\Mail\PaymentReminderMail;
use App\Mail\PaymentValidatedMail;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Notifie un étudiant que son paiement est validé
     */
    public static function notifyPaymentValidated(User $user, $paiement, bool $sendEmail = true): void
    {
        $moisLabel = self::formatMoisLabel($paiement);

        // 1. Notification in-app
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'payment_validated',
                'title' => 'Paiement validé ✅',
                'message' => "Votre paiement de {$moisLabel} a été validé.",
                'url' => '/etudiant/paiements',
                'read' => false,
                'data' => [
                    'paiement_id' => $paiement->id,
                    'mois' => $paiement->mois,
                    'annee' => $paiement->annee,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création notification paiement validé', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }

        // 2. Email
        if ($sendEmail && $user->email) {
            try {
                Mail::to($user->email)->send(
                    new PaymentValidatedMail($user, $paiement)
                );
            } catch (\Exception $e) {
                Log::error('Erreur envoi email paiement validé', [
                    'user_id' => $user->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notifie un étudiant que son paiement a été rejeté
     */
    public static function notifyPaymentRejected(User $user, $paiement, ?string $motif = null): void
    {
        $moisLabel = self::formatMoisLabel($paiement);
        $motifText = $motif ? " Motif : {$motif}" : '';

        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'payment_rejected',
                'title' => 'Paiement rejeté ❌',
                'message' => "Votre paiement de {$moisLabel} a été rejeté.{$motifText}",
                'url' => '/etudiant/paiements',
                'read' => false,
                'data' => [
                    'paiement_id' => $paiement->id,
                    'motif' => $motif,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur notification paiement rejeté', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rappel : paiement à régler (appelé par le scheduler)
     */
    public static function notifyPaymentReminder(User $user, int $mois, int $annee, bool $sendEmail = true): void
    {
        $date = Carbon::createFromDate($annee, $mois, 1);
        $moisLabel = $date->translatedFormat('F Y');

        // 1. Notification in-app
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'payment_reminder',
                'title' => 'Rappel : écolage à régler',
                'message' => "Votre écolage de {$moisLabel} doit être réglé avant le 5. Pensez à téléverser votre preuve de paiement.",
                'url' => '/etudiant/paiements',
                'read' => false,
                'data' => [
                    'mois' => $mois,
                    'annee' => $annee,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur notification rappel', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }

        // 2. Email
        if ($sendEmail && $user->email) {
            try {
                Mail::to($user->email)->send(
                    new PaymentReminderMail($user, $mois, $annee)
                );
            } catch (\Exception $e) {
                Log::error('Erreur envoi email rappel', [
                    'user_id' => $user->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Helper : formate "Octobre 2026"
     */
    private static function formatMoisLabel($paiement): string
    {
        // Si accessor "mois_label" existe
        if (method_exists($paiement, 'getMoisLabelAttribute')) {
            return $paiement->mois_label;
        }

        // Sinon, construire manuellement
        $mois = (int) ($paiement->mois ?? 1);
        $annee = (int) ($paiement->annee ?? date('Y'));

        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return ($moisNoms[$mois] ?? "Mois {$mois}") . ' ' . $annee;
    }
}