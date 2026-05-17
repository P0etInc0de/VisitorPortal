<?php

namespace Tests\Unit\Auth\Sso;

use App\Auth\Sso\Facile\FacileOidcAuthenticator;
use App\Auth\Sso\SsoAuthenticationException;
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
}
