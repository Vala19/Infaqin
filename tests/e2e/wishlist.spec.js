import { expect, test } from '@playwright/test';
import { ACTIVE_CAMPAIGN, DONOR, PROGRAM_CAMPAIGN, login } from './support.js';

test.describe('signed-in donor', () => {
    test.use({ storageState: DONOR.state });

    test('donor saves a campaign, finds it on the wishlist and removes it', async ({ page }) => {
        await page.goto(`/campaigns/${ACTIVE_CAMPAIGN.slug}`);

        // The hero heart comes before the hearts on related campaign cards.
        const heart = page.locator('[data-wishlist-button]').first();
        await expect(heart).toHaveAttribute('aria-pressed', 'false');
        await heart.click();
        await expect(heart).toHaveAttribute('aria-pressed', 'true');

        await page.getByRole('navigation', { name: 'Main' }).getByRole('link', { name: 'Wishlist' }).click();
        await expect(page).toHaveURL('/wishlist');

        const card = page.locator('article').filter({ has: page.getByRole('link', { name: ACTIVE_CAMPAIGN.title, exact: true }) });
        await expect(card).toBeVisible();
        await expect(card.getByRole('link', { name: 'Donate', exact: true })).toHaveAttribute('href', new RegExp(`/campaigns/${ACTIVE_CAMPAIGN.slug}/donate$`));

        await card.locator('[data-wishlist-button]').click();
        await expect(card).toBeHidden();

        await page.reload();
        await expect(page.getByText('Your wishlist is empty')).toBeVisible();
    });
});

test.describe('guest', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('guest heart saves the campaign right after logging in', async ({ page }) => {
        await page.goto(`/campaigns/${PROGRAM_CAMPAIGN.slug}`);
        await page.locator('[data-wishlist-button]').first().click();
        await expect(page).toHaveURL('/login');

        await login(page, DONOR);
        await expect(page).toHaveURL(`/campaigns/${PROGRAM_CAMPAIGN.slug}`);
        await expect(page.getByText('Saved to wishlist', { exact: true })).toBeVisible();
        await expect(page.locator('[data-wishlist-button]').first()).toHaveAttribute('aria-pressed', 'true');

        await page.goto('/wishlist');
        const card = page.locator('article').filter({ has: page.getByRole('link', { name: PROGRAM_CAMPAIGN.title, exact: true }) });
        await expect(card).toBeVisible();

        // Leave the donor's wishlist empty for the next run.
        await card.locator('[data-wishlist-button]').click();
        await expect(card).toBeHidden();
    });
});
