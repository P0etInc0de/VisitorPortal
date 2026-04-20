<?php

use App\Enums\VisitorStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('visitors')->where('status', 'Planned')->update(['status' => VisitorStatusEnum::Planned->value]);
        DB::table('visitors')->where('status', 'Checked_In')->update(['status' => VisitorStatusEnum::Checked_In->value]);
        DB::table('visitors')->where('status', 'Checked_Out')->update(['status' => VisitorStatusEnum::Checked_Out->value]);
        DB::table('visitors')->where('status', 'Canceled')->update(['status' => VisitorStatusEnum::Canceled->value]);
    }

    public function down(): void
    {
        DB::table('visitors')->where('status', VisitorStatusEnum::Planned->value)->update(['status' => 'Planned']);
        DB::table('visitors')->where('status', VisitorStatusEnum::Checked_In->value)->update(['status' => 'Checked_In']);
        DB::table('visitors')->where('status', VisitorStatusEnum::Checked_Out->value)->update(['status' => 'Checked_Out']);
        DB::table('visitors')->where('status', VisitorStatusEnum::Canceled->value)->update(['status' => 'Canceled']);
    }
};
