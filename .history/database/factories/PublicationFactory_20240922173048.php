<?php

namespace Database\Factories;
use App\Models\Users;

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
        $existingUserIds = User::pluck('id')->toArray();

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
