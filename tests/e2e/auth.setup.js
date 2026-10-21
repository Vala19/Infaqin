import { expect, test as setup } from '@playwright/test';
import { ADMIN, DONOR, login } from './support.js';

for (const account of [ADMIN, DONOR]) {
    setup(`log in as ${account.email}`, async ({ page }) => {
        await login(page, account);
        await expect(page).toHaveURL(/\/home$/);
        await page.context().storageState({ path: account.state });
    });
}
