<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            StateSeeder::class,
            CitySeeder::class,
        ]);

        $this->call(CategorySeeder::class);
        $this->call(UserSeeder::class);
        $this->call(PublicationSeeder::class);
        $this->call(FollowerSeeder::class);
        $this->call(CollectionSeeder::class);
        $this->call(LikeSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(ReviewSeeder::class);
        $this->call(ConversationSeeder::class);
        $this->call(BlockSeeder::class);
        $this->call(NotificationSeeder::class);
    }
}
