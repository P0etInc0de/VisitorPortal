<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultSiteId = $this->defaultSiteId();

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->restrictOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->restrictOnDelete();
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->restrictOnDelete();
            $table->boolean('is_confidential')->default(false)->after('status');
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->restrictOnDelete();
            $table->string('monitor_display_mode')->default('title_first_initial_last_name')->after('auto_generation_window_minutes');
        });

        Schema::table('monitor_slides', function (Blueprint $table) {
            $table->string('monitor_display_mode')->default('title_first_initial_last_name')->after('is_auto_generated');
        });

        Schema::table('recurring_visit_series', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->restrictOnDelete();
            $table->boolean('is_confidential')->default(false)->after('status');
        });

        foreach (['departments', 'users', 'visits', 'monitors', 'recurring_visit_series'] as $table) {
            DB::table($table)->whereNull('site_id')->update(['site_id' => $defaultSiteId]);
            $this->makeSiteIdRequired($table);
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_name_unique');
            $table->unique(['site_id', 'name'], 'departments_site_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_site_name_unique');
        });

        Schema::table('monitor_slides', function (Blueprint $table) {
            $table->dropColumn('monitor_display_mode');
        });

        foreach (['recurring_visit_series', 'monitors', 'visits', 'users', 'departments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if ($tableName === 'visits') {
                    $table->dropColumn('is_confidential');
                }

                if ($tableName === 'monitors') {
                    $table->dropColumn('monitor_display_mode');
                }

                if ($tableName === 'recurring_visit_series') {
                    $table->dropColumn('is_confidential');
                }

                $table->dropConstrainedForeignId('site_id');
            });
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->unique('name', 'departments_name_unique');
        });
    }

    private function defaultSiteId(): int
    {
        $existingId = DB::table('sites')->where('slug', 'default')->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) DB::table('sites')->insertGetId([
            'name' => 'Default Site',
            'slug' => 'default',
            'timezone' => 'Europe/Berlin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSiteIdRequired(string $table): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `{$table}` MODIFY `site_id` BIGINT UNSIGNED NOT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN site_id SET NOT NULL");
        }
    }
};
