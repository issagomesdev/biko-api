<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Publication;
use App\Models\Category;
use App\Models\User;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereIn('id', range(3, 9))->get();

        $categories = Category::all();

        Publication::factory()->count(100)->create()->each(function ($pub) use ($users, $categories) {

            $pub->user_id = $users->random()->id;
            $pub->save();

            $pub->categories()->attach(
                $categories->random(rand(2, 3))->pluck('id')->toArray()
            );
        });
    }
}

