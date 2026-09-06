import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const browser = await chromium.launch({ headless: true });
for (const [width, height] of [[414, 896], [1440, 900]]) {
    const context = await browser.newContext({ viewport: { width, height } });
    const page = await context.newPage();
    await page.goto('http://127.0.0.1:8765/login', { waitUntil: 'networkidle' });
    await fs.mkdir('docs/ui-before-after/' + width, { recursive: true });
    await page.screenshot({ path: 'docs/ui-before-after/' + width + '/login-after.png', fullPage: true });
    await context.close();
}
await browser.close();
