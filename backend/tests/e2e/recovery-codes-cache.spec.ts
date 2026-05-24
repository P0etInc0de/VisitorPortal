import { expect, type Page, test } from '@playwright/test';
import crypto from 'node:crypto';
import mysql, { type Connection } from 'mysql2/promise';

const BASE_URL = process.env.BASE_URL ?? 'http://localhost:8080';
const PASSWORD = 'password';
const PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
const USER_MODEL = 'App\\Models\\User';
const EMAIL = 'recovery-codes-cache-e2e@example.test';

test.describe('Recovery code cache protection', () => {
    test('does not show onboarding recovery codes after continue and browser back', async ({ page }) => {
        test.setTimeout(90_000);

        await resetFixture();

        await completeInitialMfaSetup(page);

        await expect.poll(() => new URL(page.url()).pathname).toBe('/security/mfa/recovery-codes');
        const recoveryCode = (await page.locator('code').first().textContent())?.trim();

        expect(recoveryCode).toBeTruthy();
        await expect(page.locator('body')).toContainText(recoveryCode as string);

        await page.getByRole('button', { name: 'Weiter' }).click();
        await page.waitForLoadState('networkidle');

        await expect.poll(() => new URL(page.url()).pathname).not.toBe('/security/mfa/recovery-codes');

        await page.goBack({ waitUntil: 'networkidle' });

        await expect.poll(() => new URL(page.url()).pathname).not.toBe('/security/mfa/recovery-codes');
        await expect.poll(async () => (await page.locator('body').textContent()) ?? '').not.toContain(recoveryCode as string);
    });

    test('does not show profile recovery codes after navigation away and browser back', async ({ page }) => {
        test.setTimeout(90_000);

        await resetFixture();

        const { secret, setupCode } = await completeInitialMfaSetup(page);

        await page.getByRole('button', { name: 'Weiter' }).click();
        await page.waitForLoadState('networkidle');
        await expect.poll(() => new URL(page.url()).pathname).not.toBe('/security/mfa/recovery-codes');

        await page.goto('/profile/security/recovery-codes');
        await page.waitForLoadState('networkidle');
        await expect.poll(() => new URL(page.url()).pathname).toBe('/security/step-up/recovery-codes:view');

        await waitForNextTotp(secret, setupCode);
        await submitTotp(page, secret);
        await expect.poll(() => new URL(page.url()).pathname).toBe('/profile/security/recovery-codes');

        const recoveryCode = (await page.locator('code').first().textContent())?.trim();

        expect(recoveryCode).toBeTruthy();
        await expect(page.locator('body')).toContainText(recoveryCode as string);

        await page.getByRole('link', { name: 'Zurück zur Sicherheit' }).click();
        await page.waitForLoadState('networkidle');
        await expect.poll(() => new URL(page.url()).pathname).toBe('/profile/security');

        await page.goBack({ waitUntil: 'networkidle' });

        await expect.poll(async () => (await page.locator('body').textContent()) ?? '').not.toContain(recoveryCode as string);
        await expect.poll(() => new URL(page.url()).pathname).not.toBe('/profile/security/recovery-codes');
    });
});

async function completeInitialMfaSetup(page: Page): Promise<{ secret: string; setupCode: string }> {
    await login(page, EMAIL);
    await page.goto('/security/mfa/setup');
    await confirmPassword(page);

    const secret = await fetchTwoFactorSecret(page);

    const setupCode = currentTotp(secret);

    await page.goto('/security/mfa/setup');
    await submitTotpCode(page, setupCode);

    return { secret, setupCode };
}

async function submitTotp(page: Page, secret: string): Promise<void> {
    await submitTotpCode(page, currentTotp(secret));
}

async function submitTotpCode(page: Page, code: string): Promise<void> {
    await page.locator('input[name="code"]').fill(code);
    await page.locator('form').filter({ has: page.locator('input[name="code"]') }).locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
}

async function waitForNextTotp(secret: string, previousCode: string): Promise<void> {
    while (currentTotp(secret) === previousCode) {
        await new Promise((resolve) => setTimeout(resolve, 1000));
    }
}

async function login(page: Page, email: string): Promise<void> {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(email);
    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
}

async function confirmPassword(page: Page): Promise<void> {
    await page.goto('/confirm-password');

    if (! new URL(page.url()).pathname.includes('/confirm-password')) {
        return;
    }

    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
}

async function fetchTwoFactorSecret(page: Page): Promise<string> {
    return page.evaluate(async () => {
        const response = await fetch('/user/two-factor-secret-key', {
            headers: { Accept: 'application/json' },
        });

        if (! response.ok) {
            throw new Error(`Could not fetch two-factor secret: HTTP ${response.status}`);
        }

        const payload = await response.json() as { secretKey?: string };

        if (! payload.secretKey) {
            throw new Error('Two-factor secret response did not include secretKey');
        }

        return payload.secretKey;
    });
}

function currentTotp(secret: string): string {
    const counter = Math.floor(Date.now() / 1000 / 30);
    const counterBuffer = Buffer.alloc(8);
    counterBuffer.writeBigUInt64BE(BigInt(counter));

    const digest = crypto.createHmac('sha1', base32Decode(secret)).update(counterBuffer).digest();
    const offset = digest[digest.length - 1] & 0x0f;
    const binary = ((digest[offset] & 0x7f) << 24)
        | ((digest[offset + 1] & 0xff) << 16)
        | ((digest[offset + 2] & 0xff) << 8)
        | (digest[offset + 3] & 0xff);

    return String(binary % 1_000_000).padStart(6, '0');
}

function base32Decode(secret: string): Buffer {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    let bits = '';

    for (const char of secret.replace(/=|\s/g, '').toUpperCase()) {
        const value = alphabet.indexOf(char);

        if (value === -1) {
            throw new Error(`Invalid base32 character: ${char}`);
        }

        bits += value.toString(2).padStart(5, '0');
    }

    const bytes: number[] = [];

    for (let index = 0; index + 8 <= bits.length; index += 8) {
        bytes.push(Number.parseInt(bits.slice(index, index + 8), 2));
    }

    return Buffer.from(bytes);
}

async function resetFixture(): Promise<void> {
    const connection = await db();

    try {
        await connection.execute("delete from cache where `key` like '%spatie.permission.cache%'");
        await connection.execute(
            `delete from model_has_roles
              where model_type = ?
                and model_id in (select id from users where email = ?)`,
            [USER_MODEL, EMAIL],
        );
        await connection.execute('delete from users where email = ?', [EMAIL]);
        await ensureAdminRole(connection);

        const siteId = await defaultSiteId(connection);
        await createUser(connection, siteId);
        await assignRole(connection, EMAIL, 'admin');
    } finally {
        await connection.end();
    }
}

async function createUser(connection: Connection, siteId: number): Promise<void> {
    await connection.execute(
        `insert into users (
             site_id, first_name, name, gender, email, email_verified_at, locale, theme_preference,
             password, is_active, local_login_allowed, created_at, updated_at
         ) values (?, 'Recovery', 'Codes Cache E2E', 'other', ?, current_timestamp, 'de', 'light', ?, true, true, current_timestamp, current_timestamp)`,
        [siteId, EMAIL, PASSWORD_HASH],
    );
}

async function ensureAdminRole(connection: Connection): Promise<void> {
    await connection.execute(
        `insert into roles (name, guard_name, created_at, updated_at)
         values ('admin', 'web', current_timestamp, current_timestamp)
         on duplicate key update updated_at = current_timestamp`,
    );
    await connection.execute(
        `insert into permissions (name, guard_name, created_at, updated_at)
         values
             ('ViewAny:Visit', 'web', current_timestamp, current_timestamp),
             ('ViewOwn:User', 'web', current_timestamp, current_timestamp)
         on duplicate key update updated_at = current_timestamp`,
    );

    const roleId = await roleIdFor(connection, 'admin');
    const visitPermissionId = await permissionIdFor(connection, 'ViewAny:Visit');
    const userPermissionId = await permissionIdFor(connection, 'ViewOwn:User');

    await connection.execute(
        `insert ignore into role_has_permissions (permission_id, role_id)
         values (?, ?), (?, ?)`,
        [visitPermissionId, roleId, userPermissionId, roleId],
    );
}

async function assignRole(connection: Connection, email: string, roleName: string): Promise<void> {
    const userId = await userIdFor(connection, email);
    const roleId = await roleIdFor(connection, roleName);

    await connection.execute(
        `insert ignore into model_has_roles (role_id, model_type, model_id)
         values (?, ?, ?)`,
        [roleId, USER_MODEL, userId],
    );
}

async function defaultSiteId(connection: Connection): Promise<number> {
    await connection.execute(
        `insert into sites (name, slug, timezone, is_active, created_at, updated_at)
         values ('Default Site', 'default', 'Europe/Berlin', true, current_timestamp, current_timestamp)
         on duplicate key update updated_at = current_timestamp`,
    );

    const [rows] = await connection.execute('select id from sites where slug = ? limit 1', ['default']);

    return Number((rows as { id: number }[])[0].id);
}

async function userIdFor(connection: Connection, email: string): Promise<number> {
    const [rows] = await connection.execute('select id from users where email = ? limit 1', [email]);

    return Number((rows as { id: number }[])[0].id);
}

async function roleIdFor(connection: Connection, name: string): Promise<number> {
    const [rows] = await connection.execute('select id from roles where name = ? and guard_name = ? limit 1', [name, 'web']);

    return Number((rows as { id: number }[])[0].id);
}

async function permissionIdFor(connection: Connection, name: string): Promise<number> {
    const [rows] = await connection.execute('select id from permissions where name = ? and guard_name = ? limit 1', [name, 'web']);

    return Number((rows as { id: number }[])[0].id);
}

async function db(): Promise<Connection> {
    return mysql.createConnection({
        host: process.env.DB_HOST ?? '127.0.0.1',
        port: Number(process.env.DB_PORT ?? 3306),
        database: process.env.DB_DATABASE ?? 'visitorportal',
        user: process.env.DB_USERNAME ?? 'visitor',
        password: process.env.DB_PASSWORD ?? 'visitor_pw',
    });
}
