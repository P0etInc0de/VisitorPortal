# Single Sign-On

VisitorPortal supports optional generic OpenID Connect SSO.

## Supported Concept

The implementation is provider-independent and can be configured for OIDC-compatible identity providers such as Microsoft Entra ID, Keycloak, Authentik, Okta, Auth0, Google Workspace and GitLab OIDC.

SSO is not implemented as a Microsoft-specific login and does not use Laravel Socialite. The OIDC client library is wrapped behind an internal adapter.

## Security Model

External users are identified by `provider + issuer + subject`. E-mail addresses are not used as primary external identity keys.

E-mail addresses are only used as a controlled linking or provisioning helper. Invited-only linking requires a verified e-mail claim from the identity provider.

Tokens are not stored and must not be logged.

OIDC claims are not stored by default. If `OIDC_STORE_CLAIMS=true` is enabled, VisitorPortal stores only a minimized allow-list of claims. Group claims can still contain internal organizational information, so enable claim storage only when it is needed for audit or troubleshooting.

## Defaults

SSO is disabled by default:

```env
AUTH_MODE=local
SSO_ENABLED=false
SSO_DRIVER=oidc
OIDC_PROVISIONING_MODE=disabled
OIDC_SYNC_ROLES=false
OIDC_SYNC_ROLES_ON_LOGIN=false
OIDC_STORE_CLAIMS=false
OIDC_LOGOUT_MODE=local
```

Auto-provisioning and role synchronization are disabled by default.

## Auth Modes

`local` means local Fortify login only. OIDC routes return 404.

`local_and_sso` enables both local login and SSO login.

`sso_only` enables SSO for normal users. Local login is only allowed for accounts with the `LoginLocallyInSsoOnlyMode` permission.

`LoginLocallyInSsoOnlyMode` is created by the permission sync, but it is not assigned to any default role. Operators must grant it deliberately to a dedicated local break-glass admin account before switching production to `sso_only`.

## Configuration

```env
AUTH_MODE=local
SSO_ENABLED=false
SSO_DRIVER=oidc

OIDC_DISPLAY_NAME="Company SSO"
OIDC_ISSUER_URL=https://idp.example.com/realms/company
OIDC_CLIENT_ID=
OIDC_CLIENT_SECRET=
OIDC_REDIRECT_URI="${APP_URL}/auth/oidc/callback"
OIDC_SCOPES="openid profile email"
OIDC_REQUIRE_VERIFIED_EMAIL=true
OIDC_ALLOWED_DOMAINS=

OIDC_PROVISIONING_MODE=disabled
OIDC_SYNC_USER_PROFILE=true
OIDC_STORE_CLAIMS=false
OIDC_SYNC_ROLES=false
OIDC_SYNC_ROLES_ON_LOGIN=false
OIDC_SYNC_ROLES_REMOVE_UNMAPPED=false
OIDC_GROUPS_CLAIM=groups
OIDC_LOGOUT_MODE=local
```

`OIDC_TOKEN_ENDPOINT_AUTH_METHOD` defaults to `client_secret_basic`. With that default, `OIDC_CLIENT_SECRET` is required. Public clients without a secret must explicitly set `OIDC_TOKEN_ENDPOINT_AUTH_METHOD=none` and should only be used when the identity provider and deployment model support that safely.

Provisioning modes:

`disabled` only allows already linked external identities.

`invited_only` links an existing local user by verified e-mail.

`auto` creates a local user on first successful SSO login. Auto-provisioned users get `local_login_allowed=false` so local password login and password reset do not bypass the IdP.

## Profile Sync

When `OIDC_SYNC_USER_PROFILE=true`, login-time profile sync only updates selected non-critical profile fields: `first_name`, `name`, verified non-conflicting `email` and `email_verified_at`.

It does not overwrite roles, permissions, department assignment, active status, local-login eligibility, admin/security fields or user preferences. Keep that separation unless the IdP is intentionally made authoritative for those fields through a dedicated change.

## Role Mapping

Role mapping only uses explicitly configured groups. Unknown groups are ignored.

Mapped roles must exist locally with the `web` guard. If a configured mapping points to a missing role, SSO role synchronization fails closed, writes a critical `sso_role_mapping_invalid` log entry and rejects the login with the generic SSO failure response.

By default, role sync does not remove existing local roles when no mapped group is present. `OIDC_SYNC_ROLES_REMOVE_UNMAPPED=true` makes the IdP authoritative and can remove all local roles if the IdP emits no mapped groups or group claims are misconfigured, so enable it only deliberately.

For Microsoft Entra ID, use dedicated application groups and ensure they are emitted directly as OIDC claims. VisitorPortal does not call Microsoft Graph or other provider APIs to resolve group overage.

## Recommended Enterprise Setup

- Use a tenant-specific issuer.
- Enforce MFA in the identity provider.
- If `APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS` contains `sso`, VisitorPortal additionally requires a local app-MFA-confirmed session for privileged admin panel access after SSO login. The confirmation is limited by `APP_MFA_SESSION_TTL_MINUTES` and by the web session lifetime.
- Keep a local break-glass admin account with `LoginLocallyInSsoOnlyMode`.
- Use dedicated VisitorPortal groups.
- Use HTTPS in production.
- Do not enable auto-provisioning unless intentionally wanted.
- Do not map admin rights from e-mail domains.

## Token Validation

The Facile OIDC adapter validates the callback through `facile-it/php-openid-client`. JWT signature validation and lifetime validation such as `exp`, `nbf` and `iat` are delegated to the library's ID-token verifier and use `OIDC_CLOCK_TOLERANCE` for clock skew.

VisitorPortal additionally validates `state`, `nonce`, issuer, subject, audience and authorized party before resolving a local user.

The automated real-provider smoke test covers the authorization-code redirect/callback path. It does not currently include a negative real-provider case for expired, not-yet-valid or otherwise invalid lifetime claims.

## Operational Notes

Concurrent first logins for the same unlinked SSO identity should be treated as a possible race during high-traffic rollouts. If that appears in production logs, retry the login once after the first request finishes and investigate whether identity creation failed on a unique constraint.

## Local Keycloak Smoke Test

Before enabling SSO in production, run at least one real-provider smoke test. A local Keycloak instance is sufficient:

```bash
docker run --rm -p 8081:8080 -e KEYCLOAK_ADMIN=admin -e KEYCLOAK_ADMIN_PASSWORD=admin quay.io/keycloak/keycloak:26.0.8 start-dev
```

Create a `visitorportal` realm, a confidential `visitorportal` client, enable the standard authorization-code flow and set the redirect URI to `${APP_URL}/auth/oidc/callback`.

Example local configuration:

```env
APP_URL=http://localhost:8080
AUTH_MODE=local_and_sso
SSO_ENABLED=true
SSO_DRIVER=oidc
OIDC_DISPLAY_NAME="Local Keycloak"
OIDC_ISSUER_URL=http://localhost:8081/realms/visitorportal
OIDC_CLIENT_ID=visitorportal
OIDC_CLIENT_SECRET=<client-secret>
OIDC_REDIRECT_URI=http://localhost:8080/auth/oidc/callback
OIDC_PROVISIONING_MODE=auto
OIDC_REQUIRE_VERIFIED_EMAIL=true
OIDC_SYNC_ROLES=false
```

OIDC issuer URLs are strict. The issuer URL configured in VisitorPortal must be reachable by the app and must match the issuer embedded in the token exactly. If the browser sees Keycloak as `localhost:8081` but the app container uses `keycloak:8080`, issuer validation will fail.

## Troubleshooting

The issuer URL must match the provider metadata exactly. Keycloak issuers are usually realm-specific.

Server clocks must be synchronized. Token validation allows only a small clock tolerance.

The PHP extension `gmp` is installed in the Docker images and configured in CI because the OIDC/JWT dependency stack may require it.
