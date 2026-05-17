# Security Hardening

## Authentication Model

VisitorPortal uses Laravel Fortify as the central backend for local authentication flows:

- Local login and logout
- Password reset
- Password confirmation
- Two-factor authentication with TOTP
- Recovery codes
- Two-factor challenge during login

The application keeps its own Blade UI, role model, redirect logic, active-user checks and Filament access rules.

Email verification is intentionally not enforced in the current Fortify/MFA step. VisitorPortal is an internal, admin-managed system without self-service registration. Enabling email verification should be handled as a separate migration step for existing users.

## MFA Defaults

MFA is available for all users. By default it is required for privileged roles:

```env
MFA_ENABLED=true
MFA_OPTIONAL_FOR_USERS=true
MFA_REQUIRED_ROLES=admin,super_admin
MFA_REQUIRED_FOR_ADMIN_PANEL=true
MFA_ENFORCED_AUTH_METHODS=local,sso
```

Privileged users must configure and confirm MFA before they can continue using the portal. This applies to the whole privileged account, not only to the Filament admin panel.

The Filament admin panel has an additional MFA middleware. When `MFA_REQUIRED_FOR_ADMIN_PANEL=true`, admin and super admin accounts need confirmed MFA before accessing `/admin`.

## Recovery Process

Users with MFA should store their recovery codes securely. Each recovery code can be used once during the two-factor login challenge.

Operators with shell access can disable MFA for a user without exposing secrets:

```bash
php artisan visitorportal:disable-mfa user@example.org
```

The command clears the local TOTP secret, recovery codes and confirmation timestamp, and writes a security log entry.

## Recommended Account Model

For production environments, operators should create separate named accounts for daily work and administrative tasks.

Example:

- `jane.doe@example.org` for normal daily work
- `admin.jane.doe@example.org` for administrative tasks

Admin accounts should:

- Be personal, not shared
- Use MFA
- Only be used for administrative work
- Not be used for daily visitor planning

This is recommended but not technically enforced, so small organizations and demo setups can stay simple.

## SSO Preparation

Fortify does not block future SSO. SSO should later be added as another login entry point that resolves or creates the same local `users` records.

Prepared architecture:

- `AuthRedirector` centralizes post-login redirects for local login and future SSO login.
- `UserSessionPreferences` centralizes locale and theme session setup.
- `user_identities` stores external identities by provider, tenant and provider user ID.
- `config/sso.php` is present but disabled by default.
- The session key `auth.method` can distinguish `local` and future `sso` logins for MFA policy decisions.

Do not rely on email alone as an SSO identifier. Future providers such as Entra ID should map `provider + tenant_id + provider_user_id` to a local user, then local roles, sites and permissions decide portal access.
