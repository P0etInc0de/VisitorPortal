<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallVisitorPortal extends Command
{
    protected $signature = 'visitorportal:install
        {--skip-migrations : Do not run database migrations}
        {--create-admin : Create an initial admin after syncing roles and permissions}';

    protected $description = 'Run the production-safe VisitorPortal installation bootstrap.';

    public function handle(): int
    {
        if (! $this->option('skip-migrations')) {
            $this->call('migrate', ['--force' => true]);
        }

        $syncExitCode = $this->call('visitorportal:sync-permissions');

        if ($syncExitCode !== self::SUCCESS) {
            return $syncExitCode;
        }

        if ($this->option('create-admin')) {
            return $this->call('visitorportal:create-admin');
        }

        $this->info('Installation bootstrap completed. Run php artisan visitorportal:create-admin to create the first admin.');

        return self::SUCCESS;
    }
}
