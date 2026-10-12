<?php

namespace App\Console\Commands;

use App\Models\Inscription;
use App\Models\Notification;
use App\Models\Paiement;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders extends Command
{
    protected $signature = 'payments:send-reminders';
    protected $description = 'Envoie les rappels de paiement 3 jours avant la deadline (le 2 du mois)';

    public function handle(): int
    {
        $today = Carbon::now();
        $mois = (int) $today->month;
        $annee = (int) $today->year;

        $this->info("=== Envoi des rappels pour {$mois}/{$annee} ===");

        $inscriptions = Inscription::with(['etudiant', 'formation'])
            ->where('statut', 'valide')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($inscriptions as $inscription) {
            $etudiant = $inscription->etudiant;

            if (!$etudiant || !$etudiant->email) {
                $skipped++;
                continue;
            }

            // Déjà payé ce mois ?
            $dejaPaye = Paiement::where('etudiant_id', $etudiant->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->whereIn('statut', ['paye', 'en_attente'])
                ->exists();

            if ($dejaPaye) {
                $skipped++;
                continue;
            }

            // Rappel déjà envoyé ce mois ?
            $rappelExiste = Notification::where('user_id', $etudiant->id)
                ->where('type', 'payment_reminder')
                ->where('data->mois', $mois)
                ->where('data->annee', $annee)
                ->exists();

            if ($rappelExiste) {
                $skipped++;
                continue;
            }

            // Inscription active pour ce mois ?
            $dateDebut = $inscription->date_validation
                ?? $inscription->date_inscription
                ?? $inscription->created_at;

            if (!$dateDebut) {
                $skipped++;
                continue;
            }

            $start = Carbon::parse($dateDebut)->startOfMonth();
            if ($start->isAfter($today->copy()->startOfMonth())) {
                $skipped++;
                continue;
            }

            // Envoyer
            NotificationService::notifyPaymentReminder($etudiant, $mois, $annee, true);
            $sent++;

            $this->line("  → Rappel envoyé à {$etudiant->email}");
        }

        $this->info("Rappels envoyés : {$sent} | Ignorés : {$skipped}");

        Log::info('SendPaymentReminders terminé', [
            'sent' => $sent,
            'skipped' => $skipped,
            'mois' => $mois,
            'annee' => $annee,
        ]);

        return Command::SUCCESS;
    }
}