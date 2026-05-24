import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 60_000,
    use: {
        baseURL: process.env.BASE_URL ?? 'http://localhost:8080',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                launchOptions: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
                    ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH }
                    : undefined,
            },
        },
    ],
});
