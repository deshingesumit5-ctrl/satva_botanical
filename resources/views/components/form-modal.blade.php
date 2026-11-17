@props([
    'id' => 'sattvaDynamicFormModal',
    'title' => 'Add Record',
    'size' => 'standard', // 'standard' (~500px), 'lg' (~800px), 'xl' (1140px item grids)
    'action' => '',
    'method' => 'POST',
    'isEdit' => false,
    'submitText' => 'Save',
    'cancelText' => 'Cancel',
    'hasForm' => false,
])

@php
    $sizeClass = match ($size) {
        'lg', 'large' => 'modal-lg sattva-modal-lg',
        'xl', 'modal-xl' => 'modal-xl sattva-modal-xl',
        default => 'sattva-modal-standard',
    };
    $upperMethod = strtoupper($method);
@endphp

<div class="modal fade sattva-form-modal" id="{{ $id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable {{ $sizeClass }}">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="{{ $id }}Title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            @if($hasForm)
                <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="sattva-ajax-form d-flex flex-column flex-grow-1 overflow-hidden" data-is-edit="{{ $isEdit ? '1' : '0' }}" autocomplete="off">
                    @csrf
                    @if(!in_array($upperMethod, ['GET', 'POST']))
                        @method($upperMethod)
                    @endif
                    <div class="modal-body" id="{{ $id }}Body">
                        {{ $slot }}
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary btn-cancel" data-bs-dismiss="modal">{{ $cancelText }}</button>
                        <button type="submit" class="btn btn-primary-sattva btn-save">{{ $submitText }}</button>
                    </div>
                </form>
            @else
                <div class="modal-body" id="{{ $id }}Body">
                    {{ $slot }}
                </div>
                <div class="modal-footer border-top" id="{{ $id }}Footer">
                    <button type="button" class="btn btn-outline-secondary btn-cancel" data-bs-dismiss="modal">{{ $cancelText }}</button>
                    <button type="button" class="btn btn-primary-sattva btn-save" id="{{ $id }}SubmitBtn">{{ $submitText }}</button>
                </div>
            @endif
        </div>
    </div>
</div>
