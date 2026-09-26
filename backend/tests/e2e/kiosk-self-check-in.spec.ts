// SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
// SPDX-License-Identifier: GPL-3.0-or-later

import { expect, type Page, test } from '@playwright/test';
import mysql, { type Connection } from 'mysql2/promise';

const KIOSK_TOKEN = 'e2ekiosktoken';
const USER_MODEL = 'App\\Models\\User';

test.use({ locale: 'de-DE' });

const fixtures = {
    host: 'e2e-kiosk-host@example.test',
    search: 'e2e-kiosk-search@example.test',
    qr: 'e2e-kiosk-qr@example.test',
    checkout: 'e2e-kiosk-checkout@example.test',
};

const searchVisitor = { firstName: 'Sofia', name: 'Suchbar' };
const qrVisitor = { firstName: 'Quirin', name: 'Qr' };
const checkoutVisitor = { firstName: 'Carla', name: 'Checkout' };

const qrCode = 'e2e-kiosk-qr-check-in-code';

test.describe('kiosk self check-in terminal', () => {
    test.beforeEach(async () => {
        await resetKioskFixtures();
    });

    test('home screen is public and offers all configured flows', async ({ page }) => {
        await page.goto(`/kiosk/${KIOSK_TOKEN}`);

        await expect(page.getByRole('heading', { name: 'Herzlich willkommen' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Ich habe einen Termin' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'QR-Code scannen' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Ich habe keinen Termin' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Auschecken' })).toBeVisible();
    });

    test('pre-registered visitor checks in by searching their name', async ({ page }) => {
        await page.goto(`/kiosk/${KIOSK_TOKEN}`);

        await page.getByRole('button', { name: 'Ich habe einen Termin' }).click();
        await page.getByPlaceholder('Name oder Firma eingeben…').fill(searchVisitor.firstName);

        await expect(page.getByText(`${searchVisitor.firstName} ${searchVisitor.name}`)).toBeVisible();
        await page.getByRole('button', { name: 'Einchecken' }).click();

        await expect(page.getByText(new RegExp(`Willkommen, ${searchVisitor.firstName} ${searchVisitor.name}`))).toBeVisible();
        expect(await isCheckedIn(fixtures.search)).toBe(true);
    });

    test('visitor checks in with a qr code', async ({ page }) => {
        await page.goto(`/kiosk/${KIOSK_TOKEN}`);

        await page.getByRole('button', { name: 'QR-Code scannen' }).click();
        await page.getByPlaceholder('Code eingeben').fill(qrCode);
        await page.getByRole('button', { name: 'Einchecken' }).click();

        await expect(page.getByText(new RegExp(`Eingecheckt: ${qrVisitor.firstName} ${qrVisitor.name}`))).toBeVisible();
        expect(await isCheckedIn(fixtures.qr)).toBe(true);
    });

    test('visitor without an appointment registers as a walk-in', async ({ page }) => {
        await page.goto(`/kiosk/${KIOSK_TOKEN}`);

        await page.getByRole('button', { name: 'Ich habe keinen Termin' }).click();
        await page.locator('input[wire\\:model="walkIn.first_name"]').fill('Wanda');
        await page.locator('input[wire\\:model="walkIn.name"]').fill('Walkin');
        await page.locator('input[wire\\:model="walkIn.company"]').fill('Example Industries');
        await page.getByRole('button', { name: 'Anmelden & einchecken' }).click();

        await expect(page.getByText(/Willkommen, Wanda Walkin/)).toBeVisible();
        expect(await walkInVisitExists('Wanda')).toBe(true);
    });

    test('checked-in visitor checks out again', async ({ page }) => {
        await page.goto(`/kiosk/${KIOSK_TOKEN}`);

        await page.getByRole('button', { name: 'Auschecken' }).click();
        await page.getByPlaceholder('Name oder Firma eingeben…').fill(checkoutVisitor.firstName);

        await expect(page.getByText(`${checkoutVisitor.firstName} ${checkoutVisitor.name}`)).toBeVisible();
        await page.getByRole('button', { name: 'Auschecken' }).click();

        await expect(page.getByText(new RegExp(`Auf Wiedersehen, ${checkoutVisitor.firstName} ${checkoutVisitor.name}`))).toBeVisible();
        expect(await isCheckedOut(fixtures.checkout)).toBe(true);
    });

    test('visitor signs on the signature pad before check-in', async ({ page }) => {
        await setKioskSignatureRequired(true);
        await page.goto(`/kiosk/${KIOSK_TOKEN}`);

        await page.getByRole('button', { name: 'Ich habe einen Termin' }).click();
        await page.getByPlaceholder('Name oder Firma eingeben…').fill(searchVisitor.firstName);
        await page.getByRole('button', { name: 'Einchecken' }).click();

        await expect(page.getByRole('heading', { name: 'Unterschrift' })).toBeVisible();

        const canvas = page.locator('canvas');
        const box = await canvas.boundingBox();

        if (! box) {
            throw new Error('Signature canvas not found');
        }

        await page.mouse.move(box.x + 40, box.y + box.height / 2);
        await page.mouse.down();
        await page.mouse.move(box.x + 120, box.y + box.height / 2 - 30, { steps: 8 });
        await page.mouse.move(box.x + 200, box.y + box.height / 2 + 20, { steps: 8 });
        await page.mouse.up();

        await page.waitForTimeout(800);

        await page.getByRole('button', { name: 'Bestätigen & einchecken' }).click();

        await expect(page.getByText(new RegExp(`Willkommen, ${searchVisitor.firstName} ${searchVisitor.name}`))).toBeVisible();
        expect(await isCheckedIn(fixtures.search)).toBe(true);
        expect(await hasSignature(fixtures.search)).toBe(true);
    });
});

async function setKioskSignatureRequired(required: boolean): Promise<void> {
    const connection = await db();

    try {
        await connection.execute('update kiosks set require_signature = ? where token = ?', [required ? 1 : 0, KIOSK_TOKEN]);
    } finally {
        await connection.end();
    }
}


async function resetKioskFixtures(): Promise<void> {
    const connection = await db();

    try {
        await connection.execute("delete from cache where `key` like '%spatie.permission.cache%'");

        const visitorEmails = [fixtures.search, fixtures.qr, fixtures.checkout];

        await connection.execute(
            `delete from visits where id in (
                select visit_id from visit_visitor where visitor_id in (
                    select id from visitors where email in (?, ?, ?)
                )
            )`,
            visitorEmails,
        );
        await connection.execute(
            `delete from visits where host_user_id in (select id from users where email = ?)`,
            [fixtures.host],
        );
        await connection.execute('delete from kiosks where token = ?', [KIOSK_TOKEN]);
        await connection.execute(
            `delete from model_has_roles where model_type = ? and model_id in (select id from users where email = ?)`,
            [USER_MODEL, fixtures.host],
        );
        await connection.execute('delete from users where email = ?', [fixtures.host]);
        await connection.execute('delete from visitors where first_name = ? and name = ?', ['Wanda', 'Walkin']);
        await connection.execute('delete from visitors where email in (?, ?, ?)', visitorEmails);

        const siteId = await defaultSiteId(connection);
        const hostId = await createUser(connection, siteId, fixtures.host, 'Kiosk', 'Host');

        const searchVisitorId = await createVisitor(connection, fixtures.search, searchVisitor.firstName, searchVisitor.name);
        const qrVisitorId = await createVisitor(connection, fixtures.qr, qrVisitor.firstName, qrVisitor.name);
        const checkoutVisitorId = await createVisitor(connection, fixtures.checkout, checkoutVisitor.firstName, checkoutVisitor.name);

        await createVisit(connection, siteId, hostId, 'E2E Kiosk Search Visit', searchVisitorId, null);
        await createVisit(connection, siteId, hostId, 'E2E Kiosk QR Visit', qrVisitorId, qrCode);
        const checkoutVisitId = await createVisit(connection, siteId, hostId, 'E2E Kiosk Checkout Visit', checkoutVisitorId, null);

        await connection.execute(
            'update visit_visitor set checked_in_at = current_timestamp where visit_id = ? and visitor_id = ?',
            [checkoutVisitId, checkoutVisitorId],
        );

        await connection.execute(
            `insert into kiosks (
                 site_id, walk_in_host_user_id, created_by_user_id, name, token,
                 is_active, allow_walk_in, allow_check_out, require_signature, created_at, updated_at
             ) values (?, ?, ?, 'E2E Empfangs-Kiosk', ?, true, true, true, false, current_timestamp, current_timestamp)`,
            [siteId, hostId, hostId, KIOSK_TOKEN],
        );
    } finally {
        await connection.end();
    }
}

async function createUser(connection: Connection, siteId: number, email: string, firstName: string, name: string): Promise<number> {
    const passwordHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    await connection.execute(
        `insert into users (
             site_id, first_name, name, gender, email, email_verified_at, locale, theme_preference,
             password, is_active, local_login_allowed, created_at, updated_at
         ) values (?, ?, ?, 'other', ?, current_timestamp, 'de', 'dark', ?, true, true, current_timestamp, current_timestamp)`,
        [siteId, firstName, name, email, passwordHash],
    );

    return userIdFor(connection, email);
}

async function createVisitor(connection: Connection, email: string, firstName: string, name: string): Promise<number> {
    await connection.execute(
        `insert into visitors (first_name, name, email, company, created_at, updated_at)
         values (?, ?, ?, 'Example Industries', current_timestamp, current_timestamp)`,
        [firstName, name, email],
    );

    return visitorIdFor(connection, email);
}

async function createVisit(
    connection: Connection,
    siteId: number,
    hostId: number,
    title: string,
    visitorId: number,
    checkInCode: string | null,
): Promise<number> {
    await connection.execute(
        `insert into visits (
             site_id, host_user_id, created_by_user_id, title, scheduled_from, scheduled_until,
             status, check_in_code, is_confidential, is_walk_in, notes, created_at, updated_at
         ) values (
             ?, ?, ?, ?,
             date_sub(current_timestamp, interval 1 hour),
             date_add(current_timestamp, interval 6 hour),
             'planned', ?, false, false, 'E2E kiosk fixture.', current_timestamp, current_timestamp
         )`,
        [siteId, hostId, hostId, title, checkInCode],
    );

    const visitId = await visitIdFor(connection, title);

    await connection.execute(
        `insert into visit_visitor (visit_id, visitor_id, created_at, updated_at)
         values (?, ?, current_timestamp, current_timestamp)`,
        [visitId, visitorId],
    );

    return visitId;
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

async function isCheckedIn(email: string): Promise<boolean> {
    return pivotHasTimestamp(email, 'checked_in_at');
}

async function isCheckedOut(email: string): Promise<boolean> {
    return pivotHasTimestamp(email, 'checked_out_at');
}

async function hasSignature(email: string): Promise<boolean> {
    const connection = await db();

    try {
        const [rows] = await connection.execute(
            `select vv.signature_data as signature
               from visit_visitor vv
               join visitors v on v.id = vv.visitor_id
              where v.email = ?
              order by vv.updated_at desc
              limit 1`,
            [email],
        );

        return Boolean((rows as { signature: string | null }[])[0]?.signature);
    } finally {
        await connection.end();
    }
}

async function pivotHasTimestamp(email: string, column: 'checked_in_at' | 'checked_out_at'): Promise<boolean> {
    const connection = await db();

    try {
        const [rows] = await connection.execute(
            `select vv.${column} as stamp
               from visit_visitor vv
               join visitors v on v.id = vv.visitor_id
              where v.email = ?
              order by vv.updated_at desc
              limit 1`,
            [email],
        );

        const stamp = (rows as { stamp: string | null }[])[0]?.stamp ?? null;

        return stamp !== null;
    } finally {
        await connection.end();
    }
}

async function walkInVisitExists(firstName: string): Promise<boolean> {
    const connection = await db();

    try {
        const [rows] = await connection.execute(
            `select count(*) as total
               from visits v
               join visit_visitor vv on vv.visit_id = v.id
               join visitors vi on vi.id = vv.visitor_id
              where v.is_walk_in = true and vi.first_name = ? and vv.checked_in_at is not null`,
            [firstName],
        );

        return Number((rows as { total: number }[])[0].total) > 0;
    } finally {
        await connection.end();
    }
}

async function visitIdFor(connection: Connection, title: string): Promise<number> {
    const [rows] = await connection.execute('select id from visits where title = ? limit 1', [title]);

    return Number((rows as { id: number }[])[0].id);
}

async function userIdFor(connection: Connection, email: string): Promise<number> {
    const [rows] = await connection.execute('select id from users where email = ? limit 1', [email]);

    return Number((rows as { id: number }[])[0].id);
}

async function visitorIdFor(connection: Connection, email: string): Promise<number> {
    const [rows] = await connection.execute('select id from visitors where email = ? limit 1', [email]);

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
