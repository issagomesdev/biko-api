<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 2) {
            return;
        }

        // Create conversations between random pairs
        $pairs = $users->random(min(6, $users->count()))->pluck('id')->all();

        for ($i = 0; $i < count($pairs) - 1; $i++) {
            for ($j = $i + 1; $j < count($pairs) && $j <= $i + 2; $j++) {
                [$min, $max] = [min($pairs[$i], $pairs[$j]), max($pairs[$i], $pairs[$j])];

                $conversation = Conversation::firstOrCreate([
                    'user_one_id' => $min,
                    'user_two_id' => $max,
                ]);

                // Add 3-8 messages per conversation
                $messageCount = fake()->numberBetween(3, 8);
                $participants = [$min, $max];

                for ($m = 0; $m < $messageCount; $m++) {
                    $conversation->messages()->create([
                        'sender_id' => $participants[array_rand($participants)],
                        'body' => fake()->realText(200),
                        'read_at' => fake()->optional(0.6)->dateTimeBetween('-2 days'),
                        'created_at' => fake()->dateTimeBetween('-7 days'),
                    ]);
                }
            }
        }
    }
}
