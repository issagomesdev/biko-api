<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Ajudante geral',
            'Babá',
            'Barbeiro',
            'Cabeleireiro',
            'Carpinteiro',
            'Costureira / Ajustes',
            'Cuidador de idoso',
            'Diarista / Limpeza',
            'Eletricista',
            'Encanador',
            'Jardineiro',
            'Lavadeira / Passadeira',
            'Mecânico',
            'Motorista',
            'Pedreiro',
            'Pintor',
            'Personal trainer',
            'Reforço escolar',
            'Entregador / Motoboy',
            'Mudança / Frete',
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate([
                'slug' => Str::slug($category)
            ], [
                'name' => $category
            ]);
        }
    }
}
