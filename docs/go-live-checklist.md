# Go-Live Checklist

Complete this checklist before using VisitorPortal with real visitor data.

## Environment

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` is set, secret and backed up securely.
- `APP_URL` is the final HTTPS URL.
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `TRUSTED_PROXIES` is configured for the actual proxy path.
- Demo `.env.demo` is not used.

## Data And Bootstrap

- Production database credentials are unique and secret.
- `AUTO_SEED=false`
- `FORCE_SEED=false`
- Demo users are absent.
- Initial admin account was created intentionally.
- `visitorportal:sync-permissions` was run after deployment.
- Database migrations completed successfully.

## Security

- HTTPS works end to end.
- MFA policy was reviewed.
- Privileged users enrolled MFA.
- Recovery codes were stored securely.
- Break-glass account and MFA reset process are documented.
- SSO, if enabled, was tested against the real identity provider.
- SSO role mapping, if enabled, uses explicit groups and not e-mail domains.
- File and directory permissions were checked.

## Operations

- Queue worker is running and healthy.
- Scheduler is running and healthy.
- `GET /up` is monitored.
- Failed-job monitoring is in place.
- Application, queue and scheduler logs are monitored.
- SMTP delivery was tested.
- Gotenberg badge PDF generation was tested.
- Backups include database and storage.
- Restore from backup was tested.

## Privacy

- Privacy notice is prepared and distributed through appropriate channels.
- `PRIVACY_NOTICE_URL` is set when guest-facing mails should include it.
- Visitor contact requirement was reviewed.
- Retention periods were reviewed.
- `visits:purge-expired --dry-run` was reviewed.
- `privacy:purge-technical-data --dry-run` was reviewed.
- Welcome monitor behavior was reviewed for public-display privacy.
- Confidential visits and walk-in defaults were reviewed.

## Final Smoke Test

- Login works for an admin account.
- Login works for a standard employee account.
- A visit can be created.
- Reception can check a visitor in and out.
- Badge PDF generation works.
- E-mail notification path works in staging or production test conditions.
- Welcome monitor shows only intended public information.
