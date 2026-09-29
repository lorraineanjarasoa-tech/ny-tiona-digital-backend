<?php
// app/Console/Commands/FermerVaguesAutomatiquement.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FermerVaguesAutomatiquement extends Command
{
    protected $signature = 'vagues:fermer-auto';
    protected $description = 'Ferme automatiquement les vagues complètes ou expirées';

    public function handle()
    {
        $this->info('Vérification des vagues à fermer...');

        $today = now()->toDateString();

        $vaguesOuvertes = DB::table('vagues')
            ->where('statut', 'ouverte')
            ->get();

        $totalTerminees = 0;

        foreach ($vaguesOuvertes as $vague) {
            $raison = null;

            // 1. Date de fin dépassée
            if ($vague->date_fin && $vague->date_fin < $today) {
                $raison = 'Date de fin dépassée';
            }

            // 2. Capacité atteinte
            $nbInscrits = DB::table('inscriptions')
                ->where('vague_id', $vague->id)
                ->where('statut', 'valide')
                ->count();

            $capacite = (int) ($vague->capacite ?? 0);

            if ($nbInscrits >= $capacite && $capacite > 0) {
                $raison = 'Capacité atteinte';
            }

            if ($raison) {
                DB::table('vagues')
                    ->where('id', $vague->id)
                    ->update([
                        'statut' => 'terminee',  // ✅ Utiliser "terminee"
                        'updated_at' => now(),
                    ]);

                $this->line("✓ Vague {$vague->vague} terminée ({$raison})");

                Log::info('Vague terminée automatiquement', [
                    'vague_id' => $vague->id,
                    'raison' => $raison,
                ]);

                $totalTerminees++;
            }
        }

        $this->info("Total : {$totalTerminees} vague(s) terminée(s).");

        return Command::SUCCESS;
    }
}