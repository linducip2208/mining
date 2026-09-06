<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\ApprovalCenterController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttendanceController;
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
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\DocumentController;
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
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LoadingPointController;
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
use App\Http\Controllers\ProductSpecificationController;
use App\Http\Controllers\ProductionBatchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSecurityController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\QcSampleController;
use App\Http\Controllers\QualityHoldController;
use App\Http\Controllers\QualityParameterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockpileController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierContractController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TelematicsController;
use App\Http\Controllers\TireController;
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

require __DIR__.'/auth.php';

// ===== DOCUMENTATION PORTAL (public/login per setting docs.public) =====
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/search', [DocsController::class, 'search'])->name('docs.search');
Route::get('/docs/suggest', [DocsController::class, 'suggest'])->name('docs.suggest');
Route::get('/docs/health', [DocsController::class, 'health'])->name('docs.health');
Route::get('/docs/sitemap.xml', [DocsController::class, 'sitemap'])->name('docs.sitemap');
Route::get('/docs/{section}', [DocsController::class, 'section'])->name('docs.section');
Route::get('/docs/{section}/{page}', [DocsController::class, 'page'])->name('docs.page');

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
    Route::get('reports/fleet', [ReportController::class, 'fleet'])->name('report.fleet')->middleware('permission:report.view');
    Route::get('reports/fuel', [ReportController::class, 'fuel'])->name('report.fuel')->middleware('permission:report.view');
    Route::get('reports/tire', [ReportController::class, 'tire'])->name('report.tire')->middleware('permission:report.view');
    Route::get('reports/dispatch', [ReportController::class, 'dispatch'])->name('report.dispatch')->middleware('permission:report.view');
    Route::get('reports/stockpile', [ReportController::class, 'stockpile'])->name('report.stockpile')->middleware('permission:report.view');
    Route::get('reports/quality', [ReportController::class, 'quality'])->name('report.quality')->middleware('permission:report.view');
    Route::get('reports/contract', [ReportController::class, 'contract'])->name('report.contract')->middleware('permission:report.view');
    Route::get('reports/budget', [ReportController::class, 'budget'])->name('report.budget')->middleware('permission:report.view');

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

    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notification.index');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'readAll'])->name('notification.read-all');
});
