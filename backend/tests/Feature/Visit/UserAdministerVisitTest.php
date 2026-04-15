<?php

namespace Tests\Feature\Visit;

use App\Enums\VisitStatusEnum;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAdministerVisitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature test relating to visits
     */
    public function test_user_can_create_visit(): void
    {
        // create necessary instances
        $user = User::factory()->create();

        $responsibleUser = User::factory()->create();

        $visitors = User::factory(4)->create();



        // create a visit
        $this->createVisit('TestVisit',
            $user,
            $responsibleUser->id,
            now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'planned',
            'notes...',
            $visitors->pluck('id')->toArray(),

        );

        // check if visit is present in database
        $this->assertDatabaseHas('visits', [
            'title' => 'TestVisit',
            'responsible_user_id' => $responsibleUser->id,
        ]);

    }

    public function test_user_can_cancel_visit(): void {

        $visit = $this->makeVisit(VisitStatusEnum::Planned);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('portal.visits.cancel', $visit));

        $this->assertDatabaseHas('visits', [
            'title' => 'Test Visit',
            'status' => VisitStatusEnum::Canceled,
        ]);



    }

    public function test_user_can_edit_visit(): void {}

    public function getIndividualUser(array $individualPermissions, string $name): User
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        foreach ($individualPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // replace old permissions with the new ones
        $role->syncPermissions($individualPermissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function createUser(User $admin, string $first_name, string $name, string $email, string $password, Role $userRole): void
    {
        // create a test user with role of an employee
        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'first_name' => $first_name,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'roles' => [$userRole->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function createVisit(
        string $title,
        User $user,
        int $responsible_user_id,
        string $scheduled_from,
        string $scheduled_until,
        string $status,
        string $notes,
        $participants
    ): void {



        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => $title,
                'user_id' => $user->id,
                'responsible_user_id' => $responsible_user_id,
                'scheduled_from' => $scheduled_from,
                'scheduled_until' => $scheduled_until,
                'status' => $status,
                'notes' => $notes,
                'participants' => $participants,
            ]);

    }


    private function makeVisit(VisitStatusEnum $status):Visit
    {
        return Visit::factory()->create([
            'title' => "Test Visit",
            'status' => $status,
        ]);
    }
}
