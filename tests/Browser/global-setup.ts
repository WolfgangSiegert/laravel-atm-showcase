import { execFileSync } from 'node:child_process';
import { closeSync, openSync, rmSync } from 'node:fs';
import { browserDatabase, browserEnvironment } from '../../playwright.config';

export default function globalSetup() {
    rmSync(browserDatabase, { force: true });
    closeSync(openSync(browserDatabase, 'w'));
    execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--force'], {
        cwd: process.cwd(),
        env: { ...process.env, ...browserEnvironment },
        stdio: 'inherit',
    });
    execFileSync('php', ['artisan', 'atm:demo-provision'], {
        cwd: process.cwd(),
        env: { ...process.env, ...browserEnvironment },
        stdio: 'inherit',
    });
}
