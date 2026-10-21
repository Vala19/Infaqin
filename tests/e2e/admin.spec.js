import { expect, test } from '@playwright/test';
import { ADMIN } from './support.js';

test.use({ storageState: ADMIN.state });

test('admin creates a draft campaign that stays hidden from the public', async ({ page, browser }) => {
    const title = `E2E Draft ${Date.now()}`;

    await page.goto('/admin/campaigns/create');
    await page.getByLabel('Title').fill(title);
    await page.locator('#summary').fill('Draft created by the Playwright suite.');
    await page.locator('#description').fill('This campaign must never be visible on public pages.');
    await page.getByLabel('Target (Rp)').fill('5000000');
    await page.locator('#status').selectOption('draft');
    await page.getByRole('button', { name: 'Create campaign' }).click();

    await expect(page).toHaveURL(/\/admin\/campaigns\/[^/]+\/edit$/);
    await expect(page.locator('#status')).toHaveValue('draft');
    await page.goto(`/admin/campaigns?q=${encodeURIComponent(title)}`);
    await expect(page.getByText(title)).toBeVisible();

    const guest = await browser.newPage({ storageState: { cookies: [], origins: [] } });
    await guest.goto('/campaigns?status=all');
    await expect(guest.getByText(title)).toHaveCount(0);

    const slug = title.toLowerCase().replace(/\s+/g, '-');
    const response = await guest.goto(`/campaigns/${slug}`);
    expect(response.status()).toBe(404);
    await guest.close();
});
