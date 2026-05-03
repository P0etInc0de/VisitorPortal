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
        if (! Schema::hasColumn('monitor_slides', 'is_active')) {
            Schema::table('monitor_slides', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }

        if (! Schema::hasColumn('monitors', 'header_text_is_light')) {
            Schema::table('monitors', function (Blueprint $table) {
                $table->boolean('header_text_is_light')->default(false);
            });
        }

        DB::table('monitor_slides')
            ->whereNull('is_active')
            ->update(['is_active' => true]);

        DB::table('monitors')
            ->whereNull('header_text_is_light')
            ->update(['header_text_is_light' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Compatibility migration for existing databases only.
    }
};
