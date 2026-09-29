// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Fermer les vagues automatiquement toutes les heures
    $schedule->command('vagues:fermer-auto')->hourly();

    // Ou tous les jours à minuit
    // $schedule->command('vagues:fermer-auto')->daily();
}