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
        if (! Schema::hasColumn('monitor_slides', 'is_auto_generated')) {
            Schema::table('monitor_slides', function (Blueprint $table) {
                $table->boolean('is_auto_generated')->default(false)->after('is_active');
            });
        }

        DB::table('monitor_slides')
            ->whereNull('is_auto_generated')
            ->update(['is_auto_generated' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Compatibility migration for existing databases only.
    }
};
