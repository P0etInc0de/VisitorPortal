<?php

namespace Tests\Unit;

use Tests\TestCase;

class DockerEntrypointTest extends TestCase
{
    public function test_entrypoint_waits_for_mysql_and_mariadb_connections(): void
    {
        $script = file_get_contents(dirname(base_path()).'/docker/app/entrypoint.sh');

        $this->assertIsString($script);
        $this->assertStringContainsString('case "${DB_CONNECTION:-mysql}" in', $script);
        $this->assertStringContainsString('mysql|mariadb)', $script);
        $this->assertStringContainsString('skipping MySQL/MariaDB wait', $script);
    }
}
