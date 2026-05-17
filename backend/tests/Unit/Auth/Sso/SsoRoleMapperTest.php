<?php

namespace Tests\Unit\Auth\Sso;

use App\Auth\Sso\DTO\OidcIdentity;
use App\Auth\Sso\SsoRoleMapper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SsoRoleMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_only_known_groups(): void
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        config([
            'sso.oidc.role_mapping' => [
                'VisitorPortal-Users' => 'user',
                'VisitorPortal-Admins' => 'admin',
            ],
            'sso.oidc.sync_roles_remove_unmapped' => false,
        ]);

        $user = User::factory()->create();
        $identity = new OidcIdentity(
            issuer: 'https://idp.example.test',
            subject: 'subject-123',
            email: 'user@example.org',
            emailVerified: true,
            displayName: 'User Example',
            groups: ['Unknown', 'VisitorPortal-Users'],
            claims: [],
        );

        app(SsoRoleMapper::class)->sync($user, $identity);

        $this->assertTrue($user->fresh()->hasRole('user'));
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }
}
