# Historical Architecture Decisions

This file keeps short historical ADR notes. It is not the primary operator documentation. Public release documentation is maintained in English; `README.de.md` is the only German top-level user document.

## ADR-001 Language And Project Conventions

Status: superseded for public documentation.

Decision:

- Code, class names, variables, methods, branches, commits and technical tickets use English.
- Public documentation in `README.md` and `docs/` uses English.
- `README.de.md` remains as the German entry point.
- UI strings are translatable and may be localized through Laravel language files.

Reason:

- Laravel and most ecosystem documentation are English.
- Open-source release material should be understandable internationally.
- German remains useful for local users through `README.de.md` and UI translations.

## ADR-002 Permission System

Status: accepted.

Decision:

- Use `spatie/laravel-permission` for roles and permissions.
- Use Filament Shield for admin-panel permission integration.
- Keep application authorization in Laravel policies and permission checks.

Reason:

- Mature Laravel ecosystem package.
- Integrates with gates, policies and Blade directives.
- More maintainable than a custom role system.

Current project version: `spatie/laravel-permission` 6.x.

References:

- [Spatie Laravel Permission v6 Introduction](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Blade Directives](https://spatie.be/docs/laravel-permission/v6/basic-usage/blade-directives)
- [Using Policies](https://spatie.be/docs/laravel-permission/v6/best-practices/using-policies)
- [Filament Shield](https://filamentphp.com/plugins/bezhansalleh-shield)

## ADR-003 Docker-First Development

Status: accepted.

Decision:

- Local development is Docker-first.
- `docker-compose.yml` mounts the source tree and provides PHP, Node.js, MariaDB, Mailhog, queue, scheduler and Gotenberg services.

Reason:

- Reproducible onboarding.
- Fewer host-specific dependency issues.
- Better parity between local services and deployment dependencies.

## ADR-004 UI Stack

Status: accepted.

Decision:

- Use Laravel Blade, Tailwind CSS and daisyUI for the portal UI.
- Use Livewire where it provides clear value, but do not make the whole UI Livewire-first by default.
- Use Filament for the internal admin panel.

Reason:

- Blade plus Tailwind/daisyUI is simple and flexible for the main portal.
- Filament is efficient for internal CRUD/admin workflows.
- This keeps the user-facing portal separate from the admin panel.

## ADR-005 PDF Generation

Status: accepted.

Decision:

- Use Spatie Laravel PDF with Gotenberg for badge PDFs.
- Generate PDFs from Blade/HTML/CSS templates.

Reason:

- Gotenberg provides Chromium-based rendering with modern CSS support.
- Server-side PDF generation is reproducible across browsers and devices.

References:

- [Spatie Laravel PDF v2](https://spatie.be/docs/laravel-pdf/v2/introduction)
- [Gotenberg](https://gotenberg.dev/)

## ADR-006 Optional Visitor Contact Data

Status: accepted.

Decision:

- Visitor e-mail and phone are optional by default.
- Visitor identity is based on internal database IDs, not e-mail or phone.
- Operators can require contact data through `VISITOR_CONTACT_REQUIREMENT` when they have a documented operational need.

Reason:

- Not every visit requires direct visitor contact.
- Mandatory contact fields can encourage fake placeholders and lower data quality.
- Optional defaults are better aligned with data minimization.

## ADR-007 Generic OpenID Connect SSO

Status: accepted.

Decision:

- Implement provider-independent OpenID Connect SSO.
- Store external identities by `provider + issuer + subject`.
- Do not use e-mail as the primary external identity key.
- Keep auto-provisioning and role sync disabled by default.

Reason:

- OIDC fits enterprise SSO better than provider-specific social login integrations.
- The internal adapter reduces vendor lock-in.
- Security-sensitive identity linking must not rely on mutable e-mail addresses alone.
