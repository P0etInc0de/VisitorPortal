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
APP_MFA_REQUIRED_ROLES=admin,super_admin
APP_MFA_REQUIRED_FOR_AUTH_METHODS=local
APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS=local,sso
```

Privileged users must configure and confirm local app MFA before they can continue using the portal after the configured login methods. Required MFA setup uses the dedicated `/security/mfa/required` onboarding flow without normal app navigation; the regular profile security page remains available for later account security management.

The Filament admin panel has an additional MFA middleware. By default, admin and super admin accounts need confirmed local app MFA before accessing `/admin` after both local and SSO logins. In `APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS`, `sso` means VisitorPortal requires additional local app MFA after SSO login; it does not mean MFA is only expected in the external identity provider.

List-style `APP_MFA_*` settings can be intentionally disabled with an empty value or `none`.

## Recovery Process

Users with MFA should store their recovery codes securely. Each recovery code can be used once during the two-factor login challenge.

Recovery codes are not shown on the regular profile security page. Users must explicitly open the recovery-code page and confirm their password before existing codes are displayed or regenerated.
Likewise, the QR code for an unconfirmed MFA setup is only shown on a password-confirmed setup page.

`MFA_ENABLED=false` disables role-based enforcement. It does not silently bypass already configured MFA; users with confirmed MFA are still challenged during login.

Operators with shell access can disable MFA for a user without exposing secrets:

```bash
php artisan visitorportal:disable-mfa user@example.org
```

The command asks for confirmation by default. For non-interactive operator runbooks, use `--force` deliberately:

```bash
php artisan visitorportal:disable-mfa user@example.org --force
```

The command clears the local TOTP secret, recovery codes and confirmation timestamp, and writes a security log entry.

Privileged roles that require MFA cannot disable MFA through the Fortify endpoint. A reset by a trusted operator forces them back through MFA setup.

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

## Single Sign-On

Fortify remains the local authentication backend. SSO is added as a separate optional OpenID Connect login entry point that resolves or creates the same local `users` records.

Implemented architecture:

- `AuthRedirector` centralizes post-login redirects for local login and SSO login.
- `UserSessionPreferences` centralizes locale and theme session setup.
- `user_identities` stores external identities by `provider + issuer + subject`.
- `config/sso.php` is disabled by default and contains generic OIDC configuration.
- The session key `auth.method` distinguishes `local` and `sso` logins for MFA policy decisions.
- The Facile OIDC library is only used inside the internal adapter.

Do not rely on e-mail alone as an SSO identifier. OIDC providers map `provider + issuer + subject` to a local user, then local roles, sites and permissions decide portal access.

Auto-provisioned SSO users have local password login disabled by default. Password reset and local login both respect `local_login_allowed`.

## Content Security Policy

The default CSP is Livewire/Alpine compatible and therefore allows `unsafe-inline` and `unsafe-eval` for scripts/styles. This is a conscious compatibility trade-off, not a strict CSP profile.

In `APP_ENV=local`, the CSP also allows the Vite dev server at `http://localhost:5173` and `ws://localhost:5173` so local hot reload works. Production does not include these development origins.
