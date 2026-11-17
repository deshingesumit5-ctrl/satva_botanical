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

    // Prevent "Blocked aria-hidden on #squareConfirmModal" warning
    const confirmModalEl = document.getElementById('squareConfirmModal');
    if (confirmModalEl) {
        confirmModalEl.addEventListener('hide.bs.modal', function () {
            if (document.activeElement && confirmModalEl.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    }
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
    subTitleEl.textContent = options.subTitle || options.message || 'Are you sure you want to proceed?';
    confirmBtn.textContent = options.confirmText || 'Confirm';

    // Styling icon based on type
    iconBox.className = 'modal-square-icon-box ' + (options.iconBoxClass || 'bg-danger-subtle text-danger');
    if (options.iconHtml) {
        iconBox.innerHTML = options.iconHtml;
    } else if (options.icon) {
        const iconName = options.icon.startsWith('bi-') ? options.icon : ('bi-' + options.icon);
        const iconColor = options.iconColor ? ` style="color: ${options.iconColor};"` : '';
        iconBox.innerHTML = `<i class="bi ${iconName}"${iconColor}></i>`;
    } else {
        iconBox.innerHTML = '<i class="bi bi-exclamation-triangle"></i>';
    }

    const btnClass = options.confirmBtnClass || options.confirmClass || 'btn-danger rounded-pill';
    confirmBtn.className = 'btn px-4 py-2 font-weight-bold ' + btnClass;

    if (options.reasonRequired) {
        if (reasonContainer) {
            reasonContainer.classList.remove('d-none');
            reasonInput.setAttribute('required', 'required');
            reasonInput.placeholder = options.reasonPlaceholder || 'Enter reason (mandatory)...';
            reasonInput.value = '';
        }
    } else {
        if (reasonContainer) {
            reasonContainer.classList.add('d-none');
            reasonInput.removeAttribute('required');
            reasonInput.value = '';
        }
    }

    if (options.formAction && typeof options.formAction === 'string' && (options.formAction.startsWith('/') || options.formAction.startsWith('http'))) {
        modalForm.action = options.formAction;
    } else if (!options.onConfirm) {
        // Do not set invalid action
        modalForm.action = '';
    }

    if (options.method && options.method.toUpperCase() !== 'POST') {
        methodInput.value = options.method.toUpperCase();
    } else {
        methodInput.value = 'POST';
    }

    // Custom submit callback if provided
    modalForm.onsubmit = function (e) {
        if (document.activeElement && modalEl.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        if (options.onConfirm && typeof options.onConfirm === 'function') {
            e.preventDefault();
            options.onConfirm(reasonInput ? reasonInput.value : '');
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            if (bsModal) bsModal.hide();
        }
    };

    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();
};

/**
 * Robust Helper for Delete confirmation with real POST/DELETE form
 * Accepts (url, name) OR legacy (message, name, form) without ever using message as URL
 */
window.confirmDelete = function (firstArg, secondArg, thirdArg) {
    let url = '';
    let recordName = '';

    // Case 1: Standard new pattern: (url, recordName)
    if (typeof firstArg === 'string' && (firstArg.startsWith('/') || firstArg.startsWith('http'))) {
        url = firstArg;
        recordName = secondArg || '';
    }
    // Case 2: Legacy pattern: (message, recordName, formElement)
    else if (thirdArg && (thirdArg instanceof HTMLFormElement || thirdArg.tagName === 'FORM')) {
        url = thirdArg.getAttribute('action') || thirdArg.action;
        recordName = secondArg || '';
    }
    // Case 3: Legacy pattern: (message, formElement)
    else if (secondArg && (secondArg instanceof HTMLFormElement || secondArg.tagName === 'FORM')) {
        url = secondArg.getAttribute('action') || secondArg.action;
    }
    // Case 4: Legacy with URL in secondArg: (message, url)
    else if (typeof secondArg === 'string' && (secondArg.startsWith('/') || secondArg.startsWith('http'))) {
        url = secondArg;
    }

    if (!url) {
        console.error('Cannot confirm delete: missing valid target URL.', firstArg, secondArg, thirdArg);
        return;
    }

    window.openConfirmModal({
        title: 'Do you want to delete this?',
        subTitle: 'This action cannot be undone.' + (recordName ? ' Record: ' + recordName : ''),
        iconHtml: '<i class="bi bi-trash-fill"></i>',
        iconBoxClass: 'bg-danger-subtle text-danger',
        confirmText: 'Yes, Delete',
        confirmBtnClass: 'btn-danger rounded-pill',
        formAction: url,
        method: 'DELETE',
        reasonRequired: false,
    });
};

/**
 * Universal Global Click Handler for Delete Buttons
 * Matches: <button type="button" data-url="..." data-name="...">
 */
document.addEventListener('click', function (e) {
    const deleteBtn = e.target.closest('[data-url], .btn-action-delete, [data-action="delete"]');
    if (deleteBtn && deleteBtn.dataset.url) {
        e.preventDefault();
        e.stopPropagation();
        const url = deleteBtn.dataset.url;
        const name = deleteBtn.dataset.name || deleteBtn.getAttribute('data-name') || '';
        window.confirmDelete(url, name);
    }
});

/**
 * Helper for Status Toggles, Approvals, Rejections, and General Actions
 */
window.confirmAction = function (message, targetFormOrAction, options = {}) {
    let form = null;
    let actionUrl = null;

    if (targetFormOrAction instanceof HTMLFormElement || (targetFormOrAction && targetFormOrAction.tagName === 'FORM')) {
        form = targetFormOrAction;
    } else if (typeof targetFormOrAction === 'string' && document.getElementById(targetFormOrAction)) {
        const el = document.getElementById(targetFormOrAction);
        if (el.tagName === 'FORM') form = el;
    } else if (typeof targetFormOrAction === 'string' && (targetFormOrAction.startsWith('/') || targetFormOrAction.startsWith('http'))) {
        actionUrl = targetFormOrAction;
    }

    window.openConfirmModal({
        title: options.title || 'Confirm Action',
        subTitle: message || 'Are you sure you want to proceed?',
        iconHtml: options.iconHtml || '<i class="bi bi-question-circle-fill"></i>',
        iconBoxClass: options.iconBoxClass || 'bg-success-subtle text-success',
        confirmText: options.confirmText || 'Confirm',
        confirmBtnClass: options.confirmBtnClass || 'btn-success rounded-pill',
        formAction: actionUrl,
        onConfirm: function () {
            if (form && typeof form.submit === 'function') {
                form.submit();
            } else if (options.onConfirm && typeof options.onConfirm === 'function') {
                options.onConfirm();
            }
        }
    });
};

/**
 * Helper for Edit confirmation
 */
window.confirmEdit = function (firstArg, secondArg) {
    let targetForm = null;
    let title = 'Do you want to update this?';
    let subTitle = 'Please confirm to save the changes.';

    // Case 1: second argument is an HTML form element
    if (secondArg && typeof secondArg === 'object' && (secondArg instanceof HTMLFormElement || secondArg.tagName === 'FORM')) {
        targetForm = secondArg;
        if (typeof firstArg === 'string' && firstArg.trim() !== '') {
            title = firstArg;
        }
    }
    // Case 2: first argument is an HTML form element
    else if (firstArg && typeof firstArg === 'object' && (firstArg instanceof HTMLFormElement || firstArg.tagName === 'FORM')) {
        targetForm = firstArg;
        if (typeof secondArg === 'string' && secondArg.trim() !== '') {
            subTitle = 'Changes will be saved for: ' + secondArg;
        }
    }
    // Case 3: first argument is an ID string or message string
    else if (typeof firstArg === 'string') {
        const foundEl = document.getElementById(firstArg);
        if (foundEl && foundEl.tagName === 'FORM') {
            targetForm = foundEl;
        }
        if (typeof secondArg === 'string' && secondArg.trim() !== '') {
            subTitle = 'Changes will be saved for: ' + secondArg;
        } else if (secondArg && typeof secondArg === 'object' && secondArg.tagName === 'FORM') {
            targetForm = secondArg;
            title = firstArg;
        }
    }

    window.openConfirmModal({
        title: title,
        subTitle: subTitle,
        iconHtml: '<i class="bi bi-pencil-square"></i>',
        iconBoxClass: 'bg-primary-subtle text-success',
        confirmText: 'Yes, Update',
        confirmBtnClass: 'btn-success rounded-pill',
        reasonRequired: false,
        onConfirm: function () {
            if (targetForm && typeof targetForm.submit === 'function') {
                targetForm.submit();
            } else if (typeof firstArg === 'string' && document.getElementById(firstArg)) {
                document.getElementById(firstArg).submit();
            } else {
                console.error('Target form not found for confirmEdit:', firstArg, secondArg);
            }
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

/**
 * Anti-Autofill & Password Eye Toggle for User Forms (Page Load, pageshow, show.bs.modal)
 */
window.clearAntiAutofillFields = function (root) {
    const scope = root || document;
    scope.querySelectorAll('.anti-autofill-username, .anti-autofill-password').forEach(function (el) {
        el.value = '';
        el.setAttribute('readonly', 'readonly');
    });
};

document.addEventListener('DOMContentLoaded', function () {
    window.clearAntiAutofillFields(document);
    setTimeout(function () { window.clearAntiAutofillFields(document); }, 60);
});

window.addEventListener('pageshow', function () {
    window.clearAntiAutofillFields(document);
});

document.addEventListener('show.bs.modal', function (e) {
    if (e.target) {
        window.clearAntiAutofillFields(e.target);
    }
});

document.addEventListener('click', function (e) {
    const eyeBtn = e.target.closest('.password-eye-toggle');
    if (!eyeBtn) return;
    e.preventDefault();

    let input = null;
    const targetId = eyeBtn.getAttribute('data-target');
    if (targetId) {
        input = document.getElementById(targetId);
    }
    if (!input) {
        const wrapper = eyeBtn.closest('.position-relative, .input-group');
        if (wrapper) {
            input = wrapper.querySelector('input[type="password"], input[type="text"]');
        }
    }
    if (!input) return;

    const icon = eyeBtn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
});
