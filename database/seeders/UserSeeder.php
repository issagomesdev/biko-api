<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        User::factory()->count(10)->create()->each(function ($user) use ($categories) {
            if ($user->id >= 3 && $user->id <= 9 && $categories->count() > 0) {
                $user->categories()->attach(
                    $categories->random(rand(2, 3))->pluck('id')->toArray()
                );
            }
        });
    }
}
