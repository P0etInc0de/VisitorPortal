<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit\Auth\Sso;

use App\Auth\Sso\DTO\OidcIdentity;
use App\Auth\Sso\SsoAuthenticationException;
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

    public function test_it_rejects_missing_mapped_roles_without_assigning_any_roles(): void
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

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
            groups: ['VisitorPortal-Users', 'VisitorPortal-Admins'],
            claims: [],
        );

        try {
            app(SsoRoleMapper::class)->sync($user, $identity);
            $this->fail('Expected missing mapped role to reject SSO role sync.');
        } catch (SsoAuthenticationException) {
            $this->assertFalse($user->fresh()->hasRole('user'));
        }
    }

    public function test_it_does_not_remove_existing_roles_by_default(): void
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        config([
            'sso.oidc.role_mapping' => [
                'VisitorPortal-Users' => 'user',
            ],
            'sso.oidc.sync_roles_remove_unmapped' => false,
        ]);

        $user = User::factory()->create();
        $user->assignRole('manager');

        app(SsoRoleMapper::class)->sync($user, $this->identity(['VisitorPortal-Users']));

        $this->assertTrue($user->fresh()->hasRole('user'));
        $this->assertTrue($user->fresh()->hasRole('manager'));
    }

    public function test_it_removes_unmapped_roles_when_configured(): void
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        config([
            'sso.oidc.role_mapping' => [
                'VisitorPortal-Users' => 'user',
            ],
            'sso.oidc.sync_roles_remove_unmapped' => true,
        ]);

        $user = User::factory()->create();
        $user->assignRole('manager');

        app(SsoRoleMapper::class)->sync($user, $this->identity(['VisitorPortal-Users']));

        $this->assertTrue($user->fresh()->hasRole('user'));
        $this->assertFalse($user->fresh()->hasRole('manager'));
    }

    /**
     * @param  list<string>  $groups
     */
    private function identity(array $groups): OidcIdentity
    {
        return new OidcIdentity(
            issuer: 'https://idp.example.test',
            subject: 'subject-123',
            email: 'user@example.org',
            emailVerified: true,
            displayName: 'User Example',
            groups: $groups,
            claims: [],
        );
    }
}
