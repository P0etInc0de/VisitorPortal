<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('monitor_slides', 'image_path')) {
            Schema::table('monitor_slides', function (Blueprint $table) {
                $table->string('image_path')->nullable()->default(null);
            });
        }

        if (! Schema::hasColumn('monitor_slides', 'background_source')) {
            Schema::table('monitor_slides', function (Blueprint $table) {
                $table->string('background_source')->nullable()->default(null);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Compatibility migration for existing databases only.
    }
};
