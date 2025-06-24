<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PlanesSeeder extends Seeder
{
    public function run()
    {
        DB::table('planes')->insert([
            [
                'nombre' => 'Plan básico',
                'precio' => 0.00,
                'descripcion' => 'Solo permite la compra y venta de productos con anuncios.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nombre' => 'Cliente fiel',
                'precio' => 199.00,
                'descripcion' => 'Sin anuncios, chat de productos de intercambio, insignias y puntos acumulables según el 1% de tus compras.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
