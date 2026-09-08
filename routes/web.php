<?php

use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\ApprovalCenterController;
use App\Http\Controllers\ApprovalWorkflowController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BfjImportController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CashAccountController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CostController;
use App\Http\Controllers\CsrController;
use App\Http\Controllers\CustomerContractController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDepositController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\DumpingPointController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\FuelController;
use App\Http\Controllers\FuelDipController;
use App\Http\Controllers\FuelIssueController;
use App\Http\Controllers\FuelReceiptController;
use App\Http\Controllers\FuelTankController;
use App\Http\Controllers\FuelTransferController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\HaulingContractController;
use App\Http\Controllers\HaulingRouteController;
use App\Http\Controllers\HseController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceRegisterController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LetterRegisterController;
use App\Http\Controllers\LetterTypeController;
use App\Http\Controllers\LoadingPointController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\MarketingSeoController;
use App\Http\Controllers\MiningActivityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperatorIncentiveController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentTermController;
use App\Http\Controllers\PayrollViewController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\PriceVarianceController;
use App\Http\Controllers\PrinterDeviceController;
use App\Http\Controllers\PrintJobController;
use App\Http\Controllers\ProductionBatchController;
use App\Http\Controllers\ProductSpecificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSecurityController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\PwaManifestController;
use App\Http\Controllers\QcSampleController;
use App\Http\Controllers\QualityHoldController;
use App\Http\Controllers\QualityParameterController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportPrintController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockpileController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierContractController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TelematicsController;
use App\Http\Controllers\TireController;
use App\Http\Controllers\TransactionPrintController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VendorBillController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WeighbridgeDeviceController;
use App\Http\Controllers\WeighbridgeTicketController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEB ROUTES — MINING ERP
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/dashboard');
Route::get('/manifest.webmanifest', PwaManifestController::class)->name('pwa.manifest');
Route::view('/offline', 'offline')->name('pwa.offline');
Route::get('/verify/{token}', DocumentVerificationController::class)->name('documents.verify');

require __DIR__.'/auth.php';

// ===== DOCUMENTATION PORTAL (public/login per setting docs.public) =====
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/search', [DocsController::class, 'search'])->name('docs.search');
Route::get('/docs/suggest', [DocsController::class, 'suggest'])->name('docs.suggest');
Route::get('/docs/health', [DocsController::class, 'health'])->name('docs.health');
Route::get('/docs/sitemap.xml', [DocsController::class, 'sitemap'])->name('docs.sitemap');
Route::get('/docs/{section}', [DocsController::class, 'section'])->name('docs.section');
Route::get('/docs/{section}/{page}', [DocsController::class, 'page'])->name('docs.page');

Route::middleware(['auth', 'feature.flags'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global search
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Profile / security
    Route::get('/profile/security', [ProfileSecurityController::class, 'password'])->name('password.change');
    Route::put('/profile/password', [ProfileSecurityController::class, 'updatePassword'])->name('password.profile-update');

    // Approval center
    Route::get('/approvals', [ApprovalCenterController::class, 'index'])->name('approval.index');
    Route::post('/approvals/{action}/act', [ApprovalCenterController::class, 'act'])->name('approval.act')->middleware('permission:approval.approve');

    // ===== ADMINISTRATION =====
    Route::resource('users', UserController::class)->except(['show'])->middleware('permission:user.view');
    Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle')->middleware('permission:user.update');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password')->middleware('permission:user.update');
    Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock')->middleware('permission:user.update');
    Route::get('users/{user}/login-history', [UserController::class, 'loginHistory'])->name('users.login-history')->middleware('permission:user.view_login_history');
    Route::post('users/{user}/logout-all', [UserController::class, 'logoutAll'])->name('users.logout-all')->middleware('permission:user.update');
    Route::post('users/{user}/roles', [UserController::class, 'assignRoles'])->name('users.assign-roles')->middleware('permission:user.assign_role');

    Route::get('roles', [RoleController::class, 'index'])->name('role.index')->middleware('permission:role.view');
    Route::get('roles/{role}', [RoleController::class, 'show'])->name('role.show')->whereNumber('role');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('role.update-permissions')->middleware('permission:role.update');
    Route::resource('roles', RoleController::class)->except(['index', 'show'])->names(['create' => 'role.create', 'store' => 'role.store', 'edit' => 'role.edit', 'update' => 'role.update', 'destroy' => 'role.destroy'])->middleware('permission:role.create');

    Route::get('audit', [AuditController::class, 'index'])->name('audit.index')->middleware('permission:audit.view');

    Route::get('settings', [SettingController::class, 'index'])->name('setting.index')->middleware('permission:setting.view');
    Route::put('settings', [SettingController::class, 'update'])->name('setting.update')->middleware('permission:setting.view');
    Route::post('settings/reset', [SettingController::class, 'resetGroup'])->name('setting.reset-group')->middleware('permission:setting.view');
    Route::post('settings/reset-all', [SettingController::class, 'resetAll'])->name('setting.reset-all')->middleware('permission:setting.view');
    Route::post('settings/reset/{key}', [SettingController::class, 'reset'])->name('setting.reset')->middleware('permission:setting.view');
    Route::get('settings/export', [SettingController::class, 'export'])->name('setting.export')->middleware('permission:advanced.setting.view');
    Route::post('settings/import', [SettingController::class, 'import'])->name('setting.import')->middleware('permission:advanced.setting.update');
    Route::get('settings/health', [SystemHealthController::class, 'index'])->name('setting.health')->middleware('permission:setting.view');
    Route::get('settings/approval-workflow', [ApprovalWorkflowController::class, 'index'])->name('setting.approval-workflow')->middleware('permission:approval.workflow.view');
    Route::post('settings/approval-workflow', [ApprovalWorkflowController::class, 'store'])->name('setting.approval-workflow.store')->middleware('permission:approval.workflow.update');
    Route::patch('settings/approval-workflow/{workflow}/toggle', [ApprovalWorkflowController::class, 'toggle'])->name('setting.approval-workflow.toggle')->middleware('permission:approval.workflow.update');
    Route::get('settings/printers', [PrinterDeviceController::class, 'index'])->name('printer.index')->middleware('permission:printer.view');
    Route::post('settings/printers', [PrinterDeviceController::class, 'store'])->name('printer.store')->middleware('permission:printer.manage');
    Route::put('settings/printers/{printer}', [PrinterDeviceController::class, 'update'])->name('printer.update')->middleware('permission:printer.manage');
    Route::delete('settings/printers/{printer}', [PrinterDeviceController::class, 'destroy'])->name('printer.destroy')->middleware('permission:printer.manage');
    Route::post('settings/printers/{printer}/default', [PrinterDeviceController::class, 'makeDefault'])->name('printer.default')->middleware('permission:printer.manage');
    Route::get('settings/printers/agent-config', [PrinterDeviceController::class, 'agentConfig'])->name('printer.agent-config')->middleware('permission:printer.view');
    Route::post('settings/printers/{printer}/test', [PrinterDeviceController::class, 'test'])->name('printer.test')->middleware('permission:printer.test');
    Route::post('print-jobs/{print_job}/retry', [PrintJobController::class, 'retry'])->name('print-jobs.retry')->middleware('permission:printer.manage');
    Route::get('print-jobs/{print_job:uuid}/package', [PrintJobController::class, 'package'])->name('print-jobs.package');
    Route::post('print-jobs/{print_job:uuid}/status', [PrintJobController::class, 'clientStatus'])->name('print-jobs.status');

    // ===== ORGANIZATION =====
    Route::resource('companies', CompanyController::class)->middleware('permission:company.view');
    Route::resource('branches', BranchController::class)->middleware('permission:branch.view');
    Route::resource('sites', SiteController::class)->middleware('permission:site.view');
    Route::resource('divisions', DivisionController::class)->middleware('permission:division.view');
    Route::resource('departments', DepartmentController::class)->middleware('permission:division.view');
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
    Route::get('payroll-runs/{payroll_run}/payslip/{detail}', [PayrollViewController::class, 'payslip'])->name('payroll.payslip')->middleware('permission:payroll.print');
    Route::get('payroll-runs/{payroll_run}/payslip/{detail}/pdf', [PayrollViewController::class, 'payslipPdf'])->name('payroll.payslip.pdf')->middleware('permission:payroll.pdf');
    Route::resource('operator-incentives', OperatorIncentiveController::class)->middleware('permission:incentive.view');
    Route::post('operator-incentives/{operator_incentive}/approve', [OperatorIncentiveController::class, 'approve'])->name('operator-incentives.approve')->middleware('permission:incentive.approve');

    // ===== MINING =====
    Route::get('mining-dashboard', [MiningActivityController::class, 'dashboard'])->name('mining.dashboard')->middleware('permission:mining.view');
    Route::resource('mining-activities', MiningActivityController::class)->middleware('permission:mining.view');
    Route::post('mining-activities/{mining_activity}/submit', [MiningActivityController::class, 'submit'])->name('mining.submit')->middleware('permission:mining.update');
    Route::post('mining-activities/{mining_activity}/approve', [MiningActivityController::class, 'approve'])->name('mining.approve')->middleware('permission:mining.approve');
    Route::post('mining-activities/{mining_activity}/post', [MiningActivityController::class, 'post'])->name('mining.post')->middleware('permission:mining.post');

    // ===== WEIGHBRIDGE =====
    Route::resource('weighbridge-tickets', WeighbridgeTicketController::class)->only(['index', 'create', 'show', 'destroy'])->middleware('permission:weighbridge.view');
    Route::post('weighbridge-tickets/first-weigh', [WeighbridgeTicketController::class, 'firstWeigh'])->name('weighbridge.first')->middleware('permission:weighbridge.create');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/second-weigh', [WeighbridgeTicketController::class, 'secondWeigh'])->name('weighbridge.second')->middleware('permission:weighbridge.create');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/override', [WeighbridgeTicketController::class, 'overrideWeight'])->name('weighbridge.override')->middleware('permission:weighbridge.update');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/post', [WeighbridgeTicketController::class, 'postTicket'])->name('weighbridge.post')->middleware('permission:weighbridge.post');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/void', [WeighbridgeTicketController::class, 'void'])->name('weighbridge.void')->middleware('permission:weighbridge.void');
    Route::match(['GET', 'POST'], 'weighbridge-tickets/{weighbridge_ticket}/print', [WeighbridgeTicketController::class, 'printTicket'])->name('weighbridge.print')->middleware('permission:weighbridge.print');
    Route::post('weighbridge-tickets/{weighbridge_ticket}/reprint', [WeighbridgeTicketController::class, 'reprint'])->name('weighbridge.reprint')->middleware('permission:weighbridge.reprint');
    Route::get('weighbridge-tickets/{weighbridge_ticket}/pdf', [WeighbridgeTicketController::class, 'pdfTicket'])->name('weighbridge.pdf')->middleware('permission:weighbridge.print');

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
    Route::resource('stock-transfers', StockTransferController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:stock.view');
    Route::post('stock-transfers/{stock_transfer}/post', [StockTransferController::class, 'post'])->name('stock-transfers.post')->middleware('permission:stock.post');
    Route::resource('stock-adjustments', StockAdjustmentController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:stock.view');
    Route::post('stock-adjustments/{stock_adjustment}/post', [StockAdjustmentController::class, 'post'])->name('stock-adjustments.post')->middleware('permission:stock.post');

    // ===== PROCUREMENT =====
    Route::resource('purchase-requests', PurchaseRequestController::class)->middleware('permission:purchase_request.view');
    Route::post('purchase-requests/{purchase_request}/cancel', [PurchaseRequestController::class, 'cancel'])->name('purchase-requests.cancel')->middleware('permission:purchase_request.cancel');
    Route::post('purchase-requests/{purchase_request}/submit', [PurchaseRequestController::class, 'submit'])->name('purchase-requests.submit')->middleware('permission:purchase_request.update');
    Route::post('purchase-requests/{purchase_request}/approve', [PurchaseRequestController::class, 'approve'])->name('purchase-requests.approve')->middleware('permission:purchase_request.approve');
    Route::resource('purchase-orders', PurchaseOrderController::class)->middleware('permission:purchase_order.view');
    Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel')->middleware('permission:purchase_order.cancel');
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve')->middleware('permission:purchase_order.approve');
    Route::resource('goods-receipts', GoodsReceiptController::class)->middleware('permission:goods_receipt.view');
    Route::post('goods-receipts/{goods_receipt}/post', [GoodsReceiptController::class, 'post'])->name('goods-receipts.post')->middleware('permission:goods_receipt.post');
    Route::resource('vendor-bills', VendorBillController::class)->middleware('permission:vendor_bill.view');
    Route::post('vendor-bills/{vendor_bill}/post', [VendorBillController::class, 'post'])->name('vendor-bills.post')->middleware('permission:vendor_bill.post');
    Route::post('vendor-bills/{vendor_bill}/pay', [VendorBillController::class, 'pay'])->name('vendor-bills.pay')->middleware('permission:vendor_bill.post');
    Route::post('vendor-bills/{vendor_bill}/void', [VendorBillController::class, 'void'])->name('vendor-bills.void')->middleware('permission:vendor_bill.void');

    // ===== SALES =====
    Route::resource('customers', CustomerController::class)->middleware('permission:sales.view');
    Route::resource('suppliers', SupplierController::class)->middleware('permission:procurement.view');
    Route::resource('payment-terms', PaymentTermController::class)->middleware('permission:finance.view');
    Route::resource('sales-orders', SalesOrderController::class)->middleware('permission:sales_order.view');
    Route::post('sales-orders/{sales_order}/approve', [SalesOrderController::class, 'approve'])->name('sales-orders.approve')->middleware('permission:sales_order.approve');
    Route::post('sales-orders/{sales_order}/submit', [SalesOrderController::class, 'submit'])->name('sales-orders.submit')->middleware('permission:sales_order.create');
    Route::post('sales-orders/{sales_order}/reserve', [SalesOrderController::class, 'reserve'])->name('sales-orders.reserve')->middleware('permission:sales_order.approve');
    Route::resource('delivery-orders', DeliveryOrderController::class)->middleware('permission:delivery_order.view');
    Route::post('delivery-orders/{delivery_order}/complete', [DeliveryOrderController::class, 'complete'])->name('delivery-orders.complete')->middleware('permission:delivery_order.update');
    Route::resource('invoices', InvoiceController::class)->middleware('permission:invoice.view');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print')->middleware('permission:invoice.print');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf')->middleware('permission:invoice.pdf');
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
    Route::resource('maintenance-schedules', MaintenanceScheduleController::class)->only(['index', 'create', 'store'])->middleware('permission:maintenance.view');
    Route::post('maintenance-schedules/generate', [MaintenanceScheduleController::class, 'generate'])->name('maintenance-schedules.generate')->middleware('permission:work_order.create');

    // ===== FINANCE & ACCOUNTING =====
    Route::resource('coa', CoaController::class)->middleware('permission:finance.view');
    Route::resource('journals', JournalController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:journal.view');
    Route::post('journals/{journal}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse')->middleware('permission:journal.unpost');
    Route::resource('cash-accounts', CashAccountController::class)->middleware('permission:finance.view');
    Route::get('finance/trial-balance', [FinanceReportController::class, 'trialBalance'])->name('finance.trial_balance')->middleware('permission:ledger.view');
    Route::get('finance/ledger', [FinanceReportController::class, 'ledger'])->name('finance.ledger')->middleware('permission:ledger.view');
    Route::get('finance/pl', [FinanceReportController::class, 'profitLoss'])->name('finance.pl')->middleware('permission:ledger.view');
    Route::get('finance/balance-sheet', [FinanceReportController::class, 'balanceSheet'])->name('finance.balance_sheet')->middleware('permission:ledger.view');
    Route::get('finance/ar-aging', [FinanceReportController::class, 'arAging'])->name('finance.ar_aging')->middleware('permission:ledger.view');
    Route::get('finance/ap-aging', [FinanceReportController::class, 'apAging'])->name('finance.ap_aging')->middleware('permission:ledger.view');
    Route::get('finance/cash-flow', [ReportController::class, 'cashFlow'])->name('finance.cash_flow')->middleware('permission:ledger.view');
    Route::get('finance/trial-balance/print', [ReportPrintController::class, 'print'])->defaults('report', 'finance-trial-balance')->name('finance.trial_balance.print')->middleware('permission:report.print');
    Route::get('finance/trial-balance/pdf', [ReportPrintController::class, 'pdf'])->defaults('report', 'finance-trial-balance')->name('finance.trial_balance.pdf')->middleware('permission:report.pdf');
    Route::get('finance/pl/print', [ReportPrintController::class, 'print'])->defaults('report', 'finance-pl')->name('finance.pl.print')->middleware('permission:report.print');
    Route::get('finance/pl/pdf', [ReportPrintController::class, 'pdf'])->defaults('report', 'finance-pl')->name('finance.pl.pdf')->middleware('permission:report.pdf');
    Route::get('finance/balance-sheet/print', [ReportPrintController::class, 'print'])->defaults('report', 'finance-balance-sheet')->name('finance.balance_sheet.print')->middleware('permission:report.print');
    Route::get('finance/balance-sheet/pdf', [ReportPrintController::class, 'pdf'])->defaults('report', 'finance-balance-sheet')->name('finance.balance_sheet.pdf')->middleware('permission:report.pdf');
    Route::get('finance/cash-flow/print', [ReportPrintController::class, 'print'])->defaults('report', 'finance-cash-flow')->name('finance.cash_flow.print')->middleware('permission:report.print');
    Route::get('finance/cash-flow/pdf', [ReportPrintController::class, 'pdf'])->defaults('report', 'finance-cash-flow')->name('finance.cash_flow.pdf')->middleware('permission:report.pdf');
    Route::resource('tax', TaxController::class)->middleware('permission:tax.view');

    // ===== DOCUMENTS & CSR =====
    Route::resource('documents', DocumentController::class)->middleware('permission:document.view');
    Route::get('transactions/{documentType}/{document}/print', [TransactionPrintController::class, 'print'])->name('transactions.print')->middleware('permission:document.print');
    Route::get('transactions/{documentType}/{document}/pdf', [TransactionPrintController::class, 'pdf'])->name('transactions.pdf')->middleware('permission:document.pdf');
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
    Route::get('reports/fleet', [ReportController::class, 'fleet'])->name('report.fleet')->middleware('permission:report.view');
    Route::get('reports/fuel', [ReportController::class, 'fuel'])->name('report.fuel')->middleware('permission:report.view');
    Route::get('reports/tire', [ReportController::class, 'tire'])->name('report.tire')->middleware('permission:report.view');
    Route::get('reports/dispatch', [ReportController::class, 'dispatch'])->name('report.dispatch')->middleware('permission:report.view');
    Route::get('reports/stockpile', [ReportController::class, 'stockpile'])->name('report.stockpile')->middleware('permission:report.view');
    Route::get('reports/quality', [ReportController::class, 'quality'])->name('report.quality')->middleware('permission:report.view');
    Route::get('reports/contract', [ReportController::class, 'contract'])->name('report.contract')->middleware('permission:report.view');
    Route::get('reports/budget', [ReportController::class, 'budget'])->name('report.budget')->middleware('permission:report.view');
    Route::get('reports/{report}/print', [ReportPrintController::class, 'print'])->name('report.print')->middleware('permission:report.print');
    Route::get('reports/{report}/pdf', [ReportPrintController::class, 'pdf'])->name('report.pdf')->middleware('permission:report.pdf');

    // ===== FLEET =====
    Route::get('fleet', [FleetController::class, 'dashboard'])->name('fleet.dashboard')->middleware('permission:fleet.view');
    Route::get('fleet/availability', [FleetController::class, 'availability'])->name('fleet.availability')->middleware('permission:fleet.view');
    Route::get('fleet/utilization', [FleetController::class, 'utilization'])->name('fleet.utilization')->middleware('permission:fleet.view');
    Route::get('fleet/downtime', [FleetController::class, 'downtime'])->name('fleet.downtime')->middleware('permission:fleet.view');
    Route::get('fleet/cost', [FleetController::class, 'cost'])->name('fleet.cost')->middleware('permission:fleet.view');
    Route::get('fleet/meters', [FleetController::class, 'meters'])->name('fleet.meters')->middleware('permission:fleet.view');
    Route::post('fleet/meters', [FleetController::class, 'storeMeter'])->name('fleet.meters.store')->middleware('permission:fleet.create');
    Route::get('fleet/inspections', [FleetController::class, 'inspections'])->name('fleet.inspections')->middleware('permission:fleet.view');
    Route::post('fleet/inspections', [FleetController::class, 'storeInspection'])->name('fleet.inspections.store')->middleware('permission:fleet.create');
    Route::get('fleet/assignments', [FleetController::class, 'assignments'])->name('fleet.assignments')->middleware('permission:fleet.view');
    Route::post('fleet/assignments', [FleetController::class, 'storeAssignment'])->name('fleet.assignments.store')->middleware('permission:fleet.create');
    Route::resource('equipment-categories', EquipmentCategoryController::class)->middleware('permission:fleet.view');
    Route::resource('vehicles', VehicleController::class)->middleware('permission:fleet.view');

    // ===== FUEL =====
    Route::get('fuel', [FuelController::class, 'dashboard'])->name('fuel.dashboard')->middleware('permission:fuel.view');
    Route::get('fuel/stock', [FuelController::class, 'stock'])->name('fuel.stock')->middleware('permission:fuel.view');
    Route::get('fuel/consumption', [FuelController::class, 'consumption'])->name('fuel.consumption')->middleware('permission:fuel.view');
    Route::get('fuel/variance', [FuelController::class, 'variance'])->name('fuel.variance')->middleware('permission:fuel.view');
    Route::resource('fuel-tanks', FuelTankController::class)->middleware('permission:fuel.view');
    Route::resource('fuel-issues', FuelIssueController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:fuel.view');
    Route::post('fuel-issues/{fuel_issue}/approve', [FuelIssueController::class, 'approve'])->name('fuel-issues.approve')->middleware('permission:fuel.create');
    Route::post('fuel-issues/{fuel_issue}/post', [FuelIssueController::class, 'post'])->name('fuel-issues.post')->middleware('permission:fuel.post');
    Route::resource('fuel-receipts', FuelReceiptController::class)->only(['index', 'create', 'store'])->middleware('permission:fuel.view');
    Route::post('fuel-receipts/{fuel_receipt}/approve', [FuelReceiptController::class, 'approve'])->name('fuel-receipts.approve')->middleware('permission:fuel.create');
    Route::post('fuel-receipts/{fuel_receipt}/post', [FuelReceiptController::class, 'post'])->name('fuel-receipts.post')->middleware('permission:fuel.post');
    Route::resource('fuel-transfers', FuelTransferController::class)->only(['index', 'create', 'store'])->middleware('permission:fuel.view');
    Route::post('fuel-transfers/{fuel_transfer}/post', [FuelTransferController::class, 'post'])->name('fuel-transfers.post')->middleware('permission:fuel.post');
    Route::get('fuel-dips', [FuelDipController::class, 'index'])->name('fuel-dips.index')->middleware('permission:fuel.view');
    Route::post('fuel-dips', [FuelDipController::class, 'store'])->name('fuel-dips.store')->middleware('permission:fuel.create');
    Route::post('fuel-dips/{dip}/approve', [FuelDipController::class, 'approve'])->name('fuel-dips.approve')->middleware('permission:fuel.approve');

    // ===== TIRE =====
    Route::resource('tires', TireController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:tire.view');
    Route::post('tires/{tire}/install', [TireController::class, 'install'])->name('tires.install')->middleware('permission:tire.update');
    Route::post('tires/{tire}/remove', [TireController::class, 'remove'])->name('tires.remove')->middleware('permission:tire.update');
    Route::post('tires/{tire}/rotate', [TireController::class, 'rotate'])->name('tires.rotate')->middleware('permission:tire.update');
    Route::post('tires/{tire}/repair', [TireController::class, 'repair'])->name('tires.repair')->middleware('permission:tire.update');

    // ===== DISPATCH & HAULING =====
    Route::get('dispatch', [DispatchController::class, 'dashboard'])->name('dispatch.dashboard')->middleware('permission:dispatch.view');
    Route::get('dispatch/trips', [DispatchController::class, 'trips'])->name('dispatch.trips.index')->middleware('permission:dispatch.view');
    Route::get('dispatch/trips/create', [DispatchController::class, 'create'])->name('dispatch.trips.create')->middleware('permission:dispatch.view');
    Route::post('dispatch/trips', [DispatchController::class, 'store'])->name('dispatch.trips.store')->middleware('permission:dispatch.assign');
    Route::get('dispatch/trips/{dispatch_trip}', [DispatchController::class, 'show'])->name('dispatch.trips.show')->middleware('permission:dispatch.view');
    Route::post('dispatch/trips/{dispatch_trip}/stamp', [DispatchController::class, 'stamp'])->name('dispatch.trips.stamp')->middleware('permission:dispatch.close');
    Route::post('dispatch/trips/{dispatch_trip}/link-ticket', [DispatchController::class, 'linkTicket'])->name('dispatch.trips.link-ticket')->middleware('permission:dispatch.close');
    Route::post('dispatch/trips/{dispatch_trip}/cancel', [DispatchController::class, 'cancel'])->name('dispatch.trips.cancel')->middleware('permission:dispatch.close');
    Route::resource('loading-points', LoadingPointController::class)->middleware('permission:dispatch.view');
    Route::resource('dumping-points', DumpingPointController::class)->middleware('permission:dispatch.view');
    Route::resource('hauling-routes', HaulingRouteController::class)->middleware('permission:dispatch.view');

    // ===== STOCKPILE =====
    Route::get('stockpiles', [StockpileController::class, 'index'])->name('stockpiles.index')->middleware('permission:stockpile.view');
    Route::get('stockpiles/dashboard', [StockpileController::class, 'dashboard'])->name('stockpiles.dashboard')->middleware('permission:stockpile.view');
    Route::get('stockpiles/create', [StockpileController::class, 'create'])->name('stockpiles.create')->middleware('permission:stockpile.view');
    Route::post('stockpiles', [StockpileController::class, 'store'])->name('stockpiles.store')->middleware('permission:stockpile.create');
    Route::get('stockpiles/{stockpile}', [StockpileController::class, 'show'])->name('stockpiles.show')->middleware('permission:stockpile.view');
    Route::post('stockpiles/{stockpile}/survey', [StockpileController::class, 'survey'])->name('stockpiles.survey')->middleware('permission:stockpile.reconcile');
    Route::post('stockpile-surveys/{survey}/approve', [StockpileController::class, 'approveSurvey'])->name('stockpile-surveys.approve')->middleware('permission:stockpile.approve');

    // ===== QUALITY =====
    Route::resource('quality-parameters', QualityParameterController::class)->middleware('permission:quality.view');
    Route::get('specs', [ProductSpecificationController::class, 'index'])->name('specs.index')->middleware('permission:quality.view');
    Route::get('specs/create', [ProductSpecificationController::class, 'create'])->name('specs.create')->middleware('permission:quality.view');
    Route::post('specs', [ProductSpecificationController::class, 'store'])->name('specs.store')->middleware('permission:quality.create');
    Route::get('specs/{spec}', [ProductSpecificationController::class, 'show'])->name('specs.show')->middleware('permission:quality.view');
    Route::get('samples', [QcSampleController::class, 'index'])->name('samples.index')->middleware('permission:quality.view');
    Route::get('samples/create', [QcSampleController::class, 'create'])->name('samples.create')->middleware('permission:quality.view');
    Route::post('samples', [QcSampleController::class, 'store'])->name('samples.store')->middleware('permission:quality.create');
    Route::get('samples/{sample}', [QcSampleController::class, 'show'])->name('samples.show')->middleware('permission:quality.view');
    Route::post('samples/{sample}/test', [QcSampleController::class, 'test'])->name('samples.test')->middleware('permission:quality.test');
    Route::post('samples/{sample}/coa', [QcSampleController::class, 'issueCoa'])->name('samples.coa')->middleware('permission:quality.approve');
    Route::get('quality-holds', [QualityHoldController::class, 'index'])->name('quality-holds.index')->middleware('permission:quality.view');
    Route::post('quality-holds', [QualityHoldController::class, 'store'])->name('quality-holds.store')->middleware('permission:quality.create');
    Route::post('quality-holds/{quality_hold}/release', [QualityHoldController::class, 'release'])->name('quality-holds.release')->middleware('permission:quality.release');
    Route::post('quality-holds/{quality_hold}/special-approve', [QualityHoldController::class, 'specialApprove'])->name('quality-holds.special-approve')->middleware('permission:quality.create');

    // ===== MINING COST =====
    Route::get('cost', [CostController::class, 'dashboard'])->name('cost.dashboard')->middleware('permission:cost.view');
    Route::get('cost/others', [CostController::class, 'others'])->name('cost.others.index')->middleware('permission:cost.view');
    Route::post('cost/others', [CostController::class, 'storeOther'])->name('cost.others.store')->middleware('permission:cost.create');
    Route::post('cost/others/{other_cost}/approve', [CostController::class, 'approveOther'])->name('cost.others.approve')->middleware('permission:cost.create');
    Route::post('cost/others/{other_cost}/post', [CostController::class, 'postOther'])->name('cost.others.post')->middleware('permission:cost.post');

    // ===== CONTRACTS =====
    Route::resource('customer-contracts', CustomerContractController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:contract.view');
    Route::post('customer-contracts/{customer_contract}/approve', [CustomerContractController::class, 'approve'])->name('customer-contracts.approve')->middleware('permission:contract.create');
    Route::resource('supplier-contracts', SupplierContractController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:contract.view');
    Route::post('supplier-contracts/{supplier_contract}/approve', [SupplierContractController::class, 'approve'])->name('supplier-contracts.approve')->middleware('permission:contract.approve');
    Route::resource('hauling-contracts', HaulingContractController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:contract.view');
    Route::post('hauling-contracts/{hauling_contract}/approve', [HaulingContractController::class, 'approve'])->name('hauling-contracts.approve')->middleware('permission:contract.create');

    // ===== BUDGET =====
    Route::resource('budgets', BudgetController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:budget.view');
    Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve'])->name('budgets.approve')->middleware('permission:budget.create');
    Route::post('budgets/{budget}/revise', [BudgetController::class, 'revise'])->name('budgets.revise')->middleware('permission:budget.revise');
    Route::post('budgets/{budget}/close', [BudgetController::class, 'close'])->name('budgets.close')->middleware('permission:budget.approve');

    // ===== HSE =====
    Route::get('hse', [HseController::class, 'dashboard'])->name('hse.dashboard')->middleware('permission:hse.view');
    Route::get('hse/reports', [HseController::class, 'reports'])->name('hse.reports.index')->middleware('permission:hse.view');
    Route::get('hse/reports/create', [HseController::class, 'createReport'])->name('hse.reports.create')->middleware('permission:hse.view');
    Route::post('hse/reports', [HseController::class, 'storeReport'])->name('hse.reports.store')->middleware('permission:hse.create');
    Route::get('hse/reports/{hse_report}', [HseController::class, 'showReport'])->name('hse.reports.show')->middleware('permission:hse.view');
    Route::post('hse/reports/{hse_report}/actions', [HseController::class, 'addAction'])->name('hse.reports.actions.store')->middleware('permission:hse.create');
    Route::post('hse/reports/{hse_report}/investigate', [HseController::class, 'investigate'])->name('hse.reports.investigate')->middleware('permission:hse.update');
    Route::post('hse/actions/{action}/verify', [HseController::class, 'verifyAction'])->name('hse.actions.verify')->middleware('permission:hse.close');
    Route::post('hse/actions/{action}/close', [HseController::class, 'closeAction'])->name('hse.actions.close')->middleware('permission:hse.update');
    Route::post('hse/reports/{hse_report}/close', [HseController::class, 'closeReport'])->name('hse.reports.close')->middleware('permission:hse.update');
    Route::get('hse/permits', [HseController::class, 'permits'])->name('hse.permits.index')->middleware('permission:hse.view');
    Route::post('hse/permits', [HseController::class, 'storePermit'])->name('hse.permits.store')->middleware('permission:hse.create');
    Route::post('hse/permits/{permit}/approve', [HseController::class, 'approvePermit'])->name('hse.permits.approve')->middleware('permission:hse.create');
    Route::get('hse/activities', [HseController::class, 'activities'])->name('hse.activities.index')->middleware('permission:hse.view');
    Route::post('hse/activities', [HseController::class, 'storeActivity'])->name('hse.activities.store')->middleware('permission:hse.create');

    // ===== COMPLIANCE =====
    Route::get('compliance', [ComplianceController::class, 'index'])->name('compliance.index')->middleware('permission:compliance.view');
    Route::get('compliance/calendar', [ComplianceController::class, 'calendar'])->name('compliance.calendar')->middleware('permission:compliance.view');
    Route::get('compliance/create', [ComplianceController::class, 'create'])->name('compliance.create')->middleware('permission:compliance.view');
    Route::post('compliance', [ComplianceController::class, 'store'])->name('compliance.store')->middleware('permission:compliance.update');
    Route::get('compliance/{compliance}', [ComplianceController::class, 'show'])->name('compliance.show')->middleware('permission:compliance.view');
    Route::post('compliance/{compliance}/renew', [ComplianceController::class, 'renew'])->name('compliance.renew')->middleware('permission:compliance.update');

    // ===== FISCAL PERIOD =====
    Route::get('fiscal-periods', [FiscalPeriodController::class, 'index'])->name('fiscal-periods.index')->middleware('permission:fiscal.view');
    Route::post('fiscal-periods/close-year', [FiscalPeriodController::class, 'closeYear'])->name('fiscal-periods.close-year')->middleware('permission:fiscal.close');
    Route::post('fiscal-periods/depreciate', [FiscalPeriodController::class, 'depreciate'])->name('fiscal-periods.depreciate')->middleware('permission:fiscal.close');
    Route::post('fiscal-periods/{fiscal_period}/close', [FiscalPeriodController::class, 'close'])->name('fiscal-periods.close')->middleware('permission:fiscal.close');
    Route::post('fiscal-periods/{fiscal_period}/reopen', [FiscalPeriodController::class, 'reopen'])->name('fiscal-periods.reopen')->middleware('permission:fiscal.reopen');

    // ===== TELEMATICS & WEIGHBRIDGE DEVICES =====
    Route::get('telematics', [TelematicsController::class, 'index'])->name('telematics.index')->middleware('permission:telematics.view');
    Route::post('telematics/providers', [TelematicsController::class, 'storeProvider'])->name('telematics.providers.store')->middleware('permission:telematics.create');
    Route::post('telematics/providers/{provider}/sync', [TelematicsController::class, 'sync'])->name('telematics.providers.sync')->middleware('permission:telematics.update');
    Route::get('weighbridge/devices', [WeighbridgeDeviceController::class, 'index'])->name('weighbridge.devices.index')->middleware('permission:weighbridge.view');
    Route::post('weighbridge/devices', [WeighbridgeDeviceController::class, 'store'])->name('weighbridge.devices.store')->middleware('permission:weighbridge.create');
    Route::get('weighbridge/devices/{device}/live', [WeighbridgeDeviceController::class, 'live'])->name('weighbridge.devices.live')->middleware('permission:weighbridge.view');

    // ===== AI / FORECAST / EXECUTIVE =====
    Route::get('ai', [AiController::class, 'index'])->name('ai.index')->middleware('permission:ai.view');
    Route::post('ai/ask', [AiController::class, 'ask'])->name('ai.ask')->middleware('permission:ai.view');
    Route::get('forecast', [ForecastController::class, 'index'])->name('forecast.index')->middleware('permission:forecast.view');
    Route::get('executive', [ExecutiveController::class, 'index'])->name('executive.index')->middleware('permission:executive.view');

    // ===== ADMINISTRATION: REGISTER SURAT / INVOICE / KWITANSI =====
    Route::get('administration', [AdministrationController::class, 'dashboard'])->name('administration.dashboard')->middleware('permission:letter.view');
    Route::resource('letters', LetterRegisterController::class)->only(['index', 'create', 'store', 'show', 'update'])->middleware('permission:letter.view');
    Route::post('letters/{letter}/reserve', [LetterRegisterController::class, 'reserve'])->name('letters.reserve')->middleware('permission:letter.create');
    Route::post('letters/{letter}/review', [LetterRegisterController::class, 'review'])->name('letters.review')->middleware('permission:letter.submit');
    Route::post('letters/{letter}/approve', [LetterRegisterController::class, 'approve'])->name('letters.approve')->middleware('permission:letter.approve');
    Route::post('letters/{letter}/publish', [LetterRegisterController::class, 'publish'])->name('letters.publish')->middleware('permission:letter.approve');
    Route::post('letters/{letter}/sign', [LetterRegisterController::class, 'sign'])->name('letters.sign')->middleware('permission:letter.approve');
    Route::post('letters/{letter}/send', [LetterRegisterController::class, 'send'])->name('letters.send')->middleware('permission:letter.send');
    Route::post('letters/{letter}/archive', [LetterRegisterController::class, 'archive'])->name('letters.archive')->middleware('permission:letter.archive');
    Route::post('letters/{letter}/void', [LetterRegisterController::class, 'void'])->name('letters.void')->middleware('permission:letter.void');
    Route::post('letters/{letter}/cancel', [LetterRegisterController::class, 'cancel'])->name('letters.cancel')->middleware('permission:letter.update');
    Route::post('letters/{letter}/attach', [LetterRegisterController::class, 'attach'])->name('letters.attach')->middleware('permission:letter.update');
    Route::get('letters/{letter}/download', [LetterRegisterController::class, 'download'])->name('letters.download')->middleware('permission:letter.view');
    Route::get('letters/{letter}/print', [LetterRegisterController::class, 'print'])->name('letters.print')->middleware('permission:letter.view');
    Route::get('letter-types', [LetterTypeController::class, 'index'])->name('letter-types.index')->middleware('permission:letter.view');
    Route::post('letter-types', [LetterTypeController::class, 'store'])->name('letter-types.store')->middleware('permission:letter.create');
    Route::put('letter-types/{letter_type}', [LetterTypeController::class, 'update'])->name('letter-types.update')->middleware('permission:letter.update');
    Route::get('invoice-register', [InvoiceRegisterController::class, 'index'])->name('invoice-register.index')->middleware('permission:invoice_register.view');
    Route::get('invoice-register/{invoice}', [InvoiceRegisterController::class, 'show'])->name('invoice-register.show')->middleware('permission:invoice_register.view');
    Route::resource('receipts', ReceiptController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:receipt.view');
    Route::post('receipts/{receipt}/issue', [ReceiptController::class, 'issue'])->name('receipts.issue')->middleware('permission:receipt.create');
    Route::post('receipts/{receipt}/confirm', [ReceiptController::class, 'confirm'])->name('receipts.confirm')->middleware('permission:receipt.create');
    Route::post('receipts/{receipt}/void', [ReceiptController::class, 'void'])->name('receipts.void')->middleware('permission:receipt.void');
    Route::get('receipts/{receipt}/print', [ReceiptController::class, 'print'])->name('receipts.print')->middleware('permission:receipt.print');

    // ===== SPAREPART WAREHOUSE =====
    Route::get('sparepart', [SparepartController::class, 'dashboard'])->name('sparepart.dashboard')->middleware('permission:sparepart.view');
    Route::get('sparepart/master', [SparepartController::class, 'master'])->name('sparepart.master')->middleware('permission:sparepart.view');
    Route::post('sparepart/master', [SparepartController::class, 'storeMaster'])->name('sparepart.master.store')->middleware('permission:sparepart.create');
    Route::put('sparepart/master/{item}', [SparepartController::class, 'updateMaster'])->name('sparepart.master.update')->middleware('permission:sparepart.update');
    Route::get('sparepart/scan', [SparepartController::class, 'scan'])->name('sparepart.scan')->middleware('permission:sparepart.view');
    Route::get('sparepart/receipt', [SparepartController::class, 'receiptForm'])->name('sparepart.receipt')->middleware('permission:sparepart_receipt.view');
    Route::post('sparepart/receipt', [SparepartController::class, 'storeReceipt'])->name('sparepart.receipt.store')->middleware('permission:sparepart_receipt.create');
    Route::get('sparepart/issue', [SparepartController::class, 'issueForm'])->name('sparepart.issue')->middleware('permission:sparepart_issue.view');
    Route::post('sparepart/issue', [SparepartController::class, 'storeIssue'])->name('sparepart.issue.store')->middleware('permission:sparepart_issue.create');
    Route::post('sparepart/reserve', [SparepartController::class, 'reserve'])->name('sparepart.reserve')->middleware('permission:sparepart_issue.create');
    Route::post('sparepart/return', [SparepartController::class, 'returnStock'])->name('sparepart.return')->middleware('permission:sparepart_issue.create');
    Route::get('sparepart/card', [SparepartController::class, 'card'])->name('sparepart.card')->middleware('permission:stock_card.view');
    Route::get('sparepart/opname', [SparepartController::class, 'opnameForm'])->name('sparepart.opname')->middleware('permission:stock_opname.view');
    Route::post('sparepart/opname', [SparepartController::class, 'storeOpname'])->name('sparepart.opname.store')->middleware('permission:stock_opname.create');
    Route::post('sparepart/opname/{adjustment}/review', [SparepartController::class, 'opnameReview'])->name('sparepart.opname.review')->middleware('permission:stock_opname.approve');
    Route::get('sparepart/reports', [SparepartController::class, 'reports'])->name('sparepart.reports')->middleware('permission:stock_report.view');
    Route::get('sparepart/recommend', [SparepartController::class, 'recommend'])->name('sparepart.recommend')->middleware('permission:sparepart.view');
    Route::post('sparepart/recommend', [SparepartController::class, 'recommendToPR'])->name('sparepart.recommend.pr')->middleware('permission:sparepart.create');
    Route::get('sparepart/compatibility', [SparepartController::class, 'compatibility'])->name('sparepart.compatibility')->middleware('permission:sparepart.view');
    Route::post('sparepart/compatibility', [SparepartController::class, 'storeCompatibility'])->name('sparepart.compatibility.store')->middleware('permission:sparepart.create');
    Route::match(['get', 'post'], 'sparepart/locations', [SparepartController::class, 'locations'])->name('sparepart.locations')->middleware('permission:sparepart.view');

    // ===== TOOLS: LEGACY IMPORT =====
    Route::get('imports', [ImportController::class, 'index'])->name('imports.index')->middleware('permission:legacy_import.review');
    Route::post('imports', [ImportController::class, 'upload'])->name('imports.upload')->middleware('permission:legacy_import.execute');
    Route::get('imports/{batch}/map', [ImportController::class, 'map'])->name('imports.map')->middleware('permission:legacy_import.review');
    Route::post('imports/{batch}/map', [ImportController::class, 'validateMap'])->name('imports.validate')->middleware('permission:legacy_import.review');
    Route::post('imports/{batch}/execute', [ImportController::class, 'execute'])->name('imports.execute')->middleware('permission:legacy_import.execute');
    Route::get('imports/{batch}/errors', [ImportController::class, 'errors'])->name('imports.errors')->middleware('permission:legacy_import.review');
    Route::delete('imports/{batch}', [ImportController::class, 'destroy'])->name('imports.destroy')->middleware('permission:legacy_import.execute');

    // ===== TOOLS: BFJ LEGACY MIGRATION (multi-sheet workbook engine) =====
    Route::get('bfj-imports', [BfjImportController::class, 'index'])->name('bfj.index')->middleware('permission:legacy_import.review');
    Route::post('bfj-imports', [BfjImportController::class, 'upload'])->name('bfj.upload')->middleware('permission:legacy_import.execute');
    Route::get('bfj-imports/{batch}', [BfjImportController::class, 'show'])->name('bfj.show')->middleware('permission:legacy_import.review');
    Route::get('bfj-imports/{batch}/mapping', [BfjImportController::class, 'mapping'])->name('bfj.mapping')->middleware('permission:legacy_import.review');
    Route::post('bfj-imports/{batch}/sheet', [BfjImportController::class, 'sheetAction'])->name('bfj.sheet')->middleware('permission:legacy_import.review');
    Route::post('bfj-imports/{batch}/resolve', [BfjImportController::class, 'resolveMaster'])->name('bfj.resolve')->middleware('permission:legacy_import.execute');
    Route::post('bfj-imports/{batch}/import', [BfjImportController::class, 'import'])->name('bfj.import')->middleware('permission:legacy_import.execute');
    Route::post('bfj-imports/{batch}/reconcile', [BfjImportController::class, 'reconcile'])->name('bfj.reconcile')->middleware('permission:legacy_import.review');
    Route::post('bfj-imports/{batch}/rollback', [BfjImportController::class, 'rollback'])->name('bfj.rollback')->middleware('permission:legacy_import.execute');

    // ===== MARKETING / PROGRAMMATIC SEO =====
    Route::get('marketing/seo', [MarketingSeoController::class, 'dashboard'])->name('marketing.seo.dashboard')->middleware('permission:marketing.view');
    Route::get('marketing/seo/pages', [MarketingSeoController::class, 'pages'])->name('marketing.seo.pages')->middleware('permission:marketing.view');
    Route::get('marketing/seo/pages/{seo_page}', [MarketingSeoController::class, 'show'])->name('marketing.seo.show')->middleware('permission:marketing.view');
    Route::post('marketing/seo/pages/{seo_page}/publish', [MarketingSeoController::class, 'publish'])->name('marketing.seo.publish')->middleware('permission:marketing.update');
    Route::post('marketing/seo/pages/{seo_page}/noindex', [MarketingSeoController::class, 'noindex'])->name('marketing.seo.noindex')->middleware('permission:marketing.update');
    Route::post('marketing/seo/pages/{seo_page}/archive', [MarketingSeoController::class, 'archive'])->name('marketing.seo.archive')->middleware('permission:marketing.update');
    Route::get('marketing/seo/catalog', [MarketingSeoController::class, 'catalog'])->name('marketing.seo.catalog')->middleware('permission:marketing.view');
    Route::post('marketing/seo/features/{feature}/toggle', [MarketingSeoController::class, 'toggleFeature'])->name('marketing.seo.feature.toggle')->middleware('permission:marketing.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notification.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notification.read-all');
});

// ===== PROGRAMMATIC SEO (public, must stay last) =====
require __DIR__.'/seo.php';
