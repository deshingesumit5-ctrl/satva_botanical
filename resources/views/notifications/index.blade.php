@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid px-0">
    <x-page-header title="System Notifications" subtitle="Real-time alerts, approvals, and manufacturing notices" />

    <div class="card card-sattva">
        <div class="card-sattva-body p-0">
            <div class="list-group list-group-flush">
                @php
                    $currentUser = auth()->user();
                    $newlyOpenedIds = $justReadIds ?? [];
                @endphp
                @forelse($notifications as $n)
                    @php
                        $isNewlyOpened = in_array($n->id, $newlyOpenedIds, true);
                        $isReadForUser = $isNewlyOpened || $n->isReadBy($currentUser);
                    @endphp
                    <div class="list-group-item p-3 d-flex align-items-start justify-content-between {{ $isNewlyOpened ? 'bg-light' : ($isReadForUser ? 'bg-white' : 'bg-light') }}">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 {{ $isNewlyOpened || !$isReadForUser ? 'bg-primary bg-opacity-10 text-primary' : 'bg-secondary bg-opacity-10 text-muted' }}">
                                <i class="bi bi-bell-fill fs-5"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="fw-bold mb-0 {{ $isNewlyOpened || !$isReadForUser ? 'text-primary' : 'text-dark' }}">{{ $n->title }}</h6>
                                    @if($isNewlyOpened)
                                        <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">New</span>
                                    @endif
                                </div>
                                <p class="text-muted small mb-1">{{ $n->message }}</p>
                                <span class="badge bg-secondary-subtle text-secondary small">{{ $n->created_at->diffForHumans() }}</span>
                                @if($n->link)
                                    <a href="{{ $n->link }}" class="btn btn-link btn-sm text-decoration-none text-success fw-semibold ms-2">Open Link <i class="bi bi-arrow-right"></i></a>
                                @endif
                            </div>
                        </div>
                        <div>
                            @if(!$isReadForUser)
                                <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Mark as Read">
                                        <i class="bi bi-check2"></i> Mark Read
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small"><i class="bi bi-check2-all text-success"></i> Read</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                        No notifications found. You're all caught up!
                    </div>
                @endforelse
            </div>
        </div>
        @if($notifications->hasPages())
            <div class="card-sattva-footer p-3 border-top">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
