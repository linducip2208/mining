<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\CustomerDeposit;
use Illuminate\Support\Facades\DB;

/**
 * Customer Deposit ledger. Balance = SUM of movements; never a single editable number.
 */
class DepositService
{
    protected static function cashCoa(?int $cashAccountId): string
    {
        if ($cashAccountId) {
            $acc = CashAccount::find($cashAccountId);
            if ($acc?->coa_id) {
                return $acc->coa?->code ?? AccountingService::map('CASH_MAIN');
            }
        }
        return AccountingService::map('CASH_MAIN');
    }

    public static function depositIn(int $companyId, int $customerId, float $amount, $date, ?int $cashAccountId, ?string $refNumber = null, ?string $notes = null): CustomerDeposit
    {
        return DB::transaction(function () use ($companyId, $customerId, $amount, $date, $cashAccountId, $refNumber, $notes) {
            $d = self::record($companyId, $customerId, 'DEPOSIT_IN', $amount, $date, $cashAccountId, 'DEPOSIT', null, $refNumber, $notes);

            if ($cashAccountId) {
                $journal = AccountingService::post($companyId, $date, [
                    ['code' => self::cashCoa($cashAccountId), 'debit' => $amount, 'memo' => 'Deposit customer ' . $refNumber],
                    ['code' => AccountingService::map('CUSTOMER_DEPOSIT'), 'credit' => $amount, 'memo' => 'Deposit customer'],
                ], 'CUSTOMER_DEPOSIT', $d->id, $refNumber, 'Deposit masuk');
                $d->journal_entry_id = $journal->id;
                $d->save();
            }

            return $d;
        });
    }

    public static function allocate(int $companyId, int $customerId, float $amount, $date, ?int $invoiceId = null, ?string $invoiceNumber = null): CustomerDeposit
    {
        $balance = self::balance($customerId);
        if ($amount > $balance + 0.001) {
            throw new \DomainException('Saldo deposit tidak cukup. Saldo: Rp ' . number_format($balance, 2));
        }
        return self::record($companyId, $customerId, 'DEPOSIT_USED', $amount, $date, null, 'INVOICE', $invoiceId, $invoiceNumber, 'Alokasi deposit ke invoice');
    }

    public static function refund(int $companyId, int $customerId, float $amount, $date, ?int $cashAccountId, ?string $refNumber = null): CustomerDeposit
    {
        $balance = self::balance($customerId);
        if ($amount > $balance + 0.001) {
            throw new \DomainException('Saldo deposit tidak cukup untuk refund.');
        }
        $d = self::record($companyId, $customerId, 'DEPOSIT_REFUND', $amount, $date, $cashAccountId, 'REFUND', null, $refNumber, 'Refund deposit');

        if ($cashAccountId) {
            $journal = AccountingService::post($companyId, $date, [
                ['code' => AccountingService::map('CUSTOMER_DEPOSIT'), 'debit' => $amount, 'memo' => 'Refund deposit'],
                ['code' => self::cashCoa($cashAccountId), 'credit' => $amount, 'memo' => 'Refund deposit ' . $refNumber],
            ], 'CUSTOMER_DEPOSIT', $d->id, $refNumber, 'Refund deposit');
            $d->journal_entry_id = $journal->id;
            $d->save();
        }

        return $d;
    }

    public static function balance(int $customerId): float
    {
        $sums = CustomerDeposit::where('customer_id', $customerId)
            ->selectRaw("SUM(CASE WHEN movement_type IN ('DEPOSIT_IN') THEN amount ELSE 0 END) as inn")
            ->selectRaw("SUM(CASE WHEN movement_type IN ('DEPOSIT_USED','DEPOSIT_REFUND') THEN amount ELSE 0 END) as outt")
            ->first();
        return (float) ($sums->inn ?? 0) - (float) ($sums->outt ?? 0);
    }

    protected static function record(int $companyId, int $customerId, string $type, float $amount, $date, ?int $cashAccountId, ?string $refType, ?int $refId, ?string $refNumber, ?string $notes): CustomerDeposit
    {
        return CustomerDeposit::create([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'deposit_date' => $date,
            'movement_type' => $type,
            'amount' => $amount,
            'ref_type' => $refType,
            'ref_id' => $refId,
            'ref_number' => $refNumber,
            'cash_account_id' => $cashAccountId,
            'notes' => $notes,
            'created_by' => auth()->id() ?? 1,
        ]);
    }
}
