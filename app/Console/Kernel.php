<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Finalizar reservas automáticamente cada hora
        $schedule->command('reservas:finalizar-automaticamente')
                 ->hourly()
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/scheduled-reservas-finalizacion.log'));

        // También ejecutar una vez al final del día para garantizar completitud
        $schedule->command('reservas:finalizar-automaticamente')
                 ->dailyAt('23:30')
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/scheduled-reservas-finalizacion-daily.log'));

        // 🆕 LIMPIAR RESERVAS DE STOCK EXPIRADAS cada 15 minutos
        $schedule->command('reservations:clean-expired')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/scheduled-stock-reservations-cleanup.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
