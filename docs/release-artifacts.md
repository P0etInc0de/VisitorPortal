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

## Updating An Existing Installation

- Production Docker and source/LAMP: follow [Production Updates](deployment.md#updates).
- Existing demos: select the desired tag in the existing `.env.demo`, then run the update script described in [Setup](setup.md#stop-reset-and-update).
- Version-specific requirements and changes: see the [v0.9.1 release notes](releases/v0.9.1.md), including the PHP requirement `>=8.4.27,<8.5` for source/LAMP installations.

Retain the existing database, storage volumes and `APP_KEY`. A demo reset is destructive and is not an update method.

## Release Build And Publication

The `Docker Image` workflow in `.github/workflows/docker-image.yml` builds the production Docker target `app`. A push to `main` builds and publishes a SHA-tagged image. A version-tag push additionally:

1. Verifies that the tagged commit belongs to the history of `main`.
2. Builds and pushes `ghcr.io/p0etinc0de/besucherportal:<tag>`.
3. Creates the demo ZIP with its image version pinned to the tag.
4. Creates the source ZIP using `git archive` of the tagged commit and replaces the demo-version placeholder. Untracked files, local secrets and dependency directories are not included in that snapshot.
5. Attaches both ZIPs to a GitHub release **draft**, using `docs/releases/<tag>.md` as the release description.

Both ZIP files are produced in this workflow. Their creation does not depend on a second workflow triggered by publishing a GitHub release. Commit the matching release-notes file before creating the tag.

Stable version-tag builds also update the Docker `latest` tag. Tags containing a hyphen, such as `v0.9.1-rc.1`, do not update `latest`. The image is pushed before the draft is created: a release draft delays the GitHub release announcement, not GHCR availability or the `latest` update. Production deployments should select a fixed version tag or digest.

### Maintainer Release Sequence

1. Merge the release preparation into `develop`, then open the release PR from `develop` to `main`.
2. Require successful `laravel-tests`, review the `sso-e2e` result and obtain the approval required by the `main` ruleset. Use **Squash and merge**.
3. Wait for the resulting `main` checks and image build. Create the annotated version tag explicitly on the approved `main` commit; the repository's default branch is `develop`.
4. Wait for the tag's `Docker Image` workflow, then inspect its GHCR image, digest and both downloadable ZIPs. Confirm PHP 8.4.27, production image defaults, platform requirements, and a successful startup. Test an upgrade with an isolated copy of the previous release's database and storage before recommending the update to operators.
5. Start a fresh demo from the actual downloaded demo ZIP. Verify the source ZIP's pinned version and contents as well.
6. Publish the GitHub release draft and mark the stable release as latest after these checks. Notify operators with the exact image tag and upgrade instructions.

Published release tags must not be moved to different code. A failed workflow can already have pushed an image; inspect the existing image and draft before retrying. The draft-creation step expects a new release and will not overwrite an existing draft or published release.
