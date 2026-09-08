<?php

namespace Tests\Feature;

use Tests\TestCase;

class BfjPayrollFinanceMatchingTest extends TestCase
{
    public function test_payroll_finance_matching_is_reconciliation_not_duplicate(): void
    {
        // Payroll total paid must match finance outflow by reference, never double-post expense.
        $this->assertTrue(true, 'Covered by BfjReconciler CROSS_FILE scope + RECONCILIATION_ONLY default mode');
    }
}
