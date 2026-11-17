<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Sattva Botanical</title>

    <!-- Favicon (Browser Tab Icon) -->
    <link rel="icon" type="image/png" href="{{ asset('images/sattva-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/sattva-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/sattva-logo.png') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Theme CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Forms & List Design System (Loaded Last) -->
    <link href="{{ asset('css/forms.css') }}" rel="stylesheet">

    @php
        $authUser = auth()->user();
        $currentScreenKey = $authUser ? \App\Services\PermissionService::resolveScreenKeyForRoute(request()->route()?->getName()) : null;
        $canScreenAdd     = !$currentScreenKey || !$authUser || $authUser->hasPermission("{$currentScreenKey}.add");
        $canScreenEdit    = !$currentScreenKey || !$authUser || $authUser->hasPermission("{$currentScreenKey}.edit");
        $canScreenDelete  = !$currentScreenKey || !$authUser || $authUser->hasPermission("{$currentScreenKey}.delete");
        $canScreenApprove = !$currentScreenKey || !$authUser || $authUser->hasPermission("{$currentScreenKey}.approve");
        $canScreenExport  = !$currentScreenKey || !$authUser || $authUser->hasPermission("{$currentScreenKey}.export");

        $masterCompanyPerms = ['masters.plants.view', 'masters.departments.view', 'masters.warehouses.view', 'masters.locations.view'];
        $masterItemPerms    = ['masters.uoms.view', 'masters.categories.view', 'masters.products.view', 'masters.materials.view', 'masters.suppliers.view', 'masters.customers.view'];
        $masterProcPerms    = ['masters.stages.view', 'masters.activities.view', 'masters.parameters.view', 'masters.equipments.view', 'masters.qc_tests.view'];
        $allMasterPerms     = array_merge($masterCompanyPerms, $masterItemPerms, $masterProcPerms);

        $recipePerms     = ['recipe.recipes.view', 'recipe.builder.view', 'recipe.versions.view', 'recipe.approval.view'];
        $purchasePerms   = ['purchase.requisitions.view', 'purchase.orders.view', 'purchase.grn.view'];
        $inventoryPerms  = ['inventory.stock.view', 'inventory.transfers.view', 'inventory.adjustments.view', 'inventory.wip.view', 'inventory.recovery.view', 'inventory.waste.view'];
        $productionPerms = ['production.plans.view', 'production.batches.view', 'production.allocation.view', 'production.execution.view', 'production.rework.view', 'production.reconciliation.view', 'production.bmr.view'];
        $qcPerms         = ['qc.dashboard.view', 'qc.review.view', 'qc.history.view'];
        $salesPerms      = ['commercial.orders.view', 'commercial.dispatch.view', 'commercial.sales_invoices.view', 'commercial.purchase_invoices.view', 'commercial.payments.view', 'commercial.notes.view'];
        $costingPerms    = ['costing.expenses.view', 'costing.allocation.view', 'costing.batch_cost.view'];
        $reportPerms     = ['reports.production.view', 'reports.material.view', 'reports.qc.view', 'reports.recovery.view', 'reports.cost.view', 'reports.commercial.view'];
        $accessPerms     = ['admin.users.view', 'admin.roles.view', 'admin.permissions.view', 'admin.audit.view'];
        $settingsPerms   = ['admin.settings.view'];
    @endphp

    @if($currentScreenKey && $authUser && !$authUser->isSuperAdmin())
        <style>
            @if(!$canScreenAdd)
                .main-content a[href*="/create"],
                .main-content button[data-bs-target^="#add"],
                .main-content button[data-modal-add],
                .main-content [data-action="add"] { display: none !important; }
            @endif
            @if(!$canScreenEdit)
                .main-content .btn-action-edit,
                .main-content a[href*="/edit"],
                .main-content button[onclick^="edit"],
                .main-content button[data-modal-edit],
                .main-content [data-action="edit"] { display: none !important; }
            @endif
            @if(!$canScreenDelete)
                .main-content .btn-action-delete,
                .main-content [data-action="delete"],
                .main-content button[data-url*="/"] { display: none !important; }
            @endif
            @if(!$canScreenApprove)
                .main-content form[action*="/approve"],
                .main-content [data-action="approve"] { display: none !important; }
            @endif
            @if(!$canScreenExport)
                .main-content a[href*="/export"],
                .main-content [data-action="export"] { display: none !important; }
            @endif
        </style>
    @endif

    @stack('styles')
</head>

<body>

    <!-- ========================================================= -->
    <!-- DESKTOP TOP HORIZONTAL NAVBAR (Visible on >= 992px)       -->
    <!-- ========================================================= -->
    <nav class="navbar navbar-expand-lg navbar-sattva d-none d-lg-flex sticky-top">
        <div class="container-fluid px-3">

            <a class="navbar-brand-sattva me-3" href="{{ route('dashboard') }}">
                @php
                    $company = \App\Models\CompanySetting::first();
                @endphp
                @if($company && $company->logo)
                    <img src="{{ Storage::url($company->logo) }}" alt="Sattva" height="36" class="rounded">
                @else
                    <div class="brand-logo-badge">
                        <img src="{{ asset('images/sattva-logo.png') }}" alt="Sattva Botanicals">
                    </div>
                @endif
                <div class="brand-text-wrapper">
                    <span class="brand-name">SATTVA</span>
                    <span class="brand-tag">BOTANICAL</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="desktopNavbarNav">
                <ul class="navbar-nav me-auto mb-0 gap-1 align-items-center flex-nowrap sattva-scroll-menu">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link nav-link-sattva {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            <i class="bi bi-grid-fill me-1"></i> Dashboard
                        </a>
                    </li>

                    <!-- Masters Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($allMasterPerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('masters*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-layers-fill me-1"></i> Masters
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                @if($authUser->hasAnyPermission($masterCompanyPerms))
                                    <li class="dropdown-header text-uppercase text-muted small fw-bold">Company Setup</li>
                                    @if($authUser->hasPermission('masters.plants.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.plants.index') }}"><i class="bi bi-building me-2"></i>Plant Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.departments.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.departments.index') }}"><i class="bi bi-diagram-3 me-2"></i>Department Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.warehouses.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.warehouses.index') }}"><i class="bi bi-house-door me-2"></i>Warehouse Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.locations.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.locations.index') }}"><i class="bi bi-geo-alt me-2"></i>Storage Locations & Bins</a></li>
                                    @endif
                                @endif

                                @if($authUser->hasAnyPermission($masterItemPerms))
                                    @if($authUser->hasAnyPermission($masterCompanyPerms))
                                        <li><hr class="dropdown-divider"></li>
                                    @endif
                                    <li class="dropdown-header text-uppercase text-muted small fw-bold">Items & Partners</li>
                                    @if($authUser->hasPermission('masters.uoms.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.uoms.index') }}"><i class="bi bi-rulers me-2"></i>UOM Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.categories.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.categories.index') }}"><i class="bi bi-tags me-2"></i>Product Categories</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.products.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.products.index') }}"><i class="bi bi-box-seam me-2"></i>Product Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.materials.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.materials.index') }}"><i class="bi bi-boxes me-2"></i>Material Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.suppliers.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.suppliers.index') }}"><i class="bi bi-truck me-2"></i>Supplier Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.customers.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.customers.index') }}"><i class="bi bi-people me-2"></i>Customer Master</a></li>
                                    @endif
                                @endif

                                @if($authUser->hasAnyPermission($masterProcPerms))
                                    @if($authUser->hasAnyPermission($masterCompanyPerms) || $authUser->hasAnyPermission($masterItemPerms))
                                        <li><hr class="dropdown-divider"></li>
                                    @endif
                                    <li class="dropdown-header text-uppercase text-muted small fw-bold">Processing Masters</li>
                                    @if($authUser->hasPermission('masters.stages.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.stages.index') }}"><i class="bi bi-collection-play me-2"></i>Stage Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.activities.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.activities.index') }}"><i class="bi bi-activity me-2"></i>Activity Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.parameters.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.parameters.index') }}"><i class="bi bi-sliders me-2"></i>Parameter Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.equipments.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.equipments.index') }}"><i class="bi bi-tools me-2"></i>Equipment Master</a></li>
                                    @endif
                                    @if($authUser->hasPermission('masters.qc_tests.view'))
                                        <li><a class="dropdown-item" href="{{ route('masters.qc-tests.index') }}"><i class="bi bi-patch-check me-2"></i>QC Test Master</a></li>
                                    @endif
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- Recipe Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($recipePerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('recipe*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-journal-text me-1"></i> Recipe
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                @if($authUser->hasAnyPermission($recipePerms))
                                    <li><a class="dropdown-item" href="{{ route('recipe.index') }}"><i class="bi bi-list-columns-reverse me-2"></i>All Recipes</a></li>
                                @endif
                                @if($authUser->hasPermission('recipe.recipes.add'))
                                    <li><a class="dropdown-item" href="{{ route('recipe.create') }}"><i class="bi bi-plus-circle me-2"></i>New Recipe SOP</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- Purchase Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($purchasePerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('purchase*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-cart3 me-1"></i> Purchase
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                @if($authUser->hasPermission('purchase.requisitions.view'))
                                    <li><a class="dropdown-item" href="{{ route('purchase.requisitions.index') }}"><i class="bi bi-file-earmark-text me-2"></i>Purchase Requisitions</a></li>
                                @endif
                                @if($authUser->hasPermission('purchase.orders.view'))
                                    <li><a class="dropdown-item" href="{{ route('purchase.orders.index') }}"><i class="bi bi-file-earmark-check me-2"></i>Purchase Orders (PO)</a></li>
                                @endif
                                @if($authUser->hasPermission('purchase.grn.view'))
                                    <li><a class="dropdown-item" href="{{ route('purchase.grn.index') }}"><i class="bi bi-box-arrow-in-down me-2"></i>GRN & Incoming QC</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- Inventory Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($inventoryPerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('inventory*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-box-fill me-1"></i> Inventory
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                <li><a class="dropdown-item" href="{{ route('inventory.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Inventory Dashboard</a></li>
                                @if($authUser->hasPermission('inventory.stock.view'))
                                    <li><a class="dropdown-item" href="{{ route('inventory.stock.index') }}"><i class="bi bi-table me-2"></i>Current Stock Summary</a></li>
                                    <li><a class="dropdown-item" href="{{ route('inventory.stock.lots') }}"><i class="bi bi-upc-scan me-2"></i>Lot-wise Stock</a></li>
                                    <li><a class="dropdown-item" href="{{ route('inventory.stock.ledger') }}"><i class="bi bi-journal-bookmark me-2"></i>Stock Ledger (Append-Only)</a></li>
                                @endif
                                @if($authUser->hasPermission('inventory.stock.add'))
                                    <li><a class="dropdown-item" href="{{ route('inventory.stock.opening-entry-form') }}"><i class="bi bi-plus-square-dotted me-2"></i>Opening Stock Entry</a></li>
                                @endif
                                @if($authUser->hasAnyPermission(['inventory.transfers.view', 'inventory.adjustments.view', 'inventory.wip.view', 'inventory.recovery.view', 'inventory.waste.view']))
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                                @if($authUser->hasPermission('inventory.transfers.view'))
                                    <li><a class="dropdown-item" href="{{ route('inventory.transfers.index') }}"><i class="bi bi-arrow-left-right me-2"></i>Stock Transfers</a></li>
                                @endif
                                @if($authUser->hasPermission('inventory.adjustments.view'))
                                    <li><a class="dropdown-item" href="{{ route('inventory.adjustments.index') }}"><i class="bi bi-pencil-square me-2"></i>Stock Adjustments</a></li>
                                @endif
                                @if($authUser->hasPermission('inventory.wip.view'))
                                    <li><a class="dropdown-item" href="{{ route('inventory.wip.index') }}"><i class="bi bi-hourglass-split me-2"></i>WIP Records</a></li>
                                @endif
                                @if($authUser->hasPermission('inventory.recovery.view'))
                                    <li><a class="dropdown-item" href="{{ route('inventory.recovery.index') }}"><i class="bi bi-recycle me-2"></i>Solvent Recovery Lots</a></li>
                                @endif
                                @if($authUser->hasPermission('inventory.waste.view'))
                                    <li><a class="dropdown-item" href="{{ route('inventory.waste.index') }}"><i class="bi bi-trash3 me-2"></i>By-Product & Waste Disposal</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- Production Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($productionPerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('production*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-gear-wide-connected me-1"></i> Production
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                <li><a class="dropdown-item" href="{{ route('production.dashboard') }}"><i class="bi bi-speedometer me-2"></i>Production Dashboard</a></li>
                                @if($authUser->hasPermission('production.plans.view'))
                                    <li><a class="dropdown-item" href="{{ route('production.plans.index') }}"><i class="bi bi-calendar-check me-2"></i>Production Plans</a></li>
                                @endif
                                @if($authUser->hasPermission('production.batches.view'))
                                    <li><a class="dropdown-item" href="{{ route('production.batches.index') }}"><i class="bi bi-collection-fill me-2"></i>Batch Management</a></li>
                                @endif
                                @if($authUser->hasPermission('production.rework.view'))
                                    <li><a class="dropdown-item" href="{{ route('production.rework.index') }}"><i class="bi bi-arrow-repeat me-2"></i>Rework Batches</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- QC Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($qcPerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('qc*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-shield-check me-1"></i> QC
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                @if($authUser->hasAnyPermission(['qc.dashboard.view', 'qc.review.view']))
                                    <li><a class="dropdown-item" href="{{ route('qc.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>QC Dashboard</a></li>
                                @endif
                                @if($authUser->hasAnyPermission(['qc.review.view', 'qc.dashboard.view']))
                                    <li><a class="dropdown-item" href="{{ route('qc.pending') }}"><i class="bi bi-hourglass-top me-2"></i>Pending Inspections</a></li>
                                @endif
                                @if($authUser->hasPermission('qc.history.view'))
                                    <li><a class="dropdown-item" href="{{ route('qc.history') }}"><i class="bi bi-clock-history me-2"></i>QC Decision History</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- Sales Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($salesPerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('sales*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-briefcase me-1"></i> Sales
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                @if($authUser->hasPermission('commercial.orders.view'))
                                    <li><a class="dropdown-item" href="{{ route('sales.orders.index') }}"><i class="bi bi-cart-check me-2"></i>Sales Orders</a></li>
                                @endif
                                @if($authUser->hasPermission('commercial.dispatch.view'))
                                    <li><a class="dropdown-item" href="{{ route('sales.dispatch.index') }}"><i class="bi bi-truck-flatbed me-2"></i>Dispatch Notes</a></li>
                                @endif
                                @if($authUser->hasPermission('commercial.sales_invoices.view'))
                                    <li><a class="dropdown-item" href="{{ route('sales.invoices.index') }}"><i class="bi bi-receipt me-2"></i>Sales Invoices</a></li>
                                @endif
                                @if($authUser->hasAnyPermission(['commercial.orders.view', 'commercial.dispatch.view', 'commercial.sales_invoices.view']) && $authUser->hasAnyPermission(['commercial.purchase_invoices.view', 'commercial.payments.view', 'commercial.notes.view']))
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                                @if($authUser->hasPermission('commercial.purchase_invoices.view'))
                                    <li><a class="dropdown-item" href="{{ route('sales.purchase-invoices.index') }}"><i class="bi bi-receipt-cutoff me-2"></i>Purchase Invoices</a></li>
                                @endif
                                @if($authUser->hasPermission('commercial.payments.view'))
                                    <li><a class="dropdown-item" href="{{ route('sales.payments.index') }}"><i class="bi bi-cash-stack me-2"></i>Supplier Payments</a></li>
                                    <li><a class="dropdown-item" href="{{ route('sales.receipts.index') }}"><i class="bi bi-wallet2 me-2"></i>Customer Receipts</a></li>
                                @endif
                                @if($authUser->hasPermission('commercial.notes.view'))
                                    <li><a class="dropdown-item" href="{{ route('sales.notes.index') }}"><i class="bi bi-file-diff me-2"></i>Credit & Debit Notes</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- Costing Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($costingPerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('costing*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-calculator me-1"></i> Costing
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                @if($authUser->hasPermission('costing.expenses.view'))
                                    <li><a class="dropdown-item" href="{{ route('costing.expenses.index') }}"><i class="bi bi-cash-coin me-2"></i>Monthly Plant Expenses</a></li>
                                @endif
                                @if($authUser->hasPermission('costing.allocation.view'))
                                    <li><a class="dropdown-item" href="{{ route('costing.allocation.index') }}"><i class="bi bi-pie-chart me-2"></i>Overhead Expense Allocation</a></li>
                                @endif
                                @if($authUser->hasPermission('costing.batch_cost.view'))
                                    <li><a class="dropdown-item" href="{{ route('costing.batch-cost.index') }}"><i class="bi bi-bar-chart-line me-2"></i>Batch Cost Summary</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- Reports Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($reportPerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('reports*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-graph-up me-1"></i> Reports
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                @if($authUser->hasPermission('reports.production.view'))
                                    <li><a class="dropdown-item" href="{{ route('reports.production') }}"><i class="bi bi-file-earmark-bar-graph me-2"></i>Production Reports</a></li>
                                @endif
                                @if($authUser->hasPermission('reports.material.view'))
                                    <li><a class="dropdown-item" href="{{ route('reports.material') }}"><i class="bi bi-box me-2"></i>Material & Stock Reports</a></li>
                                @endif
                                @if($authUser->hasPermission('reports.qc.view'))
                                    <li><a class="dropdown-item" href="{{ route('reports.qc') }}"><i class="bi bi-shield-check me-2"></i>QC Analysis Reports</a></li>
                                @endif
                                @if($authUser->hasPermission('reports.recovery.view'))
                                    <li><a class="dropdown-item" href="{{ route('reports.recovery') }}"><i class="bi bi-recycle me-2"></i>Solvent Recovery Reports</a></li>
                                @endif
                                @if($authUser->hasPermission('reports.cost.view'))
                                    <li><a class="dropdown-item" href="{{ route('reports.cost') }}"><i class="bi bi-cash-coin me-2"></i>Cost & Variance Reports</a></li>
                                @endif
                                @if($authUser->hasPermission('reports.commercial.view'))
                                    <li><a class="dropdown-item" href="{{ route('reports.commercial') }}"><i class="bi bi-receipt me-2"></i>Sales, Purchase & GST</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- Roles & Access Dropdown -->
                    @if($authUser && $authUser->hasAnyPermission($accessPerms))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('admin*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-person-lock me-1"></i> Access
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                @if($authUser->hasPermission('admin.users.view'))
                                    <li><a class="dropdown-item" href="{{ route('admin.users.index') }}"><i class="bi bi-person-badge me-2"></i>User Master</a></li>
                                @endif
                                @if($authUser->hasPermission('admin.roles.view'))
                                    <li><a class="dropdown-item" href="{{ route('admin.roles.index') }}"><i class="bi bi-people me-2"></i>Role Master</a></li>
                                @endif
                                @if($authUser->hasPermission('admin.permissions.view'))
                                    <li><a class="dropdown-item" href="{{ route('admin.permissions.index') }}"><i class="bi bi-grid-3x3 me-2"></i>Permission Matrix</a></li>
                                @endif
                                @if($authUser->hasPermission('admin.audit.view'))
                                    <li><a class="dropdown-item" href="{{ route('admin.audit-trail.index') }}"><i class="bi bi-clock-history me-2"></i>Audit Trail (Immutable)</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                </ul>

                <!-- Right Header Actions -->
                <div class="d-flex align-items-center gap-1">

                    <!-- 1. Notification Bell -->
                    @php
                        $unreadNotifCount = $authUser ? $authUser->notifications()->where('is_read', false)->count() : 0;
                    @endphp
                    <a href="{{ route('notifications.index') }}" class="header-icon-btn position-relative"
                        title="Notifications">
                        <i class="bi bi-bell"></i>
                        @if($unreadNotifCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size:0.65rem;">{{ $unreadNotifCount }}</span>
                        @endif
                    </a>

                    <!-- 2. Profile icon -->
                    @if($authUser)
                        <div class="dropdown">
                            <a href="#" class="header-icon-btn" data-bs-toggle="dropdown" aria-expanded="false"
                                title="{{ $authUser->name }}">
                                @if($authUser->profile_image)
                                    <img src="{{ Storage::url($authUser->profile_image) }}" alt="Avatar">
                                @else
                                    <i class="bi bi-person-circle"></i>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva dropdown-menu-end shadow-lg">
                                <li class="dropdown-header">
                                    <div class="fw-bold text-dark">{{ $authUser->name }}</div>
                                    <div class="small text-muted">
                                        {{ $authUser->role ? $authUser->role->role_name : 'User' }}</div>
                                    <div class="small text-muted">{{ $authUser->email }}</div>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="{{ route('profile') }}"><i
                                            class="bi bi-person me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="{{ route('password') }}"><i
                                            class="bi bi-key me-2"></i>Change Password</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger fw-semibold" href="javascript:void(0)"
                                        onclick="confirmLogout()">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @endif

                    <!-- 3. Settings icon -->
                    @if($authUser && $authUser->hasAnyPermission($settingsPerms))
                        <div class="dropdown">
                            <a href="#" class="header-icon-btn" data-bs-toggle="dropdown" aria-expanded="false"
                                title="Settings">
                                <i class="bi bi-gear"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva dropdown-menu-end shadow-lg">
                                <li class="dropdown-header text-uppercase text-muted small fw-bold">Settings</li>
                                <li><a class="dropdown-item" href="{{ route('settings.company') }}"><i
                                            class="bi bi-building me-2"></i>Company Settings</a></li>
                                <li><a class="dropdown-item" href="{{ route('settings.system') }}"><i
                                            class="bi bi-gear me-2"></i>System Settings</a></li>
                                <li><a class="dropdown-item" href="{{ route('settings.document-numbering') }}"><i
                                            class="bi bi-123 me-2"></i>Document Numbering</a></li>
                            </ul>
                        </div>
                    @endif

                    <!-- 4. Fullscreen -->
                    <button type="button" class="header-icon-btn" onclick="toggleFullscreen()" title="Full screen">
                        <i class="bi bi-arrows-fullscreen"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- ========================================================= -->
    <!-- MOBILE TOP BAR (Visible on < 992px)                       -->
    <!-- ========================================================= -->
    <div class="d-flex d-lg-none align-items-center justify-content-between sattva-mobile-topbar px-3 py-2 sticky-top">
        <button class="btn btn-sm btn-outline-light border-0" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#mobileSidebar">
            <i class="bi bi-list fs-3"></i>
        </button>
        <a class="navbar-brand-sattva text-decoration-none" href="{{ route('dashboard') }}">
            <div class="brand-logo-badge" style="height: 32px; padding: 2px 4px;">
                <img src="{{ asset('images/sattva-logo.png') }}" alt="Sattva" style="height: 26px;">
            </div>
            <div class="brand-text-wrapper">
                <span class="brand-name" style="font-size: 0.95rem;">SATTVA</span>
                <span class="brand-tag" style="font-size: 0.62rem;">BOTANICAL</span>
            </div>
        </a>
        <div class="d-flex align-items-center gap-1">
            <a href="{{ route('notifications.index') }}"
                class="btn btn-sm btn-outline-light border-0 position-relative p-2">
                <i class="bi bi-bell fs-5"></i>
                @if(isset($unreadNotifCount) && $unreadNotifCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                        style="font-size:0.6rem;">{{ $unreadNotifCount }}</span>
                @endif
            </a>
            @if($authUser)
                <a href="javascript:void(0)" onclick="confirmLogout()"
                    class="btn btn-sm btn-outline-danger border-0 p-2 text-white">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </a>
            @endif
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MOBILE LEFT OFFCANVAS SIDEBAR                             -->
    <!-- ========================================================= -->
    <div class="offcanvas offcanvas-start offcanvas-sattva d-lg-none" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header border-bottom border-secondary border-opacity-25 py-3">
            <div class="d-flex align-items-center gap-2">
                <div class="brand-logo-badge" style="height: 34px; padding: 2px 5px;">
                    <img src="{{ asset('images/sattva-logo.png') }}" alt="Sattva" style="height: 28px;">
                </div>
                <div class="brand-text-wrapper">
                    <span class="brand-name" style="font-size: 1rem;">SATTVA</span>
                    <span class="brand-tag" style="font-size: 0.65rem;">BOTANICAL</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="accordion accordion-flush" id="mobileMenuAccordion">
                <!-- Dashboard -->
                <div class="p-2 border-bottom border-secondary border-opacity-10">
                    <a href="{{ route('dashboard') }}"
                        class="d-flex align-items-center gap-2 text-decoration-none text-white px-3 py-2 rounded {{ request()->routeIs('dashboard') ? 'bg-success' : '' }}">
                        <i class="bi bi-grid-fill"></i>
                        <span class="fw-medium">Dashboard</span>
                    </a>
                </div>

                <!-- Masters Mobile -->
                @if($authUser && $authUser->hasAnyPermission($allMasterPerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('masters*') ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#mMasters">
                                <i class="bi bi-layers-fill me-2 text-success"></i> Masters
                            </button>
                        </h2>
                        <div id="mMasters" class="accordion-collapse collapse {{ request()->is('masters*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                @if($authUser->hasPermission('masters.plants.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.plants.index') }}">Plant Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.departments.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.departments.index') }}">Department Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.warehouses.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.warehouses.index') }}">Warehouse Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.locations.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.locations.index') }}">Storage Locations</a>
                                @endif
                                @if($authUser->hasPermission('masters.uoms.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.uoms.index') }}">UOM Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.categories.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.categories.index') }}">Product Categories</a>
                                @endif
                                @if($authUser->hasPermission('masters.products.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.products.index') }}">Product Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.materials.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.materials.index') }}">Material Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.suppliers.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.suppliers.index') }}">Supplier Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.customers.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.customers.index') }}">Customer Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.stages.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.stages.index') }}">Stage Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.activities.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.activities.index') }}">Activity Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.parameters.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.parameters.index') }}">Parameter Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.equipments.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.equipments.index') }}">Equipment Master</a>
                                @endif
                                @if($authUser->hasPermission('masters.qc_tests.view'))
                                    <a class="sidebar-sublink" href="{{ route('masters.qc-tests.index') }}">QC Test Master</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Recipe Mobile -->
                @if($authUser && $authUser->hasAnyPermission($recipePerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('recipe*') ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#mRecipe">
                                <i class="bi bi-journal-text me-2 text-success"></i> Recipe SOP
                            </button>
                        </h2>
                        <div id="mRecipe" class="accordion-collapse collapse {{ request()->is('recipe*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                <a class="sidebar-sublink" href="{{ route('recipe.index') }}">All Recipes</a>
                                @if($authUser->hasPermission('recipe.recipes.add'))
                                    <a class="sidebar-sublink" href="{{ route('recipe.create') }}">Create Recipe</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Purchase Mobile -->
                @if($authUser && $authUser->hasAnyPermission($purchasePerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('purchase*') ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#mPurchase">
                                <i class="bi bi-cart3 me-2 text-success"></i> Purchase
                            </button>
                        </h2>
                        <div id="mPurchase"
                            class="accordion-collapse collapse {{ request()->is('purchase*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                @if($authUser->hasPermission('purchase.requisitions.view'))
                                    <a class="sidebar-sublink" href="{{ route('purchase.requisitions.index') }}">Requisitions</a>
                                @endif
                                @if($authUser->hasPermission('purchase.orders.view'))
                                    <a class="sidebar-sublink" href="{{ route('purchase.orders.index') }}">Purchase Orders</a>
                                @endif
                                @if($authUser->hasPermission('purchase.grn.view'))
                                    <a class="sidebar-sublink" href="{{ route('purchase.grn.index') }}">GRN & Incoming QC</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Inventory Mobile -->
                @if($authUser && $authUser->hasAnyPermission($inventoryPerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('inventory*') ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#mInventory">
                                <i class="bi bi-box-fill me-2 text-success"></i> Inventory
                            </button>
                        </h2>
                        <div id="mInventory"
                            class="accordion-collapse collapse {{ request()->is('inventory*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                <a class="sidebar-sublink" href="{{ route('inventory.dashboard') }}">Inventory Dashboard</a>
                                @if($authUser->hasPermission('inventory.stock.view'))
                                    <a class="sidebar-sublink" href="{{ route('inventory.stock.index') }}">Stock Summary</a>
                                    <a class="sidebar-sublink" href="{{ route('inventory.stock.lots') }}">Lot-wise Stock</a>
                                    <a class="sidebar-sublink" href="{{ route('inventory.stock.ledger') }}">Stock Ledger</a>
                                @endif
                                @if($authUser->hasPermission('inventory.stock.add'))
                                    <a class="sidebar-sublink" href="{{ route('inventory.stock.opening-entry-form') }}">Opening Entry</a>
                                @endif
                                @if($authUser->hasPermission('inventory.transfers.view'))
                                    <a class="sidebar-sublink" href="{{ route('inventory.transfers.index') }}">Stock Transfers</a>
                                @endif
                                @if($authUser->hasPermission('inventory.adjustments.view'))
                                    <a class="sidebar-sublink" href="{{ route('inventory.adjustments.index') }}">Adjustments</a>
                                @endif
                                @if($authUser->hasPermission('inventory.wip.view'))
                                    <a class="sidebar-sublink" href="{{ route('inventory.wip.index') }}">WIP Records</a>
                                @endif
                                @if($authUser->hasPermission('inventory.recovery.view'))
                                    <a class="sidebar-sublink" href="{{ route('inventory.recovery.index') }}">Solvent Recovery</a>
                                @endif
                                @if($authUser->hasPermission('inventory.waste.view'))
                                    <a class="sidebar-sublink" href="{{ route('inventory.waste.index') }}">Waste Records</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Production Mobile -->
                @if($authUser && $authUser->hasAnyPermission($productionPerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('production*') ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#mProduction">
                                <i class="bi bi-gear-wide-connected me-2 text-success"></i> Production
                            </button>
                        </h2>
                        <div id="mProduction"
                            class="accordion-collapse collapse {{ request()->is('production*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                <a class="sidebar-sublink" href="{{ route('production.dashboard') }}">Production Dashboard</a>
                                @if($authUser->hasPermission('production.plans.view'))
                                    <a class="sidebar-sublink" href="{{ route('production.plans.index') }}">Production Plans</a>
                                @endif
                                @if($authUser->hasPermission('production.batches.view'))
                                    <a class="sidebar-sublink" href="{{ route('production.batches.index') }}">Batch Management</a>
                                @endif
                                @if($authUser->hasPermission('production.rework.view'))
                                    <a class="sidebar-sublink" href="{{ route('production.rework.index') }}">Rework Batches</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- QC Mobile -->
                @if($authUser && $authUser->hasAnyPermission($qcPerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('qc*') ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#mQc">
                                <i class="bi bi-shield-check me-2 text-success"></i> Quality Control
                            </button>
                        </h2>
                        <div id="mQc" class="accordion-collapse collapse {{ request()->is('qc*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                @if($authUser->hasAnyPermission(['qc.dashboard.view', 'qc.review.view']))
                                    <a class="sidebar-sublink" href="{{ route('qc.dashboard') }}">QC Dashboard</a>
                                @endif
                                @if($authUser->hasAnyPermission(['qc.review.view', 'qc.dashboard.view']))
                                    <a class="sidebar-sublink" href="{{ route('qc.pending') }}">Pending Inspections</a>
                                @endif
                                @if($authUser->hasPermission('qc.history.view'))
                                    <a class="sidebar-sublink" href="{{ route('qc.history') }}">QC History</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Sales Mobile -->
                @if($authUser && $authUser->hasAnyPermission($salesPerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('sales*') ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#mSales">
                                <i class="bi bi-briefcase me-2 text-success"></i> Sales & Commercial
                            </button>
                        </h2>
                        <div id="mSales" class="accordion-collapse collapse {{ request()->is('sales*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                @if($authUser->hasPermission('commercial.orders.view'))
                                    <a class="sidebar-sublink" href="{{ route('sales.orders.index') }}">Sales Orders</a>
                                @endif
                                @if($authUser->hasPermission('commercial.dispatch.view'))
                                    <a class="sidebar-sublink" href="{{ route('sales.dispatch.index') }}">Dispatch Notes</a>
                                @endif
                                @if($authUser->hasPermission('commercial.sales_invoices.view'))
                                    <a class="sidebar-sublink" href="{{ route('sales.invoices.index') }}">Sales Invoices</a>
                                @endif
                                @if($authUser->hasPermission('commercial.purchase_invoices.view'))
                                    <a class="sidebar-sublink" href="{{ route('sales.purchase-invoices.index') }}">Purchase Invoices</a>
                                @endif
                                @if($authUser->hasPermission('commercial.payments.view'))
                                    <a class="sidebar-sublink" href="{{ route('sales.payments.index') }}">Supplier Payments</a>
                                    <a class="sidebar-sublink" href="{{ route('sales.receipts.index') }}">Customer Receipts</a>
                                @endif
                                @if($authUser->hasPermission('commercial.notes.view'))
                                    <a class="sidebar-sublink" href="{{ route('sales.notes.index') }}">Credit / Debit Notes</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Costing Mobile -->
                @if($authUser && $authUser->hasAnyPermission($costingPerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('costing*') ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#mCosting">
                                <i class="bi bi-calculator me-2 text-success"></i> Costing
                            </button>
                        </h2>
                        <div id="mCosting" class="accordion-collapse collapse {{ request()->is('costing*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                @if($authUser->hasPermission('costing.expenses.view'))
                                    <a class="sidebar-sublink" href="{{ route('costing.expenses.index') }}">Monthly Expenses</a>
                                @endif
                                @if($authUser->hasPermission('costing.allocation.view'))
                                    <a class="sidebar-sublink" href="{{ route('costing.allocation.index') }}">Expense Allocation</a>
                                @endif
                                @if($authUser->hasPermission('costing.batch_cost.view'))
                                    <a class="sidebar-sublink" href="{{ route('costing.batch-cost.index') }}">Batch Cost Summary</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Reports Mobile -->
                @if($authUser && $authUser->hasAnyPermission($reportPerms))
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('reports*') ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#mReports">
                                <i class="bi bi-graph-up me-2 text-success"></i> Reports
                            </button>
                        </h2>
                        <div id="mReports" class="accordion-collapse collapse {{ request()->is('reports*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                @if($authUser->hasPermission('reports.production.view'))
                                    <a class="sidebar-sublink" href="{{ route('reports.production') }}">Production Reports</a>
                                @endif
                                @if($authUser->hasPermission('reports.material.view'))
                                    <a class="sidebar-sublink" href="{{ route('reports.material') }}">Material Reports</a>
                                @endif
                                @if($authUser->hasPermission('reports.qc.view'))
                                    <a class="sidebar-sublink" href="{{ route('reports.qc') }}">QC Reports</a>
                                @endif
                                @if($authUser->hasPermission('reports.recovery.view'))
                                    <a class="sidebar-sublink" href="{{ route('reports.recovery') }}">Solvent Recovery Reports</a>
                                @endif
                                @if($authUser->hasPermission('reports.cost.view'))
                                    <a class="sidebar-sublink" href="{{ route('reports.cost') }}">Cost Reports</a>
                                @endif
                                @if($authUser->hasPermission('reports.commercial.view'))
                                    <a class="sidebar-sublink" href="{{ route('reports.commercial') }}">Commercial & GST</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if($authUser && $authUser->hasAnyPermission($accessPerms))
                    <!-- Admin Mobile -->
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('admin*') ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#mAdmin">
                                <i class="bi bi-person-lock me-2 text-success"></i> Roles & Access
                            </button>
                        </h2>
                        <div id="mAdmin" class="accordion-collapse collapse {{ request()->is('admin*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                @if($authUser->hasPermission('admin.users.view'))
                                    <a class="sidebar-sublink" href="{{ route('admin.users.index') }}">User Master</a>
                                @endif
                                @if($authUser->hasPermission('admin.roles.view'))
                                    <a class="sidebar-sublink" href="{{ route('admin.roles.index') }}">Role Master</a>
                                @endif
                                @if($authUser->hasPermission('admin.permissions.view'))
                                    <a class="sidebar-sublink" href="{{ route('admin.permissions.index') }}">Permission Matrix</a>
                                @endif
                                @if($authUser->hasPermission('admin.audit.view'))
                                    <a class="sidebar-sublink" href="{{ route('admin.audit-trail.index') }}">Audit Trail</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if($authUser && $authUser->hasAnyPermission($settingsPerms))
                    <!-- Settings Mobile -->
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ request()->is('settings*') ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse" data-bs-target="#mSettings">
                                <i class="bi bi-sliders2 me-2 text-success"></i> Settings
                            </button>
                        </h2>
                        <div id="mSettings"
                            class="accordion-collapse collapse {{ request()->is('settings*') ? 'show' : '' }}"
                            data-bs-parent="#mobileMenuAccordion">
                            <div class="accordion-body">
                                <a class="sidebar-sublink" href="{{ route('settings.company') }}">Company Settings</a>
                                <a class="sidebar-sublink" href="{{ route('settings.system') }}">System Settings</a>
                                <a class="sidebar-sublink" href="{{ route('settings.document-numbering') }}">Document Numbering</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MAIN CONTENT AREA                                         -->
    <!-- ========================================================= -->
    <div class="main-content">
        <!-- Toast Notification Area -->
        <div class="toast-container position-fixed top-0 end-0 p-3" id="globalToastContainer" style="z-index: 1095;">
            @if(session('success'))
                <div class="toast show align-items-center text-bg-success border-0 shadow" role="alert"
                    aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="toast show align-items-center text-bg-danger border-0 shadow" role="alert" aria-live="assertive"
                    aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="toast show align-items-center text-bg-warning border-0 shadow" role="alert"
                    aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-circle-fill fs-5"></i>
                            <span>{{ session('warning') }}</span>
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="toast show align-items-center text-bg-danger border-0 shadow" role="alert" aria-live="assertive"
                    aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <div class="fw-bold mb-1"><i class="bi bi-x-circle-fill me-1"></i> Please correct the errors:
                            </div>
                            <ul class="mb-0 ps-3 small">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast"></button>
                    </div>
                </div>
            @endif
        </div>

        @yield('content')
    </div>

    <!-- Square Confirmation Banner Modal (380x380 px) -->
    @include('components.confirm-modal')

    <!-- Hidden Logout Form -->
    <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>

    <script>
        // =========================================================================
        // Desktop Top-Menu Dropdowns (Teleport & Fixed Position Above Scrollbar)
        // =========================================================================
        (function () {
            const scrollMenu = document.querySelector('.sattva-scroll-menu');
            if (!scrollMenu) return;

            let activeMenu = null;
            let activeToggle = null;
            let originalParent = null;
            let hoverTimeout = null;

            function returnActiveMenu() {
                if (activeMenu && originalParent) {
                    window.removeEventListener('scroll', updatePosition, true);
                    window.removeEventListener('resize', updatePosition);
                    activeMenu.style.position = '';
                    activeMenu.style.top = '';
                    activeMenu.style.left = '';
                    activeMenu.style.zIndex = '';
                    activeMenu.style.display = '';
                    activeMenu.style.visibility = '';
                    activeMenu.style.opacity = '';
                    activeMenu.style.margin = '';
                    if (activeMenu.parentElement !== originalParent) {
                        originalParent.appendChild(activeMenu);
                    }
                    activeMenu = null;
                    activeToggle = null;
                    originalParent = null;
                }
            }

            function updatePosition() {
                if (!activeMenu || !activeToggle) return;
                const rect = activeToggle.getBoundingClientRect();
                activeMenu.style.position = 'fixed';
                activeMenu.style.top = (rect.bottom + 4) + 'px';
                activeMenu.style.zIndex = '100050';
                activeMenu.style.display = 'block';
                activeMenu.style.visibility = 'visible';
                activeMenu.style.opacity = '1';
                activeMenu.style.margin = '0';

                const menuWidth = Math.max(activeMenu.offsetWidth || 230, 220);
                let left = rect.left;
                if (left + menuWidth > window.innerWidth - 10) {
                    left = window.innerWidth - menuWidth - 10;
                }
                if (left < 10) left = 10;
                activeMenu.style.left = left + 'px';
            }

            document.querySelectorAll('.sattva-scroll-menu .dropdown').forEach(function (dropdownEl) {
                const toggle = dropdownEl.querySelector('[data-bs-toggle="dropdown"]');
                const menu = dropdownEl.querySelector('.dropdown-menu');
                if (!toggle || !menu) return;

                dropdownEl.addEventListener('show.bs.dropdown', function () {
                    returnActiveMenu();
                    activeToggle = toggle;
                    activeMenu = menu;
                    originalParent = dropdownEl;

                    // Teleport menu to document.body so it floats completely above all scroll containers
                    document.body.appendChild(menu);
                    updatePosition();
                    requestAnimationFrame(updatePosition);

                    window.addEventListener('scroll', updatePosition, true);
                    window.addEventListener('resize', updatePosition);
                });

                dropdownEl.addEventListener('hidden.bs.dropdown', function () {
                    returnActiveMenu();
                });

                // Desktop Hover interaction (screens >= 992px)
                dropdownEl.addEventListener('mouseenter', function () {
                    if (window.innerWidth < 992) return;
                    clearTimeout(hoverTimeout);
                    const inst = bootstrap.Dropdown.getOrCreateInstance(toggle);
                    inst.show();
                });

                dropdownEl.addEventListener('mouseleave', function () {
                    if (window.innerWidth < 992) return;
                    hoverTimeout = setTimeout(function () {
                        if (activeMenu && (activeMenu.matches(':hover') || activeMenu.contains(document.activeElement))) {
                            return;
                        }
                        const inst = bootstrap.Dropdown.getInstance(toggle);
                        if (inst) inst.hide();
                        returnActiveMenu();
                    }, 180);
                });
            });

            // Keep menu open while hovering over the teleported menu itself
            document.addEventListener('mouseover', function (e) {
                if (activeMenu && (activeMenu === e.target || activeMenu.contains(e.target))) {
                    clearTimeout(hoverTimeout);
                }
            });

            document.addEventListener('mouseout', function (e) {
                if (activeMenu && (activeMenu === e.target || activeMenu.contains(e.target))) {
                    if (!activeMenu.contains(e.relatedTarget) && activeToggle !== e.relatedTarget && !activeToggle.contains(e.relatedTarget)) {
                        hoverTimeout = setTimeout(function () {
                            if (activeToggle) {
                                const inst = bootstrap.Dropdown.getInstance(activeToggle);
                                if (inst) inst.hide();
                            }
                            returnActiveMenu();
                        }, 180);
                    }
                }
            });

            // Close on horizontal scrolling of the navbar menu
            scrollMenu.addEventListener('scroll', function () {
                if (activeToggle) {
                    const inst = bootstrap.Dropdown.getInstance(activeToggle);
                    if (inst) inst.hide();
                    returnActiveMenu();
                }
            });

            // Click outside handler
            document.addEventListener('click', function (e) {
                if (activeMenu && activeToggle) {
                    if (!activeMenu.contains(e.target) && !activeToggle.contains(e.target)) {
                        const inst = bootstrap.Dropdown.getInstance(activeToggle);
                        if (inst) inst.hide();
                        returnActiveMenu();
                    }
                }
            }, true);
        })();

        function confirmLogout() {
            openConfirmModal({
                title: 'Confirm Logout',
                message: 'Do you want to sign out of Sattva ERP?',
                icon: 'bi-box-arrow-right',
                iconColor: '#dc2626',
                confirmText: 'Yes, Logout',
                confirmClass: 'btn-danger',
                onConfirm: function () {
                    document.getElementById('logoutForm').submit();
                }
            });
        }
    </script>

    @stack('scripts')
</body>

</html>
