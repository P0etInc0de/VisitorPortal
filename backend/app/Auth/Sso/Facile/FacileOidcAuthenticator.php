<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Auth\Sso\Facile;

use App\Auth\Sso\Contracts\OidcAuthenticator;
use App\Auth\Sso\DTO\OidcIdentity;
use App\Auth\Sso\SsoAuthenticationException;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Session\AuthSession;
use Facile\OpenIDClient\Session\AuthSessionInterface;
use Facile\OpenIDClient\Token\IdTokenVerifierBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Nyholm\Psr7\ServerRequest;
use Throwable;

final class FacileOidcAuthenticator implements OidcAuthenticator
{
    public function redirect(): RedirectResponse
    {
        $this->ensureConfigured();

        $state = Str::random(64);
        $nonce = Str::random(64);
        $codeVerifier = $this->randomBase64Url(64);

        $authSession = AuthSession::fromArray([
            'state' => $state,
            'nonce' => $nonce,
            'code_verifier' => $codeVerifier,
        ]);

        session(['oidc.auth_session' => $authSession->jsonSerialize()]);

        $authorizationUrl = $this->authorizationService()->getAuthorizationUri(
            $this->client(),
            [
                'scope' => implode(' ', config('sso.oidc.scopes', ['openid', 'profile', 'email'])),
                'response_type' => 'code',
                'redirect_uri' => $this->redirectUri(),
                'state' => $state,
                'nonce' => $nonce,
                'code_challenge' => $this->codeChallenge($codeVerifier),
                'code_challenge_method' => 'S256',
            ],
        );

        return redirect()->away($authorizationUrl);
    }

    public function authenticateCallback(Request $request): OidcIdentity
    {
        if ($request->query('error')) {
            throw new SsoAuthenticationException('OIDC provider returned an error.');
        }

        $storedSession = session()->pull('oidc.auth_session');

        if (! is_array($storedSession)) {
            throw new SsoAuthenticationException('OIDC session state is missing.');
        }

        $authSession = AuthSession::fromArray($storedSession);
        $this->validateState($request, $authSession);

        try {
            $client = $this->client();
            $callbackParams = $this->authorizationService()->getCallbackParams(
                $this->serverRequest($request),
                $client,
            );

            $tokenSet = $this->authorizationService()->callback(
                $client,
                $callbackParams,
                $this->redirectUri(),
                $authSession,
            );
        } catch (Throwable $exception) {
            throw new SsoAuthenticationException('OIDC callback could not be validated.', 0, $exception);
        }

        $claims = $tokenSet->claims();

        if ($claims === []) {
            throw new SsoAuthenticationException('OIDC ID token claims are missing.');
        }

        $this->validateClaims($claims, $client, $authSession);

        $issuer = (string) Arr::get($claims, 'iss');
        $subject = (string) Arr::get($claims, 'sub');

        if ($issuer === '' || $subject === '') {
            throw new SsoAuthenticationException('OIDC identity is missing issuer or subject.');
        }

        if (Str::length($issuer) > 255 || Str::length($subject) > 255) {
            throw new SsoAuthenticationException('OIDC identity issuer or subject is too long.');
        }

        $email = Arr::get($claims, 'email');
        $displayName = Arr::get($claims, 'name');

        return new OidcIdentity(
            issuer: $issuer,
            subject: $subject,
            email: is_string($email) ? Str::lower($email) : null,
            emailVerified: (bool) Arr::get($claims, 'email_verified', false),
            displayName: is_string($displayName) ? $displayName : null,
            groups: $this->groups($claims),
            claims: $this->minimizeClaims($claims),
        );
    }

    public function logoutRedirectUrl(?string $idTokenHint = null): ?string
    {
        return null;
    }

    private function ensureConfigured(): void
    {
        foreach (['issuer_url', 'client_id', 'redirect_uri'] as $key) {
            if (blank(config("sso.oidc.{$key}"))) {
                throw new SsoAuthenticationException("OIDC {$key} is not configured.");
            }
        }

        $tokenEndpointAuthMethod = (string) config('sso.oidc.token_endpoint_auth_method', 'client_secret_basic');

        if (! in_array($tokenEndpointAuthMethod, ['client_secret_basic', 'client_secret_post', 'none'], true)) {
            throw new SsoAuthenticationException('OIDC token endpoint authentication method is not supported.');
        }

        if ($tokenEndpointAuthMethod !== 'none' && blank(config('sso.oidc.client_secret'))) {
            throw new SsoAuthenticationException('OIDC client_secret is required for the configured token endpoint authentication method.');
        }
    }

    private function client(): ClientInterface
    {
        $clientMetadata = [
            'client_id' => (string) config('sso.oidc.client_id'),
            'redirect_uris' => [$this->redirectUri()],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => (string) config('sso.oidc.token_endpoint_auth_method', 'client_secret_basic'),
        ];

        if (filled(config('sso.oidc.client_secret'))) {
            $clientMetadata['client_secret'] = (string) config('sso.oidc.client_secret');
        }

        $issuer = (new IssuerBuilder)->build((string) config('sso.oidc.issuer_url'));

        return (new ClientBuilder)
            ->setIssuer($issuer)
            ->setClientMetadata(ClientMetadata::fromArray($clientMetadata))
            ->build();
    }

    private function authorizationService(): AuthorizationService
    {
        $idTokenVerifierBuilder = (new IdTokenVerifierBuilder)
            ->setClockTolerance((int) config('sso.oidc.clock_tolerance', 60));

        return (new AuthorizationServiceBuilder)
            ->setIdTokenVerifierBuilder($idTokenVerifierBuilder)
            ->build();
    }

    private function validateState(Request $request, AuthSessionInterface $authSession): void
    {
        $expectedState = $authSession->getState();
        $actualState = (string) $request->query('state', '');

        if (! is_string($expectedState) || $actualState === '' || ! hash_equals($expectedState, $actualState)) {
            throw new SsoAuthenticationException('Invalid OIDC state.');
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function validateClaims(array $claims, ClientInterface $client, AuthSessionInterface $authSession): void
    {
        $issuer = Arr::get($claims, 'iss');
        $subject = Arr::get($claims, 'sub');

        if (! is_string($issuer) || ! hash_equals($client->getIssuer()->getMetadata()->getIssuer(), $issuer)) {
            throw new SsoAuthenticationException('Invalid OIDC issuer.');
        }

        if (! is_string($subject) || $subject === '') {
            throw new SsoAuthenticationException('Invalid OIDC subject.');
        }

        $this->validateAudience($claims);

        $expectedNonce = $authSession->getNonce();
        $actualNonce = Arr::get($claims, 'nonce');

        if (! is_string($expectedNonce) || ! is_string($actualNonce) || ! hash_equals($expectedNonce, $actualNonce)) {
            throw new SsoAuthenticationException('Invalid OIDC nonce.');
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function validateAudience(array $claims): void
    {
        $clientId = (string) config('sso.oidc.client_id');
        $audience = Arr::get($claims, 'aud');
        $audiences = is_array($audience) ? $audience : [$audience];

        if (! in_array($clientId, $audiences, true)) {
            throw new SsoAuthenticationException('Invalid OIDC audience.');
        }

        $authorizedParty = Arr::get($claims, 'azp');

        if (count($audiences) > 1 && $authorizedParty !== $clientId) {
            throw new SsoAuthenticationException('Invalid OIDC authorized party.');
        }

        if (is_string($authorizedParty) && $authorizedParty !== $clientId) {
            throw new SsoAuthenticationException('Invalid OIDC authorized party.');
        }
    }

    private function redirectUri(): string
    {
        return (string) config('sso.oidc.redirect_uri');
    }

    private function serverRequest(Request $request): ServerRequest
    {
        return (new ServerRequest($request->method(), $request->fullUrl()))
            ->withQueryParams($request->query());
    }

    private function randomBase64Url(int $bytes): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    private function codeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * @param  array<string, mixed>  $claims
     * @return list<string>
     */
    private function groups(array $claims): array
    {
        $groupsClaim = (string) config('sso.oidc.groups_claim', 'groups');
        $groups = Arr::get($claims, $groupsClaim, []);

        if (is_string($groups)) {
            return [$groups];
        }

        if (! is_array($groups)) {
            return [];
        }

        return array_values(array_filter($groups, static fn (mixed $group): bool => is_string($group)));
    }

    /**
     * @param  array<string, mixed>  $claims
     * @return array<string, mixed>
     */
    private function minimizeClaims(array $claims): array
    {
        $groupsClaim = (string) config('sso.oidc.groups_claim', 'groups');

        return Arr::only($claims, array_unique([
            'iss',
            'sub',
            'email',
            'email_verified',
            'name',
            'given_name',
            'family_name',
            'preferred_username',
            'upn',
            'tid',
            $groupsClaim,
        ]));
    }
}
