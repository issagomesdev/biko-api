<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Publication;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        
        Publication::factory()->count(100)->create()->each(function ($user) use ($categories) {
            if ($user->id >= 3 && $user->id <= 9 && $categories->count() > 0) {
                $user->categories()->attach(
                    $categories->random(rand(2, 3))->pluck('id')->toArray()
                );
            }
        });
    }
}
