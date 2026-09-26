# Kiosk Mode and Self-Check-In

VisitorPortal can expose public kiosk terminals that let visitors check themselves in and out without a receptionist account.

A kiosk is a device-bound web page addressed by an unguessable token. It is intended to run full-screen on a tablet or touch terminal in a reception area.

## Security Model

- A kiosk is reached through a public URL (`/kiosk/{token}`) that does **not** require a login.
- The token is a 48-character random string. Anyone with the URL can use the terminal, so treat the URL like a physical key: only display it on the intended device and rotate it if it leaks.
- Each kiosk is bound to exactly one site. Search, check-in, check-out and walk-in registration are always scoped to that site.
- When a kiosk is deactivated or its token is regenerated, the old URL immediately returns `404`.
- Search and code entry endpoints are rate limited. Search only returns results after at least two characters.
- Search results only expose the visitor name, company, host and appointment time. Contact details are never shown on a public terminal.
- Walk-ins are created in the name of a preconfigured host. The terminal never exposes the employee directory.
- All check-in/check-out actions go through the same `VisitActionService` used by the reception surfaces, including host notifications. Because no authenticated user triggers the action, `checked_in_by_user_id` / `checked_out_by_user_id` remain `null`.

## Managing Terminals

Create and edit terminals in the admin panel under **Kiosk-Terminals**.

Fields:

- **Name**: label for operators, e.g. "Empfang Haupteingang".
- **Standort**: the site the terminal is bound to.
- **Host für Walk-ins**: the user that walk-in visits are assigned to. Walk-in self-registration is disabled when this is empty.
- **Aktiv**: turns the terminal on or off.
- **Walk-in Self-Registration erlauben**: shows or hides the walk-in option.
- **Self-Check-out erlauben**: shows or hides the check-out option.
- **Unterschrift erforderlich**: requires visitors to sign before a check-in is completed.

The list view shows the public **Kiosk-URL** and it can be copied to the device. The edit page has a **Kiosk-URL neu erzeugen** action that invalidates the previous link.

## Visitor Flows

The home screen offers up to four large touch targets:

1. **Ich habe einen Termin** – searches planned visits at the kiosk site by first name, last name or company and checks the selected participant in.
2. **QR-Code scannen** – accepts the check-in code shown in the visitor portal. A USB QR scanner that acts as a keyboard types the code and submits it. The code can also be entered manually.
3. **Ich habe keinen Termin** – walk-in self-registration. The visitor enters name and contact details and is immediately checked in.
4. **Auschecken** – searches currently checked-in guests at the site and checks the selected participant out.

After a successful action the terminal shows a confirmation and automatically returns to the home screen after the configured timeout.

## QR Check-In

Every visit receives a random `check_in_code` when it is created. The visitor portal shows a QR code that encodes this code on the visit detail page. A visitor presents the QR code at the kiosk; the kiosk resolves the code within its own site and checks in every participant of that visit that is not already checked in.

Code entry is rate limited. Codes from another site are rejected.

## Signature Capture

When **Unterschrift erforderlich** is enabled on a kiosk, every check-in flow (pre-registered search, QR code and walk-in) shows a signature pad before the check-in is completed. The visitor signs with finger or stylus, **Löschen** clears the pad and **Bestätigen & einchecken** stores the signature and performs the check-in.

- The pad is an HTML5 canvas driven by Pointer Events with `touch-action: none`, so it works with a mouse, a finger and an Apple Pencil / stylus on iPad, including pressure-sensitive stroke width for pens.
- The signature is stored as a PNG data URL together with a `signed_at` timestamp on the visit participant, so it is removed when the visit is purged.
- Captured signatures are shown to authorised staff on the visit detail page in the portal.
- The signature is printed on the visitor badge PDF (when the badge is printed after check-in).
- The host check-in notification email and the guest check-in confirmation email carry the signature as a PNG attachment. The guest confirmation is only sent when the visitor has an email address.

A missing or empty signature is rejected; a check-in is not completed without a signature while the requirement is active.


## Permissions

The synchronized permissions use the `Kiosk` resource:

- `ViewAny:Kiosk`, `View:Kiosk`, `ViewSite:Kiosk`, `ManageSite:Kiosk` – receptionist and admin.
- `Create:Kiosk`, `Update:Kiosk`, `Delete:Kiosk`, `ManageAny:Kiosk` – admin only.

Run `php artisan visitorportal:sync-permissions` after deployment.

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `KIOSK_AUTO_RESET_SECONDS` | `45` | Seconds of inactivity on the confirmation screen before returning home. |
| `KIOSK_SEARCH_RESULT_LIMIT` | `15` | Maximum number of search results shown. |
| `KIOSK_TOUCH_LAST_SEEN_SECONDS` | `60` | Minimum interval between `last_seen_at` updates for a terminal. |

The check-in window uses `RECEPTION_CHECK_IN_WINDOW_HOURS`. Walk-in confidentiality follows `PRIVACY_WALK_IN_CONFIDENTIAL_DEFAULT`.

## Privacy Notes

A public terminal processes personal data of visitors who are physically present. Before deploying kiosk terminals:

- Review the retention and privacy documentation.
- Ensure the terminal is physically supervised or locked down so it cannot be used to enumerate visitors.
- Captured signatures are personal data. Review whether signing is legally required before enabling **Unterschrift erforderlich**, and document the purpose and retention.
- Consider rotating tokens when a device is moved or retired.
- Keep the kiosk browser in a locked-down full-screen mode and disable access to other tabs and the address bar where possible.

## Browser Tests

The kiosk flows are covered by a Playwright test at `backend/tests/e2e/kiosk-self-check-in.spec.ts`. It creates its own fixtures directly in the database and does not need seeded demo data.

Run it against a running instance:

```bash
cd backend
BASE_URL=http://localhost:8080 npm run test:e2e:kiosk
```

The test connects to the database using `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`.

