import { expect, test } from '@playwright/test';
import { ACTIVE_CAMPAIGN, DONOR, acceptDialogs, donateButton } from './support.js';

// Talks to the Midtrans sandbox: needs MIDTRANS_SERVER_KEY and MIDTRANS_CLIENT_KEY in .env.e2e.
test.use({ storageState: DONOR.state });

async function startDonation(page) {
    await page.goto(`/campaigns/${ACTIVE_CAMPAIGN.slug}`);
    await donateButton(page, ACTIVE_CAMPAIGN.slug).click();
    await expect(page).toHaveURL(`/campaigns/${ACTIVE_CAMPAIGN.slug}/donate`);

    await page.locator('#amount-input').fill('25000');
    await page.getByRole('button', { name: 'Continue to payment' }).click();

    await expect(page, 'No Snap token: are the Midtrans sandbox keys set in .env.e2e?').toHaveURL(/\/donations\/INF-[A-Z0-9]+$/);
    return page.url().split('/').pop();
}

test('verified donor submits the form and the Snap popup opens', async ({ page }) => {
    await startDonation(page);

    // Snap renders its payment page in an iframe; stop there without paying.
    const snap = page.locator('iframe#snap-midtrans');
    await expect(snap).toBeVisible({ timeout: 30_000 });
    await expect(snap.contentFrame().locator('body')).toBeVisible();
});

test('donor cancels a pending donation and it becomes Failed', async ({ page }) => {
    const reference = await startDonation(page);

    // Reload without the flash so Snap does not open on top of the page.
    await page.goto(`/donations/${reference}`);
    acceptDialogs(page);
    await page.getByRole('button', { name: 'Cancel donation' }).click();

    await expect(page).toHaveURL(/\/history/);
    await expect(page.getByText(`Donation ${reference} was cancelled.`)).toBeVisible();
    const row = page.getByRole('row').filter({ hasText: reference });
    await expect(row.getByText('Failed', { exact: true })).toBeVisible();

    await page.goto(`/donations/${reference}`);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Donation cancelled / payment failed');
});
