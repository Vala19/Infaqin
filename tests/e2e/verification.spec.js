import { expect, test } from '@playwright/test';
import { ACTIVE_CAMPAIGN, donateButton } from './support.js';

test('unverified account is sent to the verification page when donating', async ({ page }) => {
    const id = Date.now().toString(36);

    await page.goto('/register');
    await page.getByLabel('Full name').fill('E2E Unverified');
    await page.getByLabel('Username').fill(`e2e_${id}`);
    await page.getByLabel('Email address').fill(`e2e-${id}@example.test`);
    await page.locator('input[name="password"]').fill('E2e#Pass12345');
    await page.locator('input[name="password_confirmation"]').fill('E2e#Pass12345');
    await page.locator('input[name="terms"]').check();
    await page.getByRole('button', { name: 'Create account' }).click();
    await expect(page).toHaveURL(/\/home$/);

    await page.goto(`/campaigns/${ACTIVE_CAMPAIGN.slug}`);
    await donateButton(page, ACTIVE_CAMPAIGN.slug).click();

    await expect(page).toHaveURL(/\/email\/verify$/);
    await expect(page.getByText('Verify your email')).toBeVisible();
});
