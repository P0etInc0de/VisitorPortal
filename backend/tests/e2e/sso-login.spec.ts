import { expect, test } from '@playwright/test';
import mysql from 'mysql2/promise';

test('user can sign in through Keycloak OIDC', async ({ page }) => {
    await page.goto('/login');

    await expect(page.getByRole('link', { name: /sign in with local keycloak/i })).toBeVisible();

    await page.getByRole('link', { name: /sign in with local keycloak/i }).click();

    await expect(page).toHaveURL(/keycloak:8080/);

    await page.locator('#username').fill('alice@example.org');
    await page.locator('#password').fill('ChangeMe-42!');
    await page.locator('#kc-login').click();

    await expect(page).toHaveURL(/\/overview/);
    await expect(page.locator('body')).toContainText(/overview|dashboard|visits|visitor/i);

    await expect.poll(async () => {
        const row = await ssoStateFor('alice@example.org');

        return row !== null
            && row.provider === 'oidc'
            && row.issuer === 'http://keycloak:8080/realms/visitorportal'
            && row.subject !== null
            && row.subject !== ''
            && row.last_login_at !== null
            && row.local_login_allowed === 0;
    }, { timeout: 10_000 }).toBe(true);
});

type SsoState = {
    provider: string | null;
    issuer: string | null;
    subject: string | null;
    last_login_at: Date | null;
    local_login_allowed: number | null;
};

async function ssoStateFor(email: string): Promise<SsoState | null> {
    const connection = await mysql.createConnection({
        host: process.env.DB_HOST ?? '127.0.0.1',
        port: Number(process.env.DB_PORT ?? 3306),
        database: process.env.DB_DATABASE ?? 'visitorportal',
        user: process.env.DB_USERNAME ?? 'visitor',
        password: process.env.DB_PASSWORD ?? 'visitor_pw',
    });

    try {
        const [rows] = await connection.execute(
            `select user_identities.provider,
                    user_identities.issuer,
                    user_identities.subject,
                    user_identities.last_login_at,
                    users.local_login_allowed
               from users
               join user_identities on user_identities.user_id = users.id
              where users.email = ?
              limit 1`,
            [email],
        );

        return (rows as SsoState[])[0] ?? null;
    } finally {
        await connection.end();
    }
}
