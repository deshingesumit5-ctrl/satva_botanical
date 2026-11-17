<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;

// Dashboard & Notifications
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;

// Masters
use App\Http\Controllers\Masters\PlantController;
use App\Http\Controllers\Masters\DepartmentController;
use App\Http\Controllers\Masters\WarehouseController;
use App\Http\Controllers\Masters\StorageLocationController;
use App\Http\Controllers\Masters\UomController;
use App\Http\Controllers\Masters\ProductCategoryController;
use App\Http\Controllers\Masters\ProductController;
use App\Http\Controllers\Masters\MaterialController;
use App\Http\Controllers\Masters\SupplierController;
use App\Http\Controllers\Masters\CustomerController;
use App\Http\Controllers\Masters\StageController;
use App\Http\Controllers\Masters\ActivityController;
use App\Http\Controllers\Masters\ParameterController;
use App\Http\Controllers\Masters\EquipmentController;
use App\Http\Controllers\Masters\QcTestController;

// Admin & Settings
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionMatrixController;
use App\Http\Controllers\Admin\AuditTrailController;
use App\Http\Controllers\Settings\CompanySettingController;
use App\Http\Controllers\Settings\SystemSettingController;
use App\Http\Controllers\Settings\DocumentNumberingController;

// Recipe
use App\Http\Controllers\Recipe\RecipeController;
use App\Http\Controllers\Recipe\RecipeBuilderController;

// Purchase
use App\Http\Controllers\Purchase\PurchaseRequisitionController;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\GrnController;

// Inventory
use App\Http\Controllers\Inventory\InventoryDashboardController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\WipController;
use App\Http\Controllers\Inventory\RecoveryController;
use App\Http\Controllers\Inventory\WasteController;

// Production
use App\Http\Controllers\Production\ProductionDashboardController;
use App\Http\Controllers\Production\ProductionPlanController;
use App\Http\Controllers\Production\BatchController;
use App\Http\Controllers\Production\MaterialRequirementController;
use App\Http\Controllers\Production\ProductionExecutionController;
use App\Http\Controllers\Production\ReconciliationController;
use App\Http\Controllers\Production\BmrController;
use App\Http\Controllers\Production\ReworkController;

// QC
use App\Http\Controllers\Qc\QcController;

// Sales & Commercial
use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Sales\DispatchController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\PurchaseInvoiceController;
use App\Http\Controllers\Sales\SupplierPaymentController;
use App\Http\Controllers\Sales\CustomerReceiptController;
use App\Http\Controllers\Sales\CreditDebitNoteController;

// Costing
use App\Http\Controllers\Costing\MonthlyExpenseController;
use App\Http\Controllers\Costing\ExpenseAllocationController;
use App\Http\Controllers\Costing\BatchCostController;

// Reports
use App\Http\Controllers\Reports\ProductionReportController;
use App\Http\Controllers\Reports\MaterialReportController;
use App\Http\Controllers\Reports\QcReportController;
use App\Http\Controllers\Reports\RecoveryReportController;
use App\Http\Controllers\Reports\CostReportController;
use App\Http\Controllers\Reports\CommercialReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Authentication
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware(['auth', 'permission'])->group(function () {

    // Profile & Password
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/password', [ProfileController::class, 'showPassword'])->name('password');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::get('/change-password', [ProfileController::class, 'showPassword'])->name('change-password');
    Route::put('/change-password', [ProfileController::class, 'updatePassword']);

    // Dashboard & Notifications
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');

    // =========================================================================
    // MASTERS
    // =========================================================================
    Route::prefix('masters')->name('masters.')->group(function () {
        Route::resource('plants', PlantController::class);
        Route::post('plants/{plant}/toggle-status', [PlantController::class, 'toggleStatus'])->name('plants.toggle-status');

        Route::resource('departments', DepartmentController::class);
        Route::post('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');

        Route::resource('warehouses', WarehouseController::class);
        Route::post('warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->name('warehouses.toggle-status');

        Route::resource('locations', StorageLocationController::class);
        Route::post('locations/{location}/toggle-status', [StorageLocationController::class, 'toggleStatus'])->name('locations.toggle-status');

        Route::resource('uoms', UomController::class);
        Route::post('uoms/{uom}/toggle-status', [UomController::class, 'toggleStatus'])->name('uoms.toggle-status');

        Route::resource('categories', ProductCategoryController::class);
        Route::post('categories/{category}/toggle-status', [ProductCategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

        Route::resource('products', ProductController::class);
        Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

        Route::resource('materials', MaterialController::class);
        Route::post('materials/{material}/toggle-status', [MaterialController::class, 'toggleStatus'])->name('materials.toggle-status');

        Route::resource('suppliers', SupplierController::class);
        Route::post('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');

        Route::resource('customers', CustomerController::class);
        Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

        Route::resource('stages', StageController::class);
        Route::post('stages/{stage}/toggle-status', [StageController::class, 'toggleStatus'])->name('stages.toggle-status');

        Route::resource('activities', ActivityController::class);
        Route::post('activities/{activity}/toggle-status', [ActivityController::class, 'toggleStatus'])->name('activities.toggle-status');

        Route::resource('parameters', ParameterController::class);
        Route::post('parameters/{parameter}/toggle-status', [ParameterController::class, 'toggleStatus'])->name('parameters.toggle-status');

        Route::resource('equipments', EquipmentController::class);
        Route::post('equipments/{equipment}/toggle-status', [EquipmentController::class, 'toggleStatus'])->name('equipments.toggle-status');

        Route::resource('qc-tests', QcTestController::class);
        Route::post('qc-tests/{qcTest}/toggle-status', [QcTestController::class, 'toggleStatus'])->name('qc-tests.toggle-status');
    });

    // =========================================================================
    // ADMIN & ROLES & ACCESS
    // =========================================================================
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::resource('roles', RoleController::class);

        Route::get('permissions', [PermissionMatrixController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionMatrixController::class, 'save'])->name('permissions.save');
        Route::put('permissions', [PermissionMatrixController::class, 'save'])->name('permissions.update');
        Route::post('permissions/update', [PermissionMatrixController::class, 'save']);

        Route::get('audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail.index');
    });

    // =========================================================================
    // SETTINGS
    // =========================================================================
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('company', [CompanySettingController::class, 'edit'])->name('company');
        Route::put('company', [CompanySettingController::class, 'update'])->name('company.update');

        Route::get('system', [SystemSettingController::class, 'edit'])->name('system');
        Route::put('system', [SystemSettingController::class, 'update'])->name('system.update');

        Route::get('document-numbering', [DocumentNumberingController::class, 'index'])->name('document-numbering');
        Route::put('document-numbering', [DocumentNumberingController::class, 'update'])->name('document-numbering.update');
    });

    // =========================================================================
    // RECIPE MODULE
    // =========================================================================
    Route::prefix('recipe')->name('recipe.')->group(function () {
        Route::get('/', [RecipeController::class, 'index'])->name('index');
        Route::get('/list', [RecipeController::class, 'index'])->name('recipes.index');
        Route::get('/create', [RecipeController::class, 'create'])->name('create');
        Route::post('/', [RecipeController::class, 'store'])->name('store');
        Route::get('/{recipe}', [RecipeController::class, 'show'])->name('show');
        Route::get('/{recipe}/edit', [RecipeController::class, 'edit'])->name('edit');
        Route::post('/{recipe}/submit', [RecipeController::class, 'submit'])->name('submit');
        Route::post('/{recipe}/approve', [RecipeController::class, 'approve'])->name('approve');
        Route::post('/{recipe}/reject', [RecipeController::class, 'reject'])->name('reject');
        Route::post('/{recipe}/activate', [RecipeController::class, 'activate'])->name('activate');
        Route::post('/{recipe}/deactivate', [RecipeController::class, 'deactivate'])->name('deactivate');
        Route::delete('/{recipe}', [RecipeController::class, 'destroy'])->name('destroy');
        Route::post('/{recipe}/toggle-status', [RecipeController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{recipe}/new-version', [RecipeController::class, 'newVersion'])->name('new-version');
        Route::post('/{recipe}/clone-version', [RecipeController::class, 'newVersion'])->name('clone-version');
        Route::post('/{recipe}/copy', [RecipeController::class, 'copy'])->name('copy');
        Route::get('/{recipe}/versions', [RecipeController::class, 'versions'])->name('versions');

        // Builder 8-Step Wizard
        Route::prefix('{recipe}/builder')->name('builder.')->group(function () {
            Route::get('/step/{step}', [RecipeBuilderController::class, 'show'])->name('step');
            Route::post('/step/{step}', [RecipeBuilderController::class, 'saveStep'])->name('save-step');
        });
    });

    // =========================================================================
    // PURCHASE MODULE
    // =========================================================================
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::resource('requisitions', PurchaseRequisitionController::class);
        Route::post('requisitions/{requisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('requisitions.submit');
        Route::post('requisitions/{requisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('requisitions.approve');
        Route::post('requisitions/{requisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('requisitions.reject');
        Route::post('requisitions/{requisition}/convert-to-po', [PurchaseRequisitionController::class, 'convertToPo'])->name('requisitions.convert-to-po');

        Route::resource('orders', PurchaseOrderController::class);
        Route::post('orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('orders.approve');
        Route::post('orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('orders/{order}/print', [PurchaseOrderController::class, 'print'])->name('orders.print');

        Route::get('grn/from-po/{order}', [GrnController::class, 'createFromPo'])->name('grn.from-po');
        Route::post('grn/from-po', [GrnController::class, 'store'])->name('grn.store-from-po');
        Route::resource('grn', GrnController::class);
    });

    // =========================================================================
    // INVENTORY MODULE
    // =========================================================================
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('dashboard', [InventoryDashboardController::class, 'index'])->name('dashboard');

        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/ledger', [StockController::class, 'ledger'])->name('stock.ledger');
        Route::get('stock/lots', [StockController::class, 'lots'])->name('stock.lots');
        Route::get('stock/opening-entry', [StockController::class, 'openingEntryForm'])->name('stock.opening-entry-form');
        Route::post('stock/opening-entry', [StockController::class, 'openingEntry'])->name('stock.opening-entry');
        Route::post('stock/store-opening-entry', [StockController::class, 'openingEntry'])->name('stock.store-opening-entry');

        Route::resource('transfers', StockTransferController::class);
        Route::resource('adjustments', StockAdjustmentController::class);

        Route::get('wip', [WipController::class, 'index'])->name('wip.index');
        Route::post('wip/{wip}/consume', [WipController::class, 'consume'])->name('wip.consume');

        Route::get('recovery', [RecoveryController::class, 'index'])->name('recovery.index');
        Route::post('recovery/{lot}/consume', [RecoveryController::class, 'consume'])->name('recovery.consume');

        Route::get('waste', [WasteController::class, 'index'])->name('waste.index');
        Route::post('waste/{record}/dispose', [WasteController::class, 'dispose'])->name('waste.dispose');
    });

    // =========================================================================
    // PRODUCTION MODULE
    // =========================================================================
    Route::prefix('production')->name('production.')->group(function () {
        Route::get('dashboard', [ProductionDashboardController::class, 'index'])->name('dashboard');

        Route::resource('plans', ProductionPlanController::class);
        Route::post('plans/{plan}/approve', [ProductionPlanController::class, 'approve'])->name('plans.approve');
        Route::post('plans/{plan}/reject', [ProductionPlanController::class, 'reject'])->name('plans.reject');
        Route::get('plans/{plan}/requirements', [MaterialRequirementController::class, 'show'])->name('plans.requirements');

        Route::resource('batches', BatchController::class);
        Route::post('batches/{batch}/start', [BatchController::class, 'start'])->name('batches.start');
        Route::post('batches/{batch}/hold', [BatchController::class, 'hold'])->name('batches.hold');
        Route::post('batches/{batch}/resume', [BatchController::class, 'resume'])->name('batches.resume');
        Route::post('batches/{batch}/close', [BatchController::class, 'close'])->name('batches.close');

        // Material Allocations & Consumptions
        Route::get('batches/{batch}/allocate', [MaterialRequirementController::class, 'allocate'])->name('batches.allocate');
        Route::post('batches/{batch}/allocate', [MaterialRequirementController::class, 'saveAllocation'])->name('batches.allocate.save');
        Route::post('batches/{batch}/save-allocation', [MaterialRequirementController::class, 'saveAllocation'])->name('batches.save-allocation');
        Route::post('batches/{batch}/consume', [MaterialRequirementController::class, 'consume'])->name('batches.consume');
        Route::post('batches/{batch}/return-material', [MaterialRequirementController::class, 'returnMaterial'])->name('batches.return-material');

        // Stage Execution Flow
        Route::get('batches/{batch}/execute', [ProductionExecutionController::class, 'show'])->name('batches.execute');
        Route::get('batches/{batch}/execution', [ProductionExecutionController::class, 'show'])->name('execution.show');
        Route::get('batches/{batch}/execution/stage/{stage_execution_id}', [ProductionExecutionController::class, 'show'])->name('execution.stage');
        Route::post('batches/{batch}/execute/stage/{stageExecution}', [ProductionExecutionController::class, 'saveStage'])->name('batches.execute.stage');
        Route::post('batches/{batch}/execute/stage/{stageExecution}/save', [ProductionExecutionController::class, 'saveStage'])->name('execution.save-stage');
        Route::post('batches/{batch}/execute/stage/{stageExecution}/start', [ProductionExecutionController::class, 'startStage'])->name('execution.start-stage');
        Route::post('batches/{batch}/execute/stage/{stageExecution}/complete', [ProductionExecutionController::class, 'completeStage'])->name('batches.execute.stage.complete');
        Route::post('batches/{batch}/execute/stage/{stageExecution}/finish', [ProductionExecutionController::class, 'completeStage'])->name('execution.complete-stage');

        // Reconciliation & Closure
        Route::get('batches/{batch}/reconcile', [ReconciliationController::class, 'show'])->name('batches.reconcile');
        Route::get('batches/{batch}/reconciliation', [ReconciliationController::class, 'show'])->name('reconciliation.show');
        Route::post('batches/{batch}/reconcile', [ReconciliationController::class, 'save'])->name('batches.reconcile.save');
        Route::post('batches/{batch}/reconciliation/close', [ReconciliationController::class, 'close'])->name('reconciliation.close');

        // BMR (Batch Manufacturing Record)
        Route::get('batches/{batch}/bmr', [BmrController::class, 'show'])->name('batches.bmr');
        Route::get('batches/{batch}/bmr-view', [BmrController::class, 'show'])->name('bmr.show');
        Route::get('batches/{batch}/bmr/pdf', [BmrController::class, 'pdf'])->name('batches.bmr.pdf');
        Route::get('batches/{batch}/bmr-download-pdf', [BmrController::class, 'pdf'])->name('bmr.pdf');

        // Rework Management
        Route::get('rework', [ReworkController::class, 'index'])->name('rework.index');
        Route::get('rework/{rework}', [ReworkController::class, 'show'])->name('rework.show');
    });

    // =========================================================================
    // QUALITY CONTROL (QC) MODULE
    // =========================================================================
    Route::prefix('qc')->name('qc.')->group(function () {
        Route::get('dashboard', [QcController::class, 'dashboard'])->name('dashboard');
        Route::get('pending', [QcController::class, 'pending'])->name('pending');
        Route::get('history', [QcController::class, 'history'])->name('history');
        Route::get('{qcRequest}', [QcController::class, 'show'])->name('show');
        Route::get('{qcRequest}/review', [QcController::class, 'show'])->name('review');
        Route::post('{qcRequest}/results', [QcController::class, 'saveResults'])->name('save-results');
        Route::post('{qcRequest}/approve', [QcController::class, 'approve'])->name('approve');
        Route::post('{qcRequest}/reject', [QcController::class, 'reject'])->name('reject');
        Route::post('{qcRequest}/rework', [QcController::class, 'requestRework'])->name('rework');
    });

    // =========================================================================
    // SALES & COMMERCIAL
    // =========================================================================
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::resource('orders', SalesOrderController::class);
        Route::post('orders/{order}/approve', [SalesOrderController::class, 'approve'])->name('orders.approve');
        Route::post('orders/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('orders.cancel');

        Route::resource('dispatch', DispatchController::class);

        Route::resource('invoices', SalesInvoiceController::class);
        Route::get('invoices/{invoice}/pdf', [SalesInvoiceController::class, 'pdf'])->name('invoices.pdf');

        Route::resource('purchase-invoices', PurchaseInvoiceController::class);
        Route::resource('payments', SupplierPaymentController::class);
        Route::resource('receipts', CustomerReceiptController::class);
        Route::resource('notes', CreditDebitNoteController::class);
    });

    // =========================================================================
    // COSTING MODULE
    // =========================================================================
    Route::prefix('costing')->name('costing.')->group(function () {
        Route::resource('expenses', MonthlyExpenseController::class);
        Route::post('expenses/{expense}/approve', [MonthlyExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('expenses/{expense}/post', [MonthlyExpenseController::class, 'post'])->name('expenses.post');

        Route::get('allocation', [ExpenseAllocationController::class, 'index'])->name('allocation.index');
        Route::post('allocation', [ExpenseAllocationController::class, 'save'])->name('allocation.save');

        Route::get('batch-cost', [BatchCostController::class, 'index'])->name('batch-cost.index');
        Route::get('batch-cost/{batch}', [BatchCostController::class, 'show'])->name('batch-cost.show');
    });

    // =========================================================================
    // REPORTS MODULE
    // =========================================================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('production', [ProductionReportController::class, 'index'])->name('production');
        Route::get('production/export', [ProductionReportController::class, 'export'])->name('production.export');
        Route::get('production/pdf', [ProductionReportController::class, 'pdf'])->name('production.pdf');

        Route::get('material', [MaterialReportController::class, 'index'])->name('material');
        Route::get('material/export', [MaterialReportController::class, 'export'])->name('material.export');

        Route::get('qc', [QcReportController::class, 'index'])->name('qc');
        Route::get('qc/export', [QcReportController::class, 'export'])->name('qc.export');

        Route::get('recovery', [RecoveryReportController::class, 'index'])->name('recovery');
        Route::get('recovery/export', [RecoveryReportController::class, 'export'])->name('recovery.export');

        Route::get('cost', [CostReportController::class, 'index'])->name('cost');
        Route::get('cost/export', [CostReportController::class, 'export'])->name('cost.export');

        Route::get('commercial', [CommercialReportController::class, 'index'])->name('commercial');
        Route::get('commercial/export', [CommercialReportController::class, 'export'])->name('commercial.export');
    });

});

// Root Redirect to Dashboard
Route::redirect('/', '/dashboard');
