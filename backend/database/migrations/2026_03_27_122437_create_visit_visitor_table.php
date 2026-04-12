<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table between visits and visitors table, DO NOT ACCESS DIRECTLY BUT VIA VISIT/VISITOR MODEL!!!
     */
    public function up(): void
    {
        Schema::create('visit_visitor', function (Blueprint $table) {
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->datetime('badge_printed_at')->nullable();
            $table->datetime('checked_in_at')->nullable();
            $table->datetime('checked_out_at')->nullable();
            $table->foreignId('checked_in_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('checked_out_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_visitor');
    }
};
