@props(['status'])

@php
    $statusLower = strtolower(trim((string)$status));
    $badgeClass = 'badge-active';
    
    if (in_array($statusLower, ['active', 'approved', 'completed', 'received', 'released', 'posted', 'paid', 'yes', 'pass'])) {
        $badgeClass = 'badge-active';
    } elseif (in_array($statusLower, ['inactive', 'rejected', 'cancelled', 'blocked', 'expired', 'exhausted', 'unpaid', 'fail', 'no'])) {
        $badgeClass = 'badge-inactive';
    } elseif (in_array($statusLower, ['in progress', 'running', 'partially received', 'partially dispatched', 'partially allocated', 'partially consumed', 'partially paid'])) {
        $badgeClass = 'badge-completed';
    } else {
        // pending, draft, planned, on hold, qc pending, rework required
        $badgeClass = 'badge-pending';
    }
@endphp

<span class="badge-pill {{ $badgeClass }}">
    <span class="badge-dot"></span>
    <span>{{ $status }}</span>
</span>
