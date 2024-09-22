<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Publication;
use App\Models\Category;
use App\Models\User;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtenha os usuários com IDs de 3 a 9
        $users = User::whereIn('id', range(3, 9))->get();

        // Obtenha todas as categorias existentes
        $categories = Category::all();

        // Crie 100 publicações
        Publication::factory()->count(100)->create()->each(function ($pub) use ($users, $categories) {
            // Atribua um user_id aleatório entre os usuários filtrados
            $pub->user_id = $users->random()->id;
            $pub->save();

            // Anexe categorias aleatórias
            $pub->categories()->attach(
                $categories->random(rand(2, 3))->pluck('id')->toArray()
            );
        });
    }
}

