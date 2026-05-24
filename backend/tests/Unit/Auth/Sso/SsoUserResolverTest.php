<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit\Auth\Sso;

use App\Auth\Sso\DTO\OidcIdentity;
use App\Auth\Sso\SsoUserResolver;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SsoUserResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_user_by_issuer_and_subject(): void
    {
        $user = User::factory()->create();
        UserIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'oidc',
            'provider_user_id' => 'subject-123',
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
            'email_at_provider' => 'old@example.org',
        ]);

        $resolved = app(SsoUserResolver::class)->resolve($this->identity());

        $this->assertTrue($resolved->is($user));
        $this->assertDatabaseHas('user_identities', [
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
            'email_at_provider' => 'user@example.org',
            'email_verified' => true,
        ]);
    }

    public function test_it_does_not_store_oidc_claims_by_default(): void
    {
        $user = User::factory()->create();
        $externalIdentity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'oidc',
            'provider_user_id' => 'subject-123',
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
            'claims' => ['groups' => ['Finance']],
        ]);

        app(SsoUserResolver::class)->resolve($this->identity());

        $this->assertNull($externalIdentity->fresh()->claims);
    }

    public function test_it_rejects_unknown_identity_when_provisioning_is_disabled(): void
    {
        config(['sso.oidc.provisioning_mode' => 'disabled']);

        $this->expectException(AuthenticationException::class);

        app(SsoUserResolver::class)->resolve($this->identity());
    }

    public function test_it_links_existing_user_by_verified_email_for_invited_only_mode(): void
    {
        config(['sso.oidc.provisioning_mode' => 'invited_only']);

        $user = User::factory()->create(['email' => 'user@example.org']);

        $resolved = app(SsoUserResolver::class)->resolve($this->identity());

        $this->assertTrue($resolved->is($user));
        $this->assertDatabaseHas('user_identities', [
            'user_id' => $user->id,
            'provider' => 'oidc',
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
        ]);
    }

    public function test_it_does_not_link_unverified_email_in_invited_only_mode(): void
    {
        config(['sso.oidc.provisioning_mode' => 'invited_only']);

        User::factory()->create(['email' => 'user@example.org']);

        $this->expectException(AuthenticationException::class);

        app(SsoUserResolver::class)->resolve($this->identity(emailVerified: false));
    }

    public function test_it_auto_provisions_user_with_local_login_disabled(): void
    {
        config([
            'sso.oidc.provisioning_mode' => 'auto',
            'sso.oidc.auto_provision.default_role' => 'user',
        ]);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $user = app(SsoUserResolver::class)->resolve($this->identity());

        $this->assertFalse($user->fresh()->local_login_allowed);
        $this->assertTrue($user->fresh()->hasRole('user'));
        $this->assertDatabaseHas('user_identities', [
            'user_id' => $user->id,
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
        ]);
    }

    public function test_it_rejects_disallowed_email_domain(): void
    {
        config([
            'sso.oidc.provisioning_mode' => 'auto',
            'sso.oidc.allowed_domains' => ['example.com'],
        ]);

        $this->expectException(AuthenticationException::class);

        app(SsoUserResolver::class)->resolve($this->identity());
    }

    public function test_it_rejects_inactive_linked_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        UserIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'oidc',
            'provider_user_id' => 'subject-123',
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User account is disabled.');

        app(SsoUserResolver::class)->resolve($this->identity());
    }

    private function identity(bool $emailVerified = true): OidcIdentity
    {
        return new OidcIdentity(
            issuer: 'https://idp.example.test',
            subject: 'subject-123',
            email: 'user@example.org',
            emailVerified: $emailVerified,
            displayName: 'User Example',
            groups: [],
            claims: [
                'iss' => 'https://idp.example.test',
                'sub' => 'subject-123',
                'email' => 'user@example.org',
                'email_verified' => $emailVerified,
                'given_name' => 'User',
                'family_name' => 'Example',
            ],
        );
    }
}
