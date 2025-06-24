<?php

namespace App\Console;
use App\Console\Commands\VerificarPlanesVencidos;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        //hace la cobrobacion cada dia
         //$schedule->command('verificar:planes-vencidos')->daily();
         //hace la comprobacion cada minuto 
          //$schedule->command('verificar:planes-vencidos')->everyTwoMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
        // VerificarPlanesVencidos::class,
        
    }
    /*protected $commands = [
        VerificarPlanesVencidos::class,
    ];*/


}
