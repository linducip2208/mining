<?php
// Second pass: trait + body-scope guards for remaining controllers (idempotent)
$traitImport = "use App\\Http\\Controllers\\Concerns\\AppliesDataScope;\n";
$traitUse = "    use AppliesDataScope;\n\n";

function ensureTrait(string $c): string
{
    global $traitImport, $traitUse;
    if (!str_contains($c, 'Concerns\\AppliesDataScope')) {
        $c = preg_replace('/(namespace App\\\\Http\\\\Controllers;\n)/', "$1" . $traitImport, $c, 1);
        $c = preg_replace('/(class \w+ extends \w+\n\{\n)/', "$1" . $traitUse, $c, 1);
    }
    return $c;
}

function insertOnce(string $c, string $anchor, string $insert): array
{
    $pos = strpos($c, $anchor);
    if ($pos === false) {
        return [$c, false];
    }
    $peek = substr($c, $pos, strlen($anchor) + 500);
    if (str_contains($peek, 'ensureCompanyInScope') || str_contains($peek, 'ensureSiteInScope') || str_contains($peek, 'ensureInScope')) {
        return [$c, 'skip'];
    }
    return [substr_replace($c, $anchor . "\n" . $insert, $pos, strlen($anchor)), true];
}

$jobs = [
    // [file, anchor, insertion]
    ['WeighbridgeTicketController.php',
        "        \$ticket = DB::transaction(function () use (\$validated) {",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ['DeliveryOrderController.php',
        "            \$so = SalesOrder::with('items')->find(\$validated['sales_order_id']);",
        "            \$this->ensureInScope(\$so);"],
    ['InvoiceController.php',
        "        \$so = SalesOrder::with('items')->find(\$validated['sales_order_id']);",
        "        \$this->ensureInScope(\$so);"],
    ['PaymentController.php',
        "        try {\n            \$payment = SalesService::receivePayment(",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureInScope(\\App\\Models\\Customer::find(\$validated['customer_id']));\n"],
    ['CustomerDepositController.php',
        "        try {\n            \$deposit = DepositService::depositIn(",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureInScope(\\App\\Models\\Customer::find(\$validated['customer_id']));\n"],
    ['GoodsReceiptController.php',
        "            \$po = PurchaseOrder::with('items')->find(\$validated['purchase_order_id']);",
        "            \$this->ensureInScope(\$po);"],
    ['VendorBillController.php',
        "        \$bill = DB::transaction(function () use (\$validated, \$request) {",
        "        \$this->ensureInScope(\\App\\Models\\Supplier::find(\$validated['supplier_id']));"],
    ['PayrollViewController.php',
        "        \$exists = PayrollRun::where('company_id', \$validated['company_id'])->where('period', \$validated['period'])->exists();",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);"],
    ['OperatorIncentiveController.php',
        "        \$incentive = OperatorIncentive::create(\$validated);",
        "        \$this->ensureInScope(\\App\\Models\\Employee::find(\$validated['employee_id']));\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ['LeaveController.php',
        "        \$leave = Leave::create(\$validated);",
        "        \$this->ensureInScope(\\App\\Models\\Employee::find(\$validated['employee_id']));"],
    ['OvertimeController.php',
        "        \$ot = Overtime::create(\$validated);",
        "        \$this->ensureInScope(\\App\\Models\\Employee::find(\$validated['employee_id']));\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ['AttendanceController.php',
        "        Attendance::updateOrCreate(",
        "        \$this->ensureInScope(\\App\\Models\\Employee::find(\$validated['employee_id']));"],
    ['PriceListController.php',
        "        \$validated = \$this->validateInput(\$request);",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);"],
    ['DocumentController.php',
        "        \$validated = \$this->validateInput(\$request);",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);"],
    ['CsrController.php',
        "        \$program = CsrProgram::create(\$validated);",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ['JournalController.php',
        "        try {\n            \$lines = collect(\$validated['lines'])->map(function (\$l) {",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);"],
    ['CoaController.php',
        "        \$coa = ChartOfAccount::create(\$validated);",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);"],
    ['CashAccountController.php',
        "        \$acc = CashAccount::create(\$validated);",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);"],
    ['BaseCrudController.php',
        "        \$validated = \$request->validate(\$this->rules());",
        "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);"],
    ['BaseCrudController.php',
        "        \$validated = \$request->validate(\$this->rules(\$item));",
        "        \$this->ensureInScope(\$item);\n        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);"],
];

foreach ($jobs as [$file, $anchor, $insert]) {
    $path = "app/Http/Controllers/{$file}";
    $c = file_get_contents($path);
    $c = ensureTrait($c);
    [$c, $ok] = insertOnce($c, $anchor, $insert);
    file_put_contents($path, $c);
    echo "{$file}: " . ($ok === true ? 'inserted' : ($ok === 'skip' ? 'already-guarded' : 'ANCHOR-NOT-FOUND')) . PHP_EOL;
}
