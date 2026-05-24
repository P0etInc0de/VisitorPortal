## Summary

<!-- Briefly describe what this PR changes. -->

## Type of change

- [ ] Bug fix
- [ ] Feature
- [ ] Security / privacy hardening
- [ ] Refactoring
- [ ] Documentation
- [ ] Dependency / infrastructure update
- [ ] UI / UX change
- [ ] Tests only

## Related issue

<!-- Example: Closes #123 -->

## Contributor confirmation

- [ ] I confirm that I am authorized to contribute this work under the project license.

## What changed?

<!-- List the relevant changes. Keep this concise but specific. -->

- 
- 
- 

## Security and privacy checklist

- [ ] No secrets, tokens, private keys, or credentials were added.
- [ ] No unnecessary personal data is stored or exposed.
- [ ] Authorization and object-level access checks were considered.
- [ ] SSO/MFA behavior was considered, if authentication is affected.
- [ ] Recovery-code behavior was considered, if MFA is affected.
- [ ] Retention/deletion behavior was considered, if personal data is affected.
- [ ] Upload handling was considered, if files/images are affected.
- [ ] Logs do not include secrets, MFA codes, recovery codes, OIDC tokens, or personal data beyond what is necessary.

## UI / UX checklist

- [ ] The change was checked in light mode.
- [ ] The change was checked in dark mode.
- [ ] The change was checked on a smaller viewport.
- [ ] Existing layout and navigation were not changed unintentionally.
- [ ] User-facing German text uses correct spelling and umlauts.

## Tests and verification

Please check all that apply:

- [ ] `./vendor/bin/pint --test`
- [ ] `php artisan test`
- [ ] `npm run build`
- [ ] `composer audit`
- [ ] `npm audit --omit=dev`
- [ ] `docker compose config --quiet`
- [ ] `docker compose -f docker-compose.demo.yml config --quiet`
- [ ] `docker compose -f docker-compose.prod.yml config --quiet`
- [ ] SSO smoke test, if SSO/MFA was affected
- [ ] Manual browser test, if UI/auth/reception/monitor flows were affected

## Manual test notes

<!-- Describe what you actually tested. For UI changes, include the browser and relevant screens. -->

## Screenshots

<!-- Add screenshots for UI, PDF, badge, monitor, and reception-flow changes. Redact personal data. -->

## Migration / deployment notes

<!-- Mention migrations, config changes, env variables, changed Docker images, seed data changes, or operational steps. -->

## Reviewer focus

<!-- Tell reviewers what they should pay special attention to. -->
