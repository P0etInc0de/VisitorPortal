// SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
// SPDX-License-Identifier: GPL-3.0-or-later

import { expect, type BrowserContext, type Page, test } from '@playwright/test';
import mysql, { type Connection } from 'mysql2/promise';

const BASE_URL = process.env.BASE_URL ?? 'http://localhost:8080';
const PASSWORD = 'password';
const PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
const USER_MODEL = 'App\\Models\\User';

const users = {
    editor: 'visit-actions-editor@example.test',
    reception: 'visit-actions-reception@example.test',
    host: 'visit-actions-host@example.test',
};

const visits = {
    completed: 'E2E completed visit action visibility',
    planned: 'E2E planned visit action visibility',
};

test.describe('visit detail action cards', () => {
    test.beforeEach(async () => {
        await resetVisitActionFixtures();
    });

    test('global editor sees reschedule and actions cards in the browser', async ({ context, page }) => {
        await applyPreferences(context);
        const visitId = await visitIdFor(visits.completed);

        await login(page, users.editor);
        await page.goto(`/portal/visits/${visitId}`);

        await expect(page.getByRole('heading', { name: visits.completed })).toBeVisible();
        await expect(page.getByTestId('visit-reschedule-card')).toBeVisible();
        await expect(page.getByTestId('visit-actions-card')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Bearbeiten' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Besuch absagen' })).toBeVisible();
    });

    test('reception sees only allowed cancel card for planned visits', async ({ context, page }) => {
        await applyPreferences(context);
        const visitId = await visitIdFor(visits.planned);

        await login(page, users.reception);
        await page.goto(`/portal/visits/${visitId}`);

        await expect(page.getByRole('heading', { name: visits.planned })).toBeVisible();
        await expect(page.getByTestId('visit-cancel-card')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Besuch absagen' })).toBeVisible();
        await expect(page.getByTestId('visit-reschedule-card')).toHaveCount(0);
        await expect(page.getByTestId('visit-actions-card')).toHaveCount(0);
        await expect(page.getByRole('link', { name: 'Bearbeiten' })).toHaveCount(0);
    });

    test('reception has no action card for completed visits and content stays constrained', async ({ context, page }) => {
        await applyPreferences(context);
        const visitId = await visitIdFor(visits.completed);

        await login(page, users.reception);
        await page.setViewportSize({ width: 1600, height: 900 });
        await page.goto(`/portal/visits/${visitId}`);

        await expect(page.getByRole('heading', { name: visits.completed })).toBeVisible();
        await expect(page.getByTestId('visit-cancel-card')).toHaveCount(0);
        await expect(page.getByTestId('visit-reschedule-card')).toHaveCount(0);
        await expect(page.getByTestId('visit-actions-card')).toHaveCount(0);

        const widthRatio = await page.evaluate(() => {
            const grid = document.querySelector('[data-testid="visit-detail-grid"]');
            const content = document.querySelector('[data-testid="visit-detail-content"]');

            if (! grid || ! content) {
                throw new Error('Visit detail layout markers not found');
            }

            return content.getBoundingClientRect().width / grid.getBoundingClientRect().width;
        });

        expect(widthRatio).toBeGreaterThan(0.55);
        expect(widthRatio).toBeLessThan(0.75);
    });
});

async function login(page: Page, email: string): Promise<void> {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(email);
    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
}

async function applyPreferences(context: BrowserContext): Promise<void> {
    await context.addCookies([
        { name: 'theme_preference', value: 'dark', url: BASE_URL },
        { name: 'locale', value: 'de', url: BASE_URL },
    ]);
}

async function resetVisitActionFixtures(): Promise<void> {
    const connection = await db();

    try {
        await connection.execute("delete from cache where `key` like '%spatie.permission.cache%'");
        await deleteExistingFixtures(connection);

        const siteId = await defaultSiteId(connection);
        await ensureRoleWithPermissions(connection, 'visit_e2e_global_editor', [
            'ViewAny:Visit',
            'EditAny:Visit',
            'DeleteAny:Visit',
            'CancelAny:Visit',
        ]);
        await ensureRoleWithPermissions(connection, 'visit_e2e_reception', [
            'ViewSite:Visit',
            'CancelSite:Visit',
        ]);

        await createUser(connection, siteId, users.editor, 'Action', 'Editor');
        await createUser(connection, siteId, users.reception, 'Action', 'Reception');
        await createUser(connection, siteId, users.host, 'Action', 'Host');
        await assignRole(connection, users.editor, 'visit_e2e_global_editor');
        await assignRole(connection, users.reception, 'visit_e2e_reception');

        const hostId = await userIdFor(connection, users.host);
        const visitorId = await createVisitor(connection, users.host);

        await createVisit(connection, siteId, hostId, visits.completed, 'completed', visitorId);
        await createVisit(connection, siteId, hostId, visits.planned, 'planned', visitorId);
    } finally {
        await connection.end();
    }
}

async function deleteExistingFixtures(connection: Connection): Promise<void> {
    const emails = Object.values(users);
    const titles = Object.values(visits);

    await connection.execute(
        `delete from visit_visitor where visit_id in (select id from visits where title in (?, ?))`,
        titles,
    );
    await connection.execute('delete from visits where title in (?, ?)', titles);
    await connection.execute('delete from visitors where email = ?', ['visit-actions-visitor@example.test']);
    await connection.execute(
        `delete from model_has_roles
          where model_type = ?
            and model_id in (select id from users where email in (?, ?, ?))`,
        [USER_MODEL, ...emails],
    );
    await connection.execute('delete from users where email in (?, ?, ?)', emails);
}

async function createUser(connection: Connection, siteId: number, email: string, firstName: string, name: string): Promise<void> {
    await connection.execute(
        `insert into users (
             site_id, first_name, name, gender, email, email_verified_at, locale, theme_preference,
             password, is_active, local_login_allowed, created_at, updated_at
         ) values (?, ?, ?, 'other', ?, current_timestamp, 'de', 'dark', ?, true, true, current_timestamp, current_timestamp)`,
        [siteId, firstName, name, email, PASSWORD_HASH],
    );
}

async function createVisitor(connection: Connection, createdByEmail: string): Promise<number> {
    const createdByUserId = await userIdFor(connection, createdByEmail);

    await connection.execute(
        `insert into visitors (first_name, name, email, company, created_by_user_id, created_at, updated_at)
         values ('Browser', 'Visitor', 'visit-actions-visitor@example.test', 'Example Industries', ?, current_timestamp, current_timestamp)`,
        [createdByUserId],
    );

    return visitorIdFor(connection, 'visit-actions-visitor@example.test');
}

async function createVisit(
    connection: Connection,
    siteId: number,
    hostId: number,
    title: string,
    status: 'completed' | 'planned',
    visitorId: number,
): Promise<void> {
    const startsAt = status === 'completed' ? '2026-05-24 13:30:00' : '2026-05-25 13:30:00';
    const endsAt = status === 'completed' ? '2026-05-24 14:45:00' : '2026-05-25 14:45:00';

    await connection.execute(
        `insert into visits (
             site_id, host_user_id, created_by_user_id, title, scheduled_from, scheduled_until,
             status, is_confidential, is_walk_in, notes, created_at, updated_at
         ) values (?, ?, ?, ?, ?, ?, ?, false, false, 'Browser visibility regression fixture.', current_timestamp, current_timestamp)`,
        [siteId, hostId, hostId, title, startsAt, endsAt, status],
    );

    const visitId = await visitIdFor(title);

    await connection.execute(
        `insert into visit_visitor (visit_id, visitor_id, created_at, updated_at)
         values (?, ?, current_timestamp, current_timestamp)`,
        [visitId, visitorId],
    );
}

async function ensureRoleWithPermissions(connection: Connection, roleName: string, permissions: string[]): Promise<void> {
    await connection.execute(
        `insert into roles (name, guard_name, created_at, updated_at)
         values (?, 'web', current_timestamp, current_timestamp)
         on duplicate key update updated_at = current_timestamp`,
        [roleName],
    );

    for (const permission of permissions) {
        await connection.execute(
            `insert into permissions (name, guard_name, created_at, updated_at)
             values (?, 'web', current_timestamp, current_timestamp)
             on duplicate key update updated_at = current_timestamp`,
            [permission],
        );

        await connection.execute(
            `insert ignore into role_has_permissions (permission_id, role_id)
             values (?, ?)`,
            [await permissionIdFor(connection, permission), await roleIdFor(connection, roleName)],
        );
    }
}

async function assignRole(connection: Connection, email: string, roleName: string): Promise<void> {
    await connection.execute(
        `insert ignore into model_has_roles (role_id, model_type, model_id)
         values (?, ?, ?)`,
        [await roleIdFor(connection, roleName), USER_MODEL, await userIdFor(connection, email)],
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

async function visitIdFor(title: string): Promise<number> {
    const connection = await db();

    try {
        const [rows] = await connection.execute('select id from visits where title = ? limit 1', [title]);

        return Number((rows as { id: number }[])[0].id);
    } finally {
        await connection.end();
    }
}

async function userIdFor(connection: Connection, email: string): Promise<number> {
    const [rows] = await connection.execute('select id from users where email = ? limit 1', [email]);

    return Number((rows as { id: number }[])[0].id);
}

async function visitorIdFor(connection: Connection, email: string): Promise<number> {
    const [rows] = await connection.execute('select id from visitors where email = ? limit 1', [email]);

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
