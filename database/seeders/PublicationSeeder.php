<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Publication;
use App\Models\Category;
use App\Models\User;

class PublicationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('id', range(3, 9))->get();
        $categories = Category::all();

        foreach (range(1, 100) as $index) {
            $userId = $users->random()->id;
            $pub = Publication::factory()->create([
                'user_id' => $userId,
            ]);

            $pub->categories()->attach(
                $categories->random(rand(2, 3))->pluck('id')->toArray()
            );
        }
    }
}
