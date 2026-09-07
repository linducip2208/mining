import { chromium } from 'playwright';
const BASE = process.env.APP_URL || 'http://miningerp.test';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 360, height: 800 } });
await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
await page.fill('#email', 'admin@miningerp.local');
await page.fill('#password', 'Admin!2345');
await page.click('button[type=submit]');
await page.waitForLoadState('domcontentloaded');

for (const path of ['/dashboard', '/users']) {
    await page.goto(BASE + path, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(800);
    const info = await page.evaluate(() => {
        const doc = document.documentElement;
        const vw = doc.clientWidth;
        const out = { vw, sw: doc.scrollWidth, bw: document.body.scrollWidth, offenders: [] };
        // elements whose right edge extends the scrollable area
        const rightmost = [];
        document.querySelectorAll('body, body *').forEach((el) => {
            const r = el.getBoundingClientRect();
            if (r.width > 0 && r.right > vw + 1) {
                const cs = getComputedStyle(el);
                rightmost.push({ tag: el.tagName, cls: String(el.className).slice(0, 70), right: Math.round(r.right), pos: cs.position, text: (el.textContent || '').trim().slice(0, 40) });
            }
        });
        out.offenders = rightmost.slice(0, 12);
        return out;
    });
    console.log(JSON.stringify(info, null, 1));
}
await browser.close();
