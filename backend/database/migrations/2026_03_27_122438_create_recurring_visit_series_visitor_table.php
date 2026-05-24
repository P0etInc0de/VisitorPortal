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
        Schema::create('recurring_visit_series_visitor', function (Blueprint $table) {
            $table->foreignId('recurring_visit_series_id')->constrained('recurring_visit_series')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->primary(['recurring_visit_series_id', 'visitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_visit_series_visitor');
    }
};
