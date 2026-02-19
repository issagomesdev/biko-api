<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Seeder;

class PublicationSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::whereIn('id', range(3, 9))->pluck('id');
        $categoryIds = Category::pluck('id');

        Publication::factory()
            ->count(50)
            ->sequence(fn () => ['user_id' => $userIds->random()])
            ->create()
            ->each(function (Publication $pub) use ($categoryIds, $userIds) {
                $pub->categories()->attach(
                    $categoryIds->random(rand(2, 3))
                );

                // Adicionar menções aleatórias em ~30% das publicações
                if (rand(1, 100) <= 30) {
                    $mentionedIds = $userIds->except($pub->user_id)->random(rand(1, 3));
                    $pub->mentions()->attach($mentionedIds);
                }
            });
    }
}
