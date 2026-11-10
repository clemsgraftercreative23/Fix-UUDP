{{-- Superadmin-only: move an approved claim back to an earlier approval step.
     Expects $data (App\Reimbursement). Posts to ReimbursementController@rollback. --}}
@php
    $rollbackTargets = \App\Support\ApprovalRollback::allowedTargets(
        $data->status,
        $data->reimbursement_type,
        $data->menyetujui_finance_supervisor
    );
@endphp
@if (\App\Support\JabatanClassifier::canRollbackApproval(auth()->user()->jabatan) && !empty($rollbackTargets))
    <div class="mt-3">
        <button type="button" class="btn btn-outline-warning" data-toggle="modal" data-target="#modalRollback">
            Rollback Approval
        </button>
    </div>

    <div class="modal fade" id="modalRollback" tabindex="-1" role="dialog" aria-labelledby="modalRollbackLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('reimbursement.rollback', $data->id) }}" method="POST">
                @csrf
                <div class="modal-content text-left">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRollbackLabel">Rollback Approval</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <i class="material-icons">close</i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Klaim <strong>{{ $data->no_reimbursement }}</strong> dikembalikan ke step approval sebelumnya.
                            Tanda tangan/persetujuan pada step tujuan dan sesudahnya akan dihapus dan harus di-approve ulang.
                            Pemohon dan approver step tujuan akan dikirimi notifikasi WhatsApp.
                        </p>
                        <div class="form-group">
                            <label for="rollbackTarget">Kembalikan ke</label>
                            <select name="target_status" id="rollbackTarget" class="form-control" required>
                                @foreach ($rollbackTargets as $targetStatus)
                                    <option value="{{ $targetStatus }}">Menunggu {{ \App\Support\ApprovalRollback::label($targetStatus) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rollbackReason">Alasan <span class="text-danger">*</span></label>
                            <textarea name="reason" id="rollbackReason" rows="4" maxlength="255" class="form-control" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal" aria-label="Close">Batal</button>
                        <button class="btn btn-warning" type="submit">Rollback</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
