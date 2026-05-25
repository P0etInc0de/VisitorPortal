# Known Limitations

This document lists important release limitations so operators can make informed decisions before using VisitorPortal with real visitor data.

## Security And Compliance

- No full independent security audit has been completed.
- VisitorPortal is not legal advice and does not make a deployment privacy-compliant by default.
- Operators remain responsible for legal basis, privacy notices, retention policy, access reviews and incident handling.
- The default CSP is framework-compatible, not a strict locked-down CSP profile.

## Product Scope

- No full multi-tenancy for legally separate organizations is implemented or audited.
- Visitor self-check-in is not implemented yet.
- Built-in monitoring and alerting are intentionally limited to health commands and endpoints.
- Reporting and export workflows are limited compared with dedicated enterprise visitor-management suites.

## Operations

- Docker Compose does not automatically restart containers only because they are unhealthy.
- Backups, restore tests, log retention, reverse-proxy retention and SIEM integration are operator responsibilities.
- Production SMTP, HTTPS and reverse-proxy behavior must be tested in the target environment.

## SSO

- Generic OpenID Connect is supported, but every identity provider configuration must be validated with that provider.
- Role mapping is disabled by default and must be tested before production use.
- VisitorPortal does not resolve Microsoft Entra group overage through Microsoft Graph or other provider APIs.

## Welcome Monitor

- Welcome monitors are public displays and can expose personal or organizational context if configured carelessly.
- Auto generation is disabled by default, but operators remain responsible for monitor placement and display mode choices.
- Uploaded monitor images are public display assets, not private storage.
