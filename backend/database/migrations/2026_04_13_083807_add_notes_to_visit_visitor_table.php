<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

	public function up(): void
{
    Schema::table('visit_visitor', function (Blueprint $table) {
        $table->text('notes')->nullable()->after('checked_out_by_user_id');
    });
}

public function down(): void
{
    Schema::table('visit_visitor', function (Blueprint $table) {
        $table->dropColumn('notes');
    });
}

};
