@php
    $checklistItems = $completionEvaluation['checklist'] ?? [];
    $unmetCount = $completionEvaluation['unmet_count'] ?? collect($checklistItems)->where('met', false)->count();
    $summaryText = $completionEvaluation['summary'] ?? ($unmetCount === 0
        ? 'All requirements met. Click Complete Stage and Move to Next.'
        : "{$unmetCount} requirement(s) not met.");
    $authUser = auth()->user();
    $canOpenQc = $authUser && $authUser->hasAnyPermission(['qc.dashboard.view', 'qc.review.view', 'qc.history.view']);
    $stageHistory = $stageHistory ?? collect();
@endphp

<!-- Summary Banner -->
<div class="alert {{ $unmetCount === 0 ? 'alert-success' : 'alert-warning' }} py-2 px-3 mb-3 d-flex align-items-center gap-2" id="checklistSummaryBanner">
    <i class="bi {{ $unmetCount === 0 ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning' }} fs-5"></i>
    <span class="small fw-semibold">{{ $summaryText }}</span>
</div>

@if(!empty($checklistItems))
    <div class="row g-2">
        @foreach($checklistItems as $item)
            <div class="col-md-6">
                <div class="p-3 rounded border h-100 {{ $item['met'] ? 'bg-success-subtle border-success-subtle text-success-emphasis' : 'bg-danger-subtle border-danger-subtle text-danger-emphasis' }}">
                    <div class="d-flex align-items-start gap-2">
                        @if($item['met'])
                            <i class="bi bi-check-circle-fill text-success fs-5 mt-1"></i>
                        @else
                            <i class="bi bi-x-circle-fill text-danger fs-5 mt-1"></i>
                        @endif
                        <div class="flex-grow-1">
                            <div class="small fw-bold">{{ $item['label'] }}</div>

                            @if(!$item['met'])
                                @if(!empty($item['reason']))
                                    <div class="small mt-1">
                                        <strong>Why:</strong> {{ $item['reason'] }}
                                    </div>
                                @endif
                                @if(!empty($item['action']))
                                    <div class="small mt-1">
                                        <strong>What to do:</strong> {{ $item['action'] }}
                                    </div>
                                @endif
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @if(!empty($item['anchor']))
                                        <a href="{{ $item['anchor'] }}"
                                            class="btn btn-sm btn-outline-danger py-0 px-2 goto-section-link"
                                            data-target-anchor="{{ $item['anchor'] }}">
                                            <i class="bi bi-arrow-down-circle me-1"></i>{{ $item['anchor_label'] ?? 'Go to section' }}
                                        </a>
                                    @endif
                                    @if($item['key'] === 'qc_approval' && !empty($item['qc_request_url']) && $canOpenQc)
                                        <a href="{{ $item['qc_request_url'] }}" class="btn btn-sm btn-primary-sattva py-0 px-2">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Open QC request
                                        </a>
                                    @endif
                                    @if($item['key'] === 'qc_approval' && !empty($item['rework_url']))
                                        <a href="{{ $item['rework_url'] }}" class="btn btn-sm btn-warning py-0 px-2">
                                            <i class="bi bi-arrow-repeat me-1"></i>Open Rework Execution
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-success small fw-semibold">
        <i class="bi bi-check-circle-fill me-1"></i> All base stage checks satisfied. Ready to complete stage.
    </div>
@endif

@if($stageHistory->count() > 1)
    <div class="mt-3 pt-3 border-top" id="stage-repetition-history">
        <div class="small fw-bold text-uppercase text-muted mb-2">Stage Execution / Repetition History (Preserved Records)</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($stageHistory as $hist)
                <a href="{{ route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $hist->id]) }}"
                    class="badge text-decoration-none px-3 py-2 {{ $hist->id === $activeExecution->id ? 'bg-success text-white' : 'bg-light text-dark border' }}">
                    Execution #{{ $hist->execution_number }} ({{ $hist->execution_type }}): {{ $hist->status }}
                </a>
            @endforeach
        </div>
    </div>
@endif
