# Contributing

Contributions are welcome. Please open an issue before working on larger changes, architectural changes, or broad refactorings.

## Project Status

The implemented workflows are designed for real-world use, while production deployment should include an organization-specific security, privacy and operations review.

Contributions should keep the project understandable, maintainable, and safe for handling visitor-related data.

## Communication

- Use English for code, branch names, commit messages, pull requests, and technical discussions when possible.
- User-facing UI strings should be translatable.
- If you add or change visible UI text, update the translation files in `backend/lang/`.
- Do not include real personal data, customer names, internal company details, credentials, or secrets in issues, commits, tests, screenshots, or seed data.

## Branch Naming

Use short descriptive branch names:

- `feature/<short-description>` for new features
- `fix/<short-description>` for bug fixes
- `docs/<short-description>` for documentation
- `test/<short-description>` for tests only
- `refactor/<short-description>` for refactoring without behavior changes

## Development Setup

Use the Docker-based development setup documented in [docs/setup.md](docs/setup.md).

Typical setup:

```bash
cp backend/.env.example backend/.env
docker compose run --rm web composer install
docker compose up -d --build
docker compose exec web php artisan key:generate
docker compose exec web php artisan migrate:fresh --seed
```

## Code Style

- Keep changes small and focused.
- Prefer readable Laravel and Livewire code over clever abstractions.
- Keep authorization checks server-side.
- Do not rely on frontend state for security decisions.
- Follow existing Blade, Livewire, Filament, Tailwind, and daisyUI patterns.
- Avoid hard-coded customer branding; use the branding configuration instead.
- Avoid broad rewrites unless they are discussed first.

## Tests

Run tests before opening a pull request:

```bash
docker compose exec web php artisan test
```

Run targeted tests while developing:

```bash
docker compose exec web php artisan test tests/Feature/Receptionist/ReceptionAdministerVisitTest.php
```

Run style checks:

```bash
docker compose exec web ./vendor/bin/pint --test -v
```

If your change affects frontend assets, build them:

```bash
docker compose run --rm node npm run build
```

If you cannot run a check, explain why in the pull request.

## Pull Request Checklist

Before submitting a pull request, verify:

- The change has a clear reason.
- The scope is limited to the described issue or feature.
- Relevant tests were added or updated.
- Existing tests pass.
- User-facing strings are translated.
- No secrets or real personal data were added.
- Demo seed data remains fictional and appropriate.
- Documentation was updated if behavior, setup, or configuration changed.
- Security-sensitive changes were reviewed carefully.

## Security Issues

Do not report security vulnerabilities in public issues or pull requests. Follow [SECURITY.md](SECURITY.md).

## Licensing

By opening a pull request, you confirm that you are authorized to contribute the work under the project license.

Contributions are provided under the same license as the project: GNU General Public License v3.0 or later.
