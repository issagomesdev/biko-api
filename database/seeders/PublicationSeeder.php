<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Seeder;

class PublicationSeeder extends Seeder
{
    private array $tagPool = [
        'urgente', 'serviço', 'profissional', 'qualidade', 'resultado',
        'reformas', 'instalação', 'manutenção', 'residencial', 'comercial',
        'rápido', 'confiável', 'barato', 'orçamento', 'experiência',
        'atendimento', 'garantia', 'pontual', 'material incluído', 'parcelas',
    ];

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

                // Tags em ~70% das publicações
                if (rand(1, 100) <= 70) {
                    $tags = collect($this->tagPool)->shuffle()->take(rand(1, 3));
                    $pub->tags()->createMany($tags->map(fn ($t) => ['tag' => $t])->all());
                }

                // Menções aleatórias em ~30% das publicações
                if (rand(1, 100) <= 30) {
                    $mentionedIds = $userIds->except($pub->user_id)->random(rand(1, 3));
                    $pub->mentions()->attach($mentionedIds);
                }
            });
    }
}
