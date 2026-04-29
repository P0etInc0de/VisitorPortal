<?php

namespace Tests\Feature\Portal;

use App\Enums\VisitStatusEnum;
use App\Livewire\Portal\CheckInOutBoard;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CheckInOutBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_check_in_updates_pivot_and_renders_db_state(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 17, 0, 0, config('app.timezone')));

        $user = User::factory()->create();
        $visitor = Visitor::factory()->create([
            'first_name' => 'Max',
            'name' => 'Mustermann',
        ]);
        $visit = Visit::factory()->create([
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
            'status' => VisitStatusEnum::Planned->value,
            'scheduled_from' => now()->addHour(),
            'scheduled_until' => now()->addHours(2),
        ]);

        $visit->visitors()->attach($visitor->id);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->call('checkIn', $visit->id, $visitor->id)
            ->assertSee(__('Eingecheckt'))
            ->assertSee(__('Check-in').':')
            ->assertSee('26.04.2026 17:00');

        $pivot = DB::table('visit_visitor')
            ->where('visit_id', $visit->id)
            ->where('visitor_id', $visitor->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertNotNull($pivot->checked_in_at);
        $this->assertNull($pivot->checked_out_at);

        Carbon::setTestNow();
    }

    public function test_portal_check_out_updates_pivot_and_renders_db_state(): void
    {
        $user = User::factory()->create();
        $visitor = Visitor::factory()->create([
            'first_name' => 'Erika',
            'name' => 'Musterfrau',
        ]);
        $visit = Visit::factory()->create([
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
            'status' => VisitStatusEnum::Planned->value,
            'scheduled_from' => now()->addHour(),
            'scheduled_until' => now()->addHours(2),
        ]);

        $visit->visitors()->attach($visitor->id, [
            'checked_in_at' => now()->subMinutes(5),
            'checked_in_by_user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->call('checkOut', $visit->id, $visitor->id)
            ->assertSee(__('Ausgecheckt'))
            ->assertSee(__('Check-out').':');

        $pivot = DB::table('visit_visitor')
            ->where('visit_id', $visit->id)
            ->where('visitor_id', $visitor->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertNotNull($pivot->checked_in_at);
        $this->assertNotNull($pivot->checked_out_at);
    }
}
