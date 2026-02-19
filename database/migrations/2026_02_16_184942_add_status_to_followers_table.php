<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('followers', function (Blueprint $table) {
            $table->string('status', 10)->default('accepted')->after('followed_id');
        });

        DB::table('followers')->update(['status' => 'accepted']);

        Schema::table('followers', function (Blueprint $table) {
            $table->index(['followed_id', 'status']);
            $table->index(['follower_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followers', function (Blueprint $table) {
            $table->dropIndex(['followed_id', 'status']);
            $table->dropIndex(['follower_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
