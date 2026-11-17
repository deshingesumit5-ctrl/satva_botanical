@php
    $sections = $sections ?? \App\Services\PermissionService::getItemsBySection();
    $actions = $actions ?? \App\Services\PermissionService::getActions();
    $assignedPermissionCodes = $assignedPermissionCodes ?? [];
    $oldPerms = old('permissions');
    $checkedCodes = is_array($oldPerms) ? $oldPerms : $assignedPermissionCodes;
    $isReadonly = $isReadonly ?? false;
@endphp

<input type="hidden" name="permissions_submitted" value="1">

<div class="table-responsive" style="border-radius: 0;">
    <table class="table align-middle mb-0" id="modulePermissionsTable">
        <thead class="table-light">
            <tr>
                <th style="min-width: 240px; padding-left: 1.5rem !important;">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input mt-0" id="globalSelectAllCb"
                            title="Select / Deselect All Permissions" {{ $isReadonly ? 'disabled' : '' }}>
                        <span>MODULE</span>
                    </div>
                </th>
                <th class="text-center" style="width: 56px;" title="Select all actions in row">ROW</th>
                @foreach($actions as $actionKey => $actionLabel)
                    <th class="text-center" style="min-width: 72px;">
                        <div class="d-flex flex-column align-items-center gap-1">
                            <span>{{ strtoupper($actionLabel) }}</span>
                            <input type="checkbox" class="form-check-input mt-0 col-select-all"
                                data-action="{{ $actionKey }}"
                                title="Select all {{ $actionLabel }}"
                                {{ $isReadonly ? 'disabled' : '' }}>
                        </div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($sections as $sectionName => $sectionItems)
                <tr class="bg-light border-top border-bottom">
                    <td colspan="{{ count($actions) + 2 }}" class="py-2" style="padding-left: 1.5rem !important; background-color: #f1f5f9 !important;">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-uppercase small" style="color: #0f172a; letter-spacing: 0.06em;">
                                <i class="bi bi-folder2-open text-success me-1"></i> {{ $sectionName }}
                            </span>
                            <span class="badge bg-white text-secondary border small fw-normal">{{ count($sectionItems) }} {{ count($sectionItems) === 1 ? 'screen' : 'screens' }}</span>
                        </div>
                    </td>
                </tr>
                @foreach($sectionItems as $item)
                    @php
                        $rowKey = $item['key'];
                        $supportedActions = $item['actions'] ?? ['view'];
                    @endphp
                    <tr data-row-key="{{ $rowKey }}">
                        <td style="padding-left: 1.5rem !important;">
                            <div class="fw-bold text-dark" style="font-size: 14px; line-height: 1.3;">{{ $item['label'] }}</div>
                            <div class="text-muted font-monospace" style="font-size: 11.5px; color: #94a3b8 !important;">{{ $rowKey }}</div>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input row-select-all"
                                data-row="{{ $rowKey }}"
                                title="Select all actions for {{ $item['label'] }}"
                                {{ $isReadonly ? 'disabled' : '' }}>
                        </td>
                        @foreach($actions as $actionKey => $actionLabel)
                            @php
                                $permCode = "{$rowKey}.{$actionKey}";
                                $isSupported = in_array($actionKey, $supportedActions, true);
                                $isChecked = $isSupported && in_array($permCode, $checkedCodes, true);
                            @endphp
                            <td class="text-center">
                                @if($isSupported)
                                    <input type="checkbox"
                                        class="form-check-input perm-cb"
                                        name="permissions[]"
                                        value="{{ $permCode }}"
                                        data-row="{{ $rowKey }}"
                                        data-action="{{ $actionKey }}"
                                        {{ $isChecked ? 'checked' : '' }}
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('modulePermissionsTable');
    if (!table) return;

    const allPermCbs = Array.from(table.querySelectorAll('.perm-cb'));
    const rowSelectCbs = Array.from(table.querySelectorAll('.row-select-all'));
    const colSelectCbs = Array.from(table.querySelectorAll('.col-select-all'));
    const globalSelectCb = document.getElementById('globalSelectAllCb');

    function getRowCbs(rowKey) {
        return allPermCbs.filter(cb => cb.dataset.row === rowKey && !cb.disabled);
    }

    function getColCbs(actionKey) {
        return allPermCbs.filter(cb => cb.dataset.action === actionKey && !cb.disabled);
    }

    function getViewCb(rowKey) {
        return table.querySelector(`.perm-cb[data-row="${rowKey}"][data-action="view"]`);
    }

    function syncIndicators() {
        // Sync each row's select-all checkbox
        rowSelectCbs.forEach(rowCb => {
            const cbs = getRowCbs(rowCb.dataset.row);
            if (!cbs.length) return;
            const checkedCount = cbs.filter(c => c.checked).length;
            rowCb.checked = checkedCount === cbs.length;
            rowCb.indeterminate = checkedCount > 0 && checkedCount < cbs.length;
        });

        // Sync each column's select-all checkbox
        colSelectCbs.forEach(colCb => {
            const cbs = getColCbs(colCb.dataset.action);
            if (!cbs.length) return;
            const checkedCount = cbs.filter(c => c.checked).length;
            colCb.checked = checkedCount === cbs.length;
            colCb.indeterminate = checkedCount > 0 && checkedCount < cbs.length;
        });

        // Sync global select-all checkbox
        if (globalSelectCb) {
            const enabledCbs = allPermCbs.filter(cb => !cb.disabled);
            if (enabledCbs.length) {
                const checkedCount = enabledCbs.filter(c => c.checked).length;
                globalSelectCb.checked = checkedCount === enabledCbs.length;
                globalSelectCb.indeterminate = checkedCount > 0 && checkedCount < enabledCbs.length;
            }
        }
    }

    // Individual permission checkbox change rule:
    // Ticking Add/Edit/Delete/Approve (or any non-view action) auto-ticks View;
    // Unticking View unticks the rest of the row.
    allPermCbs.forEach(cb => {
        cb.addEventListener('change', function () {
            const rowKey = this.dataset.row;
            const actionKey = this.dataset.action;

            if (actionKey === 'view' && !this.checked) {
                getRowCbs(rowKey).forEach(other => {
                    if (other.dataset.action !== 'view') {
                        other.checked = false;
                    }
                });
            } else if (actionKey !== 'view' && this.checked) {
                const viewCb = getViewCb(rowKey);
                if (viewCb && !viewCb.disabled) {
                    viewCb.checked = true;
                }
            }
            syncIndicators();
        });
    });

    // Row select-all
    rowSelectCbs.forEach(rowCb => {
        rowCb.addEventListener('change', function () {
            const checked = this.checked;
            getRowCbs(this.dataset.row).forEach(cb => {
                cb.checked = checked;
            });
            syncIndicators();
        });
    });

    // Column select-all
    colSelectCbs.forEach(colCb => {
        colCb.addEventListener('change', function () {
            const actionKey = this.dataset.action;
            const checked = this.checked;

            getColCbs(actionKey).forEach(cb => {
                cb.checked = checked;
                const rowKey = cb.dataset.row;
                if (actionKey === 'view' && !checked) {
                    getRowCbs(rowKey).forEach(other => {
                        other.checked = false;
                    });
                } else if (actionKey !== 'view' && checked) {
                    const viewCb = getViewCb(rowKey);
                    if (viewCb && !viewCb.disabled) {
                        viewCb.checked = true;
                    }
                }
            });
            syncIndicators();
        });
    });

    // Global select-all
    if (globalSelectCb) {
        globalSelectCb.addEventListener('change', function () {
            window.toggleAllCheckboxes(this.checked);
        });
    }

    window.toggleAllCheckboxes = function (checked) {
        allPermCbs.forEach(cb => {
            if (!cb.disabled) cb.checked = checked;
        });
        syncIndicators();
    };

    syncIndicators();
});
</script>
@endpush
@endonce
