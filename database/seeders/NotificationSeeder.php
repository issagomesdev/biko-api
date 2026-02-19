<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $publications = Publication::all();

        if ($users->count() < 2 || $publications->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // Like notifications
            $senders = $users->where('id', '!=', $user->id)->random(min(3, $users->count() - 1));
            foreach ($senders as $sender) {
                Notification::create([
                    'user_id' => $user->id,
                    'sender_id' => $sender->id,
                    'type' => Notification::TYPE_LIKE,
                    'publication_id' => $publications->random()->id,
                    'read_at' => fake()->optional(0.4)->dateTimeBetween('-7 days'),
                ]);
            }

            // Comment notifications
            $senders = $users->where('id', '!=', $user->id)->random(min(2, $users->count() - 1));
            foreach ($senders as $sender) {
                Notification::create([
                    'user_id' => $user->id,
                    'sender_id' => $sender->id,
                    'type' => Notification::TYPE_COMMENT,
                    'publication_id' => $publications->random()->id,
                    'read_at' => fake()->optional(0.3)->dateTimeBetween('-7 days'),
                ]);
            }

            // Follow notifications
            $senders = $users->where('id', '!=', $user->id)->random(min(2, $users->count() - 1));
            foreach ($senders as $sender) {
                Notification::create([
                    'user_id' => $user->id,
                    'sender_id' => $sender->id,
                    'type' => Notification::TYPE_FOLLOW,
                    'read_at' => fake()->optional(0.5)->dateTimeBetween('-7 days'),
                ]);
            }

            // Mention notifications
            if (fake()->boolean(50)) {
                $sender = $users->where('id', '!=', $user->id)->random();
                Notification::create([
                    'user_id' => $user->id,
                    'sender_id' => $sender->id,
                    'type' => Notification::TYPE_MENTION,
                    'publication_id' => $publications->random()->id,
                    'read_at' => fake()->optional(0.3)->dateTimeBetween('-7 days'),
                ]);
            }
        }
    }
}
