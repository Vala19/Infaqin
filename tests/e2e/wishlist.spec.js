import { expect, test } from '@playwright/test';
import { ACTIVE_CAMPAIGN, DONOR } from './support.js';

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
