<?php

use App\Http\Controllers\ApprovalCenterController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CashAccountController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CsrController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDepositController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\MiningActivityController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\OperatorIncentiveController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentTermController;
use App\Http\Controllers\PayrollViewController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\PriceVarianceController;
use App\Http\Controllers\ProductionBatchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSecurityController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorBillController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WeighbridgeTicketController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEB ROUTES — MINING ERP
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/dashboard');

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global search
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Profile / security
    Route::get('/profile/security', [ProfileSecurityController::class, 'password'])->name('password.change');
    Route::put('/profile/password', [ProfileSecurityController::class, 'updatePassword'])->name('password.update');

    // Approval center
    Route::get('/approvals', [ApprovalCenterController::class, 'index'])->name('approval.index');
    Route::post('/approvals/{action}/act', [ApprovalCenterController::class, 'act'])->name('approval.act')->middleware('permission:approval.approve');

    // ===== ADMINISTRATION =====
    Route::resource('users', UserController::class)->middleware('permission:user.view');
    Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
    Route::get('users/{user}/login-history', [UserController::class, 'loginHistory'])->name('users.login-history');
    Route::post('users/{user}/logout-all', [UserController::class, 'logoutAll'])->name('users.logout-all');
    Route::post('users/{user}/roles', [UserController::class, 'assignRoles'])->name('users.assign-roles');

    Route::get('roles', [RoleController::class, 'index'])->name('role.index')->middleware('permission:role.view');
    Route::get('roles/{role}', [RoleController::class, 'show'])->name('role.show')->whereNumber('role');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('role.update-permissions')->middleware('permission:role.update');
    Route::resource('roles', RoleController::class)->except(['index', 'show'])->names(['create' => 'role.create', 'store' => 'role.store', 'edit' => 'role.edit', 'update' => 'role.update', 'destroy' => 'role.destroy'])->middleware('permission:role.create');

    Route::get('audit', [App\Http\Controllers\AuditController::class, 'index'])->name('audit.index')->middleware('permission:audit.view');

    Route::get('settings', [SettingController::class, 'index'])->name('setting.index')->middleware('permission:setting.view');
    Route::put('settings', [SettingController::class, 'update'])->name('setting.update')->middleware('permission:setting.update');

    // ===== ORGANIZATION =====
    Route::resource('companies', CompanyController::class)->middleware('permission:company.view');
    Route::resource('branches', App\Http\Controllers\BranchController::class)->middleware('permission:branch.view');
    Route::resource('sites', SiteController::class)->middleware('permission:site.view');
    Route::resource('divisions', DivisionController::class)->middleware('permission:division.view');
    Route::resource('departments', App\Http\Controllers\DepartmentController::class)->middleware('permission:division.view');
    Route::resource('cost-centers', CostCenterController::class)->middleware('permission:company.view');

    // ===== HR =====
    Route::resource('employees', EmployeeController::class)->middleware('permission:employee.view');
    Route::resource('attendances', AttendanceController::class)->middleware('permission:attendance.view');
    Route::post('attendances/import', [AttendanceController::class, 'import'])->name('attendances.import')->middleware('permission:attendance.create');
    Route::resource('leaves', LeaveController::class)->middleware('permission:leave.view');
    Route::resource('overtimes', OvertimeController::class)->middleware('permission:overtime.view');
    Route::post('leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve')->middleware('permission:leave.approve');
    Route::post('overtimes/{overtime}/approve', [OvertimeController::class, 'approve'])->name('overtimes.approve')->middleware('permission:overtime.approve');
    Route::resource('payroll-runs', PayrollViewController::class)->middleware('permission:payroll.view');
    Route::post('payroll-runs/{payroll_run}/calculate', [PayrollViewController::class, 'calculate'])->name('payroll.calculate')->middleware('permission:payroll.update');
    Route::post('payroll-runs/{payroll_run}/approve', [PayrollViewController::class, 'approve'])->name('payroll.approve')->middleware('permission:payroll.approve');
    Route::post('payroll-runs/{payroll_run}/post', [PayrollViewController::class, 'post'])->name('payroll.post')->middleware('permission:payroll.post');
    Route::post('payroll-runs/{payroll_run}/pay', [PayrollViewController::class, 'pay'])->name('payroll.pay')->middleware('permission:payroll.post');
    Route::get('payroll-runs/{payroll_run}/payslip/{detail}', [PayrollViewController::class, 'payslip'])->name('payroll.payslip');
    Route::resource('operator-incentives', OperatorIncentiveController::class)->middleware('permission:incentive.view');
    Route::post('operator-incentives/{operator_incentive}/approve', [OperatorIncentiveController::class, 'approve'])->name('operator-incentives.approve')->middleware('permission:incentive.approve');

    // ===== MINING =====
    Route::resource('mining-activities', MiningActivityController::class)->middleware('permission:mining.view');
    Route::post('mining-activities/{mining_activity}/submit', [MiningActivityController::class, 'submit'])->name('mining.submit')->middleware('permission:mining.update');
    Route::post('mining-activities/{mining_activity}/approve', [MiningActivityController::class, 'approve'])->name('mining.approve')->middleware('permission:mining.approve');
    Route::post('mining-activities/{mining_activity}/post', [MiningActivityController::class, 'post'])->name('mining.post')->middleware('permission:mining.post');

    // ===== WEIGHBRIDGE =====
    Route::resource('weighbridge-tickets', WeighbridgeTicketController::class)->middleware('permission:weighbridge.view');
    Route::post('weighbridge-tickets/first-weigh', [WeighbridgeTicketController::class, 'firstWeigh'])->name('weighbridge.first')->middleware('permission:weighbridge.create');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/second-weigh', [WeighbridgeTicketController::class, 'secondWeigh'])->name('weighbridge.second')->middleware('permission:weighbridge.create');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/override', [WeighbridgeTicketController::class, 'overrideWeight'])->name('weighbridge.override')->middleware('permission:weighbridge.update');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/post', [WeighbridgeTicketController::class, 'postTicket'])->name('weighbridge.post')->middleware('permission:weighbridge.post');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/void', [WeighbridgeTicketController::class, 'void'])->name('weighbridge.void')->middleware('permission:weighbridge.void');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/print', [WeighbridgeTicketController::class, 'printTicket'])->name('weighbridge.print')->middleware('permission:weighbridge.print');

    // ===== PRODUCTION =====
    Route::resource('production-batches', ProductionBatchController::class)->middleware('permission:production.view');
    Route::post('production-batches/{production_batch}/submit', [ProductionBatchController::class, 'submit'])->name('production.submit')->middleware('permission:production.update');
    Route::post('production-batches/{production_batch}/approve', [ProductionBatchController::class, 'approve'])->name('production.approve')->middleware('permission:production.approve');
    Route::post('production-batches/{production_batch}/post', [ProductionBatchController::class, 'post'])->name('production.post')->middleware('permission:production.post');

    // ===== INVENTORY =====
    Route::get('stock/balance', [StockController::class, 'balance'])->name('stock.balance')->middleware('permission:stock.view');
    Route::get('stock/card', [StockController::class, 'card'])->name('stock.card')->middleware('permission:stock.view');
    Route::resource('items', ItemController::class)->middleware('permission:inventory.view');
    Route::resource('warehouses', WarehouseController::class)->middleware('permission:inventory.view');
    Route::resource('stock-transfers', StockTransferController::class)->middleware('permission:stock.view');
    Route::post('stock-transfers/{stock_transfer}/post', [StockTransferController::class, 'post'])->name('stock-transfers.post')->middleware('permission:stock.post');
    Route::resource('stock-adjustments', StockAdjustmentController::class)->middleware('permission:stock.view');
    Route::post('stock-adjustments/{stock_adjustment}/post', [StockAdjustmentController::class, 'post'])->name('stock-adjustments.post')->middleware('permission:stock.post');

    // ===== PROCUREMENT =====
    Route::resource('purchase-requests', PurchaseRequestController::class)->middleware('permission:purchase_request.view');
    Route::post('purchase-requests/{purchase_request}/submit', [PurchaseRequestController::class, 'submit'])->name('purchase-requests.submit')->middleware('permission:purchase_request.update');
    Route::post('purchase-requests/{purchase_request}/approve', [PurchaseRequestController::class, 'approve'])->name('purchase-requests.approve')->middleware('permission:purchase_request.approve');
    Route::resource('purchase-orders', PurchaseOrderController::class)->middleware('permission:purchase_order.view');
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve')->middleware('permission:purchase_order.approve');
    Route::resource('goods-receipts', GoodsReceiptController::class)->middleware('permission:goods_receipt.view');
    Route::post('goods-receipts/{goods_receipt}/post', [GoodsReceiptController::class, 'post'])->name('goods-receipts.post')->middleware('permission:goods_receipt.post');
    Route::resource('vendor-bills', VendorBillController::class)->middleware('permission:vendor_bill.view');
    Route::post('vendor-bills/{vendor_bill}/post', [VendorBillController::class, 'post'])->name('vendor-bills.post')->middleware('permission:vendor_bill.post');
    Route::post('vendor-bills/{vendor_bill}/pay', [VendorBillController::class, 'pay'])->name('vendor-bills.pay')->middleware('permission:vendor_bill.post');

    // ===== SALES =====
    Route::resource('customers', CustomerController::class)->middleware('permission:sales.view');
    Route::resource('suppliers', SupplierController::class)->middleware('permission:procurement.view');
    Route::resource('payment-terms', PaymentTermController::class)->middleware('permission:finance.view');
    Route::resource('sales-orders', SalesOrderController::class)->middleware('permission:sales_order.view');
    Route::post('sales-orders/{sales_order}/approve', [SalesOrderController::class, 'approve'])->name('sales-orders.approve')->middleware('permission:sales_order.approve');
    Route::resource('delivery-orders', DeliveryOrderController::class)->middleware('permission:delivery_order.view');
    Route::post('delivery-orders/{delivery_order}/complete', [DeliveryOrderController::class, 'complete'])->name('delivery-orders.complete')->middleware('permission:delivery_order.update');
    Route::resource('invoices', InvoiceController::class)->middleware('permission:invoice.view');
    Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post')->middleware('permission:invoice.post');
    Route::resource('payments', PaymentController::class)->middleware('permission:payment.view');
    Route::get('deposits', [CustomerDepositController::class, 'index'])->name('deposit.index')->middleware('permission:deposit.view');
    Route::post('deposits/in', [CustomerDepositController::class, 'depositIn'])->name('deposit.in')->middleware('permission:deposit.create');
    Route::post('deposits/refund', [CustomerDepositController::class, 'refund'])->name('deposit.refund')->middleware('permission:deposit.create');
    Route::get('deposits/{customer}/statement', [CustomerDepositController::class, 'statement'])->name('deposit.statement');
    Route::resource('price-lists', PriceListController::class)->middleware('permission:price.view');
    Route::post('price-lists/{price_list}/approve', [PriceListController::class, 'approve'])->name('price-lists.approve')->middleware('permission:price.approve');
    Route::get('price-variances', [PriceVarianceController::class, 'index'])->name('price_variance.index')->middleware('permission:price_variance.view');
    Route::post('price-variances/{price_variance}/approve', [PriceVarianceController::class, 'approve'])->name('price-variances.approve')->middleware('permission:price_variance.approve');

    // ===== ASSET & MAINTENANCE =====
    Route::resource('assets', AssetController::class)->middleware('permission:asset.view');
    Route::resource('equipment', EquipmentController::class)->middleware('permission:asset.view');
    Route::resource('work-orders', WorkOrderController::class)->middleware('permission:work_order.view');
    Route::post('work-orders/{work_order}/approve', [WorkOrderController::class, 'approve'])->name('work-orders.approve')->middleware('permission:work_order.approve');
    Route::post('work-orders/{work_order}/start', [WorkOrderController::class, 'start'])->name('work-orders.start')->middleware('permission:work_order.update');
    Route::post('work-orders/{work_order}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete')->middleware('permission:work_order.update');
    Route::post('work-orders/{work_order}/parts/{part}/issue', [WorkOrderController::class, 'issuePart'])->name('work-orders.issue-part')->middleware('permission:work_order.update');
    Route::resource('maintenance-schedules', MaintenanceScheduleController::class)->middleware('permission:maintenance.view');

    // ===== FINANCE & ACCOUNTING =====
    Route::resource('coa', CoaController::class)->middleware('permission:finance.view');
    Route::resource('journals', JournalController::class)->middleware('permission:journal.view');
    Route::post('journals/{journal}/post', [JournalController::class, 'post'])->name('journals.post')->middleware('permission:journal.post');
    Route::post('journals/{journal}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse')->middleware('permission:journal.unpost');
    Route::resource('cash-accounts', CashAccountController::class)->middleware('permission:finance.view');
    Route::get('finance/trial-balance', [FinanceReportController::class, 'trialBalance'])->name('finance.trial_balance')->middleware('permission:ledger.view');
    Route::get('finance/ledger', [FinanceReportController::class, 'ledger'])->name('finance.ledger')->middleware('permission:ledger.view');
    Route::get('finance/pl', [FinanceReportController::class, 'profitLoss'])->name('finance.pl')->middleware('permission:ledger.view');
    Route::get('finance/balance-sheet', [FinanceReportController::class, 'balanceSheet'])->name('finance.balance_sheet')->middleware('permission:ledger.view');
    Route::get('finance/ar-aging', [FinanceReportController::class, 'arAging'])->name('finance.ar_aging')->middleware('permission:ledger.view');
    Route::get('finance/ap-aging', [FinanceReportController::class, 'apAging'])->name('finance.ap_aging')->middleware('permission:ledger.view');
    Route::get('finance/cash-flow', [ReportController::class, 'cashFlow'])->name('finance.cash_flow')->middleware('permission:ledger.view');
    Route::resource('tax', TaxController::class)->middleware('permission:tax.view');

    // ===== DOCUMENTS & CSR =====
    Route::resource('documents', DocumentController::class)->middleware('permission:document.view');
    Route::post('documents/{document}/approve', [DocumentController::class, 'approve'])->name('documents.approve')->middleware('permission:document.approve');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::resource('csr', CsrController::class)->middleware('permission:csr.view');
    Route::post('csr/{csr}/approve', [CsrController::class, 'approve'])->name('csr.approve')->middleware('permission:csr.approve');
    Route::post('csr/{csr}/activities', [CsrController::class, 'addActivity'])->name('csr.activities.add')->middleware('permission:csr.update');
    Route::post('csr/{csr}/expenses', [CsrController::class, 'addExpense'])->name('csr.expenses.add')->middleware('permission:csr.update');

    // ===== REPORTS =====
    Route::get('reports/mining', [ReportController::class, 'mining'])->name('report.mining')->middleware('permission:report.view');
    Route::get('reports/production', [ReportController::class, 'production'])->name('report.production')->middleware('permission:report.view');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('report.inventory')->middleware('permission:report.view');
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('report.sales')->middleware('permission:report.view');
    Route::get('reports/hr', [ReportController::class, 'hr'])->name('report.hr')->middleware('permission:report.view');
    Route::get('reports/maintenance', [ReportController::class, 'maintenance'])->name('report.maintenance')->middleware('permission:report.view');

    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notification.index');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'readAll'])->name('notification.read-all');
});
