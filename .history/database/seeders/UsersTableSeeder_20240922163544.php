<?php

namespace Database\Seeders;
use App\Models\User;
use App\Models\Category;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        User::factory()->count(10)->create()->each(function ($user) use ($categories) {
            // Verifica se há categorias para associar
            if ($categories->count() > 0) {
                // Associa 2 ou 3 categorias aleatórias a cada usuário
                $user->categories()->attach(
                    $categories->random(rand(2, 3))->pluck('id')->toArray()
                );
            }
        });
    }
}
