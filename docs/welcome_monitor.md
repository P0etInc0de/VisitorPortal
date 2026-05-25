# Welcome Monitor

The welcome monitor is a public display for reception areas. Treat it as a privacy-sensitive feature because it can expose visitor names, companies and visit context to everyone who can see the screen.

## What It Shows

A monitor displays either:

- its fallback slide, or
- active manual slides, or
- generated slides when auto generation is enabled.

The monitor page refreshes its Livewire data every 5 seconds. Slides rotate in the browser based on the monitor transition time, which defaults to 5 seconds.

## Privacy Defaults

Privacy-sensitive defaults:

- Automatic generation is disabled by default: `BRANDING_MONITOR_AUTO_GENERATION=false`.
- New monitors show a generic fallback slide until an operator enables visitor slides or creates manual slides.
- Confidential visits are never shown by automatic generation.
- Automatic generation only uses visits from the monitor's own site.
- Manual slide visitor selection rejects visitors that are not connected to a non-confidential visit for the monitor site.
- Walk-in visits are confidential by default when `PRIVACY_WALK_IN_CONFIDENTIAL_DEFAULT=true`.

Do not place a monitor where visitors, contractors or unrelated employees can see sensitive visit context unless the operator has reviewed the privacy impact.

## Display Modes

Generated slides use the monitor's display mode. Manual slides can override it per slide.

Supported modes:

- `company_only`: show company names only and deduplicate companies.
- `title_first_name_last_initial`: show title, first name and last-name initial.
- `title_first_initial_last_name`: show title, first-name initial and last name. This is the default.
- `title_full_name`: show title, first name and last name.

Company-only mode is not automatically anonymous. A company name can reveal sensitive context such as a law firm, auditor, union representative, medical provider or security contractor.

## Automatic Generation

When enabled, the scheduler runs auto generation every minute.

Auto generation:

- removes previous generated slides for the monitor,
- selects planned, non-confidential visits within the monitor window,
- uses the monitor site only,
- splits visitors into chunks of up to 6 visitors per slide,
- creates active generated slides with logo and date enabled.

The default generation window is 30 minutes before and after the current time. It can be configured per monitor between 1 and 180 minutes.

Required runtime:

- The `scheduler` service or cron must be running.
- The `queue` service should be running for queued scheduler work.

## Manual Slides

Reception operators can create manual slides for a monitor.

Manual slides can include:

- heading and optional subheading,
- up to 6 visitor names,
- logo/date/time visibility settings,
- display mode override,
- inherited, preset or uploaded background image.

Manual visitor entries can be selected from today's non-confidential site visits or entered as manual names. Manual names should not contain sensitive information.

## Fallback Slide

The fallback slide is shown when no display slides are available. It can be configured with:

- heading,
- subheading,
- logo visibility,
- date visibility,
- background image.

Use the fallback slide for safe generic messages such as reception instructions or a neutral welcome message.

## Image Uploads

Monitor images are public display assets, not private storage.

- Accepted uploads: JPEG, PNG and WebP.
- SVG uploads are not accepted through the monitor forms.
- Uploaded raster images are validated, dimension-checked and metadata-stripped.
- Depending on storage configuration and `storage:link`, uploads can be publicly reachable under `/storage/...`.

Do not upload confidential, personal or sensitive information as monitor backgrounds.

## Retention Behavior

Generated slides are recreated by the scheduler and deleted before each regeneration run for the same monitor.

Visit retention also cleans up old generated monitor slide data and obsolete manual monitor visitor names when related visit data expires and can be safely removed.

Manual slides themselves remain operator-managed content until an operator edits or deletes them.

## Operations

Useful checks:

```bash
php artisan schedule:list
php artisan visitorportal:health scheduler
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f scheduler
```

If generated slides do not update:

- confirm auto generation is enabled for the monitor,
- confirm the scheduler is running,
- confirm matching visits are planned, non-confidential and inside the generation window,
- confirm the monitor and visits belong to the same active site.
