import { rmSync } from 'node:fs';
import { browserDatabase } from '../../playwright.config';

export default function globalTeardown() {
    rmSync(browserDatabase, { force: true });
}
