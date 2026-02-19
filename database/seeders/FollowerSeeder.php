<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FollowerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $users->each(function ($user) use ($users) {
            $others = $users->where('id', '!=', $user->id);
            $toFollow = $others->random(rand(1, min(5, $others->count())));

            foreach ($toFollow as $target) {
                $status = $target->is_private && fake()->boolean(30)
                    ? 'pending'
                    : 'accepted';

                $user->following()->attach($target->id, ['status' => $status]);
            }
        });
    }
}
