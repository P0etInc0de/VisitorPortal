# Single Sign-On

VisitorPortal supports optional generic OpenID Connect SSO.

## Supported Concept

The implementation is provider-independent and can be configured for OIDC-compatible identity providers such as Microsoft Entra ID, Keycloak, Authentik, Okta, Auth0, Google Workspace and GitLab OIDC.

SSO is not implemented as a Microsoft-specific login and does not use Laravel Socialite. The OIDC client library is wrapped behind an internal adapter.

## Security Model

External users are identified by `provider + issuer + subject`. E-mail addresses are not used as primary external identity keys.

E-mail addresses are only used as a controlled linking or provisioning helper. Invited-only linking requires a verified e-mail claim from the identity provider.

Tokens are not stored and must not be logged.

## Defaults

SSO is disabled by default:

```env
AUTH_MODE=local
SSO_ENABLED=false
SSO_DRIVER=oidc
OIDC_PROVISIONING_MODE=disabled
OIDC_SYNC_ROLES=false
OIDC_SYNC_ROLES_ON_LOGIN=false
OIDC_LOGOUT_MODE=local
```

Auto-provisioning and role synchronization are disabled by default.

## Auth Modes

`local` means local Fortify login only. OIDC routes return 404.

`local_and_sso` enables both local login and SSO login.

`sso_only` enables SSO for normal users. Local login is only allowed for accounts with the `LoginLocallyInSsoOnlyMode` permission.

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
OIDC_SYNC_ROLES=false
OIDC_SYNC_ROLES_ON_LOGIN=false
OIDC_SYNC_ROLES_REMOVE_UNMAPPED=false
OIDC_GROUPS_CLAIM=groups
OIDC_LOGOUT_MODE=local
```

Provisioning modes:

`disabled` only allows already linked external identities.

`invited_only` links an existing local user by verified e-mail.

`auto` creates a local user on first successful SSO login. Auto-provisioned users get `local_login_allowed=false` so local password login and password reset do not bypass the IdP.

## Role Mapping

Role mapping only uses explicitly configured groups. Unknown groups are ignored.

By default, role sync does not remove existing local roles when no mapped group is present. `OIDC_SYNC_ROLES_REMOVE_UNMAPPED=true` makes the IdP authoritative and can remove roles, so enable it only deliberately.

For Microsoft Entra ID, use dedicated application groups and ensure they are emitted directly as OIDC claims. VisitorPortal does not call Microsoft Graph or other provider APIs to resolve group overage.

## Recommended Enterprise Setup

- Use a tenant-specific issuer.
- Enforce MFA in the identity provider.
- Keep a local break-glass admin account with `LoginLocallyInSsoOnlyMode`.
- Use dedicated VisitorPortal groups.
- Use HTTPS in production.
- Do not enable auto-provisioning unless intentionally wanted.
- Do not map admin rights from e-mail domains.

## Troubleshooting

The issuer URL must match the provider metadata exactly. Keycloak issuers are usually realm-specific.

Server clocks must be synchronized. Token validation allows only a small clock tolerance.

The PHP extension `gmp` is installed in the Docker images and configured in CI because the OIDC/JWT dependency stack may require it.
