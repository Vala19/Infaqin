import { expect, test } from '@playwright/test';
import { ACTIVE_CAMPAIGN, DRAFT_CAMPAIGN, PROGRAM_CAMPAIGN, donateButton } from './support.js';

test('guest browses the campaign list and opens a campaign', async ({ page }) => {
    await page.goto('/campaigns');
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Find a cause close to your heart');

    await page.getByRole('link', { name: ACTIVE_CAMPAIGN.title, exact: true }).first().click();

    await expect(page).toHaveURL(`/campaigns/${ACTIVE_CAMPAIGN.slug}`);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(ACTIVE_CAMPAIGN.title);
    await expect(donateButton(page, ACTIVE_CAMPAIGN.slug)).toBeVisible();
});

test('draft campaign is hidden from guests', async ({ page }) => {
    await page.goto('/campaigns?status=all');
    await expect(page.getByText(DRAFT_CAMPAIGN.title)).toHaveCount(0);

    for (const path of [`/campaigns/${DRAFT_CAMPAIGN.slug}`, `/campaigns/${DRAFT_CAMPAIGN.slug}/comments`]) {
        const response = await page.goto(path);
        expect(response.status(), path).toBe(404);
    }
});

test('Syaamil campaign is labelled "Program", partner campaign "Campaign"', async ({ page }) => {
    await page.goto('/campaigns');
    await page.locator('label', { has: page.locator('input[name="type"][value="program"]') }).filter({ visible: true }).click();
    await expect(page).toHaveURL(/type=program/);

    const card = page.locator('article').filter({ has: page.getByRole('link', { name: PROGRAM_CAMPAIGN.title, exact: true }) });
    await expect(card.locator('[data-campaign-type]')).toHaveText('Program');
    await expect(page.getByRole('link', { name: ACTIVE_CAMPAIGN.title, exact: true })).toHaveCount(0);

    await page.goto(`/campaigns/${PROGRAM_CAMPAIGN.slug}`);
    await expect(page.locator('[data-campaign-type]').first()).toHaveText('Program');

    await page.goto(`/campaigns/${ACTIVE_CAMPAIGN.slug}`);
    await expect(page.locator('[data-campaign-type]').first()).toHaveText('Campaign');
});
