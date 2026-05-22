import { expect, type BrowserContext, type Locator, type Page, test } from '@playwright/test';
import mysql, { type Connection } from 'mysql2/promise';

const BASE_URL = process.env.BASE_URL ?? 'http://localhost:8080';
const PASSWORD = 'password';
const PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
const USER_MODEL = 'App\\Models\\User';

const fixtureUsers = {
    admin: 'mfa-e2e-admin@example.test',
    loginChallenge: 'mfa-e2e-login@example.test',
    profileSetup: 'mfa-e2e-profile@example.test',
};

const themes = ['light', 'dark', 'true-black'] as const;
type Theme = (typeof themes)[number];

const viewports = [
    { name: 'desktop', width: 1280, height: 900 },
    { name: 'narrow', width: 390, height: 844 },
] as const;

test.describe('MFA code input', () => {
    for (const theme of themes) {
        for (const viewport of viewports) {
            test(`is visible and usable in ${theme} ${viewport.name}`, async ({ context, page }) => {
                test.setTimeout(90_000);

                await resetMfaFixtures(theme);
                await page.setViewportSize({ width: viewport.width, height: viewport.height });
                await applyPreferences(context, theme);

                await login(page, fixtureUsers.admin);
                await page.goto('/security/mfa/setup');
                await validateTotpInput(page, 'Pflicht-MFA-Setup', theme);

                const encryptedSecret = await encryptedSecretFor(fixtureUsers.admin);
                await updateMfaState(fixtureUsers.admin, encryptedSecret, true);
                await updateMfaState(fixtureUsers.loginChallenge, encryptedSecret, true);

                await page.goto('/security/mfa/challenge');
                await validateTotpInput(page, 'App-MFA-Challenge', theme);
                await validateRecoveryDisclosure(page, 'App-MFA-Challenge');

                await page.goto('/security/step-up/recovery-codes:view');
                await validateTotpInput(page, 'Recovery-Code-Step-up', theme);

                await context.clearCookies();
                await applyPreferences(context, theme);
                await login(page, fixtureUsers.loginChallenge);
                await validateTotpInput(page, 'Login-MFA-Challenge', theme);
                await validateRecoveryDisclosure(page, 'Login-MFA-Challenge');
                await submitGroupedCode(page);

                await context.clearCookies();
                await applyPreferences(context, theme);
                await login(page, fixtureUsers.profileSetup);
                await updateMfaState(fixtureUsers.profileSetup, encryptedSecret, false);
                await page.goto('/profile/security/two-factor-setup');

                if (page.url().includes('/confirm-password')) {
                    await page.locator('input[name="password"]').fill(PASSWORD);
                    await page.locator('button[type="submit"]').click();
                }

                await expect.poll(() => page.evaluate(() => window.location.pathname)).toBe('/profile/security/two-factor-setup');
                await validateTotpInput(page, 'Profil-MFA-Setup', theme);
            });
        }
    }
});

async function login(page: Page, email: string): Promise<void> {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(email);
    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
}

async function validateTotpInput(page: Page, label: string, theme: Theme): Promise<Locator> {
    await forceTheme(page, theme);
    await expect(page.locator('html')).toHaveAttribute('data-theme', theme);

    const input = page.locator('input[name="code"]').first();

    await expect(input, `${label} code input is visible`).toBeVisible();
    await expect(input, `${label} code input is enabled`).toBeEnabled();
    await expect(input, `${label} uses central TOTP marker`).toHaveAttribute('data-totp-code-input', '');

    await input.focus();
    await expect(input, `${label} code input can be focused`).toBeFocused();
    await input.fill('123 456');
    await expect(input, `${label} code input can be filled`).toHaveValue('123 456');

    const styles = await input.evaluate((element) => {
        const inputElement = element as HTMLInputElement;
        const computed = window.getComputedStyle(inputElement);
        const bounds = inputElement.getBoundingClientRect();

        return {
            backgroundColor: computed.backgroundColor,
            borderTopWidth: computed.borderTopWidth,
            color: computed.color,
            height: bounds.height,
            width: bounds.width,
        };
    });

    expect(styles.height, `${label} code input has visible height`).toBeGreaterThanOrEqual(40);
    expect(styles.width, `${label} code input has visible width`).toBeGreaterThan(120);
    expect(parseFloat(styles.borderTopWidth), `${label} code input has a visible border`).toBeGreaterThan(0);
    expect(styles.backgroundColor, `${label} code input has a visible background`).not.toBe('rgba(0, 0, 0, 0)');
    expect(styles.color, `${label} code input has visible text color`).not.toBe('rgba(0, 0, 0, 0)');

    return input;
}

async function validateRecoveryDisclosure(page: Page, label: string): Promise<void> {
    const toggle = page.getByTestId('mfa-recovery-toggle').first();
    const codeInput = page.locator('input[name="code"]').first();
    const input = page.locator('input[name="recovery_code"]').first();
    const recoveryCode = 'abcdEF1234-ZYX987wvuT';

    await expect(toggle, `${label} recovery toggle is visible`).toBeVisible();
    await expect(input, `${label} recovery input is hidden before click`).toBeHidden();

    await toggle.click();

    await expect(input, `${label} recovery input is visible after click`).toBeVisible();
    await expect(input, `${label} recovery input is enabled after click`).toBeEnabled();
    await expectInputVisualMatch(page, codeInput, input, label);
    await input.fill(recoveryCode);
    await expect(input, `${label} recovery input can be filled`).toHaveValue(recoveryCode);
    await input.fill('');
}

async function expectInputVisualMatch(page: Page, expected: Locator, actual: Locator, label: string): Promise<void> {
    const styles = await page.evaluate(() => {
        const code = document.querySelector<HTMLInputElement>('input[name="code"]');
        const recovery = document.querySelector<HTMLInputElement>('input[name="recovery_code"]');

        if (! code || ! recovery) {
            throw new Error('Code inputs not found for visual comparison');
        }

        const relevantStyles = (element: HTMLInputElement) => {
            const computed = window.getComputedStyle(element);
            const bounds = element.getBoundingClientRect();

            return {
                backgroundColor: computed.backgroundColor,
                borderColor: computed.borderTopColor,
                borderRadius: computed.borderTopLeftRadius,
                borderWidth: computed.borderTopWidth,
                boxShadow: computed.boxShadow,
                color: computed.color,
                height: Math.round(bounds.height),
                paddingLeft: computed.paddingLeft,
                paddingRight: computed.paddingRight,
                width: Math.round(bounds.width),
            };
        };

        return {
            code: relevantStyles(code),
            recovery: relevantStyles(recovery),
        };
    });

    await expect(expected, `${label} authenticator input visible for style comparison`).toBeVisible();
    await expect(actual, `${label} recovery input visible for style comparison`).toBeVisible();
    expect(styles.recovery, `${label} recovery input matches authenticator input visuals`).toEqual(styles.code);
}

async function submitGroupedCode(page: Page): Promise<void> {
    await page.locator('input[name="code"]').fill('123 456');
    await page.locator('form').filter({ has: page.locator('input[name="code"]') }).locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');

    await expect(page.locator('input[name="code"]')).toBeVisible();
    await expect(page.locator('body')).toContainText(/ungültig|invalid/i);
}

async function applyPreferences(context: BrowserContext, theme: string): Promise<void> {
    await context.addCookies([
        { name: 'theme_preference', value: theme, url: BASE_URL },
        { name: 'locale', value: 'de', url: BASE_URL },
    ]);
}

async function forceTheme(page: Page, theme: Theme): Promise<void> {
    await page.evaluate((selectedTheme) => {
        const root = document.documentElement;

        root.setAttribute('data-theme', selectedTheme);
        root.setAttribute('data-theme-preference', selectedTheme);
        root.classList.toggle('dark', selectedTheme !== 'light');
        root.style.colorScheme = selectedTheme === 'light' ? 'light' : 'dark';
    }, theme);
}

async function resetMfaFixtures(theme: Theme): Promise<void> {
    const connection = await db();

    try {
        const emails = Object.values(fixtureUsers);
        await connection.execute("delete from cache where `key` like '%spatie.permission.cache%'");
        await connection.execute(
            `delete from model_has_roles
              where model_type = ?
                and model_id in (select id from users where email in (?, ?, ?))`,
            [USER_MODEL, ...emails],
        );
        await connection.execute('delete from users where email in (?, ?, ?)', emails);
        await ensureAdminRole(connection);
        await ensureUserRole(connection);

        const siteId = await defaultSiteId(connection);
        await createUser(connection, siteId, fixtureUsers.admin, 'MFA E2E Admin', theme);
        await createUser(connection, siteId, fixtureUsers.loginChallenge, 'MFA E2E Login', theme);
        await createUser(connection, siteId, fixtureUsers.profileSetup, 'MFA E2E Profile', theme);
        await assignRole(connection, fixtureUsers.admin, 'admin');
        await assignRole(connection, fixtureUsers.profileSetup, 'mfa_e2e_user');
    } finally {
        await connection.end();
    }
}

async function encryptedSecretFor(email: string): Promise<string> {
    const connection = await db();

    try {
        const [rows] = await connection.execute('select two_factor_secret from users where email = ? limit 1', [email]);
        const secret = (rows as { two_factor_secret: string | null }[])[0]?.two_factor_secret;

        if (! secret) {
            throw new Error(`No encrypted two-factor secret found for ${email}`);
        }

        return secret;
    } finally {
        await connection.end();
    }
}

async function updateMfaState(email: string, encryptedSecret: string, confirmed: boolean): Promise<void> {
    const connection = await db();

    try {
        await connection.execute(
            `update users
                set two_factor_secret = ?,
                    two_factor_confirmed_at = ?,
                    updated_at = current_timestamp
              where email = ?`,
            [encryptedSecret, confirmed ? new Date() : null, email],
        );
    } finally {
        await connection.end();
    }
}

async function createUser(connection: Connection, siteId: number, email: string, name: string, theme: Theme): Promise<void> {
    await connection.execute(
        `insert into users (
             site_id, first_name, name, gender, email, email_verified_at, locale, theme_preference,
             password, is_active, local_login_allowed, created_at, updated_at
         ) values (?, 'MFA', ?, 'other', ?, current_timestamp, 'de', ?, ?, true, true, current_timestamp, current_timestamp)`,
        [siteId, name, email, theme, PASSWORD_HASH],
    );
}

async function ensureAdminRole(connection: Connection): Promise<void> {
    await connection.execute(
        `insert into roles (name, guard_name, created_at, updated_at)
         values ('admin', 'web', current_timestamp, current_timestamp)
         on duplicate key update updated_at = current_timestamp`,
    );
}

async function ensureUserRole(connection: Connection): Promise<void> {
    await connection.execute(
        `insert into roles (name, guard_name, created_at, updated_at)
         values ('mfa_e2e_user', 'web', current_timestamp, current_timestamp)
         on duplicate key update updated_at = current_timestamp`,
    );
    await connection.execute(
        `insert into permissions (name, guard_name, created_at, updated_at)
         values ('ViewOwn:User', 'web', current_timestamp, current_timestamp)
         on duplicate key update updated_at = current_timestamp`,
    );

    const roleId = await roleIdFor(connection, 'mfa_e2e_user');
    const permissionId = await permissionIdFor(connection, 'ViewOwn:User');

    await connection.execute(
        `insert ignore into role_has_permissions (permission_id, role_id)
         values (?, ?)`,
        [permissionId, roleId],
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
