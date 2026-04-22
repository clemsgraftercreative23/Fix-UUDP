@extends('template.app')

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="card-title clr-green m-0">Activity Log</h3>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-2 mb-2">
                            <input type="date" id="date_from" class="form-control" />
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="date" id="date_to" class="form-control" />
                        </div>
                        <div class="col-md-2 mb-2">
                            <select id="module" class="form-control">
                                <option value="">Semua Modul</option>
                                <option value="pengajuan">Cash Advance</option>
                                <option value="reimbursement">Reimbursement</option>
                                <option value="reimbursement-driver">Reimbursement Driver</option>
                                <option value="reimbursement-travel">Reimbursement Travel</option>
                                <option value="reimbursement-entertaiment">Reimbursement Entertainment</option>
                                <option value="reimbursement-medical">Reimbursement Medical</option>
                                <option value="backfill-notif">Backfill Notif</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select id="action" class="form-control">
                                <option value="">Semua Aksi</option>
                                <option value="create">Create</option>
                                <option value="update">Update</option>
                                <option value="approve">Approve</option>
                                <option value="reject">Reject</option>
                                <option value="delete">Delete</option>
                                <option value="draft">Draft</option>
                                <option value="approve_multiple">Approve Multiple</option>
                                <option value="log">Log</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" id="actor" class="form-control" placeholder="User / role / nomor referensi" />
                        </div>
                        <div class="col-md-1 mb-2">
                            <button class="btn btn-primary btn-block" id="btn_filter">Filter</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="activityLogTable" class="display" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Modul</th>
                                    <th>Aksi</th>
                                    <th>Referensi</th>
                                    <th>Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        var table = $('#activityLogTable').DataTable({
            processing: false,
            serverSide: false,
            bPaginate: true,
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            order: [[0, 'desc']],
            ajax: {
                url: '{{ url("activity-log/data") }}',
                data: function (d) {
                    d.module = $('#module').val();
                    d.action = $('#action').val();
                    d.actor = $('#actor').val();
                    d.date_from = $('#date_from').val();
                    d.date_to = $('#date_to').val();
                }
            },
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'actor', name: 'actor' },
                { data: 'module', name: 'module' },
                { data: 'action', name: 'action' },
                { data: 'reference', name: 'reference' },
                { data: 'description', name: 'description' }
            ]
        });

        $('#btn_filter').on('click', function () {
            table.ajax.reload();
        });
    });
</script>
@endpush
