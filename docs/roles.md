# Roles and Permissions

VisitorPortal uses Spatie Laravel Permission and Filament Shield for role-based access control. Application code also uses Laravel policies for site, department and ownership scoping.

Synchronize the default roles and permissions after installation and after authorization changes:

```bash
php artisan visitorportal:sync-permissions
```

The command is idempotent.

## Default Roles

`user`:

- Standard employee role.
- Can create visits and visitors.
- Can view and edit own visits where the user is host or substitute.
- Can use known visitors without browsing the full global visitor archive.
- Can manage own profile.

`manager`:

- Includes `user` permissions.
- Adds department-scoped user and visit visibility.
- Can create and manage visits for the user's department.

`receptionist`:

- Reception and security-desk role.
- Can view site-scoped visits.
- Can create site visits.
- Can check visitors in and out.
- Can print visitor badges.
- Can view contact details needed for reception work.
- Can manage welcome monitors and monitor slides for assigned sites.

`admin`:

- Administrative role for users, roles, departments, sites, visitors, visits and monitors.
- Has broad application permissions synchronized by `visitorportal:sync-permissions`.
- Is also configured as the Filament Shield super-admin role in `config/filament-shield.php`.

`welcome monitor`:

- Restricted display-account role.
- Can view the assigned site's monitor and slides.
- Intended for devices that should show the public welcome monitor, not for normal user work.

## Super Admin Note

The default synchronized role set contains `admin`, not a separate `super_admin` role. MFA configuration includes `super_admin` as a supported privileged role name so operators can introduce it deliberately if they customize roles. Out of the box, Filament Shield treats `admin` as the super-admin role.

## Site And Department Scoping

VisitorPortal is designed for one organization per instance with multiple physical sites.

- `sites` represent receptions, buildings, plants or offices.
- `visits.site_id` is stored directly so historical visits do not change when a user moves to another site.
- `departments`, `users`, `visits`, `monitors` and recurring visit series are site-aware.
- Users have one primary site and can be assigned to additional sites.
- Reception visibility is site-scoped.
- Manager visibility is department-scoped.
- Employee visibility is ownership-scoped.

Run separate instances for legally separate organizations unless full multi-tenancy has been implemented and audited.

## Visitor Master Data Visibility

Visitor records are organization-wide master data to reduce duplicate external-person records across sites.

Access to visitors is still constrained by permissions and context:

- Standard employees can use known visitors related to their own work, but do not get a global visitor directory by default.
- Reception can use visitors needed for reception processes at assigned sites.
- Admins can manage visitor master data.
- Contact detail visibility is permission-controlled.

Visitor e-mail and phone are optional by default. The technical identity of a visitor is the internal database ID, not e-mail or phone.

## Welcome Monitor Privacy

Welcome monitors are public displays. They must be configured conservatively.

- Confidential visits are never shown automatically.
- Auto generation is disabled by default.
- Generated slides only use visits from the monitor's own site.
- Manual slide selection only accepts visitors from non-confidential visits associated with the monitor site.
- The default display mode is `title_first_initial_last_name`.

See [Welcome Monitor](welcome_monitor.md) for operational privacy guidance.

## SSO Role Mapping

OIDC role sync is disabled by default. If enabled, only explicitly configured OIDC groups map to local roles. Unknown groups are ignored. Invalid mappings fail closed.

Do not derive admin access from e-mail domains. Use dedicated IdP groups and test role mapping with a real-provider smoke test before production rollout.
