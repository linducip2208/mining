<?php

namespace App\Console\Commands;

use App\Models\LegacyImportBatch;
use App\Services\Bfj\BfjAcceptance;
use App\Services\Bfj\BfjCrossFile;
use App\Services\Bfj\BfjExcelWriter;
use App\Services\Bfj\BfjImportEngine;
use App\Services\Bfj\BfjReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BfjAcceptanceRunCommand extends Command
{
    protected $signature = 'bfj:acceptance-run '
        .' {--sales=} {--deposit=} {--finance=} {--payroll=} {--documents=} {--spareparts=}'
        .' {--execute-history : run safe history import on IMPORT sheets before reconciling}'
        .' {--probe : run read-only integrity audits and route checks}'
        .' {--tests-result= : attest externally-run test suite result (PASS|FAIL)}'
        .' {--build-result= : attest externally-run frontend build result (PASS|FAIL)}';

    protected $description = 'BFJ acceptance orchestrator: reconcile, cross-file, JSON/XLSX reports (no live posting)';

    public function handle(): int
    {
        $t0 = microtime(true);
        $roles = [];
        foreach (['sales', 'deposit', 'finance', 'payroll', 'documents', 'spareparts'] as $role) {
            if ($this->option($role)) {
                $roles[$role] = array_map('intval', explode(',', (string) $this->option($role)));
            }
        }
        if ($roles === []) {
            $this->error('Provide at least one --sales/--deposit/--finance/--payroll/--documents/--spareparts batch id.');

            return self::FAILURE;
        }
        $ids = collect($roles)->flatten()->values()->all();
        foreach ($ids as $id) {
            $b = LegacyImportBatch::findOrFail($id);
            if ($this->option('execute-history')) {
                BfjImportEngine::import($b->fresh());
            }
            $t = microtime(true);
            BfjReconciler::reconcile($b->fresh());
            $this->line("reconciled batch #{$id} in ".round(microtime(true) - $t, 1).'s');
        }
        // cross-file (persisted to anchor batches)
        $xf = [];
        if (isset($roles['sales'], $roles['deposit'])) {
            $r = BfjCrossFile::salesDeposit($roles['sales'][0], $roles['deposit'][0]);
            BfjCrossFile::persist($roles['sales'][0], $r['entries']);
            $xf['sales_deposit'] = $r['stats'];
        }
        if (isset($roles['sales'], $roles['finance'])) {
            $r = BfjCrossFile::salesFinance($roles['sales'][0], $roles['finance'][0]);
            BfjCrossFile::persist($roles['sales'][0], $r['entries']);
            $xf['sales_finance'] = $r['stats'];
        }
        if (isset($roles['payroll'], $roles['finance'])) {
            $r = BfjCrossFile::payrollFinance($roles['payroll'][0], $roles['finance'][0]);
            BfjCrossFile::persist($roles['payroll'][0], $r['entries']);
            $xf['payroll_finance'] = $r['stats'];
        }
        if (isset($roles['documents'], $roles['deposit'])) {
            $r = BfjCrossFile::invoiceLinks($roles['documents'], $roles['deposit'][0]);
            BfjCrossFile::persist($roles['documents'][0], $r['entries']);
            $xf['invoice_links'] = $r['stats'];
        }
        if (isset($roles['spareparts'])) {
            $r = BfjCrossFile::sparepartMaintenance($roles['spareparts']);
            BfjCrossFile::persist($roles['spareparts'][0], $r['entries']);
            $xf['sparepart_maintenance'] = $r['stats'];
        }
        $probes = [];
        if ($this->option('probe')) {
            $probes = $this->probes();
        }
        $result = BfjAcceptance::run($roles, $probes);
        $result['cross_file_stats'] = $xf;
        $result['duration_s'] = round(microtime(true) - $t0, 1);

        Storage::disk('local')->makeDirectory('reports');
        Storage::disk('local')->put('reports/bfj-acceptance.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->writeXlsx($result);
        $this->writeMarkdown($result);

        $this->printSummary($result);

        return self::SUCCESS;
    }

    private function probes(): array
    {
        // Read-only in-process checks ONLY. Never spawn migrate-capable
        // subprocesses (php artisan test / migrate) from inside this command:
        // test and build results arrive via explicit attestation flags after
        // being executed separately by the operator.
        $out = [
            'tests' => ['pass' => null, 'note' => 'run php artisan test separately; attest with --tests-result='],
            'integrity' => ['pass' => false],
            'build' => null,
            'routes' => ['count' => count(\Illuminate\Support\Facades\Route::getRoutes())],
        ];
        if ($this->option('tests-result')) {
            $out['tests'] = ['pass' => strtoupper((string) $this->option('tests-result')) === 'PASS', 'attested' => true];
        }
        if ($this->option('build-result')) {
            $out['build'] = ['pass' => strtoupper((string) $this->option('build-result')) === 'PASS', 'attested' => true];
        }
        foreach (['inventory:audit-integrity' => 'inventory', 'accounting:audit-integrity' => 'accounting'] as $cmd => $key) {
            try {
                $code = Artisan::call($cmd);
                $txt = Artisan::output();
                $out['integrity'][$key] = ['pass' => $code === 0 && ! str_contains(strtolower($txt), 'fail'), 'output' => mb_substr($txt, -1000)];
            } catch (\Throwable $e) {
                $out['integrity'][$key] = ['pass' => false, 'output' => mb_substr($e->getMessage(), 0, 500)];
            }
        }
        $out['integrity']['pass'] = ($out['integrity']['inventory']['pass'] ?? false) && ($out['integrity']['accounting']['pass'] ?? false);

        return $out;
    }

    private function writeXlsx(array $result): void
    {
        $kv = fn (array $a) => array_map(fn ($k, $v) => [$k, is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v], array_keys($a), array_values($a));
        $sheets = [
            ['Executive Summary', ['Metric', 'Value'], $kv([
                'Overall' => $result['overall_status'], 'Go-live' => $result['go_live'],
                'Files' => $result['files']['recognized'].'/6', 'Duration (s)' => $result['duration_s'],
            ])],
            ['Readiness', ['Dimension', 'Score /10'], $kv($result['readiness'])],
            ['Data Quality', ['Code|Severity', 'Count'], $kv($result['data_quality'])],
            ['Master Mapping', ['Entity|Status', 'Count'], collect($result['master_mapping']['by_entity'] ?? [])->flatMap(fn ($st, $ent) => collect($st)->map(fn ($c, $s) => [$ent.'|'.$s, $c]))->values()->all()],
        ];
        foreach (['sales', 'deposit', 'finance', 'payroll', 'spareparts', 'documents'] as $scope) {
            $sheets[] = [ucfirst($scope), ['Metric', 'Value'], $kv($result[$scope] ?? [])];
        }
        $path = Storage::disk('local')->path('reports/BFJ_Acceptance_Summary.xlsx');
        BfjExcelWriter::write($path, $sheets);
        $this->line('XLSX: '.$path);
    }

    private function writeMarkdown(array $result): void
    {
        // Real measured numbers from this run only — no placeholders, no invented figures.
        $row = function (string $k) use ($result): array {
            $s = $result[$k] ?? [];

            return [
                'rows' => $s['rows'] ?? 0,
                'valid' => $s['valid'] ?? 0,
                'warning' => $s['warning'] ?? 0,
                'error' => $s['error'] ?? 0,
                'duplicate' => $s['duplicate'] ?? 0,
                'match' => $s['match'] ?? 0,
                'variance' => $s['variance'] ?? 0,
                'unexplained' => $s['unexplained'] ?? 0,
                'critical_errors' => $s['critical_errors'] ?? 0,
            ];
        };
        $line = function (string $scope, string $label) use ($row): string {
            $r = $row($scope);

            return sprintf(
                '| %s | %d | %d | %d | %d | %d | %d | %d | %d | %d |',
                $label, $r['rows'], $r['valid'], $r['warning'], $r['error'], $r['duplicate'], $r['match'], $r['variance'], $r['unexplained'], $r['critical_errors']
            );
        };
        $tests = $result['tests']['pass'] ?? null;
        $inv = $result['integrity']['inventory']['pass'] ?? null;
        $acc = $result['integrity']['accounting']['pass'] ?? null;
        $pass = fn ($v) => $v === null ? 'N/A' : ($v ? 'PASS' : 'FAIL');
        $batches = collect($result['files']['batches'] ?? [])->map(fn ($b) => '- batch #'.($b['batch'] ?? $b['id'] ?? '?').' ('.($b['file'] ?? '?').', `'.substr((string) ($b['hash'] ?? $b['sha256'] ?? ''), 0, 12).'…`, '.($b['sheets'] ?? '?').' sheets)')->implode("\n");
        $mm = collect($result['master_mapping']['by_entity'] ?? [])->flatMap(fn ($st, $ent) => collect(is_array($st) ? $st : [])->map(fn ($c, $s) => '- '.$ent.' / '.$s.': '.$c))->implode("\n");

        $md = '# BFJ Final Execution Report'."\n\n";
        $md .= 'Generated: '.now()->toDateTimeString().' · duration '.$result['duration_s'].'s · command `php artisan bfj:acceptance-run`'."\n\n";
        $md .= '## Verdict'."\n\n";
        $md .= '- Acceptance: **'.$result['overall_status'].'**'."\n";
        $md .= '- Go-live: **'.$result['go_live'].'**'."\n";
        $md .= '- Files recognized: '.($result['files']['recognized'] ?? '?').'/'.($result['files']['expected'] ?? '?')."\n\n";
        $md .= '## Batches executed'."\n\n".($batches !== '' ? $batches : '_none_')."\n\n";
        $md .= '## Rows by domain'."\n\n";
        $md .= '| Domain | Rows | Valid | Warnings | Errors | Duplicates | Match | Variance | Unexplained | Critical |'."\n";
        $md .= '|---|---|---|---|---|---|---|---|---|---|'."\n";
        foreach (['sales' => 'Sales', 'deposit' => 'Deposit', 'finance' => 'Finance', 'payroll' => 'Payroll', 'documents' => 'Documents', 'spareparts' => 'Spareparts'] as $k => $label) {
            $md .= $line($k, $label)."\n";
        }
        $md .= "\n".'## Master mapping'."\n\n";
        $md .= '- Total: '.($result['master_mapping']['total'] ?? '?').' · resolved: '.($result['master_mapping']['resolved'] ?? '?').' · unresolved: '.($result['master_mapping']['unresolved'] ?? '?')."\n\n";
        $md .= ($mm !== '' ? $mm : '_no mapping detail_')."\n\n";
        $md .= '## Integrity & quality gates'."\n\n";
        $md .= '- Tests: '.$pass($tests)."\n";
        $md .= '- Inventory integrity: '.$pass($inv)."\n";
        $md .= '- Accounting integrity: '.$pass($acc)."\n\n";
        $md .= '## Blockers'."\n\n";
        $unexp = (int) ($row('sales')['unexplained'] + $row('deposit')['unexplained'] + $row('finance')['unexplained'] + $row('payroll')['unexplained'] + $row('spareparts')['unexplained']);
        $crit = (int) ($row('sales')['critical_errors'] + $row('deposit')['critical_errors'] + $row('finance')['critical_errors'] + $row('payroll')['critical_errors'] + $row('spareparts')['critical_errors']);
        $md .= '- Unexplained variance rows: '.$unexp."\n";
        $md .= '- Critical error rows: '.$crit."\n";
        $md .= '- Unresolved masters: '.($result['master_mapping']['unresolved'] ?? '?')."\n\n";
        $md .= '> Safety posture: sales/deposit HISTORY_ONLY, finance RECONCILIATION_ONLY, payroll PAYROLL_RECONCILIATION, invoice REGISTER_ONLY, receipt HISTORY_ONLY. No live stock/accounting posting without Finance authorization + explicit confirm flag.'."\n";

        file_put_contents(base_path('docs/BFJ_FINAL_EXECUTION_REPORT.md'), $md);
        $this->line('Markdown: '.base_path('docs/BFJ_FINAL_EXECUTION_REPORT.md'));
    }

    private function printSummary(array $r): void
    {
        $rows = fn (string $k) => $r[$k]['rows'] ?? 0;
        $tot = array_sum(array_map($rows, ['sales', 'deposit', 'finance', 'payroll', 'spareparts', 'documents']));
        $this->line('');
        $this->line('========================================================');
        $this->line('BFJ REAL DATA ACCEPTANCE');
        $this->line('========================================================');
        $this->line('');
        $this->line('Files .............. '.$r['files']['recognized'].'/6');
        $this->line('Rows Parsed ........ '.$tot);
        $this->line('Unresolved Masters . '.($r['master_mapping']['unresolved'] ?? '?'));
        $this->line('');
        foreach (['sales' => 'Sales', 'deposit' => 'Deposit', 'finance' => 'Finance', 'payroll' => 'Payroll', 'spareparts' => 'Stock'] as $k => $label) {
            $v = $r[$k]['variance'] ?? 0;
            $this->line(str_pad($label.' Variance .....', 22, '.').' '.$v.' dims');
        }
        $this->line('');
        $tv = $r['tests']['pass'] ?? null;
        $this->line('Tests .............. '.($tv === null ? 'N/A (run with --probe)' : ($tv ? 'PASS' : 'FAIL')));
        $this->line('ACCEPTANCE .......... '.$r['overall_status']);
        $this->line('GO-LIVE ............. '.$r['go_live']);
        $this->line('');
        $this->line('========================================================');
    }
}
