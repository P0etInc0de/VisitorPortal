# SSO Testing

This project includes a separate SSO E2E smoke-test stack with a real local Keycloak OIDC provider. It is intentionally separate from the normal development compose stack.

The test values in this document, including `visitorportal-secret` and `ChangeMe-42!`, are for local automated testing only. Do not use them in production.

## What It Tests

- Keycloak starts with an imported realm.
- VisitorPortal uses real OIDC discovery against Keycloak.
- Frontend assets are built inside the smoke-test container.
- The browser is redirected to Keycloak.
- The callback logs the user into VisitorPortal.
- `user_identities` stores `provider`, `issuer`, `subject` and `last_login_at`.
- The Laravel session is created through the real OIDC redirect/callback flow.
- Auto-provisioned SSO users keep `local_login_allowed=false`.

## Run Locally

From the repository root:

```bash
docker compose -f docker-compose.sso-test.yml up --build --abort-on-container-exit --exit-code-from sso-smoke
```

Clean up after the run:

```bash
docker compose -f docker-compose.sso-test.yml down -v
```

For debugging:

```bash
docker compose -f docker-compose.sso-test.yml up -d --build db keycloak web
docker compose -f docker-compose.sso-test.yml logs -f keycloak web
docker compose -f docker-compose.sso-test.yml run --rm sso-smoke
```

## Network Model

The automated test uses Docker service names everywhere:

```env
APP_URL=http://web
OIDC_ISSUER_URL=http://keycloak:8080/realms/visitorportal
OIDC_REDIRECT_URI=http://web/auth/oidc/callback
```

This avoids the common OIDC issuer mismatch where the browser sees `localhost` but the app validates tokens against `keycloak`.

For manual host-based tests, use host URLs consistently instead:

```env
APP_URL=http://localhost:8080
OIDC_ISSUER_URL=http://localhost:8081/realms/visitorportal
OIDC_REDIRECT_URI=http://localhost:8080/auth/oidc/callback
```

Do not mix the container-network issuer with the host-network issuer in one test run.

## Fixture

The imported realm is stored in:

```text
backend/tests/Fixtures/keycloak/visitorportal-realm.json
```

It contains:

- Realm: `visitorportal`
- Client: `visitorportal`
- Client secret: `visitorportal-secret`
- Redirect URI: `http://web/auth/oidc/callback`
- User: `alice@example.org` / `ChangeMe-42!`

## Known Limits

This is a smoke test for the real OIDC redirect/callback flow. It does not replace the unit and feature tests for provisioning modes, local-login blocking, role mapping, profile sync or adapter boundary enforcement.

Role-mapping E2E coverage can be added later as a second scenario with `OIDC_SYNC_ROLES=true` and a dedicated Keycloak group-to-role assertion.
