<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\PlanVigencia;
use Carbon\Carbon;

class VerificarPlanesVencidos extends Command
{
    protected $signature = 'verificar:planes-vencidos';
    protected $description = 'Verifica si hay usuarios con plan vencido y los cambia a plan básico';

    public function handle()
    {
        // Buscar todos los registros con plan vencido
        $usuariosConPlanVencido = PlanVigencia::where('fecha_fin', '<', Carbon::now())->get();

        foreach ($usuariosConPlanVencido as $vigencia) {
            $user = $vigencia->user;

            // Si aún tiene plan 2, lo bajamos a plan 1
            if ($user && $user->plan_id == 2) {
                $user->plan_id = 1;
                $user->save();

                // Opcional: puedes eliminar la vigencia vencida si ya no la necesitas
                // $vigencia->delete();

                $this->info("Usuario {$user->email} degradado a plan básico.");
            }
        }

        return 0;
    }
}
