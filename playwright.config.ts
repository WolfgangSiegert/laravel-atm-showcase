import { defineConfig, devices } from '@playwright/test';
import { resolve } from 'node:path';

export const browserDatabase = resolve('database/browser-testing.sqlite');
export const browserEnvironment = {
    APP_ENV: 'testing',
    APP_DEBUG: 'false',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: browserDatabase,
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'file',
    BCRYPT_ROUNDS: '4',
    PUBLIC_DEMO_ENABLED: 'true',
};

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: 'list',
    globalSetup: './tests/Browser/global-setup.ts',
    globalTeardown: './tests/Browser/global-teardown.ts',
    use: {
        baseURL: 'http://127.0.0.1:8010',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8010',
        env: browserEnvironment,
        url: 'http://127.0.0.1:8010/up',
        reuseExistingServer: false,
        timeout: 30_000,
    },
    projects: [{ name: process.env.PLAYWRIGHT_BROWSER === 'chromium' ? 'chromium' : 'chrome', use: { ...devices['Desktop Chrome'], channel: process.env.PLAYWRIGHT_BROWSER === 'chromium' ? undefined : 'chrome' } }],
});
