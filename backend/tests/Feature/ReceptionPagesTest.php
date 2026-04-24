<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_reception_pages_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('reception.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('reception.all-visits'))->assertOk();
    }
}
