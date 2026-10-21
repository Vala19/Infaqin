import { expect, test } from '@playwright/test';
import { DONOR, login } from './support.js';

test('login with a wrong password shows an error', async ({ page }) => {
    await login(page, { email: DONOR.email, password: 'wrong-password' });

    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByText('These credentials do not match our records.')).toBeVisible();
});

test('login with the demo account opens the home page', async ({ page }) => {
    await login(page, DONOR);

    await expect(page).toHaveURL(/\/home$/);
    await expect(page.getByText('Welcome back, Aisyah Rahmawati!')).toBeVisible();
});
