<?php

namespace App\Console\Commands;

use App\Support\VisitorPortalPermissions;
use Illuminate\Console\Command;

class SyncVisitorPortalPermissions extends Command
{
    protected $signature = 'visitorportal:sync-permissions';

    protected $description = 'Synchronize production-safe VisitorPortal roles and permissions.';

    public function handle(): int
    {
        VisitorPortalPermissions::sync($this);

        return self::SUCCESS;
    }
}
