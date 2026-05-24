<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

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
        $fallbackHeading = config('branding.monitor_fallback_heading', 'Welcome to VisitorPortal');
        $fallbackSubheading = config('branding.monitor_fallback_subheading', "We're glad you're here.");

        Schema::create('monitors', function (Blueprint $table) use ($fallbackHeading, $fallbackSubheading) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->string('image_path')->default(null)->nullable();
            $table->string('background_source')->default('preset-1');
            $table->boolean('background_overlay_enabled')->default(false);
            $table->boolean('header_text_is_light')->default(false);
            $table->string('content_card_style')->default('transparent');
            $table->string('name')->default('Entrance welcome monitor');
            $table->timestamps();
            $table->integer('transition_time_milliseconds')->default(5000);
            $table->boolean('auto_generation')->default(false);
            $table->integer('auto_generation_window_minutes')->default(30);
            $table->string('monitor_display_mode')->default('title_first_initial_last_name');
            $table->string('fallback_heading')->default($fallbackHeading);
            $table->string('fallback_subheading')->default($fallbackSubheading);
            $table->boolean('fallback_show_logo')->default(true);
            $table->boolean('fallback_show_date')->default(true);
            $table->string('fallback_image_path')->default(null)->nullable();
            $table->string('fallback_background_source')->default(null)->nullable();
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
