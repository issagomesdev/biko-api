<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Publication;
use App\Models\Category;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        
        Publication::factory()->count(100)->create()->each(function ($pub) use ($categories) {
            $pub->categories()->attach(
                $categories->random(rand(2, 3))->pluck('id')->toArray()
            );
        });
    }
}
