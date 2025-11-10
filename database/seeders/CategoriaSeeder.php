<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Hogar',
            'Deportes',
            'Belleza',
            'Juguetes',
            'Electrónicos',
            'Moda',
        ];

        foreach ($categorias as $nombre) {
            DB::table('categorias')->insert([
                'nombre' => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

