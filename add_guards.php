<?php
// Insert data-scope guards into store/update methods (idempotent)
$rules = [
    // file => [ [anchor, insertion], ... ] ; insertion placed AFTER anchor line
    'MiningActivityController.php' => [
        ["\$validated = \$this->validateInput(\$request);", "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ],
    'ProductionBatchController.php' => [
        ["\$validated = \$this->validateInput(\$request);", "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ],
    'WorkOrderController.php' => [
        ["\$validated = \$this->validateInput(\$request);", "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ],
    'PurchaseRequestController.php' => [
        ["\$validated = \$this->validateInput(\$request);", "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ],
    'PurchaseOrderController.php' => [
        ["\$validated = \$this->validateInput(\$request);", "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ],
    'SalesOrderController.php' => [
        ["\$validated = \$this->validateInput(\$request);", "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ],
    'EmployeeController.php' => [
        ["\$validated = \$this->validateInput(\$request);", "        \$this->ensureCompanyInScope(\$validated['company_id'] ?? null);\n        \$this->ensureSiteInScope(\$validated['site_id'] ?? null);"],
    ],
];

foreach ($rules as $file => $pairs) {
    $path = "app/Http/Controllers/{$file}";
    $c = file_get_contents($path);
    // ensure trait import + use
    if (!str_contains($c, 'Concerns\\AppliesDataScope')) {
        $c = preg_replace('/(namespace App\\\\Http\\\\Controllers;\n)/', "$1use App\\Http\\Controllers\\Concerns\\AppliesDataScope;\n", $c, 1);
        $c = preg_replace('/(class \w+ extends \w+\n\{\n)/', "$1    use AppliesDataScope;\n\n", $c, 1);
    }
    foreach ($pairs as [$anchor, $insert]) {
        // apply to every occurrence (store + update)
        $count = 0;
        $offset = 0;
        while (($pos = strpos($c, $anchor, $offset)) !== false) {
            // skip if already guarded (next non-empty content contains ensureCompanyInScope within 300 chars)
            $peek = substr($c, $pos, 400);
            if (str_contains($peek, 'ensureCompanyInScope') || str_contains($peek, 'ensureSiteInScope') || str_contains($peek, 'ensureInScope')) {
                $offset = $pos + strlen($anchor);
                continue;
            }
            $c = substr_replace($c, $anchor . "\n" . $insert, $pos, strlen($anchor));
            $count++;
            $offset = $pos + strlen($anchor) + strlen($insert);
        }
        echo "{$file}: +{$count} guard(s)\n";
    }
    file_put_contents($path, $c);
}
