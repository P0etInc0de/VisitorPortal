<?php

use App\Models\Monitor;
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
        Schema::table('monitors', function (Blueprint $table) {
            $table->integer('auto_generation_window_minutes')->default(Monitor::DEFAULT_AUTO_GENERATION_WINDOW_MINUTES);
            $table->string('background_source')->default(Monitor::DEFAULT_BACKGROUND_SOURCE);
            $table->boolean('background_overlay_enabled')->default(false);
            $table->string('content_card_style')->default(Monitor::DEFAULT_CONTENT_CARD_STYLE);
        });

        DB::table('monitors')->update([
            'auto_generation' => true,
            'auto_generation_window_minutes' => Monitor::DEFAULT_AUTO_GENERATION_WINDOW_MINUTES,
            'background_source' => Monitor::DEFAULT_BACKGROUND_SOURCE,
            'background_overlay_enabled' => false,
            'content_card_style' => Monitor::DEFAULT_CONTENT_CARD_STYLE,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'auto_generation_window_minutes',
                'background_source',
                'background_overlay_enabled',
                'content_card_style',
            ]);
        });
    }
};
