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
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'local_login_allowed')) {
                $table->boolean('local_login_allowed')->default(true)->after('is_active');
            }
        });

        Schema::table('user_identities', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_identities', 'issuer')) {
                $table->string('issuer', 255)->nullable()->after('tenant_id');
            }

            if (! Schema::hasColumn('user_identities', 'subject')) {
                $table->string('subject', 255)->nullable()->after('issuer');
            }

            if (! Schema::hasColumn('user_identities', 'email_verified')) {
                $table->boolean('email_verified')->default(false)->after('email_at_provider');
            }

            if (! Schema::hasColumn('user_identities', 'display_name')) {
                $table->string('display_name')->nullable()->after('email_verified');
            }

            if (! Schema::hasColumn('user_identities', 'claims')) {
                $table->json('claims')->nullable()->after('display_name');
            }

            $table->unique(['provider', 'issuer', 'subject'], 'user_identities_provider_issuer_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_identities', function (Blueprint $table): void {
            $table->dropUnique('user_identities_provider_issuer_subject_unique');
            $table->dropColumn([
                'issuer',
                'subject',
                'email_verified',
                'display_name',
                'claims',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('local_login_allowed');
        });
    }
};
