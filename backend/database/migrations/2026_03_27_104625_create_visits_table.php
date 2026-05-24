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
     * Migration that creates visits table with all columns
     */
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignId('recurring_visit_series_id')->nullable()->constrained('recurring_visit_series')->nullOnDelete();
            $table->unsignedInteger('recurrence_occurrence_number')->nullable();
            $table->dateTime('recurrence_original_scheduled_from')->nullable();
            $table->boolean('recurrence_is_modified')->default(false);
            $table->foreignId('host_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('substitute_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->datetime('scheduled_from');
            $table->datetime('scheduled_until');
            $table->string('status')->default('planned');
            $table->boolean('is_confidential')->default(false);
            $table->boolean('is_walk_in')->default(false);
            $table->text('notes')->nullable();
            $table->datetime('canceled_at')->nullable();
            $table->foreignId('canceled_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('retention_hold_until')->nullable();
            $table->text('retention_hold_reason')->nullable();
            $table->foreignId('retention_hold_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['recurring_visit_series_id', 'recurrence_occurrence_number'], 'visits_recurring_series_occurrence_unique');
            $table->index(['recurring_visit_series_id', 'scheduled_from'], 'visits_recurring_series_scheduled_from_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
