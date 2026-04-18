<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Checks if the MySQL connection is established and migrations have run.
     * Prevents a failure cascade in case of database issues.
     */
    public function test_mysql_connection_and_migrations_are_ready(): void
    {
        try {
            // Check if the database connection is working
            DB::connection()->getPdo();

            // Check if the migrations table exists to ensure a migrated schema
            $this->assertTrue(
                Schema::hasTable('migrations'),
                "MySQL is running, but tables are missing! Did you forget to migrate?"
            );
        } catch (\Exception $e) {
            $this->fail("MySQL connection error: " . $e->getMessage());
        }
    }
}
