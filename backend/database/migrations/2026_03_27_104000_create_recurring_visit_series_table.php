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
        Schema::create('recurring_visit_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignId('host_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('substitute_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status')->default('planned');
            $table->boolean('is_confidential')->default(false);
            $table->text('notes')->nullable();
            $table->dateTime('starts_at');
            $table->unsignedInteger('duration_minutes');
            $table->string('frequency');
            $table->unsignedInteger('interval_days')->nullable();
            $table->string('ends');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('occurrence_count')->nullable();
            $table->dateTime('generated_until')->nullable();
            $table->json('exclusion_dates')->nullable();
            $table->timestamps();

            $table->index(['frequency', 'ends']);
            $table->index('generated_until');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_visit_series');
    }
};
