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
        if (! Schema::hasColumn('monitors', 'auto_generation_window_minutes')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->integer('auto_generation_window_minutes')->default(30);
            });
        }

        if (! Schema::hasColumn('monitors', 'background_source')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->string('background_source')->default('preset-1');
            });
        }

        if (! Schema::hasColumn('monitors', 'background_overlay_enabled')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->boolean('background_overlay_enabled')->default(false);
            });
        }

        if (! Schema::hasColumn('monitors', 'content_card_style')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->string('content_card_style')->default('transparent');
            });
        }

        DB::table('monitors')
            ->whereNull('auto_generation_window_minutes')
            ->update(['auto_generation_window_minutes' => 30]);

        DB::table('monitors')
            ->whereNull('background_source')
            ->update(['background_source' => 'preset-1']);

        DB::table('monitors')
            ->whereNull('content_card_style')
            ->update(['content_card_style' => 'transparent']);

        DB::table('monitors')
            ->whereNull('background_overlay_enabled')
            ->update(['background_overlay_enabled' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Compatibility migration for existing databases only.
    }
};
