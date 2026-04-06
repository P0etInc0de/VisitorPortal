<?php

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
            $table->id(); // primary key
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // foreign key, user cannot be deleted if visit exists
            $table->foreignId('responsible_user_id')->constrained('users')->restrictOnDelete(); // foreign key, user cannot be deleted if visit exists
            $table->datetime('scheduled_from');
            $table->datetime('scheduled_until');
            $table->string('status')->default('planned');
            $table->text('notes')->nullable();
            $table->datetime('canceled_at')->nullable();
            $table->foreignId('canceled_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // foreign key, only there if a user canceled it
            $table->timestamps();
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
