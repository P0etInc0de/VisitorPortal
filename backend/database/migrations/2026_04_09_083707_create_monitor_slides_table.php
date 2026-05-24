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
        Schema::create('monitor_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->string('image_path')->default(null)->nullable();
            $table->string('background_source')->default(null)->nullable();
            $table->integer('slide_number')->default(0);
            $table->timestamps();
            $table->string('heading');
            $table->string('subheading')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_auto_generated')->default(false);
            $table->string('monitor_display_mode')->default('title_first_initial_last_name');
            $table->boolean('show_logo')->default(false);
            $table->boolean('show_date')->default(false);
            $table->boolean('show_time')->default(false);
            $table->json('visitors')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_slides');
    }
};
