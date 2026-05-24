<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit;

use Tests\TestCase;

class DockerEntrypointTest extends TestCase
{
    public function test_entrypoint_waits_for_mysql_and_mariadb_connections(): void
    {
        $script = file_get_contents(dirname(base_path()).'/docker/app/entrypoint.sh');

        $this->assertIsString($script);
        $this->assertStringContainsString('case "${DB_CONNECTION:-mariadb}" in', $script);
        $this->assertStringContainsString('mysql|mariadb)', $script);
        $this->assertStringContainsString('skipping MySQL/MariaDB wait', $script);
    }
}
