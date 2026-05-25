# Release Artifacts

GitHub releases can contain multiple downloadable archives. Choose the artifact by use case.

## Which ZIP Should I Download?

Use `VisitorPortal-demo-vX.Y.Z.zip` for a quick local demo.

- Intended for non-technical trial runs.
- Contains `docker-compose.demo.yml`, start/stop/reset/update scripts and a demo README.
- Uses the prebuilt Docker image from GitHub Container Registry.
- Pins `VISITORPORTAL_VERSION` to the release tag.
- Includes demo credentials and demo data behavior.
- Not suitable as a production configuration.

Use `besucherportal-vX.Y.Z.zip` for source-based review or deployment work.

- Contains the project source from the release workflow.
- Excludes generated dependency directories such as `backend/vendor` and `backend/node_modules`.
- Replaces `RELEASE_VERSION_PLACEHOLDER` in `.env.demo.example` with the release tag.
- Requires normal source setup steps for development or LAMP-style deployments.

Use the GitHub-generated `Source code (zip)` or `Source code (tar.gz)` only if you specifically want GitHub's automatic repository snapshot.

- It is not tailored by VisitorPortal's release workflow.
- It may still contain placeholders such as `RELEASE_VERSION_PLACEHOLDER`.
- It is usually less convenient than `besucherportal-vX.Y.Z.zip`.

Use the container image for production Docker deployments.

```text
ghcr.io/p0etinc0de/besucherportal:vX.Y.Z
```

Set it in the production `.env`:

```env
VISITORPORTAL_IMAGE=ghcr.io/p0etinc0de/besucherportal:vX.Y.Z
```

## Production Warning

Never use demo credentials, demo seed data or demo `.env` defaults in production.

Before going live, review [Production Deployment](deployment.md), [Security Hardening](security-hardening.md) and the [Go-Live Checklist](go-live-checklist.md).
