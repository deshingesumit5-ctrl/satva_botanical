@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
<div class="container-fluid px-0">

    <!-- Top Greeting & Header Bar (Matching Screenshots 4 & 5) -->
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <div class="text-muted small fw-semibold">Sattva Botanical Manufacturing Unit</div>
            <h2 class="fw-bold mb-1" style="color: #0f172a;">Hello, {{ auth()->user()->name }}</h2>
            <p class="text-muted small mb-0">All the manufacturing, inventory, and order analytics shown below are live.</p>
        </div>

        <!-- Filter Row -->
        <div class="mt-3 mt-lg-0">
            <form action="{{ route('dashboard') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="small text-muted d-block">Customer :</label>
                    <select name="customer_id" class="form-select form-select-sm" style="min-width: 170px;">
                        <option value="">Select Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="small text-muted d-block">From Date :</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <label class="small text-muted d-block">To Date :</label>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-dark-filter btn-sm px-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    @if($customerId || $fromDate || $toDate)
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Row 1: Order Status & Stock Status Cards -->
    <div class="row g-4 mb-4">
        <!-- Order Status Card -->
        <div class="col-12 col-xl-8">
            <div class="card card-sattva h-100">
                <div class="card-sattva-header d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark"><i class="bi bi-cart3 text-primary me-2"></i>Order Status <i class="bi bi-info-circle text-muted ms-1 small" title="Current sales order pipeline"></i></span>
                    @if(auth()->user() && auth()->user()->hasPermission('commercial.orders.view'))
                        <a href="{{ route('sales.orders.index') }}" class="small text-decoration-none text-success fw-semibold">View All <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="card-sattva-body">
                    <div class="row g-3 text-center">
                        <!-- Draft -->
                        <div class="col-6 col-sm">
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="mb-2 text-warning fs-3"><i class="bi bi-pencil-square"></i></div>
                                <div class="text-muted small fw-medium">Draft</div>
                                <div class="fs-4 fw-bold text-dark mt-1">{{ $orderStatus['draft'] }}</div>
                            </div>
                        </div>
                        <!-- InProgress -->
                        <div class="col-6 col-sm">
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="mb-2 text-primary fs-3"><i class="bi bi-gear-wide-connected"></i></div>
                                <div class="text-muted small fw-medium">InProgress</div>
                                <div class="fs-4 fw-bold text-dark mt-1">{{ $orderStatus['in_progress'] }}</div>
                            </div>
                        </div>
                        <!-- Delivered -->
                        <div class="col-6 col-sm">
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="mb-2 text-info fs-3"><i class="bi bi-truck"></i></div>
                                <div class="text-muted small fw-medium">Delivered</div>
                                <div class="fs-4 fw-bold text-dark mt-1">{{ $orderStatus['delivered'] }}</div>
                            </div>
                        </div>
                        <!-- Complete -->
                        <div class="col-6 col-sm">
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="mb-2 text-success fs-3"><i class="bi bi-check2-circle"></i></div>
                                <div class="text-muted small fw-medium">Complete</div>
                                <div class="fs-4 fw-bold text-dark mt-1">{{ $orderStatus['complete'] }}</div>
                            </div>
                        </div>
                        <!-- Cancelled -->
                        <div class="col-6 col-sm">
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="mb-2 text-danger fs-3"><i class="bi bi-x-circle"></i></div>
                                <div class="text-muted small fw-medium">Cancelled</div>
                                <div class="fs-4 fw-bold text-dark mt-1">{{ $orderStatus['cancelled'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Status Card -->
        <div class="col-12 col-xl-4">
            <div class="card card-sattva h-100">
                <div class="card-sattva-header d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark"><i class="bi bi-boxes text-warning me-2"></i>Stock Status <i class="bi bi-info-circle text-muted ms-1 small" title="Inventory stock health"></i></span>
                    @if(auth()->user() && auth()->user()->hasPermission('inventory.stock.view'))
                        <a href="{{ route('inventory.stock.lots') }}" class="small text-decoration-none text-success fw-semibold">Inspect Lots <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="card-sattva-body">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="mb-2 text-primary fs-3"><i class="bi bi-box-seam"></i></div>
                                <div class="text-muted small fw-medium">Low Stock</div>
                                <div class="fs-4 fw-bold text-dark mt-1">{{ $stockStatus['low_stock'] }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="mb-2 text-danger fs-3"><i class="bi bi-exclamation-triangle"></i></div>
                                <div class="text-muted small fw-medium">Expired</div>
                                <div class="fs-4 fw-bold text-danger mt-1">{{ $stockStatus['expired'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: GRN Status Donut & Warehouse Occupancy Donut -->
    <div class="row g-4 mb-4">
        <!-- GRN Status Donut Card -->
        <div class="col-12 col-lg-6">
            <div class="card card-sattva h-100">
                <div class="card-sattva-header d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark"><i class="bi bi-box-arrow-in-down text-info me-2"></i>GRN Status <i class="bi bi-info-circle text-muted ms-1 small"></i></span>
                    @if(auth()->user() && auth()->user()->hasPermission('purchase.grn.view'))
                        <a href="{{ route('purchase.grn.index') }}" class="small text-decoration-none text-success fw-semibold">View GRNs <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="card-sattva-body d-flex flex-column flex-sm-row align-items-center justify-content-around">
                    <div style="width: 190px; height: 190px;">
                        <canvas id="grnChart"></canvas>
                    </div>
                    <div class="mt-3 mt-sm-0">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-circle p-2 bg-primary"> </span>
                            <span class="small fw-semibold">Draft GRNs:</span>
                            <span class="small fw-bold ms-auto">{{ $grnStatus['draft'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-circle p-2 bg-warning"> </span>
                            <span class="small fw-semibold">Pending QC:</span>
                            <span class="small fw-bold ms-auto">{{ $grnStatus['pending_qc'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-circle p-2 bg-success"> </span>
                            <span class="small fw-semibold">Completed:</span>
                            <span class="small fw-bold ms-auto">{{ $grnStatus['completed'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warehouse Occupancy Donut Card -->
        <div class="col-12 col-lg-6">
            <div class="card card-sattva h-100">
                <div class="card-sattva-header d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark"><i class="bi bi-pie-chart text-success me-2"></i>Warehouse Occupancy By Lots <i class="bi bi-info-circle text-muted ms-1 small"></i></span>
                    @if(auth()->user() && auth()->user()->hasAnyPermission(['inventory.stock.view', 'inventory.transfers.view', 'inventory.adjustments.view', 'inventory.wip.view', 'inventory.recovery.view', 'inventory.waste.view']))
                        <a href="{{ route('inventory.dashboard') }}" class="small text-decoration-none text-success fw-semibold">Inventory Hub <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="card-sattva-body d-flex flex-column flex-sm-row align-items-center justify-content-around">
                    <div style="width: 190px; height: 190px;">
                        <canvas id="whChart"></canvas>
                    </div>
                    <div class="mt-3 mt-sm-0">
                        @foreach($warehouseData as $idx => $wh)
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge rounded-circle p-1" style="background-color: {{ ['#059669', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#64748b'][$idx % 6] }};"> </span>
                                <span class="small fw-medium text-truncate" style="max-width: 160px;">{{ $wh['name'] }}:</span>
                                <span class="small fw-bold ms-auto">{{ $wh['lots'] }} lots</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Quick Production & Batch Health -->
    <div class="row g-4">
        <!-- Live Batch Executions -->
        <div class="col-12">
            <div class="card card-sattva">
                <div class="card-sattva-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cpu text-primary fs-5"></i>
                        <span class="fw-bold text-dark">Active Manufacturing Batches</span>
                        <span class="badge bg-success rounded-pill">{{ $activeBatchCount }} active</span>
                        @if($pendingQcCount > 0)
                            <span class="badge bg-warning text-dark rounded-pill">{{ $pendingQcCount }} QC Pending</span>
                        @endif
                    </div>
                    <div>
                        @if(auth()->user() && auth()->user()->hasPermission('production.batches.add'))
                            <a href="{{ route('production.batches.create') }}" class="btn btn-primary-sattva btn-sm me-2">
                                <i class="bi bi-plus-lg me-1"></i> New Batch
                            </a>
                        @endif
                        @if(auth()->user() && auth()->user()->hasPermission('production.batches.view'))
                            <a href="{{ route('production.batches.index') }}" class="btn btn-outline-secondary btn-sm">
                                View All Batches
                            </a>
                        @endif
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch No</th>
                                <th>Product Name</th>
                                <th>Plan Ref</th>
                                <th>Batch Size</th>
                                <th>Start Date</th>
                                <th>Current Status</th>
                                <th class="text-end">Quick Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBatches as $b)
                                <tr>
                                    <td class="fw-bold">
                                        @if(auth()->user() && auth()->user()->hasPermission('production.batches.view'))
                                            <a href="{{ route('production.batches.show', $b->id) }}" class="text-decoration-none text-dark">
                                                {{ $b->batch_no }}
                                            </a>
                                        @else
                                            <span class="text-dark">{{ $b->batch_no }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $b->product ? $b->product->name : 'N/A' }}</td>
                                    <td>{{ $b->productionPlan ? $b->productionPlan->plan_no : '-' }}</td>
                                    <td>{{ number_format($b->planned_qty, 2) }} {{ $b->uom ? $b->uom->uom_code : 'KG' }}</td>
                                    <td>{{ $b->actual_start_date ? \Carbon\Carbon::parse($b->actual_start_date)->format('d-m-Y') : 'Not Started' }}</td>
                                    <td>
                                        <x-status-badge :status="$b->status" />
                                    </td>
                                    <td class="text-end">
                                        @if(auth()->user() && auth()->user()->hasAnyPermission(['production.execution.view', 'production.batches.view']))
                                            <a href="{{ route('production.batches.execute', $b->id) }}" class="btn btn-sm btn-outline-success" title="Execute Stage SOP">
                                                <i class="bi bi-play-circle me-1"></i> Run
                                            </a>
                                        @endif
                                        @if(auth()->user() && auth()->user()->hasAnyPermission(['production.bmr.view', 'production.batches.view']))
                                            <a href="{{ route('production.batches.bmr', $b->id) }}" class="btn btn-sm btn-outline-primary" title="View BMR">
                                                <i class="bi bi-file-earmark-medical"></i> BMR
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                                        No batches recorded yet. You can create a production batch to start manufacturing.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. GRN Status Donut Chart
        const grnCtx = document.getElementById('grnChart');
        if (grnCtx) {
            new Chart(grnCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Draft', 'Pending QC', 'Completed'],
                    datasets: [{
                        data: [
                            {{ $grnStatus['draft'] ?: 0 }},
                            {{ $grnStatus['pending_qc'] ?: 0 }},
                            {{ $grnStatus['completed'] ?: 0 }}
                        ],
                        backgroundColor: ['#3b82f6', '#f59e0b', '#059669'],
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    cutout: '72%'
                }
            });
        }

        // 2. Warehouse Occupancy Donut Chart
        const whCtx = document.getElementById('whChart');
        if (whCtx) {
            const whLabels = @json(array_column($warehouseData, 'name'));
            const whLots = @json(array_column($warehouseData, 'lots'));
            const whColors = ['#059669', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#64748b'];

            new Chart(whCtx, {
                type: 'doughnut',
                data: {
                    labels: whLabels.length ? whLabels : ['No Warehouses'],
                    datasets: [{
                        data: whLots.length ? whLots : [1],
                        backgroundColor: whColors.slice(0, whLabels.length || 1),
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    cutout: '72%'
                }
            });
        }
    });
</script>
@endpush
