<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration that creates visitors table with all columns
     */
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();                                                       // primary key
            $table->string('name');
            $table->string('first_name');
            $table->string('gender')->default('other');
            $table->string('title')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('company')->nullable();
            $table->string('status')->default('Planned');
            $table->text('notes')->nullable();
            $table->timestamps();                                               // creates created_at and updated_at automatically
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
