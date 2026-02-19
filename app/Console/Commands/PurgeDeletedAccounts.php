<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

class PurgeDeletedAccounts extends Command
{
    protected $signature = 'accounts:purge-deleted';

    protected $description = 'Permanently delete user accounts that were soft-deleted more than 60 days ago';

    public function handle(UserService $service): int
    {
        $count = $service->permanentlyDeleteExpired();

        $this->info("Purged {$count} expired account(s).");

        return self::SUCCESS;
    }
}
