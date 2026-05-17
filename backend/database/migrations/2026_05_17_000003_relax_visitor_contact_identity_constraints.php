<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('visitors', ['email'], 'unique')) {
            Schema::table('visitors', function (Blueprint $table): void {
                $table->dropUnique(['email']);
            });
        }

        if (! Schema::hasIndex('visitors', ['email'])) {
            Schema::table('visitors', function (Blueprint $table): void {
                $table->index('email');
            });
        }

        if (Schema::hasIndex('visitors', ['phone'], 'unique')) {
            Schema::table('visitors', function (Blueprint $table): void {
                $table->dropUnique(['phone']);
            });
        }

        if (! Schema::hasIndex('visitors', ['phone'])) {
            Schema::table('visitors', function (Blueprint $table): void {
                $table->index('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('visitors', ['email']) && ! Schema::hasIndex('visitors', ['email'], 'unique')) {
            Schema::table('visitors', function (Blueprint $table): void {
                $table->dropIndex(['email']);
            });
        }

        if (! Schema::hasIndex('visitors', ['email'], 'unique')) {
            Schema::table('visitors', function (Blueprint $table): void {
                $table->unique('email');
            });
        }
    }
};
