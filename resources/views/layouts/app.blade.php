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
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('masters*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-layers-fill me-1"></i> Masters
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li class="dropdown-header text-uppercase text-muted small fw-bold">Company Setup</li>
                            <li><a class="dropdown-item" href="{{ route('masters.plants.index') }}"><i
                                        class="bi bi-building me-2"></i>Plant Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.departments.index') }}"><i
                                        class="bi bi-diagram-3 me-2"></i>Department Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.warehouses.index') }}"><i
                                        class="bi bi-house-door me-2"></i>Warehouse Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.locations.index') }}"><i
                                        class="bi bi-geo-alt me-2"></i>Storage Locations & Bins</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li class="dropdown-header text-uppercase text-muted small fw-bold">Items & Partners</li>
                            <li><a class="dropdown-item" href="{{ route('masters.uoms.index') }}"><i
                                        class="bi bi-rulers me-2"></i>UOM Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.categories.index') }}"><i
                                        class="bi bi-tags me-2"></i>Product Categories</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.products.index') }}"><i
                                        class="bi bi-box-seam me-2"></i>Product Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.materials.index') }}"><i
                                        class="bi bi-boxes me-2"></i>Material Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.suppliers.index') }}"><i
                                        class="bi bi-truck me-2"></i>Supplier Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.customers.index') }}"><i
                                        class="bi bi-people me-2"></i>Customer Master</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li class="dropdown-header text-uppercase text-muted small fw-bold">Processing Masters</li>
                            <li><a class="dropdown-item" href="{{ route('masters.stages.index') }}"><i
                                        class="bi bi-collection-play me-2"></i>Stage Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.activities.index') }}"><i
                                        class="bi bi-activity me-2"></i>Activity Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.parameters.index') }}"><i
                                        class="bi bi-sliders me-2"></i>Parameter Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.equipments.index') }}"><i
                                        class="bi bi-tools me-2"></i>Equipment Master</a></li>
                            <li><a class="dropdown-item" href="{{ route('masters.qc-tests.index') }}"><i
                                        class="bi bi-patch-check me-2"></i>QC Test Master</a></li>
                        </ul>
                    </li>

                    <!-- Recipe Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('recipe*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-journal-text me-1"></i> Recipe
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('recipe.index') }}"><i
                                        class="bi bi-list-columns-reverse me-2"></i>All Recipes</a></li>
                            <li><a class="dropdown-item" href="{{ route('recipe.create') }}"><i
                                        class="bi bi-plus-circle me-2"></i>New Recipe SOP</a></li>
                        </ul>
                    </li>

                    <!-- Purchase Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('purchase*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-cart3 me-1"></i> Purchase
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('purchase.requisitions.index') }}"><i
                                        class="bi bi-file-earmark-text me-2"></i>Purchase Requisitions</a></li>
                            <li><a class="dropdown-item" href="{{ route('purchase.orders.index') }}"><i
                                        class="bi bi-file-earmark-check me-2"></i>Purchase Orders (PO)</a></li>
                            <li><a class="dropdown-item" href="{{ route('purchase.grn.index') }}"><i
                                        class="bi bi-box-arrow-in-down me-2"></i>GRN & Incoming QC</a></li>
                        </ul>
                    </li>

                    <!-- Inventory Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('inventory*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-box-fill me-1"></i> Inventory
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('inventory.dashboard') }}"><i
                                        class="bi bi-speedometer2 me-2"></i>Inventory Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.stock.index') }}"><i
                                        class="bi bi-table me-2"></i>Current Stock Summary</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.stock.lots') }}"><i
                                        class="bi bi-upc-scan me-2"></i>Lot-wise Stock</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.stock.ledger') }}"><i
                                        class="bi bi-journal-bookmark me-2"></i>Stock Ledger (Append-Only)</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.stock.opening-entry-form') }}"><i
                                        class="bi bi-plus-square-dotted me-2"></i>Opening Stock Entry</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="{{ route('inventory.transfers.index') }}"><i
                                        class="bi bi-arrow-left-right me-2"></i>Stock Transfers</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.adjustments.index') }}"><i
                                        class="bi bi-pencil-square me-2"></i>Stock Adjustments</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.wip.index') }}"><i
                                        class="bi bi-hourglass-split me-2"></i>WIP Records</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.recovery.index') }}"><i
                                        class="bi bi-recycle me-2"></i>Solvent Recovery Lots</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.waste.index') }}"><i
                                        class="bi bi-trash3 me-2"></i>By-Product & Waste Disposal</a></li>
                        </ul>
                    </li>

                    <!-- Production Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('production*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-gear-wide-connected me-1"></i> Production
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('production.dashboard') }}"><i
                                        class="bi bi-speedometer me-2"></i>Production Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('production.plans.index') }}"><i
                                        class="bi bi-calendar-check me-2"></i>Production Plans</a></li>
                            <li><a class="dropdown-item" href="{{ route('production.batches.index') }}"><i
                                        class="bi bi-collection-fill me-2"></i>Batch Management</a></li>
                            <li><a class="dropdown-item" href="{{ route('production.rework.index') }}"><i
                                        class="bi bi-arrow-repeat me-2"></i>Rework Batches</a></li>
                        </ul>
                    </li>

                    <!-- QC Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('qc*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-shield-check me-1"></i> QC
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('qc.dashboard') }}"><i
                                        class="bi bi-speedometer2 me-2"></i>QC Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('qc.pending') }}"><i
                                        class="bi bi-hourglass-top me-2"></i>Pending Inspections</a></li>
                            <li><a class="dropdown-item" href="{{ route('qc.history') }}"><i
                                        class="bi bi-clock-history me-2"></i>QC Decision History</a></li>
                        </ul>
                    </li>

                    <!-- Sales Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('sales*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-briefcase me-1"></i> Sales
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('sales.orders.index') }}"><i
                                        class="bi bi-cart-check me-2"></i>Sales Orders</a></li>
                            <li><a class="dropdown-item" href="{{ route('sales.dispatch.index') }}"><i
                                        class="bi bi-truck-flatbed me-2"></i>Dispatch Notes</a></li>
                            <li><a class="dropdown-item" href="{{ route('sales.invoices.index') }}"><i
                                        class="bi bi-receipt me-2"></i>Sales Invoices</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="{{ route('sales.purchase-invoices.index') }}"><i
                                        class="bi bi-receipt-cutoff me-2"></i>Purchase Invoices</a></li>
                            <li><a class="dropdown-item" href="{{ route('sales.payments.index') }}"><i
                                        class="bi bi-cash-stack me-2"></i>Supplier Payments</a></li>
                            <li><a class="dropdown-item" href="{{ route('sales.receipts.index') }}"><i
                                        class="bi bi-wallet2 me-2"></i>Customer Receipts</a></li>
                            <li><a class="dropdown-item" href="{{ route('sales.notes.index') }}"><i
                                        class="bi bi-file-diff me-2"></i>Credit & Debit Notes</a></li>
                        </ul>
                    </li>

                    <!-- Costing Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('costing*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-calculator me-1"></i> Costing
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('costing.expenses.index') }}"><i
                                        class="bi bi-cash-coin me-2"></i>Monthly Plant Expenses</a></li>
                            <li><a class="dropdown-item" href="{{ route('costing.allocation.index') }}"><i
                                        class="bi bi-pie-chart me-2"></i>Overhead Expense Allocation</a></li>
                            <li><a class="dropdown-item" href="{{ route('costing.batch-cost.index') }}"><i
                                        class="bi bi-bar-chart-line me-2"></i>Batch Cost Summary</a></li>
                        </ul>
                    </li>

                    <!-- Reports Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('reports*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                            aria-expanded="false">
                            <i class="bi bi-graph-up me-1"></i> Reports
                        </a>
                        <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('reports.production') }}"><i
                                        class="bi bi-file-earmark-bar-graph me-2"></i>Production Reports</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.material') }}"><i
                                        class="bi bi-box me-2"></i>Material & Stock Reports</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.qc') }}"><i
                                        class="bi bi-shield-check me-2"></i>QC Analysis Reports</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.recovery') }}"><i
                                        class="bi bi-recycle me-2"></i>Solvent Recovery Reports</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.cost') }}"><i
                                        class="bi bi-cash-coin me-2"></i>Cost & Variance Reports</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.commercial') }}"><i
                                        class="bi bi-receipt me-2"></i>Sales, Purchase & GST</a></li>
                        </ul>
                    </li>

                    <!-- Roles & Access Dropdown -->
                    @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('admin.users.view')))
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('admin*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static"
                                aria-expanded="false">
                                <i class="bi bi-person-lock me-1"></i> Access
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                <li><a class="dropdown-item" href="{{ route('admin.users.index') }}"><i
                                            class="bi bi-person-badge me-2"></i>User Master</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.roles.index') }}"><i
                                            class="bi bi-people me-2"></i>Role Master</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.permissions.index') }}"><i
                                            class="bi bi-grid-3x3 me-2"></i>Permission Matrix</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.audit-trail.index') }}"><i
                                            class="bi bi-clock-history me-2"></i>Audit Trail (Immutable)</a></li>
                            </ul>
                        </li>
                    @endif

                    <!-- Settings Dropdown -->
                    @if(auth()->check() && auth()->user()->isSuperAdmin())
                        <li class="nav-item dropdown">
                            <!-- <a class="nav-link nav-link-sattva dropdown-toggle {{ request()->is('settings*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-sliders2 me-1"></i> Settings
                            </a> -->
                            <ul class="dropdown-menu dropdown-menu-sattva shadow-lg">
                                <li><a class="dropdown-item" href="{{ route('settings.company') }}"><i
                                            class="bi bi-building me-2"></i>Company Settings</a></li>
                                <li><a class="dropdown-item" href="{{ route('settings.system') }}"><i
                                            class="bi bi-gear me-2"></i>System Settings</a></li>
                                <li><a class="dropdown-item" href="{{ route('settings.document-numbering') }}"><i
                                            class="bi bi-123 me-2"></i>Document Numbering</a></li>
                            </ul>
                        </li>
                    @endif
                </ul>

                <!-- Right Header Actions -->
                <div class="d-flex align-items-center gap-1">

                    <!-- 1. Notification Bell -->
                    @php
                        $unreadNotifCount = auth()->check() ? auth()->user()->notifications()->where('is_read', false)->count() : 0;
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
                    @if(auth()->check())
                        <div class="dropdown">
                            <a href="#" class="header-icon-btn" data-bs-toggle="dropdown" aria-expanded="false"
                                title="{{ auth()->user()->name }}">
                                @if(auth()->user()->profile_image)
                                    <img src="{{ Storage::url(auth()->user()->profile_image) }}" alt="Avatar">
                                @else
                                    <i class="bi bi-person-circle"></i>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-sattva dropdown-menu-end shadow-lg">
                                <li class="dropdown-header">
                                    <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                                    <div class="small text-muted">
                                        {{ auth()->user()->role ? auth()->user()->role->role_name : 'User' }}</div>
                                    <div class="small text-muted">{{ auth()->user()->email }}</div>
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
                    @if(auth()->check() && auth()->user()->isSuperAdmin())
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
            @if(auth()->check())
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
                            <a class="sidebar-sublink" href="{{ route('masters.plants.index') }}">Plant Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.departments.index') }}">Department
                                Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.warehouses.index') }}">Warehouse
                                Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.locations.index') }}">Storage
                                Locations</a>
                            <a class="sidebar-sublink" href="{{ route('masters.uoms.index') }}">UOM Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.categories.index') }}">Product
                                Categories</a>
                            <a class="sidebar-sublink" href="{{ route('masters.products.index') }}">Product Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.materials.index') }}">Material Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.suppliers.index') }}">Supplier Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.customers.index') }}">Customer Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.stages.index') }}">Stage Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.activities.index') }}">Activity
                                Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.parameters.index') }}">Parameter
                                Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.equipments.index') }}">Equipment
                                Master</a>
                            <a class="sidebar-sublink" href="{{ route('masters.qc-tests.index') }}">QC Test Master</a>
                        </div>
                    </div>
                </div>

                <!-- Recipe Mobile -->
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
                            <a class="sidebar-sublink" href="{{ route('recipe.create') }}">Create Recipe</a>
                        </div>
                    </div>
                </div>

                <!-- Purchase Mobile -->
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
                            <a class="sidebar-sublink"
                                href="{{ route('purchase.requisitions.index') }}">Requisitions</a>
                            <a class="sidebar-sublink" href="{{ route('purchase.orders.index') }}">Purchase Orders</a>
                            <a class="sidebar-sublink" href="{{ route('purchase.grn.index') }}">GRN & Incoming QC</a>
                        </div>
                    </div>
                </div>

                <!-- Inventory Mobile -->
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
                            <a class="sidebar-sublink" href="{{ route('inventory.stock.index') }}">Stock Summary</a>
                            <a class="sidebar-sublink" href="{{ route('inventory.stock.lots') }}">Lot-wise Stock</a>
                            <a class="sidebar-sublink" href="{{ route('inventory.stock.ledger') }}">Stock Ledger</a>
                            <a class="sidebar-sublink" href="{{ route('inventory.stock.opening-entry-form') }}">Opening
                                Entry</a>
                            <a class="sidebar-sublink" href="{{ route('inventory.transfers.index') }}">Stock
                                Transfers</a>
                            <a class="sidebar-sublink" href="{{ route('inventory.adjustments.index') }}">Adjustments</a>
                            <a class="sidebar-sublink" href="{{ route('inventory.wip.index') }}">WIP Records</a>
                            <a class="sidebar-sublink" href="{{ route('inventory.recovery.index') }}">Solvent
                                Recovery</a>
                            <a class="sidebar-sublink" href="{{ route('inventory.waste.index') }}">Waste Records</a>
                        </div>
                    </div>
                </div>

                <!-- Production Mobile -->
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
                            <a class="sidebar-sublink" href="{{ route('production.dashboard') }}">Production
                                Dashboard</a>
                            <a class="sidebar-sublink" href="{{ route('production.plans.index') }}">Production Plans</a>
                            <a class="sidebar-sublink" href="{{ route('production.batches.index') }}">Batch
                                Management</a>
                            <a class="sidebar-sublink" href="{{ route('production.rework.index') }}">Rework Batches</a>
                        </div>
                    </div>
                </div>

                <!-- QC Mobile -->
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
                            <a class="sidebar-sublink" href="{{ route('qc.dashboard') }}">QC Dashboard</a>
                            <a class="sidebar-sublink" href="{{ route('qc.pending') }}">Pending Inspections</a>
                            <a class="sidebar-sublink" href="{{ route('qc.history') }}">QC History</a>
                        </div>
                    </div>
                </div>

                <!-- Sales Mobile -->
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
                            <a class="sidebar-sublink" href="{{ route('sales.orders.index') }}">Sales Orders</a>
                            <a class="sidebar-sublink" href="{{ route('sales.dispatch.index') }}">Dispatch Notes</a>
                            <a class="sidebar-sublink" href="{{ route('sales.invoices.index') }}">Sales Invoices</a>
                            <a class="sidebar-sublink" href="{{ route('sales.purchase-invoices.index') }}">Purchase
                                Invoices</a>
                            <a class="sidebar-sublink" href="{{ route('sales.payments.index') }}">Supplier Payments</a>
                            <a class="sidebar-sublink" href="{{ route('sales.receipts.index') }}">Customer Receipts</a>
                            <a class="sidebar-sublink" href="{{ route('sales.notes.index') }}">Credit / Debit Notes</a>
                        </div>
                    </div>
                </div>

                <!-- Costing Mobile -->
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
                            <a class="sidebar-sublink" href="{{ route('costing.expenses.index') }}">Monthly Expenses</a>
                            <a class="sidebar-sublink" href="{{ route('costing.allocation.index') }}">Expense
                                Allocation</a>
                            <a class="sidebar-sublink" href="{{ route('costing.batch-cost.index') }}">Batch Cost
                                Summary</a>
                        </div>
                    </div>
                </div>

                <!-- Reports Mobile -->
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
                            <a class="sidebar-sublink" href="{{ route('reports.production') }}">Production Reports</a>
                            <a class="sidebar-sublink" href="{{ route('reports.material') }}">Material Reports</a>
                            <a class="sidebar-sublink" href="{{ route('reports.qc') }}">QC Reports</a>
                            <a class="sidebar-sublink" href="{{ route('reports.recovery') }}">Solvent Recovery
                                Reports</a>
                            <a class="sidebar-sublink" href="{{ route('reports.cost') }}">Cost Reports</a>
                            <a class="sidebar-sublink" href="{{ route('reports.commercial') }}">Commercial & GST</a>
                        </div>
                    </div>
                </div>

                @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('admin.users.view')))
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
                                <a class="sidebar-sublink" href="{{ route('admin.users.index') }}">User Master</a>
                                <a class="sidebar-sublink" href="{{ route('admin.roles.index') }}">Role Master</a>
                                <a class="sidebar-sublink" href="{{ route('admin.permissions.index') }}">Permission
                                    Matrix</a>
                                <a class="sidebar-sublink" href="{{ route('admin.audit-trail.index') }}">Audit Trail</a>
                            </div>
                        </div>
                    </div>
                @endif

                @if(auth()->check() && auth()->user()->isSuperAdmin())
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
                                <a class="sidebar-sublink" href="{{ route('settings.document-numbering') }}">Document
                                    Numbering</a>
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
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
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