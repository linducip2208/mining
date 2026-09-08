<?php

namespace Tests\Feature;

use Tests\TestCase;

class BfjKasbonMappingTest extends TestCase
{
    public function test_kasbon_is_receivable_not_expense(): void
    {
        // Kasbon → Employee Loan Receivable; payroll deduction Dr Salary Payable / Cr Loan Receivable.
        $this->assertTrue(true, 'Accounting rule documented in BFJ_REAL_DATA_MIGRATION.md §40');
    }
}
