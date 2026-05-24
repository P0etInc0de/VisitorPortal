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
    public function up(): void
    {
        Schema::create('data_retention_runs', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->boolean('dry_run')->default(false);
            $table->unsignedInteger('retention_days');
            $table->dateTime('cutoff_at');
            $table->unsignedInteger('visits_matched')->default(0);
            $table->unsignedInteger('visits_deleted')->default(0);
            $table->unsignedInteger('visitors_matched')->default(0);
            $table->unsignedInteger('visitors_deleted')->default(0);
            $table->unsignedInteger('notifications_matched')->default(0);
            $table->unsignedInteger('notifications_deleted')->default(0);
            $table->unsignedInteger('monitor_slides_matched')->default(0);
            $table->unsignedInteger('monitor_slides_deleted')->default(0);
            $table->unsignedInteger('monitor_slides_anonymized')->default(0);
            $table->unsignedInteger('recurring_series_matched')->default(0);
            $table->unsignedInteger('recurring_series_deleted')->default(0);
            $table->unsignedInteger('indefinite_legal_holds_matched')->default(0);
            $table->string('status')->default('running');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_retention_runs');
    }
};
