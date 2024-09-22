<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Publication>
 */
class PublicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    // Obtém todos os IDs de usuários existentes
    $existingUserIds = User::pluck('id')->toArray();

    // Filtra para incluir apenas IDs entre 3 e 9
    $filteredUserIds = array_filter($existingUserIds, function ($id) {
        return $id >= 3 && $id <= 9;
    });

    return [
        'title' => $this->faker->sentence,
        'text' => $this->faker->paragraph,
        'user_id' => $this->faker->randomElement($filteredUserIds) // Garante que o ID exista e esteja no intervalo
    ];
}
}
