<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 2) {
            return;
        }

        foreach ($users as $user) {
            $reviewers = $users->where('id', '!=', $user->id)->random(min(3, $users->count() - 1));

            foreach ($reviewers as $reviewer) {
                $review = Review::create([
                    'user_id' => $user->id,
                    'reviewer_id' => $reviewer->id,
                    'stars' => fake()->numberBetween(1, 5),
                    'comment' => fake()->realText(200),
                ]);

                // Add 0-2 replies per review
                $repliers = $users->where('id', '!=', $reviewer->id)->random(min(fake()->numberBetween(0, 2), $users->count() - 1));
                foreach ($repliers as $replier) {
                    Review::create([
                        'user_id' => $user->id,
                        'reviewer_id' => $replier->id,
                        'comment' => fake()->realText(150),
                        'parent_id' => $review->id,
                    ]);
                }
            }
        }
    }
}
