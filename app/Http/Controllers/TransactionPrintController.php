<?php

namespace App\Http\Controllers;

use App\Models\CustomerDeposit;
use App\Models\DeliveryOrder;
use App\Models\FuelIssue;
use App\Models\FuelReceipt;
use App\Models\GoodsReceipt;
use App\Models\HseReport;
use App\Models\JournalEntry;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesOrder;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\PrintDocumentService;
use App\Support\DateFormatter;
use App\Support\HumanLabel;
use App\Support\NumberFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class TransactionPrintController extends Controller
{
    private const DOCUMENTS = [
        'purchase-request' => ['model' => PurchaseRequest::class, 'title' => 'PURCHASE REQUEST', 'permission' => 'purchase_request.print'],
        'purchase-order' => ['model' => PurchaseOrder::class, 'title' => 'PURCHASE ORDER', 'permission' => 'purchase_order.print'],
        'goods-receipt' => ['model' => GoodsReceipt::class, 'title' => 'GOODS RECEIPT', 'permission' => 'goods_receipt.print'],
        'sales-order' => ['model' => SalesOrder::class, 'title' => 'SALES ORDER', 'permission' => 'sales_order.print'],
        'delivery-order' => ['model' => DeliveryOrder::class, 'title' => 'DELIVERY ORDER', 'permission' => 'delivery_order.print'],
        'fuel-issue' => ['model' => FuelIssue::class, 'title' => 'FUEL ISSUE', 'permission' => 'fuel.print'],
        'fuel-receipt' => ['model' => FuelReceipt::class, 'title' => 'FUEL RECEIPT', 'permission' => 'fuel.print'],
        'stock-transfer' => ['model' => StockTransfer::class, 'title' => 'STOCK TRANSFER', 'permission' => 'stock.print'],
        'stock-adjustment' => ['model' => StockAdjustment::class, 'title' => 'STOCK ADJUSTMENT', 'permission' => 'stock.print'],
        'work-order' => ['model' => WorkOrder::class, 'title' => 'WORK ORDER', 'permission' => 'work_order.print'],
        'journal' => ['model' => JournalEntry::class, 'title' => 'JOURNAL VOUCHER', 'permission' => 'journal.print'],
        'customer-deposit' => ['model' => CustomerDeposit::class, 'title' => 'CUSTOMER DEPOSIT', 'permission' => 'deposit.print'],
        'hse-report' => ['model' => HseReport::class, 'title' => 'HSE INCIDENT REPORT', 'permission' => 'hse.print'],
    ];

    public function print(Request $request, string $documentType, int $document)
    {
        return $this->response($request, $documentType, $document, false);
    }

    public function pdf(Request $request, string $documentType, int $document)
    {
        return $this->response($request, $documentType, $document, true);
    }

    private function response(Request $request, string $type, int $id, bool $pdf)
    {
        abort_unless(isset(self::DOCUMENTS[$type]), 404);
        $config = self::DOCUMENTS[$type];
        abort_unless(auth()->user()?->hasPermission($config['permission']) || auth()->user()?->isSuperAdmin(), 403);
        $model = $config['model']::query()->findOrFail($id);
        $this->ensureScope($model);
        $data = ['documentTitle' => $config['title'], 'documentNumber' => $this->number($model), 'record' => $model, 'fields' => $this->fields($model)];
        AuditService::log($pdf ? 'PDF_DOWNLOAD' : 'PRINT', strtoupper($type), $model->getKey(), $config['model'], null, ['number' => $data['documentNumber']]);
        if ($pdf) {
            return PrintDocumentService::pdf('print.transaction', $data, $config['title'].'-'.$data['documentNumber']);
        }

        return view('print.transaction', PrintDocumentService::context($data));
    }

    private function number(Model $model): string
    {
        foreach (['number', 'code', 'ticket_no', 'reference_no', 'document_no'] as $field) {
            if (filled($model->{$field} ?? null)) {
                return (string) $model->{$field};
            }
        }

        return 'Nomor belum tersedia';
    }

    private function fields(Model $model): array
    {
        $fields = [];
        foreach ($model->getAttributes() as $key => $value) {
            if (in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by']) || str_ends_with($key, '_id') || is_null($value) || $value === '') {
                continue;
            }
            $fields[HumanLabel::label($key)] = $this->format($key, $value);
        }

        return array_slice($fields, 0, 12, true);
    }

    private function format(string $key, mixed $value): string
    {
        if (str_contains($key, 'date') || str_ends_with($key, '_at')) {
            return DateFormatter::short($value);
        }
        if (str_contains($key, 'amount') || str_contains($key, 'price') || in_array($key, ['total', 'subtotal', 'tax_amount', 'actual_cost'])) {
            return NumberFormatter::money($value);
        }
        if (in_array(strtolower((string) $value), ['true', 'false', '0', '1'])) {
            return filter_var($value, FILTER_VALIDATE_BOOL) ? 'Ya' : 'Tidak';
        }
        if (in_array($key, ['status', 'type', 'category', 'document_type', 'movement_type'])) {
            return HumanLabel::label((string) $value);
        }

        return (string) $value;
    }

    private function ensureScope(Model $model): void
    {
        $user = auth()->user();
        if (! $user || $user->isSuperAdmin()) {
            return;
        }
        if (isset($model->company_id) && $user->accessibleCompanyIds() !== null && ! in_array($model->company_id, $user->accessibleCompanyIds())) {
            abort(403);
        }
        if (isset($model->site_id) && $user->accessibleSiteIds() !== null && ! in_array($model->site_id, $user->accessibleSiteIds())) {
            abort(403);
        }
    }
}
