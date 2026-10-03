@extends('template.app')

@section('content')


<style>
  .modal-dialog {
    max-width: 100%;
    margin: 0 auto;
  }

  .modal-content {
    max-height: 100vh; 
    overflow-y: auto; 
  }

  .modal-body {
    overflow-y: auto;
    max-height: 90vh; 
  }
  
  @media (max-width: 768px) {
      /* MOBILE ONLY */
      .input-style {
        width: 150px !important;
      }
      .select-style {
        width: 80px !important;
      }
  }

  /* Evidence upload, restyled to match the Travel reimbursement form's
     dropzone look (Sep 2026 feedback: "tampilan entertainment belum
     berubah, sesuaikan dengan tampilan travel") -- same visual language,
     kept inside Entertainment's existing per-row table structure rather
     than switching to Travel's day-card layout. The actual upload/OCR
     wiring is unchanged: still public/js/reimbursement-driver-upload.js
     (bound on .addFile/.addCamera) + reimbursement-ocr-check.js for the
     badge -- this only adds a drag-and-drop target on top of it.
     Compact version (Sep 2026 feedback: "buat lebih rapih") -- the dropzone
     started out full-size like Travel's, but that's disproportionate inside
     a narrow table cell (lots of empty dashed box + a wordy "Upload" label +
     the camera button stacked awkwardly below). Here it's a small square
     icon target with the camera button sitting right beside it instead. */
  /* Per-row upload/camera buttons are hidden: uploading happens once, in
     Step 1 (Sep 2026 feedback: "satu aja upload nya jadi yang diatas aja").
     Only the BUTTONS go -- the row's hidden <input type="file"> stays in the
     DOM and is still what Step 1's dropzone fills via
     DriverUpload.processAndAppendFile(), so removing it would break uploading
     altogether. The Evidence column header is hidden too (see .et-col-evidence)
     since nothing visible is left in it. */
  .et-evidence-cell { display: none; }
  /* The Evidence column itself now holds nothing visible -- only the hidden
     file inputs Step 1 writes into -- so the column is collapsed rather than
     left as a confusing empty gap. The inputs stay in the DOM and are still
     submitted normally; display:none on a cell does not stop that. */
  .et-col-evidence, td.file-proof { display: none; }
  .et-dropzone {
    width: 40px; height: 40px; border: 2px dashed #cfd8e3; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: border-color .15s, background .15s; flex: none;
  }
  .et-dropzone:hover, .et-dropzone.is-dragover { border-color: #28a745; background: #f4fff7; }
  .et-dropzone i { font-size: 15px; color: #8a94a6; }
  .et-camera-btn { width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; flex: none; }
  /* Preview: the shared renderFilePreview()/reimbursement-ocr-check.js badge
     markup (from public/js/reimbursement-driver-upload.js, also used by
     Driver reimbursement) renders a bulky bordered box with a solid red
     "x" BUTTON and an OCR badge that can overflow this narrow column (Sep
     2026 feedback: "dibikin rapih jangan kayak gitu"). Overridden here,
     scoped to .et-preview only so Driver's own look is untouched, to match
     Travel's compact preview-card pattern instead: a small round "x"
     overlaid on the corner of the thumbnail (see
     reimbursement-travel-upload.js's .preview-card-remove) rather than a
     separate filled button sitting beside it, and the OCR badge/thumbnail
     both pinned to the same 96px column width so nothing pokes out into
     the Remark/Action columns next to it. */
  .et-preview { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; width: 96px; }
  .et-preview .pending-attachment-item, .et-preview .preview-card {
    position: relative; width: 64px; margin: 0 !important; border: none !important; padding: 0 !important;
  }
  .et-preview .pending-attachment-item > div { display: block !important; }
  .et-preview .preview-thumbnail { width: 64px !important; height: 64px !important; max-width: 64px !important; max-height: 64px !important; object-fit: cover; }
  .et-preview .remove-pending-attachment {
    position: absolute !important; top: -6px !important; right: -6px !important;
    width: 18px !important; height: 18px !important; padding: 0 !important; margin: 0 !important;
    line-height: 16px !important; font-size: 12px !important; border-radius: 50% !important;
    background: #fff !important; border: 1px solid #dc3545 !important; color: #dc3545 !important;
    box-shadow: none !important;
  }
  .et-preview .remove-pending-attachment:hover { background: #dc3545 !important; color: #fff !important; }
  .et-preview .ocr-check-badge { width: 96px !important; max-width: 96px !important; }

  /* Same numbered step badges / section-card look as the Travel reimbursement
     form (Sep 2026 feedback: "samain designnya kayak di reimbursement
     travel") -- applied on top of Entertainment's existing single-row-per-
     expense table instead of switching to Travel's per-day card layout,
     since the two forms model different things (one line item per guest/
     attendance here vs. one card per travel day there). Modal body gets a
     light grey backdrop (matching the page background Travel's cards sit
     on) so the white .et-section-card panels actually stand out instead of
     blending into the modal's own white background. */
  #formModal .modal-content { background: #f4f5f7; }
  #formModal .modal-header, #formModal .modal-footer { background: #fff; }
  .et-step-title { display: flex; align-items: center; gap: 10px; margin: 4px 0 14px; }
  .et-step-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 50%; background: #28a745; color: #fff;
    font-weight: 700; font-size: 13px; flex: none;
  }
  .et-step-title h5 { margin: 0; font-weight: 700; }
  .et-section-card { background: #fff; border: 1px solid #e6e9ee; border-radius: 10px; padding: 18px 20px; margin-bottom: 20px; }

  /* Step 1: Upload Evidence -- a general dropzone at the top of the form,
     matching Travel's Step 1 (Sep 2026 feedback: "yang di entertainment
     form nya samain kayak di travel buat di atas untuk uploadnya"). Each
     file dropped here becomes its own new row via .addMore, with the file
     attached to that row's own Evidence cell/OCR check -- the per-row
     dropzone in the table (see .et-dropzone above) still exists for adding
     more evidence to an existing row or when rows are added manually first. */
  .et-tips-trigger { padding: 0; margin-left: auto; color: #c99a1a; font-size: 15px; }
  .et-tips-trigger:hover { color: #8a6416; }
  .et-tips-modal-body { background: #fffbea; border: 1px solid #ffe9a8; border-radius: 8px; padding: 14px 16px; font-size: 13px; }
  .et-top-dropzone {
    border: 2px dashed #cfd8e3; border-radius: 8px; padding: 22px 16px; text-align: center;
    cursor: pointer; transition: border-color .15s, background .15s;
  }
  .et-top-dropzone:hover, .et-top-dropzone.is-dragover { border-color: #28a745; background: #f4fff7; }
  .et-top-dropzone i { font-size: 22px; color: #8a94a6; display: block; margin-bottom: 6px; }
  .et-top-dropzone span { font-size: 12.5px; color: #495057; }
  .et-top-dropzone small { display: block; font-size: 10.5px; color: #8a94a6; margin-top: 4px; }
  /* "Take Photo" under the Step 1 dropzone: the per-row camera button still
     exists in the markup but its whole column is hidden (see .et-col-evidence),
     so this is the only camera the redesigned form actually shows. */
  .et-top-camera-btn { margin-top: 10px; width: 100%; }
  .et-top-evidence-list { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
  .et-top-evidence-item {
    display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #d9d9d9;
    border-radius: 6px; padding: 6px 10px; font-size: 11.5px; max-width: 220px;
  }
  /* Delete control on a Step 1 chip. flex:none keeps it at its own size while
     the text column absorbs the slack, so it always sits inside the chip. */
  .et-top-evidence-remove {
    flex: none; width: 20px; height: 20px; padding: 0;
    display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; color: #c0392b;
    font-size: 16px; line-height: 1; cursor: pointer; border-radius: 50%;
  }
  .et-top-evidence-remove:hover { background: #fdecea; }
  .et-top-evidence-item img { width: 36px; height: 36px; object-fit: cover; border-radius: 4px; flex: none; }
  /* The text column must be allowed to shrink: a flex item defaults to
     min-width:auto, so a long file name would push the X button past the
     chip's max-width instead of being ellipsised. */
  .et-top-evidence-item .et-top-evidence-text { flex: 1 1 auto; min-width: 0; overflow: hidden; }
  .et-top-evidence-item .et-top-evidence-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .et-top-evidence-item .et-top-evidence-row { display: block; font-size: 10px; color: #1e7e34; font-weight: 600; }
  .et-top-evidence-item .et-top-evidence-num { flex: none; font-weight: 700; color: #6c757d; margin-right: 2px; }
  /* Preview column now shows the file NUMBER(s) attached to that row instead
     of a thumbnail -- the pictures live in Step 1. */
  /* The shared uploader still renders a thumbnail card into .et-preview; it is
     hidden here because this column shows numbers now. It stays in the DOM (it
     carries the hidden file input and the OCR badge hooks) -- only its visual
     part is suppressed. */
  .et-preview .pending-attachment-item { display: none; }
  .et-preview-num {
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    height: 30px; padding: 0 11px; margin: 2px;
    background: #e8f7ee; color: #1e7e34; border: 1px solid #b7e2c6;
    border-radius: 15px; font-size: 12.5px; font-weight: 700; cursor: pointer;
  }
  .et-preview-num:hover { background: #d4f0de; }
  /* Eye a touch larger than the digit: at the same size it read as a smudge
     rather than an icon (Sep 2026 feedback: "icon nya gedein dikit"). */
  .et-preview-num i { font-size: 15px; line-height: 1; }
</style>

<div class="page-content" id="app">

@php
  $showApprovalTab = in_array(auth()->user()->jabatan, ['Owner', 'Finance', 'HR GA', 'Finance Supervisor', 'Finance Manager', 'superadmin', 'admin'], true) || (int) $check_approval > 0;
@endphp
@if($showApprovalTab)
<div class="clearfix">
     <a href="{!!url('reimbursement-entertaiment')!!}" class="btn btn-success float-left" style="width: 48%;">My Inquiry</a>
     <a href="{!!url('reimbursement-entertaiment-approval')!!}" class="btn btn-info float-right" style="width: 48%;">Approval</a>
</div>
@endif

<!-- @if($showApprovalTab)
<div class="alert alert-info mt-2 mb-0" role="alert">
  <strong>Cara approve:</strong> Di halaman ini tidak ada tombol approve per baris. Buka tab <strong>Approval</strong> untuk setujui banyak klaim sekaligus, atau klik <strong>nomor klaim</strong> lalu gunakan tombol <strong>Approve</strong> di halaman detail (sesuai peran: Head Department → HR GA → Finance).
</div>
@else
<div class="alert alert-light border mt-2 mb-0" role="alert">
  Pengajuan Anda bisa dilihat di tabel di bawah. Untuk menyetujui klaim orang lain, akun Anda harus memiliki peran verifikator (Head Department / HR GA / Finance) atau akses Approval.
</div>
@endif -->

<br>

<div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                    <div class="col-12">
                      <p class="card-title clr-green" >Dashboard</p>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="row"> 
                            <div class="col-sm-6">
                                <h2 class="card-title clr-green">Reimbursement Entertainment @if($check_approval > 0) (My Inquiry) @endif</h2>
                            </div>
                        </div>
                    </div>
                  
                  
                  
                  <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-1 mb-3">
                            <label for="status">Show</label>
                            <select id="show-data" class="form-control select2">
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                      
                        <div class="col-md-2 mb-3">
                            <label for="status">Status</label>
                            <select name="status" class="form-control select2 status" @change="searchStatus" v-model="status">
                                <option value="1">APPROVED HEAD DEPT</option>
                                <option value="2">APPROVED HR GA</option>
                                <option value="3">APPROVED FINANCE MANAGER / PROCESS SETTLEMENT</option>
                                <option value="5">SETTLED</option>
                                <option value="9">REJECT</option>
                                <option value="0">PENDING</option>
                                <option value="10">DRAFT</option>
                            </select>
                        </div>
                       <!--  @if (auth()->user()->jabatan != "karyawan")
                            <div class="col-md-3 mb-3">
                                <label for="user_id">Employee</label>
                                <select name="user_id" @change="searchDriver" class="form-control select2" v-model="user_id">
                                    <option v-for="item in employees" :value="item.id">@{{item.name}}</option>
                                </select>
                            </div>
                        @endif -->
                        <div class="col-md-2 mb-3">
                            <label for="inquiry_no">Inquiry No</label>
                            <input type="text" class="form-control" v-model="inquiry_no" placeholder="998 / 00999" @keyup.enter="search()">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="daterange">Period</label>
                            <input type="text" name="daterange" class="form-control daterange"/>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <button class="btn btn-primary d-block" @click="search()" style="margin-top:32px"><i class="fa fa-search"></i></button>
                                <button class="btn btn-primary d-block" @click="reset()" style="margin-top:32px"><i class="fas fa-sync-alt fa-fw"></i></button>
                                <button class="btn btn-primary d-block" @click="print()" style="margin-top:32px"><i class="fa fa-print"></i></button>
                                <button type="button" class="btn btn-primary btn-sm w-100 create-data" data-toggle="modal" data-target=".bd-example-modal-lg" style="margin-top:32px">
                                <i class="fa fa-plus-circle" aria-hidden="true"></i> Create Inquiry
                            </button>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
                </div>
                @if(session()->has('success'))
                    {{-- Popup rather than an inline banner (same as Travel), so a
                         finished create/update is never missed -- especially on a
                         phone, where a banner above the table scrolls out of view. --}}
                    <div class="modal fade" id="etSuccessModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content" style="border-radius:10px;border:none;">
                                <div class="modal-body text-center" style="padding:28px 24px 8px;">
                                    <div style="width:46px;height:46px;border-radius:50%;background:#e6f6ec;color:#28a745;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:10px;"><i class="fa fa-check-circle"></i></div>
                                    <h5 style="font-weight:700;color:#2b3a55;">Success</h5>
                                    <p style="color:#495057;font-size:14px;white-space:pre-line;">{{ session()->get('success') }}</p>
                                </div>
                                <div class="modal-footer" style="border-top:none;justify-content:center;padding:0 24px 24px;">
                                    <button type="button" class="btn btn-primary" data-dismiss="modal" style="min-width:120px;">OK</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        window.addEventListener('load', function () {
                            if (window.jQuery && jQuery.fn.modal) { jQuery('#etSuccessModal').modal('show'); }
                        });
                    </script>
                @endif
                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                        <div class="alert alert-danger">
                            {{ $error }}
                        </div>
                    @endforeach
                @endif
                

                  <table id="myTable" class="display" style="width:1080px"  >
                      <thead>
                          <tr>
                              <th><div class="form-check"><input class="form-check-input" type="checkbox" value="" id="checkAll"></div></th>
                              <th>Inquiry No</th>
                              <th>Apply Date</th>
                              <th>Transaction Date</th>
                              <th>Inquiry By</th>
                              <th>Total Inquiry</th>
                              <th>Status Inquiry</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>


                      </tbody>

                  </table>
              </div>
          </div>
      </div>
  </div>
  <div class="modal fade bd-example-modal-lg" id="formModal" style="overflow-y: auto">
    <form method="post" id="sample_form" action="{{url('/')."/reimbursement-entertaiment"}}" enctype="multipart/form-data">
    @csrf
      <!--<div class="modal-dialog modal-xxl" style="max-width: 80% !important">-->
      <div class="modal-dialog modal-xxl" style="max-width: min(100%, 1400px);margin: 19px auto;display: flex;">
          <div class="modal-content">
              <div class="modal-header border-bottom"  >
              <div class="d-flex justify-content-between w-100">
                    <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle">Create Reimbursement Entertainment</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
                </div>
              </div>

              <div class="modal-body py-3">

              <div class="et-section-card">
              <div class="et-step-title">
                  <span class="et-step-badge">1</span>
                  <h5>Upload Evidence (Invoice / Receipt)</h5>
                  <button type="button" class="btn btn-link btn-sm et-tips-trigger" title="Tips" data-toggle="modal" data-target="#etEvidenceTipsModal">
                      <i class="fa fa-lightbulb"></i>
                  </button>
              </div>
              <p class="text-muted" style="margin-top:-8px;margin-bottom:14px;font-size:12.5px;">Upload struk/invoice di sini untuk langsung membuat baris baru di Detail Reimbursement -- atau upload manual per baris di kolom Evidence pada tabel di bawah.</p>
              <div class="et-top-dropzone" id="etTopDropzone" title="Klik atau drag &amp; drop file di sini">
                  <i class="fa fa-cloud-upload-alt"></i>
                  <span>Drag &amp; drop file di sini atau <b>klik untuk pilih file</b></span>
                  <small>JPG, PNG, PDF -- bisa lebih dari satu file sekaligus, tiap file jadi satu baris baru</small>
              </div>
              <input type="file" id="etTopFileInput" accept="image/*,.pdf,application/pdf" multiple style="display:none">
              <button type="button" id="etTopCameraBtn" class="btn btn-outline-success btn-sm et-top-camera-btn">
                  <i class="fa fa-camera"></i> Take Photo
              </button>
              <div id="etTopEvidenceList" class="et-top-evidence-list"></div>
              </div>

              <div class="et-section-card">
              <div class="et-step-title">
                  <span class="et-step-badge">2</span>
                  <h5>Informasi Reimbursement</h5>
              </div>
              <div class="row my-3">

                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">Employee</label>
                       <input type="hidden" name="id_user" value="{{Auth::user()->id}}">
                       <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" placeholder="Nama Lengkap" value="{{Auth::user()->name}}">
                     </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">NIK</label>
                       <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" value="{{Auth::user()->idKaryawan}}">
                     </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">Apply Date</label>
                       <input type="text" class="form-control date-picker" style="border-radius: 10px;" value="{{date('d F Y')}}" readonly>
                     </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">Transaction Date</label>
                       <input type="date" class="form-control date-picker" name="date" id="exampleFormControlInput1" style="border-radius: 10px;" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Department</label>
                      <select name="reimbursement_department_id" id="" class="form-control">
                        @foreach (\App\Departemen::get() as $item)
                            <option value="{{$item->id}}" @if(auth()->user()->departmentId == $item->id) selected @endif>{{$item->nama_departemen}}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Remark</label>
                      <input type="text" class="form-control date-picker" name="remark_parent" id="exampleFormControlInput1" style="border-radius: 10px;" value="" required>
                    </div>
                  </div>

                </div>
              </div>

              <div class="et-section-card">
              <div class="et-step-title">
                  <span class="et-step-badge">3</span>
                  <h5>Detail Reimbursement</h5>
              </div>
              <p class="text-muted" style="margin-top:-8px;margin-bottom:14px;font-size:12.5px;">Isi setiap baris kehadiran/tamu dan upload bukti (invoice/struk) untuk masing-masing baris.</p>
<div class="respon respon-big table-responsive">
                <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3
            align=center width="1400">
                  <thead>
                      <tr>
                          <td>No of Attendance</td>
                          <td>Attendance</td>
                          <td>Position</td>
                          <td>Place</td>
                          <td>Guest</td>
                          <td>Guest Position</td>
                          <td>Company</td>
                          <td>Type</td>
                          <td>Payment</td>
                          <td>Amount</td>
                          <td width="100" class="et-col-evidence">Evidence</td>
                          <td>Preview</td>
                          <td>Remark</td>
                          <th align="center">Action</th>
                      </tr>
                  </thead>
                  <tbody>
                    <tr class="fieldGroup">
                      
                          <td>
                            <input type="text" class="form-control input-style" name="empty_zone[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="attendance[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="position[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="place[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="guest[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="guest_position[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="company[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="type[]" placeholder="">
                          </td>
                          <td>
                              <select name="payment_type[]" class="form-control select-style" required>
                                  <option value="" selected disabled>Select...</option>
                                  <option value="BDC">BDC</option>
                                  <option value="Cash">Cash</option>
                              </select>
                          </td>
                          <td>
                            <input type="text" class="form-control amount-input currency amount1 change-amount input-style" name="amount[]"  placeholder="">
                          </td>
                          <td class="file-proof">
                              <div class="et-evidence-cell">
                                  <div class="et-dropzone addFile" data-idx="1" title="Klik atau drag & drop file di sini">
                                      <i class="fa fa-cloud-upload-alt"></i>
                                  </div>
                                  <button type="button" data-idx="1" class="btn btn-outline-secondary btn-sm et-camera-btn addCamera" title="Ambil foto">
                                      <i class="fa fa-camera"></i>
                                  </button>
                              </div>
                              <input type="file" accept="image/*,.pdf,application/pdf" name="file[]" style="display: none;" class="file-input file1">
                              <input type="file" accept="image/*,.pdf,application/pdf" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                          </td>
                          <td>
                              <div id="preview_1" class="et-preview"></div>
                          </td>
                          
                          <td>
                            <input type="text" class="form-control input-style" name="remark[]" placeholder="Remark">
                          </td>
                          <td>
                            <button  type="button" name="add" id="add" class="btn btn-success full-width addMore">+</button>
                          </td>
                    </tr>
                  </tbody>
                </table>

</div>
              </div>

              <div class="et-section-card">
              <div class="et-step-title">
                  <span class="et-step-badge">4</span>
                  <h5>Total</h5>
              </div>
                <div class="form-group">
                <label for="exampleFormControlInput1">Total Inquiry</label>
                <input type="text" v-model="grandtotal" class="form-control number-format" id="sum" style="border-radius: 10px;" name="total_pengajuan" readonly placeholder="">
                </div>

              </div>
              </div>

              <span style="color:#62d49e;text-align:right;" class="warning-upload">The button is disabled until a file is uploaded.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
              <div class="modal-footer">
                  <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                  <button class="btn btn-primary" type="submit" id="action_button" name="save">Submit</button>
                  <button class="btn btn-warning" type="submit" id="action_button_draft" name="save_draft">Draft</button>
              </div>
          </div>
      </div>
  </div>
</form>
</div>

<!-- Modal -->
<div class="modal fade" id="modalPassword"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">AMANKAN PASSWORD</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div class="modal-body">
              <span id="form_result_add"></span>
              <p>Password Anda masih menggunakan password default dari sistem. <br>
              Demi keamanan akun, kami menyarankan Anda melakukan perubahan password terlebih dahulu!</p>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Nanti dulu</button>
                  <a href="{!! url('profile') !!}" class="btn btn-primary">Ubah Password</a>
              </div>
        </div>
    </div>
</div>



</div>

<!-- Tips for Step 1 "Upload Evidence" at the top of the create form. -->
<div class="modal fade" id="etEvidenceTipsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-lightbulb" style="color:#c99a1a;"></i> Tips Upload Evidence</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div class="modal-body">
                <div class="et-tips-modal-body">
                    Gunakan foto yang jelas dan terbaca. Pastikan seluruh invoice/struk terlihat dalam foto.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Mengerti</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalPhoto"  data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Upload Gambar</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <i class="material-icons">close</i>
              </button>
          </div>
          <div class="modal-body">
            <video id="videoElement" autoplay style="width: 100%"></video>
            <!-- <canvas id="canvas"></canvas> -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button id="captureButton" class="btn btn-success">Capture Image</button>
            </div>
      </div>
  </div>
  </div>
</div>

<!-- End Modal -->

<!-- Closes the #app / page-content wrapper opened at the top of this file.
     That closing tag was missing entirely (a pre-existing bug, not
     introduced in this change), which meant the #app element's outerHTML
     (what Vue's el:'#app' mount compiles as its template, since no Vue
     `template` option is given) never actually closed anywhere on this
     page. That left every element after this point, including the
     scripts pushed below, inside Vue's template -- exactly what produces
     the "avoid placing tags with side-effects such as script" console
     warning. -->
</div>

@push('scripts')
@if($travelEntertainmentOcrEnabled ?? false)
<script src="{{ asset('js/reimbursement-ocr-check.js') }}?v={{ @filemtime(public_path('js/reimbursement-ocr-check.js')) }}"></script>
@endif
<script src="{{ asset('js/reimbursement-driver-upload.js') }}?v={{ @filemtime(public_path('js/reimbursement-driver-upload.js')) }}"></script>
<script src="{{ asset('js/reimbursement-duplicate-check.js') }}?v={{ @filemtime(public_path('js/reimbursement-duplicate-check.js')) }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.13.4/jquery.mask.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){

    @if(Auth::user()->status_password != 1)
        $('#modalPassword').modal('show');
    @endif

    @if ($errors->any())
        $('#formModal').modal('show');
    @endif

    // No standalone date-only duplicate popup here -- a duplicate is only
    // flagged when tanggal + No Invoice + Nominal ALL match an existing
    // claim (business decision, Sep 2026), which is checked per cost-line
    // row via the OCR badge (see reimbursement-ocr-check.js) and enforced
    // server-side at save time.

    // Drag & drop onto the Evidence dropzone (Sep 2026 restyle to match the
    // Travel form's look). Delegated on 'body' since rows are added
    // dynamically by .addMore. Click-to-browse and the actual upload/OCR
    // pipeline are untouched -- this only adds a second way to feed the same
    // row's .file-input, via DriverUpload.processAndAppendFile() (the exact
    // function .addFile's click handler already uses in
    // reimbursement-driver-upload.js), so both paths behave identically.
    $('body').on('dragover', '.et-dropzone', function (e) {
        e.preventDefault();
        $(this).addClass('is-dragover');
    });
    $('body').on('dragleave', '.et-dropzone', function () {
        $(this).removeClass('is-dragover');
    });
    $('body').on('drop', '.et-dropzone', function (e) {
        e.preventDefault();
        $(this).removeClass('is-dragover');
        var file = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files && e.originalEvent.dataTransfer.files[0];
        if (!file || !window.DriverUpload) {
            return;
        }
        var $row = $(this).closest('tr');
        window.DriverUpload.processAndAppendFile($row, file);
    });

    /**
     * Step 1 "Upload Evidence" -- a general dropzone at the top of the form
     * (Sep 2026 feedback: "yang di entertainment form nya samain kayak di
     * travel buat di atas untuk uploadnya"), mirroring Travel's Step 1.
     * Each file dropped/picked here gets its own row: the very first file
     * reuses the form's single still-empty starting row (so opening the
     * modal and immediately uploading doesn't leave a pointless blank row
     * above it); every file after that clicks .addMore to create a fresh
     * row, then attaches to it via the exact same
     * DriverUpload.processAndAppendFile() the per-row dropzone/camera/drag-
     * drop already use, so preview + OCR check behave identically either
     * way. A small confirmation chip per file (thumbnail + which row number
     * it landed on) is appended to #etTopEvidenceList for feedback, since
     * the file's real preview lives down in that row's own Evidence column.
     */
    /**
     * Where the next Step 1 file should land: the FIRST row that has no file
     * yet, whichever it is -- only adding a new row when every existing one is
     * already taken.
     *
     * It used to reuse a row only when there was exactly one and it was empty,
     * so pressing (+) first, or deleting a photo and re-uploading, always
     * appended yet another row (Sep 2026 feedback: "saya pencet (+) ... trus
     * upload foto, fotonya jadi no 3 dan line nya nambah jadi 3" and "saya
     * hapus dari dua-duanya, lalu upload ulang tapi malah nambah line 3").
     */
    function etTopDropzoneTargetRow() {
        var $rows = $('#dynamic_field tbody tr.fieldGroup');
        var $free = $rows.filter(function () {
            return $(this).find('.et-preview').children().length === 0;
        }).first();

        if ($free.length) {
            return $free;
        }

        $('.addMore').trigger('click');
        return $('#dynamic_field tbody tr.fieldGroup').last();
    }

    /**
     * Renumbers every chip (1., 2., 3. ...) and re-binds each file to the row
     * at the SAME position: file 1 -> row 1, file 2 -> row 2, and so on. There
     * is no row picker any more (Sep 2026: "gausah ada baris 1 atau sejenisnya
     * langsung sesuai urutan aja") -- order is the whole rule, so deleting a
     * file shifts everything after it up automatically.
     */
    function etRefreshTopEvidenceChips() {
        $('#etTopEvidenceList .et-top-evidence-item').each(function (i) {
            var $chip = $(this);
            $chip.find('.et-top-evidence-num').text((i + 1) + '.');
            etBindChipToRow($chip, i + 1);
        });
        etSyncPreviewNumbers();
    }

    /**
     * Moves the chip's real <input type="file"> into row `rowNo` and renames it
     * to that row's index. The server binds a file to its row purely by the
     * index in the input name (attachments[<rowIndex>][]) -- see
     * EntertaimentReimbursementController -- so this is what actually makes
     * "file N belongs to row N" true on save, not just on screen.
     */
    function etBindChipToRow($chip, rowNo) {
        $chip.attr('data-row', String(rowNo));
        var uid = $chip.attr('data-uid');
        if (!uid) {
            return;
        }
        var $target = $('#dynamic_field tbody tr.fieldGroup').eq(rowNo - 1);
        var $input = $('.pending-attachment-input[data-uid="' + uid + '"]');
        if (!$target.length || !$input.length) {
            return;
        }
        var $box = $target.find('.attachment-inputs').first();
        if (!$box.length) {
            $box = $('<div class="attachment-inputs" style="display:none;"></div>');
            $target.find('.file-proof').first().append($box);
        }
        $input.attr('name', 'attachments[' + (rowNo - 1) + '][]').appendTo($box);
    }

    /**
     * The Detail table's Preview column shows the NUMBER of each file attached
     * to that row (Sep 2026: "preview nya nggk perlu gambar foto lagi tapi
     * angka aja"), so the table stays compact and the pictures live in Step 1.
     */
    function etSyncPreviewNumbers() {
        $('#dynamic_field tbody tr.fieldGroup').each(function (idx) {
            var rowNo = idx + 1;
            var nums = [];
            $('#etTopEvidenceList .et-top-evidence-item').each(function (i) {
                if (String($(this).attr('data-row')) === String(rowNo)) {
                    nums.push(i + 1);
                }
            });
            var $cell = $(this).find('.et-preview').first();
            // Only the number badges are rebuilt -- NOT .empty(), which would
            // also destroy the hidden .pending-attachment-item cards that carry
            // the real file inputs and OCR hooks.
            $cell.find('.et-preview-num').remove();
            nums.forEach(function (n) {
                // Eye icon so it reads as clickable (Sep 2026: "harusnya
                // preview nya ada icon mata gitu biar user tau bisa dipencet").
                $('<button type="button" class="et-preview-num" title="Lihat file ' + n + '">')
                    .attr('data-file-no', n)
                    .append($('<i class="fa fa-eye">'))
                    .append($('<span>').text(n))
                    .appendTo($cell);
            });
        });
    }

    /**
     * @param {string} uid ties this chip to the row's real hidden file input,
     *   so the X button can delete the actual attachment and not just the chip.
     */
    function etAddTopEvidenceChip(file, $row, uid) {
        var $item = $('<div class="et-top-evidence-item">');
        if (uid) {
            $item.attr('data-uid', uid);
        }
        if (file.type && file.type.indexOf('image/') === 0) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $item.prepend($('<img>').attr('src', e.target.result));
                // Kept on the chip so the Preview eye button can open this
                // exact file without re-reading it.
                $item.attr('data-src', e.target.result).attr('data-kind', 'image');
            };
            reader.readAsDataURL(file);
        } else {
            $item.prepend($('<i class="fa fa-file-pdf" style="color:#dc3545;font-size:22px;">'));
            try {
                $item.attr('data-src', URL.createObjectURL(file)).attr('data-kind', 'pdf');
            } catch (e) { /* preview simply unavailable */ }
        }
        $item.append(
            $('<div class="et-top-evidence-text">').append(
                $('<span class="et-top-evidence-num">').text('0.'),
                $('<span class="et-top-evidence-name">').text(file.name)
            )
        );
        // Delete control (Sep 2026 feedback: "nggk ada tombol x (silang) yg
        // biasa digunakan untuk hapus foto"). Removes the real attachment on
        // the row too, not just this chip -- see the handler below.
        $item.append(
            $('<button type="button" class="et-top-evidence-remove" title="Hapus file ini">&times;</button>')
        );
        $('#etTopEvidenceList').append($item);
        etRefreshTopEvidenceChips();
        return $item;
    }

    function etHandleTopEvidenceFiles(fileList) {
        if (!fileList || !fileList.length || !window.DriverUpload) {
            return;
        }
        Array.prototype.forEach.call(fileList, function (file) {
            var $row = etTopDropzoneTargetRow();
            // The chip goes up right away (so the file is visible while it is
            // still being compressed/OCR'd) and is tied to its attachment once
            // processAndAppendFile resolves with the uid.
            var $chip = etAddTopEvidenceChip(file, $row);
            window.DriverUpload.processAndAppendFile($row, file).then(function (processed) {
                if ($chip && processed && processed.attachmentUid) {
                    $chip.attr('data-uid', processed.attachmentUid);
                }
            });
        });
    }

    // X on a Step 1 chip deletes the real attachment as well: the chip is only
    // a receipt for a file that actually lives in its row's Evidence cell, so
    // removing the chip alone would leave the file silently attached.
    $('body').on('click', '.et-top-evidence-remove', function () {
        var $chip = $(this).closest('.et-top-evidence-item');
        var uid = $chip.attr('data-uid');
        if (uid && window.DriverUpload && window.DriverUpload.removePendingPreview) {
            var $item = $('.pending-attachment-item[data-uid="' + uid + '"]');
            if ($item.length) {
                window.DriverUpload.removePendingPreview($item);
            } else {
                // Preview not rendered (yet): drop the hidden input directly so
                // the file still doesn't get submitted.
                $('.pending-attachment-input[data-uid="' + uid + '"]').remove();
            }
        }
        $chip.remove();
        etRefreshTopEvidenceChips();
    });

    /** Opens file number N (the chip at that position) full size. */
    $('body').on('click', '.et-preview-num', function () {
        var n = parseInt($(this).attr('data-file-no'), 10);
        var $chip = $('#etTopEvidenceList .et-top-evidence-item').eq(n - 1);
        var src = $chip.attr('data-src');
        if (!src) {
            return;
        }
        if ($chip.attr('data-kind') === 'pdf') {
            window.open(src, '_blank');
            return;
        }
        if (!$('#etImageLightbox').length) {
            $('body').append(
                '<div id="etImageLightbox" style="display:none;position:fixed;inset:0;z-index:2000;' +
                     'background:rgba(0,0,0,.8);align-items:center;justify-content:center;padding:20px;">' +
                  '<img style="max-width:100%;max-height:100%;border-radius:6px;">' +
                '</div>'
            );
            $('body').on('click', '#etImageLightbox', function () { $(this).hide(); });
        }
        $('#etImageLightbox img').attr('src', src);
        $('#etImageLightbox').css('display', 'flex');
    });

    $('#etTopDropzone').on('click', function () {
        $('#etTopFileInput').trigger('click');
    });
    $('#etTopFileInput').on('change', function (e) {
        etHandleTopEvidenceFiles(e.target.files);
        e.target.value = '';
    });

    /**
     * Step 1 "Take Photo": opens the webcam in #modalPhoto and feeds the
     * captured frame into etHandleTopEvidenceFiles() -- the same entry point
     * the dropzone and drag-drop use -- so the photo becomes a normal row
     * attachment with the same preview, OCR and submit behaviour.
     *
     * The stream is stopped on every way out (capture, Cancel, the X, or a
     * backdrop click); otherwise the camera light stays on and the device
     * stays locked for other apps.
     */
    $('#etTopCameraBtn').on('click', function () {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Browser ini tidak bisa mengakses kamera. Silakan pakai "klik untuk pilih file".');
            return;
        }

        var $modal = $('#modalPhoto');
        var video = document.getElementById('videoElement');
        var activeStream = null;

        var stopCamera = function () {
            if (activeStream) {
                activeStream.getTracks().forEach(function (t) { t.stop(); });
                activeStream = null;
            }
            if (video) {
                video.srcObject = null;
            }
        };

        $modal.off('hidden.bs.modal.etcam').on('hidden.bs.modal.etcam', function () {
            $('#captureButton').off('click.etcam');
            stopCamera();
        });

        navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'environment' }
        }).then(function (stream) {
            activeStream = stream;
            video.srcObject = stream;
            $modal.modal('show');

            $('#captureButton').off('click.etcam').on('click.etcam', function () {
                var w = video.videoWidth || 1280;
                var h = video.videoHeight || 720;
                var canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(video, 0, 0, w, h);

                canvas.toBlob(function (blob) {
                    if (!blob) {
                        return;
                    }
                    // A unique name keeps each shot distinct in the chip list
                    // and in the duplicate-file check.
                    var name = 'camera-' + Date.now() + '.jpg';
                    var photo = new File([blob], name, { type: 'image/jpeg' });
                    etHandleTopEvidenceFiles([photo]);
                }, 'image/jpeg', 0.85);

                $modal.modal('hide');
            });
        }).catch(function () {
            // Permission denied, no camera, or a non-HTTPS origin (browsers
            // only expose getUserMedia on https:// or localhost).
            alert('Kamera tidak bisa dibuka. Cek izin kamera di browser, atau pakai "klik untuk pilih file".');
        });
    });
    $('#etTopDropzone').on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('is-dragover');
    });
    $('#etTopDropzone').on('dragleave', function () {
        $(this).removeClass('is-dragover');
    });
    $('#etTopDropzone').on('drop', function (e) {
        e.preventDefault();
        $(this).removeClass('is-dragover');
        var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
        etHandleTopEvidenceFiles(files);
    });

    function numberWithCommas(x) {
        return x.toString().replace(/\B(?<!\.\d*)(?=(\d{3})+(?!\d))/g, ".");
    }

    $("#checkAll").click(function(){
        $('input:checkbox').not(this).prop('checked', this.checked);
    });

    $("#action_button").prop("disabled", true);
    $("#action_button_draft").prop("disabled", true);
    $(".warning-upload").show();
    
    $(".change-amount").change(function(){
        if ($(".amount1").val()) {
            var amount1 = $(".amount1").val().split(".").join("");
        } else {
            var amount1 = 0;
        }
        if ($(".amount2").val()) {
            var amount2 = $(".amount2").val().split(".").join("");
        } else {
            var amount2 = 0;
        }
        if ($(".amount3").val()) {
            var amount3 = $(".amount3").val().split(".").join("");
        } else {
            var amount3 = 0;
        }
        if ($(".amount4").val()) {
            var amount4 = $(".amount4").val().split(".").join("");
        } else {
            var amount4 = 0;
        }
        if ($(".amount5").val()) {
            var amount5 = $(".amount5").val().split(".").join("");
        } else {
            var amount5 = 0;
        }
        if ($(".amount6").val()) {
            var amount6 = $(".amount6").val().split(".").join("");
        } else {
            var amount6 = 0;
        }
        if ($(".amount7").val()) {
            var amount7 = $(".amount7").val().split(".").join("");
        } else {
            var amount7 = 0;
        }
        if ($(".amount8").val()) {
            var amount8 = $(".amount8").val().split(".").join("");
        } else {
            var amount8 = 0;
        }
        if ($(".amount9").val()) {
            var amount9 = $(".amount9").val().split(".").join("");
        } else {
            var amount9 = 0;
        }
        if ($(".amount10").val()) {
            var amount10 = $(".amount10").val().split(".").join("");
        } else {
            var amount10 = 0;
        }
        
        var total  = +amount1 + +amount2 + +amount3 + +amount4 + +amount5 + +amount6 + +amount7 + +amount8 + +amount9 + +amount10;
        $("#sum").val(numberWithCommas(total));
        
     });
        
    $('.currency').mask("#.##0", {
      reverse: true
    });

    $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:0});
    // $('#sum').maskMoney({ thousands:'.', decimal:',', precision:0});

    // Warning (not a block) when this applicant already submitted a claim for
    // this date. Scoped to the applicant on purpose -- the cross-applicant
    // check is the invoice/OCR one, which stays a separate, harder gate.
    if (typeof window.bindReimbursementDuplicateChecks === 'function') {
        window.bindReimbursementDuplicateChecks({
            formSelector: '#sample_form',
            earlyCheckSelectors: 'input[name="date"]',
            checks: [
                {
                    url: '{{ url('/reimbursement/check-duplicate-date') }}',
                    warnOnly: true,
                    params: function ($form) {
                        var date = $form.find('input[name="date"]').val();
                        if (!date) return null;
                        return { reimbursement_type: 3, dates: [date] };
                    }
                }
            ]
        });
    }
    
    $('select[name="status"]').on('change', function(){
        var status = $(this).val();
        if(status) {
            $.ajax({
                url: 'reimbursement-user?status='+status+'&reimbursement_type=3',
                type:"GET",
                dataType:"json",
                beforeSend: function(){
                
                },
                success:function(data) {
                    $('select[name="user_id"]').empty();
                    $('select[name="user_id"]').append('<option value="">-Select Employee-</option>')
                    $.each(data, function(key, value){
                    $('select[name="user_id"]').append('<option value="'+ value.id +'">' + value.name + '</option>');
                    });


                },
            });
        } else {
            $('select[name="user_id"]').empty();
        }
    });
    
    
    var maxGroup = 10;
       var i = 1;
       
       $(".addMore").click(function(){
            i++;
            $("#action_button").prop("disabled", true);
            $("#action_button_draft").prop("disabled", true);
            $(".warning-upload").show();
            $(".modal-body").animate(
              {
                scrollTop: $(".modal-body")[0].scrollHeight,
              },
              500
            );
            if($('body').find('.fieldGroup').length < maxGroup){
             
              var fieldHTML = '<tr class="fieldGroup"><td><input type="text" class="form-control" name="empty_zone[]" placeholder=""></td><td><input type="text" class="form-control" name="attendance[]" placeholder=""></td><td><input type="text" class="form-control" name="position[]" placeholder=""></td><td><input type="text" class="form-control" name="place[]" placeholder=""></td><td><input type="text" class="form-control" name="guest[]" placeholder=""></td><td><input type="text" class="form-control" name="guest_position[]" placeholder=""></td><td><input type="text" class="form-control" name="company[]" placeholder=""></td><td><input type="text" class="form-control" name="type[]" placeholder=""></td><td><select name="payment_type[]" class="form-control" required><option value="" selected disabled>Select...</option><option value="BDC">BDC</option><option value="Cash">Cash</option></select></td><td><input type="text" class="form-control amount-input currency amount'+i+' change-amount" name="amount[]"  placeholder=""></td><td class="file-proof"><div class="et-evidence-cell"><div class="et-dropzone addFile" data-idx="'+i+'" title="Klik atau drag & drop file di sini"><i class="fa fa-cloud-upload-alt"></i></div><button type="button" data-idx="'+i+'" class="btn btn-outline-secondary btn-sm et-camera-btn addCamera" title="Ambil foto"><i class="fa fa-camera"></i></button></div><input type="file" accept="image/*,.pdf,application/pdf" name="file[]"  style="display: none;" class="file-input file'+i+'"><input type="file" accept="image/*,.pdf,application/pdf" name="proof[]" capture="camera" class="camera-input" style="display: none;"></td><td><div id="preview_'+i+'" class="et-preview"></div></td><td><input type="text" class="form-control" name="remark[]" placeholder="Remark"></td><td><button  type="button" name="add" id="add" class="btn btn-danger full-width remove-item">-</button></td></tr>';
              
              $('body').find('.fieldGroup:last').after(fieldHTML);
              // New row available: chip dropdowns must offer it.
              if (typeof etRefreshTopEvidenceChips === 'function') {
                  etRefreshTopEvidenceChips();
              }
              
              $("body").on("click",".remove-item",function(){ 
                 $(this).parents(".fieldGroup").remove();
                 // Row list changed: chip dropdowns and the Preview numbers
                 // must be rebuilt against the rows that remain.
                 if (typeof etRefreshTopEvidenceChips === 'function') {
                     etRefreshTopEvidenceChips();
                 }
                 
                    if ($(".amount1").val()) {
                        var amount1 = $(".amount1").val().split(".").join("");
                    } else {
                        var amount1 = 0;
                    }
                    if ($(".amount2").val()) {
                        var amount2 = $(".amount2").val().split(".").join("");
                    } else {
                        var amount2 = 0;
                    }
                    if ($(".amount3").val()) {
                        var amount3 = $(".amount3").val().split(".").join("");
                    } else {
                        var amount3 = 0;
                    }
                    if ($(".amount4").val()) {
                        var amount4 = $(".amount4").val().split(".").join("");
                    } else {
                        var amount4 = 0;
                    }
                    if ($(".amount5").val()) {
                        var amount5 = $(".amount5").val().split(".").join("");
                    } else {
                        var amount5 = 0;
                    }
                    if ($(".amount6").val()) {
                        var amount6 = $(".amount6").val().split(".").join("");
                    } else {
                        var amount6 = 0;
                    }
                    if ($(".amount7").val()) {
                        var amount7 = $(".amount7").val().split(".").join("");
                    } else {
                        var amount7 = 0;
                    }
                    if ($(".amount8").val()) {
                        var amount8 = $(".amount8").val().split(".").join("");
                    } else {
                        var amount8 = 0;
                    }
                    if ($(".amount9").val()) {
                        var amount9 = $(".amount9").val().split(".").join("");
                    } else {
                        var amount9 = 0;
                    }
                    if ($(".amount10").val()) {
                        var amount10 = $(".amount10").val().split(".").join("");
                    } else {
                        var amount10 = 0;
                    }
                    
                    var total  = +amount1 + +amount2 + +amount3 + +amount4 + +amount5 + +amount6 + +amount7 + +amount8 + +amount9 + +amount10;
                    $("#sum").val(numberWithCommas(total));
              });
              
              $('.currency').mask("#.##0", {
                  reverse: true
              });
              
              $(".change-amount").change(function(){
                if ($(".amount1").val()) {
                    var amount1 = $(".amount1").val().split(".").join("");
                } else {
                    var amount1 = 0;
                }
                if ($(".amount2").val()) {
                    var amount2 = $(".amount2").val().split(".").join("");
                } else {
                    var amount2 = 0;
                }
                if ($(".amount3").val()) {
                    var amount3 = $(".amount3").val().split(".").join("");
                } else {
                    var amount3 = 0;
                }
                if ($(".amount4").val()) {
                    var amount4 = $(".amount4").val().split(".").join("");
                } else {
                    var amount4 = 0;
                }
                if ($(".amount5").val()) {
                    var amount5 = $(".amount5").val().split(".").join("");
                } else {
                    var amount5 = 0;
                }
                if ($(".amount6").val()) {
                    var amount6 = $(".amount6").val().split(".").join("");
                } else {
                    var amount6 = 0;
                }
                if ($(".amount7").val()) {
                    var amount7 = $(".amount7").val().split(".").join("");
                } else {
                    var amount7 = 0;
                }
                if ($(".amount8").val()) {
                    var amount8 = $(".amount8").val().split(".").join("");
                } else {
                    var amount8 = 0;
                }
                if ($(".amount9").val()) {
                    var amount9 = $(".amount9").val().split(".").join("");
                } else {
                    var amount9 = 0;
                }
                if ($(".amount10").val()) {
                    var amount10 = $(".amount10").val().split(".").join("");
                } else {
                    var amount10 = 0;
                }
                
                var total  = +amount1 + +amount2 + +amount3 + +amount4 + +amount5 + +amount6 + +amount7 + +amount8 + +amount9 + +amount10;
                $("#sum").val(numberWithCommas(total));
                
             });
             
            } else{
              alert('Maximum '+maxGroup+' groups are allowed.');
            }
          });

});
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  new Vue({
      el: '#app',
      data: {
          reimburses: [
            {
              id: null,
              empty_zone: null,
              attendance: null,
              position: null,
              place: null,
              guest: null,
              guest_position: null,
              company: null,
              type: null,
              amount: null,
              total: 0,
              remark: null,
              evidence: null
            }
          ],
          grandtotal: 0,
          start: null,
          end: null,
          user_id: null,
          status: null,
          inquiry_no: '',
      },
      
      mounted() {
        
        // this.loadData()
        $('#show-data').on('change', () => {
          this.loadData(this.start, this.end, this.status, this.user_id);
        });
        $(".number-format").change(function() {
          $(this).maskMoney({ thousands:'.', decimal:',', precision:0});
        })
        $(function() {
            $('input.daterange').daterangepicker({
                opens: 'left'
            }, function(start, end, label) {
                console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
            });
        });
        self = this
        self.loadData(self.start,self.end,self.status, self.user_id);
        $("input.daterange").on('apply.daterangepicker', function(ev, picker) {
          var startDate = picker.startDate.format('YYYY-MM-DD');
          var endDate = picker.endDate.format('YYYY-MM-DD');
          self.start = startDate
          self.end = endDate
          console.log("Selected date range: " + startDate + ' to ' + endDate);
          self.loadData(startDate,endDate,self.status, self.user_id);
      });
        this.initSelectForm()
       
      },
      
      methods: {
        // searchStatus(){
        //   self = this
        //   // this.loadData(this.start,this.end,this.status, this.user_id);
        //   $.ajax({
        //     url: `{{url("/")}}/reimbursement-user?status=${self.status}&reimbursement_type=3`,
        //     methods: 'GET',
        //     success: function(e) {
        //       console.log(e)
              
        //       self.employees = e.data
        //     }
        //   })

        // },
        searchDriver(){

        },
        reset(){
          this.status = null
          this.user_id = null
          this.inquiry_no = ''
          this.start = null;
          this.end = null;
          $('input.daterange').val('');
          this.loadData(this.start,this.end,this.status, this.user_id);

        },
        search(){
          this.loadData(this.start,this.end,this.status, this.user_id);
        },
        print(){
          var selectedValues = [];
          $('.check-print:checked').each(function(){
              selectedValues.push($(this).val());
          });

          var printQs = [];
          if (this.start) {
            printQs.push('start=' + encodeURIComponent(this.start));
          }
          if (this.end) {
            printQs.push('end=' + encodeURIComponent(this.end));
          }
          var printRangeSuffix = printQs.length ? ('&' + printQs.join('&')) : '';

          if (selectedValues.length > 0) {
              var id = selectedValues.join(',');
              var qs = ['selected=' + encodeURIComponent(id)];
              if (this.user_id != null && this.user_id !== '' && String(this.user_id) !== 'undefined') {
                qs.push('driver=' + encodeURIComponent(this.user_id));
              }
              if (this.status != null && this.status !== '') {
                qs.push('status=' + encodeURIComponent(this.status));
              }
              window.open("{{ url('/') }}/reimbursement-entertaiment-print?" + qs.join('&') + printRangeSuffix, "_blank");
              return;
          }

          var status = $('.status').val();
          if (status == null || status === '') {
            alert('Status cannot be empty');
            return false;
          }

          var user_id = "{{auth()->user()->id}}";
          window.open("{{url('/')}}/reimbursement-entertaiment-print?driver="+encodeURIComponent(user_id)+"&status="+encodeURIComponent(status)+printRangeSuffix, "_blank");
        },
        
        changeAmount(i){
          subtotal = 0;
          this.reimburses.forEach(element => {
              subtotal += parseInt(element.amount.replaceAll(".",""))
          });
          this.grandtotal = subtotal.toLocaleString('de-DE')
          // $(".number-format").trigger('blur')

        },
        initSelectForm() {
          
        },
        loadData(start = null,end = null, status= null, driver= null) {
          try {
            $('#myTable').dataTable().fnDestroy();
            
          } catch (error) {
            
          }
          
          const perPage = parseInt($('#show-data').val()) || 10;
            
          $('#myTable').dataTable({
            processing: false,
            serverSide: false,
            bPaginate: true,
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            bAutoWidth: false,
            pageLength: perPage,
            order: [],
            ajax: {
              url:'{{ url("reimbursement-entertaiment") }}',
              data:{
                first:start,
                last:end,
                status:status,
                driver:driver,
                inquiry_no: self.inquiry_no,
              }
            },
            columns: [

                      {
                        data: 'checkbox',
                        name: 'checkbox'
                      },
                      {
                        data: 'no_reimbursement',
                        name: 'no_reimbursement'
                      },
                      {
                        data: 'created_at',
                        name: 'created_at'
                      },
                      {
                        data: 'date',
                        name: 'date'
                      },
                      {
                        data: 'no_project',
                        name: 'no_project'
                      },
                      {
                        data: 'nominal_pengajuan',
                        name: 'nominal_pengajuan'
                      },
                      {
                        data: 'status_label',
                        name: 'status_label'
                      },
                      {
                        data: 'action',
                        name: 'action'
                      },

                ],
            });
        },
        calculate(el,item) {
          item.total = ((item.toll) ? parseInt(item.toll) : 0) + ((item.parking) ? parseInt(item.parking) : 0) + ((item.gasoline) ? parseInt(item.gasoline) : 0) + ((item.other) ? parseInt(item.other) : 0) 
          this.grandtotal = 0
          self = this
          this.reimburses.forEach(element => {
            self.grandtotal += parseInt(element.total)            
          });
        },
        addReimbursement() {
          this.reimburses.push({
            id: null,
              empty_zone: null,
              attendance: null,
              position: null,
              place: null,
              guest: null,
              guest_position: null,
              company: null,
              type: null,
              amount: null,
              total: 0,
              remark: null,
              evidence: null
            })
            this.initSelectForm()

            $('input.form-control').focus(function() {
                // Select all text inside the input field
                $(this).select();
            });

            self = this

            this.$nextTick(() => {
              self.initSelectForm();

              $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-input').on('change', (event) => {
                const index = $(event.target).closest('tr').index();
                this.reimburses[index].amount = ($(event.target).val());
                self.changeAmount(0);

              });
            })
        },
        removeReimbursement(i) {
          this.reimburses.splice(i,1)
          self = this
          this.reimburses.forEach(element => {
            self.grandtotal += parseInt(element.total)            
          });
        }
      },
      watch: {
        reimburses(newValue, oldValue) {
          console.log(`Count changed from ${oldValue} to ${newValue}`);
          for (let i = 0; i < newValue.length; i++) {
            const element = newValue[i];
          }
          // Additional logic based on count change
        }
      },
  });
  

  // function this.initSelectForm() {
     
  // }

</script>

@endpush
@endsection
