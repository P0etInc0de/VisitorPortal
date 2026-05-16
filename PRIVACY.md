# Privacy Notes

VisitorPortal is designed with privacy-friendly defaults, but it is not automatically GDPR-compliant by deployment alone. The operating organization remains responsible for the legal basis, visitor information duties, role configuration, retention periods, security controls, processor agreements, and any required data protection impact assessment.

## Personal Data Processed

Depending on configuration and usage, VisitorPortal may process:

- Visitor names, optional salutation/title, company, optional e-mail address, optional phone number, and operational notes.
- Visit metadata such as host, substitute/reception contact, scheduled time, status, check-in/check-out timestamps, badge-print status, and cancellation metadata.
- User account data such as name, e-mail address, department, roles, language/theme preference, and authentication metadata.
- Notification records and technical logs with minimized identifiers.
- Welcome-monitor slide data, which can include visitor display names when configured or generated.

## Privacy Defaults

- Visitor e-mail and phone fields are optional by default.
- `VISITOR_CONTACT_REQUIREMENT` can be configured to require one or specific contact fields when the operator has a documented need.
- Normal users do not receive the global visitor directory. Existing visitor suggestions are scoped to records visible to the current user and exclude contact details from the page payload.
- Existing visitor records are not automatically overwritten by normal visit creation. Updating visitor master data requires visitor update permissions.
- Demo seeders and demo credentials are blocked in `production`.
- Automated retention is available through `php artisan visits:purge-expired` and is scheduled when `PRIVACY_PURGE_ENABLED=true`.
- Welcome-monitor auto generation is disabled by default; operators must intentionally enable generated visitor slides.
- `PRIVACY_NOTICE_URL` can be configured for operator documentation or future external flows, but it is not automatically displayed in internal staff forms.

## Operator Responsibilities

Before production use, the operator should define and document:

- The legal basis for visitor registration and badge processing.
- The privacy notice provided to visitors at or before data collection.
- Retention periods for visits, visitors, notifications, logs, backups, and monitor displays.
- Which roles may view contact details, create visitors, check visitors in/out, print badges, and administer visitor master data.
- Whether welcome monitors may show full visitor names or should use a data-minimized display mode.
- How visitors are informed, for example by reception notice, QR code, website, invitation text, or paper notice.
- Backup encryption, backup retention, log retention, and incident-response procedures.
- Whether MFA, SSO, network restrictions, or additional audit logging are required.

## Visitor Notice Template

Operators can adapt the following short notice for visitor-facing communication:

> We process your visitor data to organize your visit, inform your host, support reception/check-in processes, and issue visitor badges where required. The data may include your name, company, visit time, host, badge/check-in status, and optional contact details depending on local requirements. Data is retained only for the configured operational retention period unless a legal hold applies. For details, contact the organization operating this VisitorPortal instance.

This template is not legal advice and should be reviewed for the operator's jurisdiction, legal basis, retention period, controller identity, data-subject rights, recipients, and contact details.

## Welcome Monitor Privacy

Displaying full visitor names on public or semi-public monitors can create additional privacy risk. Operators should prefer data-minimized monitor content unless full names are necessary and appropriately documented.

Possible lower-risk configurations include:

- Showing company names or appointment categories instead of full names.
- Showing only current/imminent visits.
- Disabling manual visitor-name slides where not needed.
- Ensuring monitor data is covered by the retention policy.

## Retention And Legal Holds

The retention command removes expired visits, expired recurring visit series without retained/future visits, orphaned visitors, old notifications, stale auto-generated monitor slides, and old manual monitor visitor-name payloads. It also removes monitor-slide references to visitors purged by the same run.

A `retention_hold_reason` without `retention_hold_until` is treated as an indefinite legal hold and prevents automated visit deletion. Operators should review indefinite legal holds regularly and document who approved them and why.
