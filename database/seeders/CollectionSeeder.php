<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $publicationIds = Publication::pluck('id');

        $users->each(function (User $user) use ($publicationIds) {
            // Criar coleção default para cada usuário
            $default = $user->collections()->create([
                'name' => 'Salvos',
                'is_default' => true,
            ]);

            // Salvar 2-5 publicações aleatórias na default
            $default->publications()->attach(
                $publicationIds->random(rand(2, min(5, $publicationIds->count())))
            );

            // Criar 1-2 coleções extras para alguns usuários
            if ($user->id % 2 === 0) {
                $extra = Collection::factory()->create([
                    'user_id' => $user->id,
                    'name' => 'Favoritos',
                ]);

                $extra->publications()->attach(
                    $publicationIds->random(rand(1, min(3, $publicationIds->count())))
                );
            }
        });
    }
}
