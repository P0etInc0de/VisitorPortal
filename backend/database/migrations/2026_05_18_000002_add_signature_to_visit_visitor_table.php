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
        Schema::table('visit_visitor', function (Blueprint $table) {
            $table->longText('signature_data')->nullable()->after('notes');
            $table->dateTime('signed_at')->nullable()->after('signature_data');
        });
    }

    public function down(): void
    {
        Schema::table('visit_visitor', function (Blueprint $table) {
            $table->dropColumn(['signature_data', 'signed_at']);
        });
    }
};
