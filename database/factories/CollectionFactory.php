<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement([
                'Favoritos', 'Para depois', 'Eletricistas', 'Encanadores',
                'Pintores', 'Pedreiros', 'Profissionais top', 'Urgentes',
            ]),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state([
            'name' => 'Salvos',
            'is_default' => true,
        ]);
    }
}
