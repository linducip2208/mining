import { chromium } from 'playwright';
const BASE = process.env.APP_URL || 'http://miningerp.test';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 360, height: 800 } });
await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
await page.fill('#email', 'admin@miningerp.local');
await page.fill('#password', 'Admin!2345');
await page.click('button[type=submit]');
await page.waitForLoadState('domcontentloaded');
await page.goto(BASE + '/users', { waitUntil: 'domcontentloaded' });
await page.waitForTimeout(600);
const count = await page.locator('[aria-label^="Aksi lainnya"]').count();
console.log('action buttons:', count);
const rows = await page.locator('tbody tr').count();
console.log('rows:', rows);
const btn = page.locator('[aria-label^="Aksi lainnya"]').first();
try {
    await btn.click({ timeout: 5000 });
    console.log('clicked OK');
} catch (e) {
    console.log('click fail:', String(e).split('\n').slice(0, 6).join(' | '));
}
await page.screenshot({ path: 'docs/responsive-audit/debug-users-360.png' });
await browser.close();
