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
        $fallbackHeading = config('branding.monitor_fallback_heading', 'Welcome to VisitorPortal');
        $fallbackSubheading = config('branding.monitor_fallback_subheading', "We're glad you're here.");

        if (! Schema::hasColumn('monitors', 'fallback_heading')) {
            Schema::table('monitors', function (Blueprint $table) use ($fallbackHeading) {
                $table->string('fallback_heading')->default($fallbackHeading);
            });
        }

        if (! Schema::hasColumn('monitors', 'fallback_subheading')) {
            Schema::table('monitors', function (Blueprint $table) use ($fallbackSubheading) {
                $table->string('fallback_subheading')->default($fallbackSubheading);
            });
        }

        if (! Schema::hasColumn('monitors', 'fallback_show_logo')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->boolean('fallback_show_logo')->default(true);
            });
        }

        if (! Schema::hasColumn('monitors', 'fallback_show_date')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->boolean('fallback_show_date')->default(true);
            });
        }

        if (! Schema::hasColumn('monitors', 'fallback_image_path')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->string('fallback_image_path')->nullable()->default(null);
            });
        }

        if (! Schema::hasColumn('monitors', 'fallback_background_source')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->string('fallback_background_source')->nullable()->default(null);
            });
        }

        DB::table('monitors')
            ->whereNull('fallback_heading')
            ->update(['fallback_heading' => $fallbackHeading]);

        DB::table('monitors')
            ->whereNull('fallback_subheading')
            ->update(['fallback_subheading' => $fallbackSubheading]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('monitors', 'fallback_heading') ? 'fallback_heading' : null,
            Schema::hasColumn('monitors', 'fallback_subheading') ? 'fallback_subheading' : null,
            Schema::hasColumn('monitors', 'fallback_show_logo') ? 'fallback_show_logo' : null,
            Schema::hasColumn('monitors', 'fallback_show_date') ? 'fallback_show_date' : null,
            Schema::hasColumn('monitors', 'fallback_image_path') ? 'fallback_image_path' : null,
            Schema::hasColumn('monitors', 'fallback_background_source') ? 'fallback_background_source' : null,
        ]));

        if ($columns !== []) {
            Schema::table('monitors', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
