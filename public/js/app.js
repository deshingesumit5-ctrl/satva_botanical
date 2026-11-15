// Sattva Botanical ERP - Main JavaScript

document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts
    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

/**
 * Universal Square Confirmation Banner Modal Function
 * Used for Delete, Edit, Approve, Reject, Hold, Resume, Cancel, Close, Logout
 */
window.openConfirmModal = function (options) {
    const modalEl = document.getElementById('squareConfirmModal');
    if (!modalEl) return;

    const iconBox = document.getElementById('modalIconBox');
    const titleEl = document.getElementById('modalTitle');
    const subTitleEl = document.getElementById('modalSubTitle');
    const reasonContainer = document.getElementById('modalReasonContainer');
    const reasonInput = document.getElementById('modalReasonInput');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    const modalForm = document.getElementById('modalConfirmForm');
    const methodInput = document.getElementById('modalMethodInput');

    titleEl.textContent = options.title || 'Confirm Action';
    subTitleEl.textContent = options.subTitle || 'Are you sure you want to proceed?';
    confirmBtn.textContent = options.confirmText || 'Confirm';

    // Styling icon based on type
    iconBox.className = 'modal-square-icon-box ' + (options.iconBoxClass || 'bg-danger-subtle text-danger');
    iconBox.innerHTML = options.iconHtml || '<i class="bi bi-exclamation-triangle"></i>';

    confirmBtn.className = 'btn px-4 py-2 font-weight-bold ' + (options.confirmBtnClass || 'btn-danger');

    if (options.reasonRequired) {
        reasonContainer.classList.remove('d-none');
        reasonInput.setAttribute('required', 'required');
        reasonInput.placeholder = options.reasonPlaceholder || 'Enter reason (mandatory)...';
        reasonInput.value = '';
    } else {
        reasonContainer.classList.add('d-none');
        reasonInput.removeAttribute('required');
        reasonInput.value = '';
    }

    if (options.formAction) {
        modalForm.action = options.formAction;
    }

    if (options.method && options.method.toUpperCase() !== 'POST') {
        methodInput.value = options.method.toUpperCase();
    } else {
        methodInput.value = 'POST';
    }

    // Custom submit callback if provided
    modalForm.onsubmit = function (e) {
        if (options.onConfirm && typeof options.onConfirm === 'function') {
            e.preventDefault();
            options.onConfirm(reasonInput.value);
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            bsModal.hide();
        }
    };

    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/**
 * Helper for Delete confirmation with real POST/DELETE form
 */
window.confirmDelete = function (formAction, recordName) {
    window.openConfirmModal({
        title: 'Do you want to delete this?',
        subTitle: 'This action cannot be undone. Record: ' + (recordName || ''),
        iconHtml: '<i class="bi bi-trash-fill"></i>',
        iconBoxClass: 'bg-danger-subtle text-danger',
        confirmText: 'Yes, Delete',
        confirmBtnClass: 'btn-danger rounded-pill',
        formAction: formAction,
        method: 'DELETE',
        reasonRequired: false,
    });
};

/**
 * Helper for Edit confirmation
 */
window.confirmEdit = function (formId, recordName) {
    window.openConfirmModal({
        title: 'Do you want to update this?',
        subTitle: 'Changes will be saved for: ' + (recordName || 'this record'),
        iconHtml: '<i class="bi bi-pencil-square"></i>',
        iconBoxClass: 'bg-primary-subtle text-success',
        confirmText: 'Yes, Update',
        confirmBtnClass: 'btn-success rounded-pill',
        reasonRequired: false,
        onConfirm: function () {
            document.getElementById(formId).submit();
        }
    });
};

/**
 * Toggle Fullscreen
 */
window.toggleFullscreen = function () {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
};
