<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlockSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            ['user_id' => 1, 'blocked_user_id' => 5],
            ['user_id' => 3, 'blocked_user_id' => 7],
            ['user_id' => 6, 'blocked_user_id' => 2],
        ];

        foreach ($blocks as $block) {
            DB::table('blocks')->insertOrIgnore(array_merge($block, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
