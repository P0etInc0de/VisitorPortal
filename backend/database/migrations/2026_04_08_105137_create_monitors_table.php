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
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('image_path')->default(null)->nullable();
            $table->string('background_source')->default('preset-1');
            $table->boolean('background_overlay_enabled')->default(false);
            $table->string('content_card_style')->default('transparent');
            $table->string('name')->default('Willkommensmonitor Eingang');
            $table->timestamps();
            $table->integer('transition_time_milliseconds')->default(5000);
            $table->boolean('auto_generation')->default(true);
            $table->integer('auto_generation_window_minutes')->default(30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
