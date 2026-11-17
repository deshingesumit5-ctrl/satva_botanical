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

    // Prevent "Blocked aria-hidden on #squareConfirmModal" warning & manage stacked modal z-index
    const confirmModalEl = document.getElementById('squareConfirmModal');
    if (confirmModalEl) {
        confirmModalEl.addEventListener('shown.bs.modal', function () {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 1) {
                backdrops[backdrops.length - 1].classList.add('confirm-backdrop-top');
            }
        });

        confirmModalEl.addEventListener('hide.bs.modal', function () {
            if (document.activeElement && confirmModalEl.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });

        confirmModalEl.addEventListener('hidden.bs.modal', function () {
            if (document.querySelector('.modal.show')) {
                document.body.classList.add('modal-open');
            }
        });
    }

    // Upgrade any existing inline modals on the page to match the Reference Add Warehouse modal design
    window.upgradeInlineModals(document);

    // If user directly visited a modal-eligible /create or /edit URL, open it as a modal over the list
    window.handleDirectCreateOrEditUrlVisit();
});

/**
 * Show a Toast notification in #globalToastContainer
 */
window.showSattvaToast = function (message, type = 'success') {
    const container = document.getElementById('globalToastContainer') || document.querySelector('.toast-container');
    if (!container || !message) return;

    const bgClass = type === 'error' || type === 'danger' ? 'text-bg-danger' : (type === 'warning' ? 'text-bg-warning' : 'text-bg-success');
    const iconClass = type === 'error' || type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';

    const toastEl = document.createElement('div');
    toastEl.className = `toast show align-items-center ${bgClass} border-0 shadow mb-2`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi ${iconClass} fs-5"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    container.appendChild(toastEl);

    setTimeout(function () {
        toastEl.classList.remove('show');
        setTimeout(() => toastEl.remove(), 300);
    }, 4500);
};

/**
 * Universal Square Confirmation Banner Modal Function
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
        modalForm.action = '';
    }

    if (options.method && options.method.toUpperCase() !== 'POST') {
        methodInput.value = options.method.toUpperCase();
    } else {
        methodInput.value = 'POST';
    }

    modalForm.onsubmit = function (e) {
        if (document.activeElement && modalEl.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        if (options.onConfirm && typeof options.onConfirm === 'function') {
            e.preventDefault();
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            if (bsModal) bsModal.hide();
            options.onConfirm(reasonInput ? reasonInput.value : '');
        }
    };

    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();
};

/**
 * Helper for Delete confirmation
 */
window.confirmDelete = function (firstArg, secondArg, thirdArg) {
    let url = '';
    let recordName = '';

    if (typeof firstArg === 'string' && (firstArg.startsWith('/') || firstArg.startsWith('http'))) {
        url = firstArg;
        recordName = secondArg || '';
    } else if (thirdArg && (thirdArg instanceof HTMLFormElement || thirdArg.tagName === 'FORM')) {
        url = thirdArg.getAttribute('action') || thirdArg.action;
        recordName = secondArg || '';
    } else if (secondArg && (secondArg instanceof HTMLFormElement || secondArg.tagName === 'FORM')) {
        url = secondArg.getAttribute('action') || secondArg.action;
    } else if (typeof secondArg === 'string' && (secondArg.startsWith('/') || secondArg.startsWith('http'))) {
        url = secondArg;
    }

    if (!url) return;

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
 * Helper for Edit confirmation ("Do you want to update this?")
 * Appears ABOVE the form modal (z-index 1085) and submits via AJAX if inside a modal
 */
window.confirmEdit = function (firstArg, secondArg) {
    let targetForm = null;
    let title = 'Do you want to update this?';
    let subTitle = 'Please confirm to save the changes.';

    if (secondArg && typeof secondArg === 'object' && (secondArg instanceof HTMLFormElement || secondArg.tagName === 'FORM')) {
        targetForm = secondArg;
        if (typeof firstArg === 'string' && firstArg.trim() !== '') {
            title = firstArg;
        }
    } else if (firstArg && typeof firstArg === 'object' && (firstArg instanceof HTMLFormElement || firstArg.tagName === 'FORM')) {
        targetForm = firstArg;
        if (typeof secondArg === 'string' && secondArg.trim() !== '') {
            subTitle = 'Changes will be saved for: ' + secondArg;
        }
    } else if (typeof firstArg === 'string') {
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
            if (!targetForm && typeof firstArg === 'string' && document.getElementById(firstArg)) {
                targetForm = document.getElementById(firstArg);
            }
            if (!targetForm) return;

            if (targetForm.closest('.modal') || targetForm.classList.contains('sattva-ajax-form')) {
                targetForm.dataset.editConfirmed = '1';
                window.submitModalFormAjax(targetForm);
            } else {
                targetForm.submit();
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
    } else if (document.exitFullscreen) {
        document.exitFullscreen();
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

// ============================================================================
// CHANGE 4: UNIVERSAL REUSABLE FORM MODAL ENGINE (REFERENCE ADD WAREHOUSE)
// ============================================================================

/**
 * Check if a URL belongs to the Change 4 modal scope
 * KEEP AS FULL PAGES: Role Add/Edit, Recipe Builder wizard, Production Execution, BMR, Reconciliation, QC Review, reports
 */
window.isModalEligibleFormUrl = function (rawUrl) {
    if (!rawUrl) return false;
    let path = '';
    try {
        const u = new URL(rawUrl, window.location.origin);
        if (u.origin !== window.location.origin) return false;
        path = u.pathname.replace(/\/+$/, '');
    } catch (e) {
        return false;
    }

    // Explicitly excluded full-page screens
    if (
        path.startsWith('/admin/roles') ||
        path.includes('/builder/') ||
        path.includes('/execute') ||
        path.includes('/execution') ||
        path.includes('/bmr') ||
        path.includes('/reconcil') ||
        path.startsWith('/qc') ||
        path.startsWith('/reports') ||
        path.includes('/allocate') ||
        path.includes('/requirements')
    ) {
        return false;
    }

    // Eligible /create, /edit, /opening-entry, /from-po/* routes
    if (
        /\/(create|edit)$/.test(path) ||
        path === '/inventory/stock/opening-entry' ||
        path.startsWith('/purchase/grn/from-po/')
    ) {
        return true;
    }

    return false;
};

/**
 * Determine modal size class based on route path:
 * - modal-xl for documents with item grids (PR, PO, GRN, Sales Order, Invoices)
 * - modal-lg (~800px, 2 columns) for large masters/forms (Product, Material, Supplier, Customer, Equipment, User, etc.)
 * - standard (~500px) for short forms
 */
window.resolveModalSizeClasses = function (rawUrl) {
    let path = '';
    try {
        path = new URL(rawUrl, window.location.origin).pathname;
    } catch (e) {
        path = rawUrl || '';
    }

    if (
        path.startsWith('/purchase/requisitions') ||
        path.startsWith('/purchase/orders') ||
        path.startsWith('/purchase/grn') ||
        path.startsWith('/sales/orders') ||
        path.startsWith('/sales/invoices') ||
        path.startsWith('/sales/purchase-invoices')
    ) {
        return ['modal-xl', 'sattva-modal-xl'];
    }

    if (
        path.startsWith('/masters/products') ||
        path.startsWith('/masters/materials') ||
        path.startsWith('/masters/suppliers') ||
        path.startsWith('/masters/customers') ||
        path.startsWith('/masters/equipments') ||
        path.startsWith('/admin/users') ||
        path.startsWith('/production/plans') ||
        path.startsWith('/production/batches') ||
        path.startsWith('/sales/dispatch') ||
        path.startsWith('/inventory/transfers')
    ) {
        return ['modal-lg', 'sattva-modal-lg'];
    }

    return ['sattva-modal-standard'];
};

/**
 * Convert Active/Inactive <select name="status"> inside modal into the Reference "Active" checkbox
 */
window.normalizeModalStatusField = function (formEl) {
    if (!formEl) return;
    const statusSelect = formEl.querySelector('select[name="status"]');
    if (!statusSelect) return;

    const options = Array.from(statusSelect.options).map(o => o.value);
    if (options.length === 2 && options.includes('Active') && options.includes('Inactive')) {
        const isChecked = statusSelect.value !== 'Inactive';
        const wrapper = statusSelect.closest('.mb-3, .mb-4, .col-md-4, .col-md-6, .col-6') || statusSelect.parentElement;
        if (wrapper) {
            const cbId = 'modalActiveCheck_' + Math.floor(Math.random() * 100000);
            wrapper.innerHTML = `
                <input type="hidden" name="status" value="Inactive">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="status" value="Active" id="${cbId}" ${isChecked ? 'checked' : ''}>
                    <label class="form-check-label fw-semibold" for="${cbId}" style="text-transform: none !important; font-size: 14px !important;">Active</label>
                </div>
            `;
        }
    }
};

/**
 * Upgrade existing inline modals (#addPlantModal, #addWhModal, etc.) to match reference styling & AJAX submission
 */
window.upgradeInlineModals = function (root) {
    const scope = root || document;
    scope.querySelectorAll('.modal:not(#squareConfirmModal):not(#sattvaDynamicFormModal)').forEach(function (modalEl) {
        const dialog = modalEl.querySelector('.modal-dialog');
        if (dialog) {
            dialog.classList.add('modal-dialog-centered', 'modal-dialog-scrollable');
            if (!dialog.classList.contains('modal-lg') && !dialog.classList.contains('modal-xl')) {
                dialog.classList.add('sattva-modal-standard');
            }
        }
        const form = modalEl.querySelector('form');
        if (form) {
            form.classList.add('sattva-ajax-form');
            window.normalizeModalStatusField(form);
        }
    });
};

/**
 * Clear validation errors inside a form
 */
window.clearFormValidationErrors = function (form) {
    if (!form) return;
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.modal-field-error, .modal-general-error').forEach(el => el.remove());
};

/**
 * Render validation errors under each field inside the modal
 */
window.renderFormValidationErrors = function (form, errors, generalMessage) {
    window.clearFormValidationErrors(form);

    if (errors && typeof errors === 'object') {
        Object.keys(errors).forEach(function (fieldKey) {
            const messages = Array.isArray(errors[fieldKey]) ? errors[fieldKey] : [errors[fieldKey]];
            const firstMsg = messages[0];

            if (fieldKey === '_general') {
                generalMessage = firstMsg;
                return;
            }

            // Try exact name match or bracket notation
            const bracketName = fieldKey.includes('.')
                ? fieldKey.split('.').map((p, i) => (i === 0 ? p : `[${p}]`)).join('')
                : fieldKey;

            let field = form.querySelector(`[name="${fieldKey}"]:not([type="hidden"])`)
                || form.querySelector(`[name="${bracketName}"]:not([type="hidden"])`)
                || form.querySelector(`[name^="${fieldKey}["]:not([type="hidden"])`);

            if (field) {
                field.classList.add('is-invalid');
                const errDiv = document.createElement('div');
                errDiv.className = 'invalid-feedback d-block modal-field-error';
                errDiv.textContent = firstMsg;

                const inputWrapper = field.closest('.position-relative, .input-group');
                if (inputWrapper && inputWrapper.parentElement) {
                    inputWrapper.parentElement.appendChild(errDiv);
                } else if (field.parentElement) {
                    field.parentElement.appendChild(errDiv);
                }
            }
        });
    }

    if (generalMessage) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger py-2 px-3 small mb-3 modal-general-error';
        alertDiv.innerHTML = `<i class="bi bi-exclamation-circle-fill me-1"></i> ${generalMessage}`;
        const modalBody = form.closest('.modal')?.querySelector('.modal-body') || form;
        modalBody.prepend(alertDiv);
    }
};

/**
 * Refresh the list page table in-place after modal save
 */
window.refreshCurrentListView = async function (fallbackRedirectUrl) {
    let targetUrl = window.location.href;
    if (window.isModalEligibleFormUrl(targetUrl)) {
        targetUrl = fallbackRedirectUrl || targetUrl.replace(/\/(create|\d+\/edit)$/, '');
    }

    try {
        const res = await fetch(targetUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMain = doc.querySelector('.main-content');
            const currentMain = document.querySelector('.main-content');

            if (newMain && currentMain) {
                // Preserve toast container while updating main content
                const toastContainer = document.getElementById('globalToastContainer');
                Array.from(currentMain.children).forEach(child => {
                    if (child.id !== 'globalToastContainer') {
                        child.remove();
                    }
                });
                Array.from(newMain.children).forEach(child => {
                    if (child.id !== 'globalToastContainer') {
                        currentMain.appendChild(child);
                    }
                });
                if (window.location.href !== targetUrl) {
                    window.history.pushState({}, doc.title || document.title, targetUrl);
                }
                window.upgradeInlineModals(currentMain);
                return;
            }
        }
    } catch (err) {
        console.error('List refresh fallback:', err);
    }

    window.location.href = fallbackRedirectUrl || targetUrl;
};

/**
 * Submit a modal form via AJAX with FormData (supporting file uploads & field validation errors)
 */
window.submitModalFormAjax = async function (form) {
    if (!form) return;

    const methodOverride = form.querySelector('input[name="_method"]')?.value?.toUpperCase() || '';
    const isEdit = methodOverride === 'PUT' || methodOverride === 'PATCH' || form.dataset.isEdit === '1';

    // Show Square Confirmation Banner ("Do you want to update this?") ABOVE the form modal on Edit
    if (isEdit && form.dataset.editConfirmed !== '1') {
        window.confirmEdit('Do you want to update this?', form);
        return;
    }
    delete form.dataset.editConfirmed;

    window.clearFormValidationErrors(form);

    const modalEl = form.closest('.modal');
    const submitBtn = modalEl?.querySelector('#sattvaDynamicFormModalSubmitBtn, .modal-footer button[type="submit"], .modal-footer .btn-save, .modal-footer .btn-primary-sattva');
    const originalBtnHtml = submitBtn ? submitBtn.innerHTML : null;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const formData = new FormData(form);

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        let data = {};
        try {
            data = await res.json();
        } catch (e) {
            data = {};
        }

        if (res.status === 422 || (!res.ok && data.errors)) {
            window.renderFormValidationErrors(form, data.errors || {}, data.message);
            return;
        }

        if (!res.ok) {
            window.renderFormValidationErrors(form, {}, data.message || 'An error occurred while saving.');
            return;
        }

        // Success: close modal, show toast, refresh list
        if (modalEl) {
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            if (bsModal) bsModal.hide();
        }

        window.showSattvaToast(data.message || 'Saved successfully.', 'success');
        await window.refreshCurrentListView(data.redirect);
    } catch (error) {
        window.renderFormValidationErrors(form, {}, 'Network error. Please try again.');
    } finally {
        if (submitBtn && originalBtnHtml !== null) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        }
    }
};

/**
 * Intercept form submit inside any modal (except #squareConfirmModal)
 */
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || form.id === 'modalConfirmForm' || form.id === 'logoutForm') return;

    // Only intercept POST/PUT/PATCH forms inside a form modal
    const modalEl = form.closest('.modal:not(#squareConfirmModal)');
    if (!modalEl) return;

    const method = (form.getAttribute('method') || 'GET').toUpperCase();
    if (method === 'GET') return;

    e.preventDefault();
    window.submitModalFormAjax(form);
});

/**
 * Open a /create or /edit URL inside the reusable #sattvaDynamicFormModal component
 */
window.openUrlInFormModal = async function (url) {
    const modalEl = document.getElementById('sattvaDynamicFormModal');
    if (!modalEl) {
        window.location.href = url;
        return;
    }

    const dialogEl = modalEl.querySelector('.modal-dialog');
    const titleEl = document.getElementById('sattvaDynamicFormModalTitle');
    const bodyEl = document.getElementById('sattvaDynamicFormModalBody');
    const submitBtn = document.getElementById('sattvaDynamicFormModalSubmitBtn');

    // Configure modal size
    dialogEl.classList.remove('modal-lg', 'modal-xl', 'sattva-modal-standard', 'sattva-modal-lg', 'sattva-modal-xl');
    window.resolveModalSizeClasses(url).forEach(cls => dialogEl.classList.add(cls));

    titleEl.textContent = 'Loading...';
    bodyEl.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border text-success mb-2"></div><div>Loading form...</div></div>';

    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();

    try {
        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
        });

        if (!res.ok) {
            bodyEl.innerHTML = `<div class="alert alert-danger mb-0">Unable to load form (HTTP ${res.status}).</div>`;
            return;
        }

        const html = await res.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const mainContent = doc.querySelector('.main-content') || doc.body;
        const pageTitle = mainContent.querySelector('.page-title, h2, h1')?.textContent?.trim()
            || doc.title.replace(/\s*-\s*Sattva Botanical.*$/i, '').trim()
            || 'Form';

        const form = mainContent.querySelector('form');
        if (!form) {
            bodyEl.innerHTML = mainContent.innerHTML;
            return;
        }

        titleEl.textContent = pageTitle;

        // Detect if edit form
        const isEdit = url.endsWith('/edit') || !!form.querySelector('input[name="_method"][value="PUT"], input[name="_method"][value="PATCH"]');
        form.dataset.isEdit = isEdit ? '1' : '0';
        form.classList.add('sattva-ajax-form');

        // Extract submit button label before removing in-form footer buttons
        const innerSubmitBtn = form.querySelector('button[type="submit"], button.btn-primary-sattva');
        const submitLabel = innerSubmitBtn ? innerSubmitBtn.textContent.trim() : (isEdit ? 'Save Changes' : 'Save');
        if (submitBtn) {
            submitBtn.textContent = submitLabel || 'Save';
        }

        // Remove the inner form's bottom Cancel/Save bar so only the modal footer shows
        const bottomBars = form.querySelectorAll('.d-flex.justify-content-end, .d-flex.justify-content-between.pt-3');
        if (bottomBars.length > 0) {
            const lastBar = bottomBars[bottomBars.length - 1];
            if (lastBar.querySelector('a.btn, button[type="submit"], button.btn-primary-sattva')) {
                lastBar.remove();
            }
        }

        window.normalizeModalStatusField(form);

        bodyEl.innerHTML = '';
        bodyEl.appendChild(form);

        // Execute any inline scripts from the form page (e.g. addItemRow, calculateTotals for item grids)
        mainContent.querySelectorAll('script').forEach(function (oldScript) {
            if (oldScript.src) return;
            const newScript = document.createElement('script');
            newScript.textContent = oldScript.textContent;
            bodyEl.appendChild(newScript);
        });

        // Apply anti-autofill rules for User forms
        window.clearAntiAutofillFields(bodyEl);

        if (submitBtn) {
            submitBtn.onclick = function () {
                const activeForm = bodyEl.querySelector('form');
                if (!activeForm) return;
                if (typeof activeForm.reportValidity === 'function' && !activeForm.reportValidity()) {
                    return;
                }
                window.submitModalFormAjax(activeForm);
            };
        }
    } catch (err) {
        bodyEl.innerHTML = '<div class="alert alert-danger mb-0">Failed to load form content.</div>';
    }
};

/**
 * Intercept clicks on Add (/create) and Edit (/edit) links in the Change 4 scope
 */
document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href]');
    if (!link) return;
    if (e.ctrlKey || e.metaKey || e.shiftKey || link.target === '_blank' || link.hasAttribute('download')) return;

    // Ignore links inside navbar/sidebar unless it's an explicit create link
    const href = link.getAttribute('href');
    if (!window.isModalEligibleFormUrl(href)) return;

    e.preventDefault();
    window.openUrlInFormModal(link.href);
});

/**
 * When a user directly visits a modal-eligible /create or /edit URL in the browser,
 * load the index list behind it and open the form inside #sattvaDynamicFormModal
 */
window.handleDirectCreateOrEditUrlVisit = async function () {
    const currentPath = window.location.pathname.replace(/\/+$/, '');
    if (!window.isModalEligibleFormUrl(window.location.href)) return;

    const mainContent = document.querySelector('.main-content');
    const form = mainContent?.querySelector('form');
    if (!form) return;

    const modalEl = document.getElementById('sattvaDynamicFormModal');
    if (!modalEl) return;

    const dialogEl = modalEl.querySelector('.modal-dialog');
    const titleEl = document.getElementById('sattvaDynamicFormModalTitle');
    const bodyEl = document.getElementById('sattvaDynamicFormModalBody');
    const submitBtn = document.getElementById('sattvaDynamicFormModalSubmitBtn');

    dialogEl.classList.remove('modal-lg', 'modal-xl', 'sattva-modal-standard', 'sattva-modal-lg', 'sattva-modal-xl');
    window.resolveModalSizeClasses(currentPath).forEach(cls => dialogEl.classList.add(cls));

    const pageTitle = mainContent.querySelector('.page-title, h2, h1')?.textContent?.trim() || 'Form';
    titleEl.textContent = pageTitle;

    const isEdit = currentPath.endsWith('/edit') || !!form.querySelector('input[name="_method"][value="PUT"], input[name="_method"][value="PATCH"]');
    form.dataset.isEdit = isEdit ? '1' : '0';
    form.classList.add('sattva-ajax-form');

    const backLink = mainContent.querySelector('a[href]')?.getAttribute('href')
        || currentPath.replace(/\/(create|\d+\/edit)$/, '');

    // Remove inner Cancel/Save bar
    const bottomBars = form.querySelectorAll('.d-flex.justify-content-end');
    if (bottomBars.length > 0) {
        const lastBar = bottomBars[bottomBars.length - 1];
        if (lastBar.querySelector('a.btn, button')) {
            lastBar.remove();
        }
    }

    window.normalizeModalStatusField(form);
    bodyEl.innerHTML = '';
    bodyEl.appendChild(form);
    window.clearAntiAutofillFields(bodyEl);

    if (submitBtn) {
        submitBtn.textContent = isEdit ? 'Save Changes' : 'Save';
        submitBtn.onclick = function () {
            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return;
            }
            window.submitModalFormAjax(form);
        };
    }

    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();

    // Load the list page in the background behind the opened modal
    if (backLink && backLink !== window.location.href) {
        await window.refreshCurrentListView(backLink);
    }
};
