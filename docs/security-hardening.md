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
APP_MFA_SESSION_TTL_MINUTES=720
```

Privileged users must configure and confirm local app MFA before they can continue using the portal after the configured login methods. Required MFA setup uses the dedicated `/security/mfa/required` onboarding flow without normal app navigation and without Fortify password confirmation, so SSO-only users without a local password can complete mandatory app MFA. After confirmation, the onboarding flow shows the recovery codes once and then continues to the originally requested URL.

VisitorPortal treats configured MFA and session-satisfied MFA as separate states. `hasConfirmedTwoFactorAuthentication()` only means that local app MFA exists for the account. A separate session marker records that the current session has passed a local app-MFA challenge by TOTP or recovery code. `APP_MFA_SESSION_TTL_MINUTES` limits this marker within the current web session; if the web session ends earlier, the app-MFA confirmation ends with it.

The regular profile security page remains available for later voluntary account security management. Voluntary profile MFA setup still uses password confirmation before QR codes or secrets are displayed; recovery-code management uses the SSO-compatible app-MFA step-up described below.

The Filament admin panel has an additional MFA middleware. By default, admin and super admin accounts need session-satisfied local app MFA before accessing `/admin` after both local and SSO logins. `APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS` means that the admin panel requires an App-MFA-confirmed session; it does not mean every navigation into `/admin` prompts again. If local app MFA was already confirmed during login or by an app-MFA challenge in the same session, the user can move between the user area and admin panel without entering MFA again. If local app MFA is configured but has not yet been passed in the current session, the user is redirected to `/security/mfa/challenge`. In `APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS`, `sso` means VisitorPortal requires an additional local app-MFA challenge after SSO login; it does not mean MFA is only expected in the external identity provider.

List-style `APP_MFA_*` settings can be intentionally disabled with an empty value or `none`.

## Recovery Process

Users with MFA should store their recovery codes securely. Each recovery code can be used once during any MFA fallback path: local login, SSO/app-MFA challenge, admin-panel app-MFA challenge and purpose-bound step-up flows. After successful use, the code is removed from the active recovery-code list in a database transaction with a row lock, so parallel requests cannot consume the same code twice. Other unused recovery codes remain valid, and explicit regeneration creates a new full active list.

Recovery codes are not shown on the regular profile security page. Users must explicitly open the recovery-code page and pass a fresh, purpose-bound step-up challenge before existing codes are displayed or regenerated. The step-up is fresh for 10 minutes and works for SSO-only users without a local password.

Recovery-code viewing and regeneration use different purposes. Viewing existing codes requires a fresh TOTP app-MFA step-up (`recovery-codes:view`); a recovery code cannot be used to reveal all existing recovery codes. Regeneration uses `recovery-codes:regenerate` and accepts either TOTP or a recovery code as a recovery scenario. Regeneration replaces old codes and then shows the newly generated codes once.

Fortify's default recovery-code endpoints are intentionally blocked. Recovery codes may only be viewed or regenerated through the VisitorPortal profile security flow so these purpose-specific step-up rules cannot be bypassed.

Sensitive MFA events are written to the web log without codes, secrets or token material. Logged events include app-MFA step-up completion/failure, recovery-code use, recovery-code viewing and recovery-code regeneration.

Mandatory MFA onboarding is the exception: it uses the fresh login or SSO session instead of `password.confirm`, then displays recovery codes immediately after the TOTP code has been confirmed.

`MFA_ENABLED=false` disables role-based enforcement. It does not silently bypass already configured MFA; users with confirmed MFA are still challenged during login.

`visitorportal:disable-mfa` is a break-glass and support tool for trusted operators with server shell access. It can disable MFA even when MFA is mandatory for the affected user's login or admin-panel context, so it must only be used for account recovery or support cases. Operators can disable MFA for a user without exposing secrets:

```bash
php artisan visitorportal:disable-mfa user@example.org
```

The command asks for confirmation by default. For non-interactive operator runbooks, use `--force` deliberately:

```bash
php artisan visitorportal:disable-mfa user@example.org --force
```

The command clears the local TOTP secret, recovery codes and confirmation timestamp, and writes a security log entry. If MFA is mandatory for that user, the next protected access forces the user back through MFA setup.

Privileged roles that require app MFA in any configured login or admin-panel context cannot disable MFA through the Fortify endpoint. A reset by a trusted operator forces them back through MFA setup.

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

## Branding and Monitor Image Uploads

Branding, logo and welcome-monitor image uploads are treated as public display assets, not as a private document store. Depending on the configured storage disk and `php artisan storage:link`, uploaded monitor backgrounds can be publicly reachable under `/storage/...`. Do not upload confidential, personal or otherwise sensitive information as branding, logo or monitor images.

Raster image uploads are validated server-side. The accepted upload MIME types are `image/jpeg`, `image/png` and `image/webp`; SVG is not accepted through the monitor upload forms. The shipped SVG branding files are trusted operator-managed static assets configured by path, not user uploads.

The 20 MB upload limit is intentionally retained for large displays. Additional defensive limits reject pathological dimensions and decompression-bomb style images:

```env
UPLOAD_IMAGE_MAX_SIZE_KB=20480
UPLOAD_IMAGE_MAX_WIDTH=20000
UPLOAD_IMAGE_MAX_HEIGHT=20000
UPLOAD_IMAGE_MAX_PIXELS=150000000
```

Width/height and total pixels are both checked. For example, `20000 x 2000` is allowed by the pixel guard, while `20000 x 20000` is rejected because it exceeds `UPLOAD_IMAGE_MAX_PIXELS`.

Uploaded JPEG, PNG and WebP files are normalized before they are stored publicly. EXIF/XMP/text/profile-style metadata chunks are removed where the format supports them, including GPS and camera metadata in JPEG EXIF blocks. The original uploaded file is not stored directly.
