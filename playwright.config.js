import { defineConfig, devices } from '@playwright/test';

const BASE_URL = 'http://127.0.0.1:8001';

export default defineConfig({
    testDir: './tests/e2e',
    // One SQLite file and a single-threaded `php artisan serve`: run tests one at a time.
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    timeout: 60_000,
    use: {
        baseURL: BASE_URL,
        locale: 'en-US',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /.*\.setup\.js/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
        },
    ],
    webServer: {
        // Always start from a freshly seeded database/e2e.sqlite.
        command: 'npm run e2e:reset && php artisan serve --env=e2e --host=127.0.0.1 --port=8001',
        url: `${BASE_URL}/up`,
        reuseExistingServer: false,
        timeout: 180_000,
        stdout: 'ignore',
        stderr: 'pipe',
    },
});
