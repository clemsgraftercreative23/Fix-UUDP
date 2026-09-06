@extends('template.app')

@section('content')

<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <h2 class="card-title clr-green">Pengaturan Fitur</h2>

                @if (session('success'))
                  <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ url('pengaturan-fitur') }}">
                  @csrf

                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="hidden" name="travel_entertainment_ocr_check_enabled" value="0">
                      <input
                        type="checkbox"
                        class="custom-control-input"
                        id="travel_entertainment_ocr_check_enabled"
                        name="travel_entertainment_ocr_check_enabled"
                        value="1"
                        @if($travelEntertainmentOcrEnabled) checked @endif
                      >
                      <label class="custom-control-label" for="travel_entertainment_ocr_check_enabled">
                        Aktifkan pengecekan OCR No. Invoice/Receipt untuk reimbursement Travel &amp; Entertainment
                      </label>
                    </div>
                    <small class="form-text text-muted">
                      Kalau aktif: No. Invoice/Receipt di form Travel &amp; Entertainment otomatis dibaca dari foto
                      struk yang diupload, dan struk yang sudah pernah dipakai di pengajuan lain akan ditolak otomatis.
                      Kalau nonaktif: tidak ada pembacaan OCR sama sekali (tidak ada panggilan ke Gemini API / tidak
                      memakai kuota), form berjalan seperti biasa tanpa pengisian ataupun pengecekan No. Invoice/Receipt.
                    </small>
                  </div>

                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="hidden" name="driver_ocr_invoice_check_enabled" value="0">
                      <input
                        type="checkbox"
                        class="custom-control-input"
                        id="driver_ocr_invoice_check_enabled"
                        name="driver_ocr_invoice_check_enabled"
                        value="1"
                        @if($driverOcrEnabled) checked @endif
                      >
                      <label class="custom-control-label" for="driver_ocr_invoice_check_enabled">
                        Aktifkan pengecekan OCR No. Invoice/Receipt untuk reimbursement Driver
                      </label>
                    </div>
                    <small class="form-text text-muted">
                      Kalau aktif: No. Invoice/Receipt di form Driver otomatis dibaca dari foto struk yang diupload
                      (bukan diketik manual lagi), dan struk yang sudah pernah dipakai di pengajuan lain akan ditolak
                      otomatis.
                      Kalau nonaktif, form Driver tetap seperti sekarang (No. Invoice diketik manual, tanpa pengecekan).
                    </small>
                  </div>

                  <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
              </div>
          </div>
      </div>
  </div>

</div>

@endsection
