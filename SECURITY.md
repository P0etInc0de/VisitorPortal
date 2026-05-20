# Security Policy

## Supported Versions

VisitorPortal does not currently have a long-term support release line.

| Version | Support Status |
| --- | --- |
| Current `main` branch | Best-effort security fixes |
| Current tagged releases | Best-effort security fixes |
| Older commits or demo snapshots | Not supported |

This policy may change once the project has stable public releases.

## Reporting A Vulnerability

Please do not report sensitive security issues through public GitHub issues.

Preferred reporting path:

- Use GitHub private vulnerability reporting or GitHub Security Advisories if available for the repository.
- If private vulnerability reporting is not available, contact the maintainers privately through GitHub and ask for a secure reporting channel without disclosing technical details publicly.

Please include:

- Affected version, branch, commit, or Docker image tag
- A clear description of the issue
- Steps to reproduce the issue
- Potential impact
- Whether the issue affects authentication, authorization, visitor data, exports, PDFs, notifications, or admin functionality
- Any known workaround or mitigation

## Security Expectations

VisitorPortal is a production-oriented visitor management system first developed as part of a student team project. Its implemented core workflows are functional and designed to support real visitor management processes, but the project has not undergone a full independent security review.

The application uses server-side authorization checks for protected workflows and Laravel's standard CSRF/session protections. This does not guarantee that the application is safe for production use.

Before using VisitorPortal in a real organization, we strongly recommend reviewing the following areas:
- Threat modeling for the intended deployment
- Authorization review for all roles and permissions
- CSRF, session, and authentication review
- Dependency vulnerability scanning for Composer and npm packages
- File upload and generated PDF review
- Mail, notification, and queue security review
- Logging and auditability review
- Data retention and privacy review
- Infrastructure and reverse proxy hardening
- Backup and restore testing

## Handling Visitor Data

VisitorPortal can store personal data such as visitor names, companies, email addresses, phone numbers, visit times, badge timestamps, and check-in/check-out timestamps.

For real deployments:

- Do not use demo credentials.
- Do not seed demo data into production.
- Set `APP_DEBUG=false`.
- Use a unique `APP_KEY`.
- Use strong database credentials.
- Use HTTPS.
- Restrict admin and receptionist access.
- Configure mail settings carefully.
- Review retention and deletion requirements.
- Review logs for accidental personal data exposure.
- Disable automatic seeding unless explicitly required.

## Dependency Security

Run dependency checks regularly:

```bash
docker compose exec web composer audit
```

For frontend dependencies, run npm audit in the Docker Node 24 environment:

```bash
docker compose run --rm node npm audit --audit-level=moderate
```

Dependency scans do not replace manual security review.

## Public Disclosure

Please give maintainers reasonable time to investigate and fix reported vulnerabilities before publishing details.

This project does not currently offer a bug bounty program.
