<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitor_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->integer('slide_number')->default(0);
            $table->timestamps();
            $table->string('background_path')->default('');
            $table->string('heading');
            $table->string('subheading')->nullable();
            $table->boolean('show_logo')->default(false);
            $table->boolean('show_date')->default(false);
            $table->boolean('show_time')->default(false);
            $table->json('visitors')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_slides');
    }
};
