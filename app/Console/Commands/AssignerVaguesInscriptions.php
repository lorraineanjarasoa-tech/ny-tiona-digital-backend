<?php
// app/Console/Commands/AssignerVaguesInscriptions.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignerVaguesInscriptions extends Command
{
    protected $signature = 'inscriptions:assigner-vagues';
    protected $description = 'Assigne automatiquement les vagues aux inscriptions validées sans vague_id';

    public function handle()
    {
        $this->info('Assignation des vagues aux inscriptions...');

        $inscriptions = DB::table('inscriptions')
            ->where('statut', 'valide')
            ->whereNull('vague_id')
            ->get();

        if ($inscriptions->isEmpty()) {
            $this->info('Aucune inscription à assigner.');
            return Command::SUCCESS;
        }

        $total = 0;
        $sansVague = 0;

        foreach ($inscriptions as $inscription) {
            $vague = DB::table('vagues')
                ->where('formation_id', $inscription->formation_id)
                ->where('statut', 'ouverte')
                ->orderBy('vague')
                ->first();

            if ($vague) {
                DB::table('inscriptions')
                    ->where('id', $inscription->id)
                    ->update(['vague_id' => $vague->id]);

                $this->line("✓ Inscription #{$inscription->id} → Vague {$vague->vague}");
                $total++;
            } else {
                $this->warn("⚠ Aucune vague ouverte pour l'inscription #{$inscription->id}");
                $sansVague++;
            }
        }

        $this->info("Total : {$total} inscription(s) assignée(s).");
        if ($sansVague > 0) {
            $this->warn("Inscriptions sans vague : {$sansVague}");
        }

        return Command::SUCCESS;
    }
}