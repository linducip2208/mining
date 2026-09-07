/**
 * Mining ERP pSEO — responsive + metadata smoke across clusters.
 * Usage: APP_URL=http://127.0.0.1:8000 node tests/playwright/seo-audit.mjs
 */
import { chromium } from 'playwright';

const BASE = process.env.APP_URL || 'http://127.0.0.1:8000';
const PAGES = [
    '/erp-mining',
    '/source-code-erp-tambang',
    '/harga-erp-tambang',
    '/modul/fuel',
    '/industri/pertambangan-batubara',
    '/lokasi/kalimantan-timur/erp-tambang',
    '/solusi/fuel-management-tambang',
    '/cari-solusi',
];
const VIEWPORTS = [
    [360, 800], [390, 844], [430, 932], [768, 1024],
    [1024, 768], [1366, 768], [1440, 900], [1920, 1080],
];

const browser = await chromium.launch();
let fails = 0;
for (const [w, h] of VIEWPORTS) {
    const page = await browser.newPage({ viewport: { width: w, height: h } });
    for (const path of PAGES) {
        const problems = [];
        try {
            const resp = await page.goto(BASE + path, { waitUntil: 'domcontentloaded', timeout: 30000 });
            if (!resp || resp.status() !== 200) {
                problems.push(`HTTP-${resp ? resp.status() : 'none'}`);
            } else {
                await page.waitForTimeout(500);
                const found = await page.evaluate(() => {
                    const doc = document.documentElement;
                    const out = { overflow: doc.scrollWidth > doc.clientWidth + 1, h1: document.querySelectorAll('h1').length };
                    return out;
                });
                if (found.overflow) {
                    problems.push('PAGE-OVERFLOW');
                }
                if (found.h1 !== 1 && !path.includes('cari-solusi')) {
                    problems.push(`H1-COUNT=${found.h1}`);
                }
            }
        } catch (e) {
            problems.push('NAV-ERROR ' + String(e).split('\n')[0].slice(0, 80));
        }
        if (problems.length) {
            fails++;
            console.log(`FAIL ${w}x${h} ${path}: ${problems.join(' | ')}`);
        }
    }
    await page.close();
}
await browser.close();
console.log(fails === 0 ? `ALL ${PAGES.length * VIEWPORTS.length} SEO viewport checks PASS` : `${fails} FAILURES`);
process.exit(fails > 0 ? 1 : 0);
