// Shared constants and helpers for the Playwright suite. Data comes from DatabaseSeeder.
export const ADMIN = { email: 'admin@infaqin.test', password: 'Admin#12345', state: 'tests/e2e/.auth/admin.json' };
export const DONOR = { email: 'user@infaqin.test', password: 'Donor#12345', state: 'tests/e2e/.auth/donor.json' };

export const ACTIVE_CAMPAIGN = { title: 'GEMAS — Gerakan Membersihkan Masjid', slug: 'gemas-gerakan-membersihkan-masjid' };
export const DRAFT_CAMPAIGN = { title: 'Ramadan Iftar for Pesantren Students', slug: 'ramadan-iftar-for-pesantren-students' };

export async function login(page, { email, password }) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.locator('input[name="password"]').fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
}

// Accepts the window.confirm() used by destructive forms.
export function acceptDialogs(page) {
    page.on('dialog', (dialog) => dialog.accept());
}

// The campaign's own donate button (the navbar also has a "Donate now" link to /campaigns).
export function donateButton(page, slug) {
    return page.locator(`a[href$="/campaigns/${slug}/donate"]`).filter({ visible: true }).first();
}
