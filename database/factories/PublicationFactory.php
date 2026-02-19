<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;

class PublicationFactory extends Factory
{
    protected $model = Publication::class;

    public function definition(): array
    {
        return [
            'text' => $this->faker->paragraph(3),
            'type' => $this->faker->randomElement([
                Publication::TYPE_CLIENT,
                Publication::TYPE_PROVIDER,
            ]),
            'city_id' => City::inRandomOrder()->value('id'),
        ];
    }

    public function client(): static
    {
        return $this->state(['type' => Publication::TYPE_CLIENT]);
    }

    public function provider(): static
    {
        return $this->state(['type' => Publication::TYPE_PROVIDER]);
    }
}
