<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_visit_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('responsible_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status')->default('planned');
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

        Schema::create('recurring_visit_series_visitor', function (Blueprint $table) {
            $table->foreignId('recurring_visit_series_id')->constrained('recurring_visit_series')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->primary(['recurring_visit_series_id', 'visitor_id']);
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('recurring_visit_series_id')->nullable()->after('id')->constrained('recurring_visit_series')->nullOnDelete();
            $table->unsignedInteger('recurrence_occurrence_number')->nullable()->after('recurring_visit_series_id');
            $table->dateTime('recurrence_original_scheduled_from')->nullable()->after('recurrence_occurrence_number');
            $table->boolean('recurrence_is_modified')->default(false)->after('recurrence_original_scheduled_from');

            $table->unique(['recurring_visit_series_id', 'recurrence_occurrence_number'], 'visits_recurring_series_occurrence_unique');
            $table->index(['recurring_visit_series_id', 'scheduled_from'], 'visits_recurring_series_scheduled_from_index');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropUnique('visits_recurring_series_occurrence_unique');
            $table->dropIndex('visits_recurring_series_scheduled_from_index');
            $table->dropConstrainedForeignId('recurring_visit_series_id');
            $table->dropColumn([
                'recurrence_occurrence_number',
                'recurrence_original_scheduled_from',
                'recurrence_is_modified',
            ]);
        });

        Schema::dropIfExists('recurring_visit_series_visitor');
        Schema::dropIfExists('recurring_visit_series');
    }
};
