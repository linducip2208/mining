<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\CustomerDeposit;
use Illuminate\Support\Facades\DB;

/**
 * Customer Deposit ledger. Balance = SUM of movements; never a single editable number.
 * Kinds: MONEY (uang) dan MATERIAL_CREDIT (hak volume material) — tidak boleh dicampur.
 */
class DepositService
{
    public const KIND_MONEY = 'MONEY';

    public const KIND_MATERIAL_CREDIT = 'MATERIAL_CREDIT';

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

    public static function depositIn(int $companyId, int $customerId, float $amount, $date, ?int $cashAccountId, ?string $refNumber = null, ?string $notes = null, string $kind = self::KIND_MONEY): CustomerDeposit
    {
        return DB::transaction(function () use ($companyId, $customerId, $amount, $date, $cashAccountId, $refNumber, $notes, $kind) {
            $d = self::record($companyId, $customerId, 'DEPOSIT_IN', $amount, $date, $cashAccountId, 'DEPOSIT', null, $refNumber, $notes, $kind);

            if ($cashAccountId) {
                $journal = AccountingService::post($companyId, $date, [
                    ['code' => self::cashCoa($cashAccountId), 'debit' => $amount, 'memo' => 'Deposit customer '.$refNumber],
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
        return DB::transaction(function () use ($companyId, $customerId, $amount, $date, $invoiceId, $invoiceNumber) {
            // serialisasi alokasi concurrent per customer — saldo dihitung ulang di dalam lock
            self::lockCustomer($customerId);
            $balance = self::balance($customerId);
            if ($amount > $balance + 0.001) {
                throw new \DomainException('Saldo deposit tidak cukup. Saldo: Rp '.number_format($balance, 2));
            }

            return self::record($companyId, $customerId, 'DEPOSIT_USED', $amount, $date, null, 'INVOICE', $invoiceId, $invoiceNumber, 'Alokasi deposit ke invoice');
        });
    }

    public static function refund(int $companyId, int $customerId, float $amount, $date, ?int $cashAccountId, ?string $refNumber = null): CustomerDeposit
    {
        return DB::transaction(function () use ($companyId, $customerId, $amount, $date, $cashAccountId, $refNumber) {
            self::lockCustomer($customerId);
            $balance = self::balance($customerId);
            if ($amount > $balance + 0.001) {
                throw new \DomainException('Saldo deposit tidak cukup untuk refund.');
            }
            $d = self::record($companyId, $customerId, 'DEPOSIT_REFUND', $amount, $date, $cashAccountId, 'REFUND', null, $refNumber, 'Refund deposit');

            if ($cashAccountId) {
                $journal = AccountingService::post($companyId, $date, [
                    ['code' => AccountingService::map('CUSTOMER_DEPOSIT'), 'debit' => $amount, 'memo' => 'Refund deposit'],
                    ['code' => self::cashCoa($cashAccountId), 'credit' => $amount, 'memo' => 'Refund deposit '.$refNumber],
                ], 'CUSTOMER_DEPOSIT', $d->id, $refNumber, 'Refund deposit');
                $d->journal_entry_id = $journal->id;
                $d->save();
            }

            return $d;
        });
    }

    /**
     * Koreksi saldo (tinjauan, pembulatan, kesalahan input historis).
     * Amount > 0 menambah, amount < 0 mengurangi. Wajib disertai alasan; ter-audit.
     */
    public static function adjust(int $companyId, int $customerId, float $amount, $date, ?string $refNumber, string $reason, string $kind = self::KIND_MONEY): CustomerDeposit
    {
        return DB::transaction(function () use ($companyId, $customerId, $amount, $date, $refNumber, $reason, $kind) {
            if ($amount === 0.0) {
                throw new \InvalidArgumentException('Penyesuaian deposit tidak boleh nol.');
            }
            self::lockCustomer($customerId);
            if ($amount < 0 && abs($amount) > self::balance($customerId, $kind) + 0.001) {
                throw new \DomainException('Penyesuaian mengurangi melebihi saldo deposit.');
            }
            $d = self::record($companyId, $customerId, 'DEPOSIT_ADJUSTMENT', $amount, $date, null, 'ADJUSTMENT', null, $refNumber, $reason, $kind);
            AuditService::log('ADJUST', 'DEPOSIT', $d->id, CustomerDeposit::class, null, ['amount' => $amount, 'customer_id' => $customerId, 'kind' => $kind], $reason);

            return $d;
        });
    }

    public static function balance(int $customerId, string $kind = self::KIND_MONEY): float
    {
        $sums = CustomerDeposit::where('customer_id', $customerId)
            ->where(function ($q) use ($kind) {
                $q->where('kind', $kind)->orWhereNull('kind');
            })
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'PLUS' THEN amount WHEN direction = 'MINUS' THEN -amount ELSE 0 END),0) as bal")
            ->value('bal');

        return (float) $sums;
    }

    protected static function record(int $companyId, int $customerId, string $type, float $amount, $date, ?int $cashAccountId, ?string $refType, ?int $refId, ?string $refNumber, ?string $notes, string $kind = self::KIND_MONEY): CustomerDeposit
    {
        $signed = $type === 'DEPOSIT_ADJUSTMENT' ? $amount : abs($amount);
        $direction = $type === 'DEPOSIT_ADJUSTMENT' ? ($amount >= 0 ? 'PLUS' : 'MINUS') : ($type === 'DEPOSIT_IN' ? 'PLUS' : 'MINUS');

        return CustomerDeposit::create([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'deposit_date' => $date,
            'movement_type' => $type,
            'direction' => $direction,
            'amount' => abs($signed),
            'kind' => $kind,
            'ref_type' => $refType,
            'ref_id' => $refId,
            'ref_number' => $refNumber,
            'cash_account_id' => $cashAccountId,
            'notes' => $notes,
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    private static function lockCustomer(int $customerId): void
    {
        CustomerDeposit::where('customer_id', $customerId)->lockForUpdate()->get();
    }
}
