<!-- Reusable Square Confirmation Banner Modal (380x380 px) -->
<div class="modal fade" id="squareConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content modal-square-content">
            <div id="modalIconBox" class="modal-square-icon-box bg-danger-subtle text-danger">
                <i class="bi bi-trash-fill"></i>
            </div>
            
            <h4 id="modalTitle" class="modal-square-title">Do you want to delete this?</h4>
            <p id="modalSubTitle" class="modal-square-subtitle">This action cannot be undone.</p>
            
            <form id="modalConfirmForm" method="POST" action="" class="w-100">
                @csrf
                <input type="hidden" name="_method" id="modalMethodInput" value="POST">
                
                <div id="modalReasonContainer" class="mb-3 d-none text-start">
                    <label class="form-label small font-weight-bold text-muted">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" id="modalReasonInput" class="form-control form-control-sm" rows="3" placeholder="Enter reason here..."></textarea>
                </div>

                <div class="d-flex justify-content-center gap-3 mt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 py-2 border font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="modalConfirmBtn" class="btn btn-danger rounded-pill px-4 py-2 font-weight-bold">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
