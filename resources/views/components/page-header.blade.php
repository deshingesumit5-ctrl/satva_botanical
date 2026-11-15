@props(['title', 'subtitle' => null, 'addRoute' => null, 'addText' => null, 'permission' => null])

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center page-title-area gap-2">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if($subtitle)
            <div class="page-subtitle">{{ $subtitle }}</div>
        @endif
    </div>
    
    <div class="d-flex align-items-center gap-2">
        {{ $slot }}

        @if($addRoute)
            @if(!$permission || (auth()->user() && auth()->user()->hasPermission($permission)))
                <a href="{{ $addRoute }}" class="btn btn-primary-sattva shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> {{ $addText ?? '+ Add New' }}
                </a>
            @endif
        @endif
    </div>
</div>
