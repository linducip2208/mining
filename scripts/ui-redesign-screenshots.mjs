import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const base = 'http://127.0.0.1:8765';
const pages = [['dashboard','/dashboard'],['users','/users'],['roles','/roles'],['mining','/mining-dashboard'],['fuel','/fuel'],['fleet','/fleet'],['finance','/finance/pl'],['approval','/approvals']];
const before = {dashboard:'public/docs-assets/screenshots/dashboard/dashboard.png',users:'public/docs-assets/screenshots/administration/users.png',roles:'public/docs-assets/screenshots/administration/roles.png',mining:'public/docs-assets/screenshots/mining/activity.png',fuel:'public/docs-assets/screenshots/fuel/dashboard.png',fleet:'public/docs-assets/screenshots/fleet/dashboard.png',finance:'public/docs-assets/screenshots/accounting/pl.png',approval:'public/docs-assets/screenshots/approval/center.png'};
await fs.mkdir('docs/ui-before-after/before', {recursive:true});
for (const [name, source] of Object.entries(before)) {
    try { await fs.copyFile(source, `docs/ui-before-after/before/${name}-before.png`); } catch {}
}
const browser = await chromium.launch({headless:true});
for (const width of [414, 1440, 1920]) {
    const context = await browser.newContext({viewport:{width,height:1080}});
    const page = await context.newPage();
    await page.goto(`${base}/login`, {waitUntil:'networkidle'});
    await page.locator('input[name="email"]').fill('superadmin');
    await page.locator('input[name="password"]').fill('Admin!2345');
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
    for (const [name, path] of pages) {
        await page.goto(`${base}${path}`, {waitUntil:'networkidle'});
        await fs.mkdir(`docs/ui-before-after/${width}`, {recursive:true});
        await page.screenshot({path:`docs/ui-before-after/${width}/${name}-after.png`, fullPage:true});
    }
    await context.close();
}
await browser.close();
