<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit\Auth\Sso;

use App\Auth\Sso\Facile\FacileOidcAuthenticator;
use App\Auth\Sso\SsoAuthenticationException;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadataInterface;
use Facile\OpenIDClient\Session\AuthSession;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class FacileOidcAuthenticatorTest extends TestCase
{
    public function test_secret_based_token_endpoint_auth_requires_client_secret(): void
    {
        config([
            'sso.oidc.issuer_url' => 'https://idp.example.test',
            'sso.oidc.client_id' => 'visitorportal',
            'sso.oidc.client_secret' => '',
            'sso.oidc.redirect_uri' => 'https://visitorportal.example.test/auth/oidc/callback',
            'sso.oidc.token_endpoint_auth_method' => 'client_secret_basic',
        ]);

        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('OIDC client_secret is required');

        app(FacileOidcAuthenticator::class)->redirect();
    }

    public function test_it_rejects_unsupported_token_endpoint_auth_method(): void
    {
        config([
            'sso.oidc.issuer_url' => 'https://idp.example.test',
            'sso.oidc.client_id' => 'visitorportal',
            'sso.oidc.client_secret' => 'secret',
            'sso.oidc.redirect_uri' => 'https://visitorportal.example.test/auth/oidc/callback',
            'sso.oidc.token_endpoint_auth_method' => 'private_key_jwt',
        ]);

        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('OIDC token endpoint authentication method is not supported.');

        app(FacileOidcAuthenticator::class)->redirect();
    }

    public function test_it_rejects_invalid_state(): void
    {
        $request = Request::create('/auth/oidc/callback', 'GET', ['state' => 'actual-state']);
        $authSession = AuthSession::fromArray(['state' => 'expected-state']);

        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC state.');

        $this->invokePrivate(app(FacileOidcAuthenticator::class), 'validateState', [$request, $authSession]);
    }

    public function test_it_rejects_missing_state(): void
    {
        $request = Request::create('/auth/oidc/callback', 'GET');
        $authSession = AuthSession::fromArray(['state' => 'expected-state']);

        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC state.');

        $this->invokePrivate(app(FacileOidcAuthenticator::class), 'validateState', [$request, $authSession]);
    }

    public function test_it_rejects_wrong_issuer(): void
    {
        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC issuer.');

        $this->validateClaims(['iss' => 'https://evil.example.test']);
    }

    public function test_it_rejects_missing_issuer(): void
    {
        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC issuer.');

        $this->validateClaims(['iss' => null]);
    }

    public function test_it_rejects_missing_subject(): void
    {
        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC subject.');

        $this->validateClaims(['sub' => '']);
    }

    public function test_it_rejects_wrong_audience(): void
    {
        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC audience.');

        $this->validateClaims(['aud' => 'other-client']);
    }

    public function test_it_rejects_wrong_authorized_party_for_multiple_audiences(): void
    {
        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC authorized party.');

        $this->validateClaims([
            'aud' => ['visitorportal', 'other-client'],
            'azp' => 'other-client',
        ]);
    }

    public function test_it_rejects_wrong_authorized_party_for_single_audience(): void
    {
        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC authorized party.');

        $this->validateClaims([
            'aud' => 'visitorportal',
            'azp' => 'other-client',
        ]);
    }

    public function test_it_rejects_wrong_nonce(): void
    {
        $this->expectException(SsoAuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC nonce.');

        $this->validateClaims(['nonce' => 'actual-nonce']);
    }

    /**
     * @param  array<string, mixed>  $claimOverrides
     */
    private function validateClaims(array $claimOverrides): void
    {
        config(['sso.oidc.client_id' => 'visitorportal']);

        $claims = array_merge([
            'iss' => 'https://idp.example.test',
            'sub' => 'subject-123',
            'aud' => 'visitorportal',
            'nonce' => 'expected-nonce',
        ], $claimOverrides);

        $authSession = AuthSession::fromArray(['nonce' => 'expected-nonce']);
        $client = $this->clientWithIssuer('https://idp.example.test');

        $this->invokePrivate(app(FacileOidcAuthenticator::class), 'validateClaims', [$claims, $client, $authSession]);
    }

    private function clientWithIssuer(string $issuerUrl): ClientInterface
    {
        $metadata = $this->createMock(IssuerMetadataInterface::class);
        $metadata->method('getIssuer')->willReturn($issuerUrl);

        $issuer = $this->createMock(IssuerInterface::class);
        $issuer->method('getMetadata')->willReturn($metadata);

        $client = $this->createMock(ClientInterface::class);
        $client->method('getIssuer')->willReturn($issuer);

        return $client;
    }

    /**
     * @param  list<mixed>  $arguments
     */
    private function invokePrivate(object $object, string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);

        return $reflection->invokeArgs($object, $arguments);
    }
}
