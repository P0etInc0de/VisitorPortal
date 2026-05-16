<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $default = 'title_first_initial_last_name';
        $previousDefault = 'title_first_name_last_initial';

        if (! Schema::hasColumn('monitors', 'monitor_display_mode')) {
            Schema::table('monitors', function (Blueprint $table) use ($default) {
                $table->string('monitor_display_mode')->default($default)->after('auto_generation_window_minutes');
            });
        }

        if (! Schema::hasColumn('monitor_slides', 'monitor_display_mode')) {
            Schema::table('monitor_slides', function (Blueprint $table) use ($default) {
                $table->string('monitor_display_mode')->default($default)->after('is_auto_generated');
            });
        }

        DB::table('monitors')
            ->whereNull('monitor_display_mode')
            ->orWhere('monitor_display_mode', $previousDefault)
            ->update(['monitor_display_mode' => $default]);

        DB::table('monitor_slides')
            ->whereNull('monitor_display_mode')
            ->orWhere('monitor_display_mode', $previousDefault)
            ->update(['monitor_display_mode' => $default]);
    }

    public function down(): void
    {
        // Compatibility migration for databases that already ran the site-support migration.
    }
};
