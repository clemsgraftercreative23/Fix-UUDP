@extends('template.app')

@section('content')

<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <h2 class="card-title clr-green">Rollback Approval</h2>
                <p class="text-muted">
                  Kembalikan reimbursement yang sudah di-approve ke step approval sebelumnya (Head Dept / HR GA /
                  Finance Supervisor / Finance Manager). Hanya klaim yang sedang menunggu HR GA sampai disetujui
                  Finance (belum dicairkan) yang tampil di sini. Klaim yang sudah dicairkan: lakukan
                  <em>Reset Settlement</em> dulu.
                </p>

                @if (session('success'))
                  <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                  <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                      <div>{{ $error }}</div>
                    @endforeach
                  </div>
                @endif

                <form method="GET" action="{{ url('pengaturan-rollback-approval') }}" class="form-row mb-3">
                  <div class="col-md-4 mb-2">
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Cari no. reimbursement / nama pemohon">
                  </div>
                  <div class="col-md-2 mb-2">
                    <select name="type" class="form-control">
                      <option value="">Semua Tipe</option>
                      @foreach ($typeLabels as $typeValue => $typeLabel)
                        <option value="{{ $typeValue }}" @if(($filters['type'] ?? '') == $typeValue) selected @endif>{{ $typeLabel }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                      <option value="">Semua Tahap</option>
                      <option value="1" @if(($filters['status'] ?? '') == '1') selected @endif>Menunggu HR GA</option>
                      <option value="2" @if(($filters['status'] ?? '') == '2') selected @endif>Menunggu Finance Supervisor</option>
                      <option value="11" @if(($filters['status'] ?? '') == '11') selected @endif>Menunggu Finance Manager</option>
                      <option value="3" @if(($filters['status'] ?? '') == '3') selected @endif>Disetujui Finance (siap pencairan)</option>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <a href="{{ url('pengaturan-rollback-approval') }}" class="btn btn-secondary">Reset</a>
                  </div>
                </form>

                <div class="table-responsive">
                  <table class="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>No. Reimbursement</th>
                        <th>Tipe</th>
                        <th>Pemohon</th>
                        <th class="text-right">Nominal</th>
                        <th>Tahap Saat Ini</th>
                        <th class="text-nowrap">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($rows as $row)
                        <tr>
                          <td>{{ $row->no }}</td>
                          <td>{{ $row->type }}</td>
                          <td>{{ $row->applicant }}</td>
                          <td class="text-right">Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
                          <td>{{ $row->stage }}</td>
                          <td class="text-nowrap">
                            <div class="d-flex flex-row flex-nowrap align-items-center">
                            <a href="{{ $row->detailUrl }}" class="btn btn-sm btn-outline-secondary mr-2 mb-0" target="_blank">Detail</a>
                            @if (!empty($row->targets))
                              <button type="button" class="btn btn-sm btn-warning mb-0 js-open-rollback"
                                data-id="{{ $row->id }}"
                                data-no="{{ $row->no }}"
                                data-stage="{{ $row->stage }}"
                                data-targets="{{ json_encode($row->targets) }}">Rollback</button>
                            @endif
                            </div>
                          </td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="6" class="text-center text-muted">Tidak ada klaim yang bisa di-rollback.</td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                {{ $claims->links() }}
              </div>
          </div>
      </div>
  </div>
</div>

<div class="modal fade" id="modalRollbackSetting" tabindex="-1" role="dialog" aria-labelledby="modalRollbackSettingLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="rollbackSettingForm" action="#" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalRollbackSettingLabel">Rollback Approval</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <i class="material-icons">close</i>
          </button>
        </div>
        <div class="modal-body">
          <p class="text-muted">
            Klaim <strong id="rollbackSettingNo"></strong> (<span id="rollbackSettingStage"></span>) dikembalikan ke
            step approval sebelumnya. Persetujuan pada step tujuan dan sesudahnya dihapus dan harus di-approve ulang.
            Pemohon dan approver step tujuan akan dikirimi notifikasi WhatsApp.
          </p>
          <div class="form-group">
            <label for="rollbackSettingTarget">Kembalikan ke</label>
            <select name="target_status" id="rollbackSettingTarget" class="form-control" required></select>
          </div>
          <div class="form-group">
            <label for="rollbackSettingReason">Alasan <span class="text-danger">*</span></label>
            <textarea name="reason" id="rollbackSettingReason" rows="4" maxlength="255" class="form-control" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
          <button class="btn btn-warning" type="submit">Rollback</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  (function () {
    var baseUrl = "{{ url('reimbursement/rollback') }}";

    $(document).on('click', '.js-open-rollback', function () {
      var $btn = $(this);
      var targets = $btn.data('targets') || [];
      var $select = $('#rollbackSettingTarget').empty();

      targets.forEach(function (t) {
        $select.append($('<option>').val(t.value).text(t.label));
      });

      $('#rollbackSettingForm').attr('action', baseUrl + '/' + $btn.data('id'));
      $('#rollbackSettingNo').text($btn.data('no'));
      $('#rollbackSettingStage').text($btn.data('stage'));
      $('#rollbackSettingReason').val('');
      $('#modalRollbackSetting').modal('show');
    });
  })();
</script>
@endpush

@endsection
