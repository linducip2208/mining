import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const jobsFile = process.argv[2];
if (!jobsFile) {
  console.error('Usage: node docs-screenshots.mjs <jobs.json>');
  process.exit(2);
}
const { base, user, pass, out, jobs } = JSON.parse(fs.readFileSync(jobsFile, 'utf8'));

const shot = async (page, file) => {
  const dest = path.join(out, file);
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  await page.screenshot({ path: dest, fullPage: false });
  console.log(`OK ${file}`);
};

const settle = async (page) => {
  try {
    await page.waitForSelector('main', { timeout: 15000 });
  } catch { /* lanjut walau main tak ketemu */ }
  try {
    await page.waitForLoadState('networkidle', { timeout: 10000 });
  } catch { /* abaikan timeout networkidle */ }
  await page.waitForTimeout(800);
};

const pickFirstOption = async (page, name) => {
  const sel = `select[name="${name}"]`;
  if ((await page.locator(sel).count()) === 0) return;
  const values = await page.locator(`${sel} option`).evaluateAll((opts) =>
    opts.map((o) => o.value).filter((v) => v !== '')
  );
  if (values.length > 0) {
    await page.locator(sel).selectOption(values[0]);
  }
};

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await context.newPage();
const guestContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const guestPage = await guestContext.newPage();

try {
  // ---- login ----
  await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.locator('input[name="email"]').fill(user);
  await page.locator('input[name="password"]').fill(pass);
  await Promise.all([
    page.waitForURL('**/dashboard', { timeout: 30000 }),
    page.locator('button[type="submit"]').click(),
  ]);
  console.log('OK login');

  for (const job of jobs) {
    const label = job.file || job.flow || 'job';
    try {
      if (job.flow === 'weighbridge') {
        await weighbridgeFlow(page, job);
        continue;
      }
      const pg = job.guest ? guestPage : page;
      await pg.goto(job.url, { waitUntil: 'domcontentloaded', timeout: 30000 });
      await settle(pg);
      await shot(pg, job.file);
    } catch (e) {
      console.log(`FAIL ${label}: ${String(e.message || e).slice(0, 160)}`);
    }
  }
} catch (e) {
  console.log(`FAIL login: ${String(e.message || e).slice(0, 160)}`);
  process.exitCode = 1;
} finally {
  await browser.close();
}

async function weighbridgeFlow(page, job) {
  const files = job.files;
  // 1. form timbang pertama (sudah ada shot-nya sebagai job biasa; tetap isi untuk flow)
  await page.goto(job.createUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await settle(page);
  for (const name of ['weighbridge_id', 'company_id', 'site_id']) {
    await pickFirstOption(page, name);
  }
  const dirSel = 'select[name="direction"]';
  if ((await page.locator(dirSel).count()) > 0) {
    await page.locator(dirSel).selectOption('OUT');
  }
  await page.locator('input[name="vehicle_plate"]').fill('B 9001 TST');
  const drv = page.locator('input[name="driver_name"]');
  if ((await drv.count()) > 0) await drv.fill('Docs Driver');
  await page.locator('input[name="weight"]').fill('25000');
  await Promise.all([
    page.waitForURL(/weighbridge-tickets\/\d+/, { timeout: 30000 }),
    page.locator('form[action*="first-weigh"] button[type="submit"], form[action*="first-weigh"] button:not([type])').first().click(),
  ]);
  await settle(page);
  await shot(page, files['ticket-detail']);

  // 2. timbang kedua -> COMPLETE
  await page.locator('form[action*="second-weigh"] input[name="weight"]').fill('17000');
  await Promise.all([
    page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {}),
    page.locator('form[action*="second-weigh"] button[type="submit"], form[action*="second-weigh"] button:not([type])').first().click(),
  ]);
  await settle(page);
  await shot(page, files['second-weigh']);

  // 3. posting -> POSTED
  const postBtn = page.locator('form[action*="/post"] button[type="submit"], form[action*="/post"] button:not([type])');
  if ((await postBtn.count()) > 0) {
    await Promise.all([
      page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {}),
      postBtn.first().click(),
    ]);
    await settle(page);
  }
  await shot(page, files['posted-ticket']);

  // 4. print view (GET kini didukung)
  const printHref = await page.locator('a[href*="/print"]').first().getAttribute('href').catch(() => null);
  if (printHref) {
    const url = printHref.startsWith('http') ? printHref : new URL(printHref, page.url()).toString();
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await settle(page);
    await shot(page, files['print']);
  } else {
    console.log('FAIL weighbridge/print.png: tombol cetak tidak ditemukan');
  }
}
