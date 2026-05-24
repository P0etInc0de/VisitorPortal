<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'site_id'], 'site_user_unique');
        });

        $now = now();
        DB::table('users')
            ->select(['id', 'site_id'])
            ->whereNotNull('site_id')
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($now): void {
                $rows = $users->map(fn ($user): array => [
                    'user_id' => $user->id,
                    'site_id' => $user->site_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('site_user')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_user');
    }
};
