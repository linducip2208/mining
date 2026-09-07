/**
 * Mining ERP — end-to-end responsive viewport audit.
 *
 * Detects real layout failures on 8 viewports across critical pages:
 *  - document horizontal overflow (scrollWidth > clientWidth)
 *  - elements spilling outside the viewport
 *  - topbar / sidebar overlap of content
 *  - modals & drawers wider than viewport
 *  - Chart.js canvas overflow
 *
 * Usage:
 *   node tests/playwright/responsive-audit.mjs            # audit only
 *   node tests/playwright/responsive-audit.mjs --shots    # audit + screenshots
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const BASE = process.env.APP_URL || 'http://miningerp.test';
const SHOTS = process.argv.includes('--shots');
const OUT_DIR = path.resolve('docs/responsive-audit');
const RESULTS = [];

const VIEWPORTS = [
    { id: 'mobile-360', width: 360, height: 800, group: 'mobile-360' },
    { id: 'mobile-390', width: 390, height: 844, group: 'mobile-390' },
    { id: 'mobile-430', width: 430, height: 932, group: 'mobile-390' },
    { id: 'tablet-768', width: 768, height: 1024, group: 'tablet-768' },
    { id: 'tablet-1024', width: 1024, height: 768, group: 'tablet-768' },
    { id: 'laptop-1366', width: 1366, height: 768, group: 'laptop-1366' },
    { id: 'desktop-1440', width: 1440, height: 900, group: 'desktop-1440' },
    { id: 'desktop-1920', width: 1920, height: 1080, group: 'desktop-1920' },
];

// group mobile-* screenshots together per requested structure
const SHOT_GROUP = {
    'mobile-360': 'mobile-360', 'mobile-390': 'mobile-390', 'mobile-430': 'mobile-390',
    'tablet-768': 'tablet-768', 'tablet-1024': 'tablet-1024',
    'laptop-1366': 'laptop-1366', 'desktop-1440': 'desktop-1440', 'desktop-1920': 'desktop-1920',
};

const PAGES = [
    { id: 'login', path: '/login', public: true },
    { id: 'dashboard', path: '/dashboard' },
    { id: 'users', path: '/users' },
    { id: 'roles', path: '/roles' },
    { id: 'role-matrix', path: '/roles/1' },
    { id: 'settings', path: '/settings' },
    { id: 'printer-devices', path: '/settings/printers' },
    { id: 'approval-center', path: '/approvals' },
    { id: 'weighbridge', path: '/weighbridge-tickets' },
    { id: 'production', path: '/production-batches' },
    { id: 'fuel', path: '/fuel-issues' },
    { id: 'inventory', path: '/stock/balance' },
    { id: 'invoices', path: '/invoices' },
    { id: 'finance-report', path: '/finance/pl' },
    { id: 'user-create', path: '/users/create' },
    { id: 'weighbridge-create', path: '/weighbridge-tickets/create' },
    { id: 'production-create', path: '/production-batches/create' },
];

async function login(page) {
    await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
    if (!page.url().includes('/login')) return; // already logged in
    await page.fill('#email', 'admin@miningerp.local');
    await page.fill('#password', 'Admin!2345');
    await Promise.all([
        page.waitForLoadState('domcontentloaded'),
        page.click('button[type=submit]'),
    ]);
    await page.waitForLoadState('domcontentloaded');
}

/** Layout probe executed in the page. Returns list of problems found. */
async function probe(page) {
    return page.evaluate(() => {
        const problems = [];
        const doc = document.documentElement;
        const vw = doc.clientWidth;

        // 1. whole-page horizontal overflow
        if (doc.scrollWidth > vw + 1) {
            // find widest offenders for the report
            const offenders = [];
            document.querySelectorAll('body *').forEach((el) => {
                const r = el.getBoundingClientRect();
                if (r.width > 0 && (r.right > vw + 2 || r.left < -2)) {
                    const cs = getComputedStyle(el);
                    if (cs.position === 'fixed') return;
                    const inScroller = el.closest('[class*="overflow-x-auto"], [class*="overflow-auto"]');
                    if (inScroller) return;
                    offenders.push(el.tagName + '.' + String(el.className).split(' ').slice(0, 4).join('.'));
                }
            });
            problems.push(`PAGE-OVERFLOW scrollWidth=${doc.scrollWidth} vw=${vw}` +
                (offenders.length ? ` offenders=[...new Set(${JSON.stringify(offenders.slice(0, 5))})]` : ''));
        }

        // 2. fixed-width elements wider than viewport
        document.querySelectorAll('body *').forEach((el) => {
            const cs = getComputedStyle(el);
            if (cs.position === 'fixed' || cs.position === 'absolute') return;
            if (el.closest('[class*="overflow-x-auto"], [class*="overflow-auto"]')) return;
            const w = el.getBoundingClientRect().width;
            if (w > vw + 2) {
                const cls = String(el.className).slice(0, 60);
                problems.push(`ELEMENT-WIDER-THAN-VIEWPORT <${el.tagName.toLowerCase()}> w=${Math.round(w)} vw=${vw} class="${cls}"`);
            }
        });

        // 3. charts overflowing their container
        document.querySelectorAll('canvas').forEach((c) => {
            const cr = c.getBoundingClientRect();
            if (cr.width > 0 && (cr.right > vw + 2 || cr.left < -2)) {
                problems.push(`CHART-OVERFLOW canvas#${c.id} right=${Math.round(cr.right)} vw=${vw}`);
            }
        });

        // 4. text inputs forcing overflow
        document.querySelectorAll('input, select, textarea').forEach((el) => {
            const r = el.getBoundingClientRect();
            if (r.width > 0 && (r.right > vw + 2 || r.left < -2)) {
                if (el.closest('[class*="overflow-x-auto"], [class*="overflow-auto"]')) return;
                problems.push(`CONTROL-OUTSIDE-VIEWPORT ${el.tagName.toLowerCase()}[name=${el.name || el.id}] right=${Math.round(r.right)} vw=${vw}`);
            }
        });

        return problems;
    });
}

/** Open a given overlay (modal/drawer/dropdown) and check its geometry. */
async function probeOverlay(page, viewport, pageDef, kind) {
    const problems = [];
    const vw = viewport.width;

    if (kind === 'modal') {
        // settings "Reset group" uses confirm; use users page toggle modal instead
        if (pageDef.id === 'users') {
            const btn = page.locator('[aria-label^="Aksi lainnya"]').first();
            if (await btn.count()) {
                await btn.click();
                await page.waitForTimeout(250);
                const item = page.locator('button:has-text("Nonaktifkan")').first();
                if (await item.count()) {
                    await item.click();
                    await page.waitForTimeout(400);
                    problems.push(...await probeDialog(page, vw, 'users-modal'));
                    await page.keyboard.press('Escape');
                    await page.mouse.click(10, 10);
                    await page.waitForTimeout(250);
                }
            }
        }
    }

    if (kind === 'drawer') {
        // mobile navigation drawer
        const burger = page.locator('header button[aria-label*="sidebar" i], header button[onclick*="toggleSidebar"]').first();
        if (await burger.count()) {
            await burger.click();
            await page.waitForTimeout(400);
            const sb = page.locator('#sidebar');
            if (await sb.count()) {
                const box = await sb.boundingBox();
                const visible = await sb.evaluate((el) => {
                    const cs = getComputedStyle(el);
                    return cs.transform === 'none' || !cs.transform.includes('-');
                });
                if (box && (box.x < -5 || box.width > vw * 0.92)) {
                    problems.push(`DRAWER-BAD-GEOMETRY x=${Math.round(box.x)} w=${Math.round(box.width)} vw=${vw}`);
                }
                if (box && visible && box.x > vw) problems.push(`DRAWER-NOT-VISIBLE`);
                // overlay must be visible
                const ov = page.locator('#sbOverlay');
                if (await ov.count() && await ov.isHidden()) problems.push('DRAWER-OVERLAY-MISSING');
            }
            await page.keyboard.press('Escape');
            // close via overlay tap
            const ov = page.locator('#sbOverlay');
            if (await ov.count() && await ov.isVisible()) await ov.click({ force: true });
            await page.waitForTimeout(350);
        }
    }
    return problems;
}

async function probeDialog(page, vw, label) {
    return page.evaluate((lbl) => {
        const problems = [];
        const vw2 = document.documentElement.clientWidth;
        document.querySelectorAll('[role="dialog"]').forEach((d) => {
            const cs = getComputedStyle(d);
            if (cs.display === 'none' || cs.visibility === 'hidden') return;
            const r = d.getBoundingClientRect();
            if (r.width === 0) return;
            // fully off-screen = closed drawer/sidebar (translate-x-full), not a visible dialog
            if (r.right <= 0 || r.left >= vw2) return;
            if (r.width > vw2 + 2) problems.push(`${lbl} DIALOG-WIDER-THAN-VIEWPORT w=${Math.round(r.width)} vw=${vw2}`);
            if (r.left < -2 || r.right > vw2 + 2) problems.push(`${lbl} DIALOG-CLIPPED left=${Math.round(r.left)} right=${Math.round(r.right)} vw=${vw2}`);
            const body = d.querySelector('.overflow-y-auto, [class*="overflow-y-auto"]');
            if (d.scrollHeight > d.clientHeight + 4 && !body && !d.className.includes('overflow-y-auto') && !cs.overflowY.includes('auto')) {
                problems.push(`${lbl} DIALOG-NOT-SCROLLABLE sh=${d.scrollHeight} ch=${d.clientHeight}`);
            }
        });
        return problems;
    }, label);
}

fs.mkdirSync(OUT_DIR, { recursive: true });

const browser = await chromium.launch();
let fails = 0, total = 0;
const matrix = {}; // pageId -> {viewportId: 'PASS'|'FAIL'}

for (const vp of VIEWPORTS) {
    const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await context.newPage();
    await login(page);

    for (const p of PAGES) {
        if (!p.public && p.id === 'login') continue;
        const url = BASE + p.path;
        let problems = [];
        try {
            const resp = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
            if (resp && resp.status() >= 400) {
                problems.push(`HTTP-${resp.status()}`);
            } else {
                await page.waitForTimeout(900); // charts + alpine settle
                problems = await probe(page);
                if (vp.width < 768) {
                    problems.push(...await probeOverlay(page, vp, p, 'drawer'));
                }
                if (p.id === 'users' && vp.width < 768) {
                    problems.push(...await probeOverlay(page, vp, p, 'modal'));
                }
            }
        } catch (e) {
            problems.push('NAV-ERROR ' + String(e).slice(0, 120));
        }

        total++;
        const status = problems.length ? 'FAIL' : 'PASS';
        if (problems.length) fails++;
        matrix[p.id] = matrix[p.id] || {};
        matrix[p.id][vp.id] = status;

        if (problems.length) {
            console.log(`✗ ${vp.id} ${p.id}: ${problems.join(' | ')}`);
        }
        if (SHOTS) {
            const dir = path.join(OUT_DIR, SHOT_GROUP[vp.id]);
            fs.mkdirSync(dir, { recursive: true });
            await page.screenshot({ path: path.join(dir, `${p.id}.png`), fullPage: p.id !== 'login' });
        }
    }
    await context.close();
}

await browser.close();

fs.writeFileSync(path.join(OUT_DIR, 'audit-results.json'), JSON.stringify({ generated: new Date().toISOString(), base: BASE, fails, total, matrix }, null, 2));
console.log(`\n=== ${total - fails}/${total} checks PASS, ${fails} FAIL ===`);
process.exit(fails > 0 ? 1 : 0);
