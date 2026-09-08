# Go-Live Checklist

All items must be verified with evidence (command output / screenshot / signed doc). No item may be marked done on assumption.

## Environment

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, trusted proxies/hosts configured
- [ ] HTTPS enforced; `SESSION_SECURE_COOKIE=true`
- [ ] Queue worker running (database queue) + scheduler cron (`schedule:run` every minute)
- [ ] `storage/app/documents` on backed-up volume; `public/storage` link present
- [ ] Mail driver configured and tested (approval + alert notifications)

## Data

- [ ] `php artisan migrate:fresh --seed` passes on a staging clone (production uses `migrate --force`, never fresh)
- [ ] Full database backup taken and restore-tested
- [ ] Company / sites / pits / warehouses / items / customers / suppliers / employees masters loaded and reconciled to counts
- [ ] COA complete; accounting mappings configured (missing mapping blocks posting — verify by posting one test journal per source type, then reversing it)
- [ ] Tax codes + PPN rates configured
- [ ] Numbering formats reviewed (SO/DO/INV/PO/PR/GRN/WB/WO/LETTER/RECEIPT/JOURNAL); no duplicate numbers in a 100-parallel smoke test
- [ ] Fiscal periods: current OPEN, prior CLOSED where applicable
- [ ] Opening balances: stock (OPENING movements), AR/AP, cash/bank, deposits, payroll payable — each tied to a reconciliation row

## Access

- [ ] Demo `superadmin` NOT present in production (seeder skips it when `APP_ENV=production`)
- [ ] Real admin created via tinker with strong password; force-password-change policy set
- [ ] Roles assigned per RACI; every user has company/site scope (null scope = unrestricted — audit the list)
- [ ] Open self-registration disabled or gated (route `register` — decide explicitly)

## Operations

- [ ] Printers registered; one test print per document type; ESC/POS agent paired where used
- [ ] PWA manifest + icons serve; service worker updates on deploy (bump `sw.js` cache key when branding/offline page changes)
- [ ] `php artisan inventory:audit-integrity` → PASS
- [ ] `php artisan accounting:audit-integrity` → PASS
- [ ] `php artisan test` → PASS on staging clone
- [ ] `npm run build` → PASS
- [ ] BFJ acceptance → PASS with 0 unresolved critical masters (only if migrating legacy data)

## Monitoring

- [ ] `alerts:scan` scheduled; alert rules reviewed (approval pending, overdue AR/AP, low stock, fuel anomaly, maintenance due, permit expiry, budget ≥90%)
- [ ] Log channel writable; no passwords/tokens/bank numbers in logs (spot-check)
- [ ] Backup job scheduled and verified (database + uploaded documents)
