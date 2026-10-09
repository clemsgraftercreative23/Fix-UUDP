@extends('template.app')

@section('content')

<?php function rupiah($angka)
{
    return number_format((float) $angka, 0, ',', '.');
} ?>

<?php function entertainment_amount_idr($angka)
{
    $s = number_format((float) \App\Support\ExchangeRateParser::parseFloat($angka), 2, ',', '.');
    // User tidak mau ada ",00": 1.000,00 -> 1.000 ; 1.000,50 tetap.
    if (substr($s, -3) === ',00') {
        $s = substr($s, 0, -3);
    }
    return $s;
}

/**
 * Amount column in the EDIT form: blank stays blank and zero renders as a
 * plain "0", never "0,00" (mirrors Travel's formatTravelAmountForEditForm).
 * "0,00" meant clearing the field took four backspaces and left the caret
 * mid-number, so typing a new amount needed the cursor moved first (Sep 2026
 * feedback). Both "" and "0" still parse back to 0 everywhere.
 * Tambahan: user tidak mau ada ",00" sama sekali -> 1.000,00 tampil "1.000",
 * desimal non-nol seperti ",50" tetap ditampilkan.
 */
function entertainment_amount_edit($angka)
{
    if ($angka === null || (is_string($angka) && trim($angka) === '')) {
        return '';
    }
    $value = (float) \App\Support\ExchangeRateParser::parseFloat($angka);
    if (abs($value) < 0.00001) {
        return '0';
    }
    $s = number_format($value, 2, ',', '.');
    if (substr($s, -3) === ',00') {
        $s = substr($s, 0, -3);
    }
    return $s;
} ?>

@php
if (!function_exists('ent_attachment_rows')) {
    function ent_attachment_rows($detailId, $legacy = '') {
        $rows = [];
        $detailId = (int) $detailId;
        if ($detailId > 0 && \Illuminate\Support\Facades\Schema::hasTable('reimbursement_attachments')) {
            $rows = \App\ReimbursementAttachment::where('detail_type', 'reimbursement_entertaiments')
                ->where('detail_id', $detailId)
                ->orderBy('id')
                ->get(['id', 'file_name', 'original_name'])
                ->toArray();
        }
        $legacy = trim((string) $legacy);
        if ($legacy !== '') {
            $exists = false;
            foreach ($rows as $r) {
                if (($r['file_name'] ?? '') === $legacy) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $rows[] = ['id' => 0, 'file_name' => $legacy, 'original_name' => $legacy];
            }
        }
        return $rows;
    }
}
@endphp

<style>
    .form-control{
        border-radius:5px;
    }
    .custom{
        height:2em; 
        width:80%;
        border-radius: 5px;
    }
    .dotted{
    border: dotted 2px #dee2e6;
    }
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

    /* Table cell padding for better separation */
    .table-bordered td {
        padding-left: 12px;
        padding-right: 12px;
    }

    @media (max-width: 768px) {
        select[name="payment_type[]"] {
            min-width: 100px;
            margin-right: 8px;
        }
        .amount-input {
            min-width: 100px;
            margin-left: 8px;
        }
    }

    .img-lightbox-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.85);
        z-index: 20000;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .img-lightbox-overlay.active {
        display: flex;
    }

    .img-lightbox-content {
        position: relative;
        max-width: 95vw;
        max-height: 90vh;
    }

    .img-lightbox-content img {
        max-width: 95vw;
        max-height: 90vh;
        border-radius: 8px;
    }

    .img-lightbox-close {
        position: absolute;
        right: -12px;
        top: -12px;
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 999px;
        background: #fff;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
    }

    .preview-thumbnail {
        cursor: pointer !important;
    }

  /* ---- Step 1 Upload Evidence: same layout as the create form
     (Sep 2026: "tampilan edit sesuaikan dengan form create"). Uploading
     happens once at the top; the per-row buttons are hidden and the
     Preview column shows file numbers with an eye button. ---- */
  .et-evidence-cell { display: none; }
  .et-col-evidence, td.file-proof { display: none; }
  .et-top-dropzone {
    border: 2px dashed #cfd8e3; border-radius: 8px; padding: 22px 16px; text-align: center;
    cursor: pointer; transition: border-color .15s, background .15s;
  }
  .et-top-dropzone:hover, .et-top-dropzone.is-dragover { border-color: #28a745; background: #f4fff7; }
  .et-top-dropzone i { font-size: 22px; color: #8a94a6; display: block; margin-bottom: 6px; }
  .et-top-dropzone span { font-size: 12.5px; color: #495057; }
  .et-top-dropzone small { display: block; font-size: 10.5px; color: #8a94a6; margin-top: 4px; }
  .et-top-evidence-list { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
  .et-top-evidence-item {
    display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #d9d9d9;
    border-radius: 6px; padding: 6px 10px; font-size: 11.5px; max-width: 220px;
  }
  .et-top-evidence-item img { width: 36px; height: 36px; object-fit: cover; border-radius: 4px; flex: none; }
  .et-top-evidence-item .et-top-evidence-text { flex: 1 1 auto; min-width: 0; overflow: hidden; }
  .et-top-evidence-item .et-top-evidence-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .et-top-evidence-item .et-top-evidence-row { display: block; font-size: 10px; color: #1e7e34; font-weight: 600; }
  .et-top-evidence-item .et-top-evidence-num { flex: none; font-weight: 700; color: #6c757d; margin-right: 2px; }
  .et-top-evidence-remove {
    flex: none; width: 20px; height: 20px; padding: 0;
    display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; color: #c0392b;
    font-size: 16px; line-height: 1; cursor: pointer; border-radius: 50%;
  }
  .et-top-evidence-remove:hover { background: #fdecea; }
  .et-preview .pending-attachment-item { display: none; }
  /* Saved attachments are listed as chips in Step 1 now, so their old card in
     the Preview column is hidden -- the hidden keep_attachment_ids input that
     sits beside it stays in the DOM and is still submitted. */
  .et-preview .existing-attachment-item { display: none; }
  .et-preview-num {
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    height: 30px; padding: 0 11px; margin: 2px;
    background: #e8f7ee; color: #1e7e34; border: 1px solid #b7e2c6;
    border-radius: 15px; font-size: 12.5px; font-weight: 700; cursor: pointer;
  }
  .et-preview-num:hover { background: #d4f0de; }
  /* The digit is now a COUNT of files, not a file number, so it is set in a
     darker pill against the badge to read as a quantity. */
  .et-preview-num span {
      background: #1e7e34; color: #fff; border-radius: 9px;
      min-width: 18px; height: 18px; padding: 0 5px; font-size: 11px;
      display: inline-flex; align-items: center; justify-content: center;
  }

  /* Row evidence gallery -- same shape as the Travel row preview. */
  #etGalleryModal .et-gallery-wrap { position: relative; }
  #etGalleryModal .et-gallery-stage {
      background: #f1f3f5; border-radius: 8px; min-height: 320px;
      display: flex; align-items: center; justify-content: center; padding: 10px;
  }
  #etGalleryModal .et-gallery-stage img { max-width: 100%; max-height: 62vh; object-fit: contain; }
  #etGalleryModal .et-gallery-stage iframe { width: 100%; height: 62vh; border: 0; background: #fff; }
  #etGalleryModal .et-gallery-nav {
      position: absolute; top: 50%; transform: translateY(-50%);
      background: rgba(0,0,0,.45); color: #fff; border: 0; border-radius: 50%;
      width: 36px; height: 36px; display: flex; align-items: center;
      justify-content: center; cursor: pointer;
  }
  #etGalleryModal .et-gallery-nav:hover { background: rgba(0,0,0,.68); }
  #etGalleryModal .et-gallery-prev { left: 10px; }
  #etGalleryModal .et-gallery-next { right: 10px; }
  #etGalleryModal .et-gallery-meta { margin-top: 10px; font-size: 12px; color: #495057; }
  #etGalleryModal .et-gallery-thumbs { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
  #etGalleryModal .et-gallery-thumb {
      width: 52px; height: 52px; border-radius: 6px; overflow: hidden;
      border: 2px solid transparent; cursor: pointer; background: #f1f3f5;
      display: flex; align-items: center; justify-content: center;
  }
  #etGalleryModal .et-gallery-thumb.is-active { border-color: #28a745; }
  #etGalleryModal .et-gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
  #etGalleryModal .et-gallery-thumb i { font-size: 20px; color: #868e96; }
  .et-preview-num i { font-size: 15px; line-height: 1; }

</style>

<div class="page-content" id="app">   

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                {{-- Goes back one step in history so the approval list reappears with the
                     filters still applied (status, period, employee ...) -- those live in
                     the page's Vue state, not the URL, so a plain link to the list would
                     reset them (Oct 2026 feedback). Falls back to the list itself when
                     there is no history to return to, e.g. a link opened in a new tab. --}}
                <a href="{!!url('reimbursement-entertaiment')!!}" class="btn btn-primary" style="float:left;"
                   onclick="if (document.referrer && history.length > 1) { history.back(); return false; }"><i class="fa fa-arrow-circle-left"></i> Back </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">DETAIL REIMBURSEMENT ENTERTAINMENT</h5><hr>
                        <p>Below is the reimbursement data submitted by <b>{{$data->user->name}}</b>.</p>
                        @php
                          $isOwnSubmission = (int) auth()->id() === (int) $data->id_user;
                          $isAssignedHeadDept = auth()->user()->isHeadDeptApproverForSubmitter((int) $data->id_user);
                          $isApproverRole = in_array(auth()->user()->jabatan, ['Direktur Operasional', 'Finance', 'HR GA', 'Finance Supervisor', 'Finance Manager', 'Owner', 'superadmin', 'admin'], true);
                        @endphp
                        @if($isApproverRole && !$isOwnSubmission && in_array((int) $data->status, [0, 1, 2, 11], true))
                        <div class="alert alert-info mb-0 mt-2" role="alert">
                          Verifikasi bertahap: Head Department → HR GA → <strong>Finance Supervisor</strong> → <strong>Finance Manager</strong> → settlement. Direktur Utama dapat menyetujui langsung ke settlement dari tahap HR GA. Anda juga bisa memproses dari halaman <a href="{{ url('reimbursement-entertaiment-approval') }}" class="alert-link">Approval (bulk)</a>.
                        </div>
                        @elseif($isOwnSubmission && in_array((int) $data->status, [0, 1, 2, 11], true))
                        <div class="alert alert-secondary mb-0 mt-2" role="alert">
                          Ini pengajuan Anda. Tombol verifikasi disembunyikan agar tidak ada persetujuan sendiri. Silakan tunggu Head Department / HR GA / Finance Supervisor / Finance Manager.
                        </div>
                        @endif
                        <hr>
                        @if(session()->has('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                        @endif
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <div class="alert alert-danger">
                                    {{ $error }}
                                </div>
                            @endforeach
                        @endif
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Apply Date</label>
                                <input type="text" class="form-control" value="{{ date('d F Y', strtotime($data->created_at))}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Transaction Date</label>
                                <input type="text" class="form-control" id="date" value="{{ date('d F Y', strtotime($data->date))}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Number</label>
                                <input type="text" class="form-control" value="{{$data->no_reimbursement}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Total</label>
                                <input type="text" class="form-control" value="{{ rupiah($data->nominal_pengajuan) }}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Approved by Head Department</label>
                                <input type="text" class="form-control" value="{{strtoupper($data->mengetahui_op)}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Approved by HR GA</label>
                                <input type="text" class="form-control" id="date" value="{{strtoupper($data->mengetahui_finance)}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Approved by Finance Supervisor</label>
                                <input type="text" class="form-control" value="{{ strtoupper($data->menyetujui_finance_supervisor ?? '') }}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Approved by Finance Manager</label>
                                <input type="text" class="form-control" value="{{strtoupper($data->mengetahui_owner)}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Status</label>
                                @php
                                    if($data->mengetahui_op=='-') {
                                        $meng = 'HEAD DEPARTMENT';
                                    } else if($data->mengetahui_finance=='-') {
                                        $meng = 'HR GA';
                                    } else if($data->mengetahui_owner=='-') {
                                        $meng = 'FINANCE';
                                    }
                                    
                                    $status = "PENDING";
                                    switch ((int) $data->status) {
                                        case 1:
                                            $status = "APPROVED HEAD DEPARTMENT";
                                            break;
                                        case 2:
                                            $status = "APPROVED HR GA";
                                            break;
                                        case 11:
                                            $status = "APPROVED FINANCE SUPERVISOR";
                                            break;
                                        case 3:
                                            $status = "APPROVED FINANCE MANAGER / PROCESS SETTLEMENT";
                                            break;
                                        case 9:
                                            $status = "REJECTED ".$meng."";
                                            break;
                                        case 5:
                                            $status = "SETTLED";
                                            break;
                                        case 10:
                                            $status = "DRAFT";
                                            break;
                                        
                                        default:
                                            # code...
                                            break;
                                    }
                                @endphp
                                <input type="text" class="form-control" value="{{$status}}" readonly>
                            </div>

                            @if ($data->status == 9)
                            <div class="form-group col-md-4">
                                <label for="inputPassword4">Reject Reason</label>
                                <input type="text" class="form-control" value="{{$data->reject_reason}}" readonly >
                            </div>
                            @endif
                          <div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body" style="display: block;width: 100%;overflow-x: auto;">
                <hr><span style="color:#66da90;"><h5>Detail Reimbursement</h5></span><hr>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th width="5%">No</th>
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
                        <td>Attachment</td>
                    </tr>
                    </thead>
                    <?php $no = 1; ?>
                    @foreach($data->entertaiments as $row)
                    <tr>
                        <td width="1px">{{$no++}}</td>
                        <td>{{$row->empty_zone}}</td>
                        <td>{{$row->attendance}}</td>
                        <td>{{$row->position}}</td>
                        <td>{{$row->place}}</td>
                        <td>{{$row->guest}}</td>
                        <td>{{$row->guest_position}}</td>
                        <td>{{$row->company}}</td>
                        <td>{{$row->type}}</td>
                        <td>{{$row->payment_type}}</td>
                        <td>{{ rupiah($row->amount) }}</td>
                        <td width="260px">
                            @foreach(ent_attachment_rows($row->id ?? 0, $row->evidence ?? '') as $att)
                                @php $fileName = $att['file_name'] ?? ''; $display = $att['original_name'] ?? $fileName; @endphp
                                @if($fileName !== '')
                                    <div><a href="{{ URL::to('/') }}/images/file_bukti/{{$fileName}}" target="_blank">{{ $display }}</a></div>
                                @endif
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </table>
            </div>

            <div class="col-lg-12">

                    @if ($data->status == 5)
                    <hr />
                    <span style="color: #66da90;"><h5>Detail Settlement</h5></span>
                    
                    <hr />
                    <h6>BDC</h6>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Method</label>
                                <input readonly type="text" class="form-control"/>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Name</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->penerima}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Number</label>
                                <input readonly type="text" class="form-control" name="no_rek" value="{{$data->no_rek}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{$data->bank}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Total</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{ rupiah($bdc) }}" />
                            </div>
                        </div>
                    </div>
                    <hr>

                    <h6>Cash</h6>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Method</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$metode_cash}}" />
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Name</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->penerima}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Number</label>
                                <input readonly type="text" class="form-control" name="no_rek" value="{{$data->no_rek}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{ rupiah($cash) }}" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Total</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{ rupiah($cash) }}" />
                            </div>
                        </div>
                    </div>
                    @endif 
                    <br>
                    <center>
                        @php
                            $isSuperadmin = in_array(auth()->user()->jabatan, ['superadmin', 'admin'], true);
                        @endphp
                        @if ($data->status == 0 && (((auth()->user()->jabatan == 'Direktur Operasional' && $isAssignedHeadDept) || $isSuperadmin)) && ($isSuperadmin || !$isOwnSubmission))                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-warning"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 9 && (auth()->user()->id == $data->id_user || $isSuperadmin)) 
                            <button type="button" class="btn btn-primary"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                        @endif

                        @if ($data->status == 10 && (auth()->user()->id == $data->id_user || $isSuperadmin)) 
                            <button type="button" class="btn btn-primary"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                        @endif
                        
                        @if ($data->status == 1 && (in_array(auth()->user()->jabatan, ['Finance', 'HR GA', 'superadmin', 'admin'], true)) && ($isSuperadmin || !$isOwnSubmission))                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-warning"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 2 && auth()->user()->jabatan == 'Finance Supervisor' && !$isOwnSubmission)
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-warning"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve (Finance Supervisor)</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        @if ($data->status == 2 && (in_array(auth()->user()->jabatan, ['Owner', 'superadmin', 'admin'], true)) && ($isSuperadmin || !$isOwnSubmission))
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-warning"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve ke settlement (Owner)</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        @if ($data->status == 11 && (in_array(auth()->user()->jabatan, ['Finance Manager', 'Owner', 'superadmin', 'admin'], true)) && ($isSuperadmin || !$isOwnSubmission))
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-warning"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve (Finance Manager)</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                    </center>
                    <br><br><br>
            </div>

        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-lg" id="formModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="overflow-y: auto">
    @if (auth()->user()->jabatan == 'karyawan') 
        <form method="post" id="sample_form" action="{{url('/')."/reimbursement-entertaiment/".$data->id}}" enctype="multipart/form-data">
    @else
        <form method="post" id="sample_form" action="{{url('/')."/reimbursement-entertaiment/update-approval/".$data->id}}" enctype="multipart/form-data">
    @endif
    @csrf
    @method('PUT')
    <input type="hidden" name="deletedId" :model="deletedId">
      <div class="modal-dialog modal-xl" style="max-width: 100%;margin: 19;top: 19;bottom: 19;left: 19;right: 19;display: flex;">
          <div class="modal-content">
              <div class="modal-header border-bottom"  >
              <div class="d-flex justify-content-between w-100">
                    <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle">Edit Reimbursement UUDP</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
                </div>
              </div>

              <div class="modal-body py-3">

              <!-- Step 1: Upload Evidence -- same single upload point as the
                   create form. Files land in the rows below in order (file 1 ->
                   row 1, file 2 -> row 2 ...); the per-row upload buttons are
                   hidden by CSS and the Preview column shows numbers. -->
              <div style="border:1px solid #e6e9ef;border-radius:8px;padding:14px 16px;margin-bottom:16px;">
                  <h5 style="font-weight:700;font-size:15px;margin-bottom:4px;">Upload Evidence (Invoice / Receipt)</h5>
                  <p class="text-muted" style="margin-bottom:12px;font-size:12.5px;">Semua bukti diupload di sini. Urutan file mengikuti urutan baris pada Detail Reimbursement.</p>
                  <div class="et-top-dropzone" id="etTopDropzone" title="Klik atau drag &amp; drop file di sini">
                      <i class="fa fa-cloud-upload-alt"></i>
                      <span>Drag &amp; drop file di sini atau <b>klik untuk pilih file</b></span>
                      <small>JPG, PNG, PDF -- bisa lebih dari satu file sekaligus</small>
                  </div>
                  <input type="file" id="etTopFileInput" accept="image/*,.pdf,application/pdf" multiple style="display:none">
                  <div id="etTopEvidenceList" class="et-top-evidence-list"></div>
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
                       <input type="text" class="form-control"  style="border-radius: 10px;" value="{{ date('d F Y', strtotime($data->created_at))}}" readonly>
                     </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">Transaction Date</label>
                       <input type="date" class="form-control date-picker" name="date" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{$data->date}}" required>
                     </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Department</label>
                      <select name="reimbursement_department_id" id="" class="form-control">
                        @foreach (\App\Departemen::get() as $item)
                            <option value="{{$item->id}}">{{$item->nama_departemen}}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Remark</label>
                      <input type="text" class="form-control date-picker" name="remark_parent" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{$data->remark}}">
                    </div>
                  </div>
                  <hr>
                </div>
                <label class="modal-title clr-green" id="exampleModalCenterTitle">Detail Reimbursement</label>
                <div class="respon respon-big table-responsive">
                    
                     <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3 align=center width="1400">
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
                              <td align="center" >Action</td>
                          </tr>
                      </thead>
                      <tbody>
                        <tr class="fieldGroup">
                              <td>
                                <input type="hidden" name="id_detail[]" value="{{ $detail[0]->id ?? '' }}">
                                <input type="text" class="form-control" name="empty_zone[]" required value="{{ $detail[0]->empty_zone ?? '' }}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="attendance[]" required value="{{ $detail[0]->attendance ?? '' }}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="position[]" required value="{{ $detail[0]->position ?? '' }}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="place[]" required value="{{ $detail[0]->place ?? '' }}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="guest[]" required value="{{ $detail[0]->guest ?? '' }}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="guest_position[]" required value="{{ $detail[0]->guest_position ?? '' }}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="company[]" required value="{{ $detail[0]->company ?? '' }}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="type[]" required value="{{ $detail[0]->type ?? '' }}">
                              </td>
                               <td>
                                    <select class="form-control" name="payment_type[]" style="width:100%">
                                        <option value="">Select...</option>
                                        <option value="BDC" @if(($detail[0]->payment_type ?? '') == 'BDC') selected @endif>BDC</option>
                                        <option value="Cash" @if(($detail[0]->payment_type ?? '') == 'Cash') selected @endif>Cash</option>
                                    </select>
                                </td>
                                <td>
                                <input type="text" class="form-control amount-input amount1 currency change-amount" name="amount[]" placeholder="Amount" required value="{{ entertainment_amount_edit($detail[0]->amount ?? null) }}">
                                </td>
                                <td class="file-proof">
                                    <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                                        <i class="fa fa-upload"></i>
                                    </button>
                                    <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                                        <i class="fa fa-camera"></i>
                                    </button>
                                    <input type="file" accept="image/*,.pdf,application/pdf" name="file[]" style="display: none;" class="file-input file1">
                                    <input type="file" accept="image/*,.pdf,application/pdf" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                                </td>
                                <td>
                                    <div id="preview_1" class="et-preview">
                                        @php
                                            // Penanda bahwa baris ini MEMANG mengirim daftar keep_attachment_ids.
                                            // keep_attachment_ids[0][] hanya ada untuk file yang dipertahankan,
                                            // jadi kalau user menghapus SEMUA evidence lama field itu hilang
                                            // total dan controller menyimpulkan "tidak ada perubahan" lalu
                                            // menyalin ulang lampiran lama -- evidence yang sudah dihapus
                                            // muncul lagi setelah Save as Draft. Pola ini mengikuti Travel.
                                            $entRow0Attachments = ent_attachment_rows($detail[0]->id ?? 0, $detail[0]->evidence ?? '');
                                        @endphp
                                        @if(count($entRow0Attachments) > 0)
                                        <input type="hidden" name="keep_attachment_ids_present[0]" value="1" class="keep-attachment-present-marker" data-row="1">
                                        @endif
                                        @foreach($entRow0Attachments as $att)
                                        @php
                                            $attId = (int) ($att['id'] ?? 0);
                                            $fileName = $att['file_name'] ?? '';
                                            $display = $att['original_name'] ?? $fileName;
                                        @endphp
                                        @if($attId > 0)
                                        <input type="hidden" name="keep_attachment_ids[0][]" value="{{ $attId }}" class="keep-attachment-input">
                                        @endif
                                        <div class="existing-attachment-item" style="margin-top:6px; border:1px solid #d9d9d9; border-radius:6px; padding:6px;">
                                            <div style="display:flex; gap:6px; align-items:center;">
                                                <a href="{!!url('images/file_bukti/'.$fileName.'')!!}" target="_blank" class="preview-link" data-preview-src="{!!url('images/file_bukti/'.$fileName.'')!!}">
                                                    <img src="{!!url('images/file_bukti/'.$fileName.'')!!}" class="preview-thumbnail" data-preview-src="{!!url('images/file_bukti/'.$fileName.'')!!}" onclick="openImageLightbox(this.getAttribute('data-preview-src') || this.src)" style="max-width: 55px; max-height: 55px; border: 2px solid rgb(40, 167, 69); border-radius: 5px; margin-top: 5px; cursor: pointer;">
                                                </a>
                                                <a href="{!!url('images/file_bukti/'.$fileName.'')!!}" target="_blank" style="font-size:12px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;">{{ $display }}</a>
                                                @if($attId > 0)
                                                <button type="button" class="btn btn-sm btn-danger remove-existing-attachment" data-attachment-id="{{ $attId }}" style="margin-left:auto;">x</button>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </td>
                              
                              <td>
                                <input type="text" class="form-control" name="remark[]" placeholder="Remark"  value="{{ $detail[0]->remark ?? '' }}">
                              </td>
                              <td>
                                <button type="button" name="add" id="add" class="btn btn-success full-width addMore">+</button>
                              </td>
                        </tr>
                        <?php $numb = 1;?>
                            @foreach ($detail as $key => $row)
                            @if($key > 0)
                            <?php $numb++ ?>
                            <tr class="fieldGroup">
                                  <td>
                                    <input type="hidden" name="id_detail[]" value="{{$row->id}}">
                                    <input type="text" class="form-control" name="empty_zone[]" required value="{{$row->empty_zone}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="attendance[]" required value="{{$row->attendance}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="position[]" required value="{{$row->position}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="place[]" required value="{{$row->place}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="guest[]" required value="{{$row->guest}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="guest_position[]" required value="{{$row->guest_position}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="company[]" required value="{{$row->company}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="type[]" required value="{{$row->type}}">
                                  </td>
                                  <td>
                                        <select class="form-control" name="payment_type[]" style="width:100%">
                                            <option value="">Select...</option>
                                            <option value="BDC" @if($row->payment_type=='BDC') selected @endif>BDC</option>
                                            <option value="Cash" @if($row->payment_type=='Cash') selected @endif>Cash</option>
                                        </select>
                                  </td>
                                  <td>
                                    <input type="text" class="form-control amount{{$numb}} currency change-amount" name="amount[]" placeholder="Amount" required value="{{entertainment_amount_edit($row->amount)}}">
                                  </td>
                                  <td class="file-proof">
                                        <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                                            <i class="fa fa-upload"></i>
                                        </button>
                                        <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                                            <i class="fa fa-camera"></i>
                                        </button>
                                        <input type="file" accept="image/*,.pdf,application/pdf" name="file[]" style="display: none;" class="file-input file1">
                                        <input type="file" accept="image/*,.pdf,application/pdf" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                                    </td>
                                    <td>
                                        <div id="preview_{{$numb}}" class="et-preview">
                                            @php
                                                // Lihat catatan pada baris pertama: penanda ini harus tetap
                                                // terkirim walau semua evidence lama dihapus.
                                                $entRowAttachments = ent_attachment_rows($row->id ?? 0, $row->evidence ?? '');
                                            @endphp
                                            @if(count($entRowAttachments) > 0)
                                            <input type="hidden" name="keep_attachment_ids_present[{{$key}}]" value="1" class="keep-attachment-present-marker" data-row="{{$numb}}">
                                            @endif
                                            @foreach($entRowAttachments as $att)
                                            @php
                                                $attId = (int) ($att['id'] ?? 0);
                                                $fileName = $att['file_name'] ?? '';
                                                $display = $att['original_name'] ?? $fileName;
                                            @endphp
                                            @if($attId > 0)
                                            <input type="hidden" name="keep_attachment_ids[{{$key}}][]" value="{{ $attId }}" class="keep-attachment-input">
                                            @endif
                                            <div class="existing-attachment-item" style="margin-top:6px; border:1px solid #d9d9d9; border-radius:6px; padding:6px;">
                                                <div style="display:flex; gap:6px; align-items:center;">
                                                    <a href="{!!url('images/file_bukti/'.$fileName.'')!!}" target="_blank" class="preview-link" data-preview-src="{!!url('images/file_bukti/'.$fileName.'')!!}">
                                                        <img src="{!!url('images/file_bukti/'.$fileName.'')!!}" class="preview-thumbnail" data-preview-src="{!!url('images/file_bukti/'.$fileName.'')!!}" onclick="openImageLightbox(this.getAttribute('data-preview-src') || this.src)" style="max-width: 55px; max-height: 55px; border: 2px solid rgb(40, 167, 69); border-radius: 5px; margin-top: 5px; cursor: pointer;">
                                                    </a>
                                                    <a href="{!!url('images/file_bukti/'.$fileName.'')!!}" target="_blank" style="font-size:12px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;">{{ $display }}</a>
                                                    @if($attId > 0)
                                                    <button type="button" class="btn btn-sm btn-danger remove-existing-attachment" data-attachment-id="{{ $attId }}" style="margin-left:auto;">x</button>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </td>
                                  
                                  <td>
                                    <input type="text" class="form-control" name="remark[]" placeholder="Remark"  value="{{$row->remark}}">
                                  </td>
                                  <td>
                                    <button type="button" class="btn btn-danger full-width remove-item">-</button>
                                  </td>
                            </tr>
                            
                            
                            @endif
                        @endforeach
                      </tbody>
                    </table>
                        
                </div>
                <br>
                <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Nominal</label>
                <div class="form-group">
                <label for="exampleFormControlInput1">Total Inquiry</label>
                <input type="text" class="form-control number-format" id="sum" style="border-radius: 10px;" name="total_pengajuan" readonly placeholder="" value="{{entertainment_amount_idr($data->nominal_pengajuan)}}">
                </div>

              </div>
              <div class="modal-footer">
{{-- "Back", not "Cancel": this closes the edit modal and returns to the
                       submission detail behind it -- one step back, which is where an
                       approver continues the approval after editing (Oct 2026 feedback). --}}
                  <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-arrow-circle-left"></i> Back</button>
                  <button   class="btn btn-warning" type="submit" name="save_draft">Draft</button>
                  <button   class="btn btn-primary" type="submit" name="save">Submit</button>
              </div>
          </div>
      </div>
  </div>
</form>
</div>


<!-- Modal Edit-->
<div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Insert Pertanggungjawaban</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div id="detailPertanggungjawaban"></div>
              
        </div>
    </div>
</div>
<!-- End Modal Edit-->

<!-- Modal Change-->
<div class="modal fade" id="formModalChange" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Pertanggungjawaban</h5>
                <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button> -->
            </div>
            <div id="changePertanggungjawaban"></div>
              
        </div>
    </div>
</div>
<!-- End Modal Change-->

<!-- Modal Change-->
<div class="modal fade" id="modalReject" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{url('/reimbursement/reject/'.$data->id)}}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Reject Reason</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="material-icons">close</i>
                    </button>
                </div>
                <div class="modal-body">
    
                    <div id="changePertanggungjawaban">
                        <div class="form-group">
                            <label for="">Reason</label>
                            <textarea name="reason" id="" cols="30" rows="10" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal" aria-label="Close">Cancel</button>
                    <button class="btn btn-primary" type="submit">Submit</button>
                </div>
                  
            </div>
        </form>
    </div>
</div>
<!-- End Modal Change-->

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

<!-- Modal Preview Image -->
<div class="modal fade" id="previewImageModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content" style="background: transparent; border: 0; box-shadow: none;">
          <div class="modal-header" style="border: 0;">
              <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1; font-size: 2rem;">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <div class="modal-body text-center pt-0">
              <img id="previewImageModalSrc" src="" alt="Preview" style="max-width: 100%; max-height: 80vh; border-radius: 8px;">
          </div>
      </div>
  </div>
</div>
<!-- End Modal Preview Image -->

<!-- Custom Lightbox -->
<div id="imgLightboxOverlay" class="img-lightbox-overlay">
    <div class="img-lightbox-content">
        <button type="button" class="img-lightbox-close" aria-label="Close">&times;</button>
        <img id="imgLightboxImage" src="" alt="Preview Besar">
    </div>
</div>
<!-- End Custom Lightbox -->
  

{{-- Row evidence gallery. Deliberately OUTSIDE the Vue root above, like the
     rest of the modals here, so Vue never tries to compile it. Mirrors the
     Travel row preview: one stage, prev/next, and a thumbnail strip. --}}
<div class="modal fade" id="etGalleryModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="etGalleryTitle">
          <i class="fa fa-images"></i> EVIDENCE
        </h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="et-gallery-wrap">
          <button type="button" class="et-gallery-nav et-gallery-prev" id="etGalleryPrev"
                  aria-label="Sebelumnya">
            <i class="fa fa-chevron-left"></i>
          </button>
          <div class="et-gallery-stage" id="etGalleryStage"></div>
          <button type="button" class="et-gallery-nav et-gallery-next" id="etGalleryNext"
                  aria-label="Berikutnya">
            <i class="fa fa-chevron-right"></i>
          </button>
        </div>
        <div class="et-gallery-meta">
          <span id="etGalleryName" class="et-gallery-name"></span>
        </div>
        <div class="et-gallery-thumbs" id="etGalleryThumbs"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="etGalleryOpenTab">
          <i class="fa fa-external-link-alt"></i> Open in new tab
        </button>
        <button type="button" class="btn btn-success btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="{{ asset('js/reimbursement-driver-upload.js') }}?v={{ @filemtime(public_path('js/reimbursement-driver-upload.js')) }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.13.4/jquery.mask.min.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        window.openImageLightbox = function (src) {
            if (!src) return;
            $('#imgLightboxImage').attr('src', src);
            $('#imgLightboxOverlay').addClass('active');
        };

        $('[data-toggle="tooltip"]').tooltip();   
        
        $('.currency').not('input[name="amount[]"]').mask("#.##0", {
          reverse: true
        });
        // Amount entertainment TANPA maskMoney: maskMoney precision:2 membaca
        // string display sebagai digit sen, sehingga nilai bulat tanpa desimal
        // rusak 100x saat mask/blur (500.000 -> 5.000). Format sendiri:
        // kosong tetap kosong, 0 -> "0", bulat -> titik ribuan tanpa ",00",
        // desimal non-nol (",50") dipertahankan.
        function parseEntAmount(s) {
            s = (s || '').trim();
            if (s === '') return NaN;
            var t = s.split('.').join('').replace(',', '.').replace(/[^0-9.\-]/g, '');
            return parseFloat(t);
        }
        function formatEntAmount(n) {
            if (isNaN(n)) return '';
            if (Math.abs(n) < 0.00001) return '0';
            var neg = n < 0;
            var a = Math.abs(Math.round(n * 100) / 100);
            var intPart = Math.floor(a);
            var frac = Math.round((a - intPart) * 100);
            if (frac === 100) { intPart += 1; frac = 0; }
            var intStr = String(intPart).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            if (frac === 0) return (neg ? '-' : '') + intStr;
            return (neg ? '-' : '') + intStr + ',' + (frac < 10 ? '0' : '') + frac;
        }
        function normalizeEntAmountZero(el) {
            var raw = ($(el).val() || '').trim();
            if (raw === '') return;
            var n = parseEntAmount(raw);
            if (isNaN(n)) return;
            $(el).val(formatEntAmount(n));
        }
        $(document).off('blur.entZero').on('blur.entZero', 'input[name="amount[]"]', function () {
            normalizeEntAmountZero(this);
        });

    /**
     * Edit-only: files already saved on this submission are rendered by Blade
     * as .existing-attachment-item cards inside each row's Preview cell. Lift
     * them into the Step 1 list as chips so saved and newly-added files look
     * and behave the same. Their hidden keep_attachment_ids input is left
     * untouched in the row, so removing a chip must also remove that input --
     * otherwise the server would keep a file the user just deleted.
     */
    function etSeedChipsFromSaved() {
        $('#dynamic_field tbody tr.fieldGroup').each(function () {
            var $row = $(this);
            $row.find('.existing-attachment-item').each(function () {
                var $card = $(this);
                var $img = $card.find('img').first();
                var $link = $card.find('a[target="_blank"]').last();
                var attId = $card.find('.remove-existing-attachment').attr('data-attachment-id') || '';
                var src = $img.attr('src') || $card.find('.preview-link').attr('data-preview-src') || '';
                var name = ($link.text() || '').trim() || 'Lampiran';

                var $item = $('<div class="et-top-evidence-item">')
                    .attr('data-existing-id', attId)
                    .attr('data-src', src)
                    .attr('data-kind', 'image');

                if (src) {
                    $item.append($('<img>').attr('src', src));
                } else {
                    $item.append($('<i class="fa fa-file-pdf" style="color:#dc3545;font-size:22px;">'));
                }
                $item.append(
                    $('<div class="et-top-evidence-text">').append(
                        $('<span class="et-top-evidence-num">').text('0.'),
                        $('<span class="et-top-evidence-name">').text(name)
                    )
                );
                $item.append($('<button type="button" class="et-top-evidence-remove" title="Hapus file ini">&times;</button>'));
                $('#etTopEvidenceList').append($item);
            });
        });
        etRefreshTopEvidenceChips();
    }

    /* ===== Step 1 Upload Evidence -- ported from the create form so both
       screens behave identically (Sep 2026). Files are uploaded once at the
       top; chip order decides the row (file 1 -> row 1), the Preview column
       shows numbers with an eye button, and the per-row upload buttons are
       hidden by CSS. ===== */
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
            // Re-bind to the row this chip already belongs to, NOT to position
            // i+1. The old rule was "file N -> row N", which forced one row per
            // file; a row may hold several files, so the chip keeps its own row
            // and only falls back to its position when it has none yet.
            var rowNo = parseInt($chip.attr('data-row'), 10);
            if (!rowNo || rowNo < 1) {
                rowNo = i + 1;
            }
            etBindChipToRow($chip, rowNo);
        });
        etSyncPreviewNumbers();
    }

    /**
     * Guarantees row `rowNo` submits keep_attachment_ids_present[<rowIndex>].
     *
     * The server treats a MISSING keep field as "user changed nothing, keep
     * every stored attachment". An empty list has to mean "keep none", and the
     * only way to tell those apart is this marker, which is sent even when the
     * row has no kept files left.
     */
    function etEnsureKeepPresentMarker(rowNo) {
        var name = 'keep_attachment_ids_present[' + (rowNo - 1) + ']';
        if ($('input.keep-attachment-present-marker[name="' + name + '"]').length) {
            return;
        }
        var $host = $('#preview_' + rowNo);
        if (!$host.length) {
            $host = $('#dynamic_field tbody tr.fieldGroup').eq(rowNo - 1).find('.et-preview').first();
        }
        if (!$host.length) {
            return;
        }
        $('<input>', {
            type: 'hidden',
            name: name,
            value: '1',
            'class': 'keep-attachment-present-marker'
        }).attr('data-row', String(rowNo)).appendTo($host);
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

        // Saved file: its binding lives in keep_attachment_ids[<rowIndex>][],
        // so re-key that input instead of moving a file input.
        var existingId = $chip.attr('data-existing-id');
        if (existingId) {
            $('.keep-attachment-input[value="' + existingId + '"]')
                .attr('name', 'keep_attachment_ids[' + (rowNo - 1) + '][]');
            // A kept file can be moved to a row that had no stored attachments
            // of its own, so that row never got a server-rendered marker. Without
            // one, the row it LEFT could end up sending no keep field at all and
            // the server would restore everything it used to hold.
            etEnsureKeepPresentMarker(rowNo);
            return;
        }

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
            if (!nums.length) {
                return;
            }
            // ONE button per row showing how many files it holds, not one per
            // file (Oct 2026: "kalau upload 5 gambar masa 5 icon?"). The eye
            // keeps it reading as clickable; the gallery it opens is where the
            // individual files live.
            var count = nums.length;
            $('<button type="button" class="et-preview-num">')
                .attr('title', count > 1
                    ? 'Lihat ' + count + ' file bukti'
                    : 'Lihat file bukti')
                .attr('data-file-no', nums[0])
                .append($('<i class="fa fa-eye">'))
                .append($('<span>').text(count))
                .appendTo($cell);
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
        // Remember which row this file was dropped on, so a later renumber
        // keeps it there instead of pushing it to its position in the list.
        if ($row && $row.length) {
            var rowNo = $('#dynamic_field tbody tr.fieldGroup').index($row) + 1;
            if (rowNo > 0) {
                $item.attr('data-row', String(rowNo));
            }
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
        // One target row for the WHOLE batch, resolved once outside the loop.
        // It used to be resolved per file, so uploading two documents for a
        // single expense created a second, empty row (no guest, amount 0) that
        // carried nothing but the second file -- see the Oct 2026 report. Same
        // change as the create form in index.blade.php.
        var $row = etTopDropzoneTargetRow();
        Array.prototype.forEach.call(fileList, function (file) {
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
        // Saved file: drop its keep_attachment_ids input so the server stops
        // keeping it, and remove the (hidden) card it came from.
        var existingId = $chip.attr('data-existing-id');
        if (existingId) {
            $('.keep-attachment-input[value="' + existingId + '"]').remove();
            $('.remove-existing-attachment[data-attachment-id="' + existingId + '"]')
                .closest('.existing-attachment-item').remove();
            $chip.remove();
            etRefreshTopEvidenceChips();
            return;
        }
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
    /**
     * Files of one row, in the order they appear in Step 1.
     * @returns {Array<{src: string, kind: string, name: string, no: number}>}
     */
    function etRowGalleryFiles(rowNo) {
        var out = [];
        $('#etTopEvidenceList .et-top-evidence-item').each(function (i) {
            var $chip = $(this);
            if (String($chip.attr('data-row')) !== String(rowNo)) {
                return;
            }
            var src = $chip.attr('data-src');
            if (!src) {
                return;
            }
            out.push({
                src: src,
                kind: $chip.attr('data-kind') || 'image',
                name: $chip.find('.et-top-evidence-name').text() || ('File ' + (i + 1)),
                no: i + 1
            });
        });
        return out;
    }

    var etGallery = { files: [], index: 0 };

    function etRenderGallery() {
        var f = etGallery.files[etGallery.index];
        if (!f) {
            return;
        }
        var total = etGallery.files.length;
        $('#etGalleryTitle').text('EVIDENCE — ' + (etGallery.index + 1) + ' OF ' + total);

        var $stage = $('#etGalleryStage').empty();
        if (f.kind === 'pdf') {
            $('<iframe>').attr('src', f.src).appendTo($stage);
        } else {
            $('<img>').attr('src', f.src).appendTo($stage);
        }
        $('#etGalleryName').text(f.name);

        // Arrows only earn their place when there is somewhere to go.
        $('#etGalleryPrev, #etGalleryNext').toggle(total > 1);

        var $thumbs = $('#etGalleryThumbs').empty();
        etGallery.files.forEach(function (file, i) {
            var $t = $('<div class="et-gallery-thumb">')
                .toggleClass('is-active', i === etGallery.index)
                .on('click', function () {
                    etGallery.index = i;
                    etRenderGallery();
                });
            if (file.kind === 'pdf') {
                $t.append($('<i class="fa fa-file-pdf">'));
            } else {
                $t.append($('<img>').attr('src', file.src));
            }
            $t.appendTo($thumbs);
        });
        $thumbs.toggle(total > 1);
    }

    function etOpenGallery(rowNo, startFileNo) {
        var files = etRowGalleryFiles(rowNo);
        if (!files.length) {
            return;
        }
        var start = 0;
        files.forEach(function (f, i) {
            if (f.no === startFileNo) {
                start = i;
            }
        });
        etGallery = { files: files, index: start };
        etRenderGallery();
        $('#etGalleryModal').modal('show');
    }

    // One eye per row opens all of that row's evidence as a gallery, starting
    // at its first file (Oct 2026: match the Travel preview).
    $('body').on('click', '.et-preview-num', function () {
        var $cell = $(this).closest('tr.fieldGroup');
        var rowNo = $('#dynamic_field tbody tr.fieldGroup').index($cell) + 1;
        etOpenGallery(rowNo, parseInt($(this).attr('data-file-no'), 10));
    });

    $('body').on('click', '#etGalleryPrev', function () {
        if (!etGallery.files.length) { return; }
        etGallery.index = (etGallery.index - 1 + etGallery.files.length) % etGallery.files.length;
        etRenderGallery();
    });
    $('body').on('click', '#etGalleryNext', function () {
        if (!etGallery.files.length) { return; }
        etGallery.index = (etGallery.index + 1) % etGallery.files.length;
        etRenderGallery();
    });
    $('body').on('click', '#etGalleryOpenTab', function () {
        var f = etGallery.files[etGallery.index];
        if (f) { window.open(f.src, '_blank'); }
    });

    // Lift already-saved attachments into Step 1 before anything else runs.
    etSeedChipsFromSaved();

    // Close the edit modal as soon as a valid submit starts, so the page is
    // not left staring at the form while the request is in flight (Sep 2026:
    // "ketika pencet submit atau draft modal edit nya ke tutup juga").
    // Bound to 'submit', NOT to the buttons: submit only fires once the
    // browser's own required-field validation has passed, so an incomplete
    // form still shows its errors with the modal open.
    $('#sample_form').on('submit', function () {
        $('#formModal').modal('hide');
        // The backdrop is removed manually because the page navigates away
        // before Bootstrap's hide transition finishes, which would otherwise
        // leave a grey overlay over the list for a moment.
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });

    $('#etTopDropzone').on('click', function () {
        $('#etTopFileInput').trigger('click');
    });
    $('#etTopFileInput').on('change', function (e) {
        etHandleTopEvidenceFiles(e.target.files);
        e.target.value = '';
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

        $('input[name="amount[]"]').each(function () {
            normalizeEntAmountZero(this);
        });
        // Fokus ke Amount nol ("0"/"0,00") langsung jadi blank agar ngetik
        // seenak form buat-baru (tanpa geser kursor dulu). Hanya untuk
        // field yang bisa diketik; readonly dibiarkan "0". Dihapus semua
        // tetap blank, bukan refill "0,00".
        // Delegated sehingga berlaku juga untuk row tambah-baru.
        $(document).off('focusin.entAmountBlank click.entAmountBlank').on('focusin.entAmountBlank click.entAmountBlank', 'input[name="amount[]"]', function (event) {
            var $el = $(event.target).closest('input[name="amount[]"]');
            if (!$el.length || $el.prop('readonly') || $el.prop('disabled')) {
                return;
            }
            var v = ($el.val() || '').trim();
            if (v === '') {
                return;
            }
            var n = parseEntAmount(v);
            if (!isNaN(n) && Math.abs(n) < 0.00001) {
                $el.val('');
            }
        });
        // Batasi ketikan amount ke angka/titik/koma saja.
        // Delegated sehingga berlaku juga untuk row tambah-baru.
        $(document).off('keypress.entAmount').on('keypress.entAmount', 'input[name="amount[]"]', function (e) {
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            var k = e.key;
            if (typeof k === 'string' && k.length === 1 && /[^0-9.,]/.test(k)) {
                e.preventDefault();
            }
        });

        function numberWithCommas(x) {
            var num = Math.round((parseFloat(x) || 0) * 100) / 100;
            var parts = num.toFixed(2).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return parts[1] === '00' ? parts[0] : parts[0] + ',' + parts[1];
        }


       var maxGroup = 10;
       var count = "{{count($detail)}}";
       
       $(".addMore").click(function(){
            count++;
            $(".modal-body").animate(
              {
                scrollTop: $(".modal-body")[0].scrollHeight,
              },
              500
            );
            if($('body').find('.fieldGroup').length < maxGroup){

              var fieldHTML = '<tr class="fieldGroup"><td><input type="text" class="form-control" name="empty_zone[]" placeholder=""></td><td><input type="text" class="form-control" name="attendance[]" placeholder=""></td><td><input type="text" class="form-control" name="position[]" placeholder=""></td><td><input type="text" class="form-control" name="place[]" placeholder=""></td><td><input type="text" class="form-control" name="guest[]" placeholder=""></td><td><input type="text" class="form-control" name="guest_position[]" placeholder=""></td><td><input type="text" class="form-control" name="company[]" placeholder=""></td><td><input type="text" class="form-control" name="type[]" placeholder=""></td><td><select class="form-control" name="payment_type[]" style="width:100%"><option value="">Select...</option><option value="BDC">BDC</option><option value="Cash">Cash</option></select></td><td><input type="text" class="form-control amount-input currency amount'+count+' change-amount" name="amount[]"  placeholder=""></td><td class="file-proof"><button type="button" data-idx="'+count+'" class="btn btn-success btn-sm addFile"><i class="fa fa-upload"></i></button><button type="button" data-idx="'+count+'" class="btn btn-success btn-sm addCamera"><i class="fa fa-camera"></i></button><input type="file" accept="image/*,.pdf,application/pdf" name="file[]"  style="display: none;" class="file-input file'+count+'"><input type="file" accept="image/*,.pdf,application/pdf" name="proof[]" capture="camera" class="camera-input" style="display: none;"></td><td><div id="preview_'+count+'" class="et-preview"></div></td><td><input type="text" class="form-control" name="remark[]" placeholder="Remark"></td><td><button  type="button" name="add" id="add" class="btn btn-danger full-width remove-item">-</button></td></tr>';

              $('body').find('.fieldGroup:last').after(fieldHTML);
              
              $("body").on("click",".remove-item",function(){ 
                 $(this).parents(".fieldGroup").remove();
                 
                    if ($(".amount1").val()) {
                        var amount1 = $(".amount1").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount1 = 0;
                    }
                    if ($(".amount2").val()) {
                        var amount2 = $(".amount2").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount2 = 0;
                    }
                    if ($(".amount3").val()) {
                        var amount3 = $(".amount3").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount3 = 0;
                    }
                    if ($(".amount4").val()) {
                        var amount4 = $(".amount4").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount4 = 0;
                    }
                    if ($(".amount5").val()) {
                        var amount5 = $(".amount5").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount5 = 0;
                    }
                    if ($(".amount6").val()) {
                        var amount6 = $(".amount6").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount6 = 0;
                    }
                    if ($(".amount7").val()) {
                        var amount7 = $(".amount7").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount7 = 0;
                    }
                    if ($(".amount8").val()) {
                        var amount8 = $(".amount8").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount8 = 0;
                    }
                    if ($(".amount9").val()) {
                        var amount9 = $(".amount9").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount9 = 0;
                    }
                    if ($(".amount10").val()) {
                        var amount10 = $(".amount10").val().split(".").join("").replace(",", ".");
                    } else {
                        var amount10 = 0;
                    }
                    
                    var total  = +amount1 + +amount2 + +amount3 + +amount4 + +amount5 + +amount6 + +amount7 + +amount8 + +amount9 + +amount10;
                    $("#sum").val(numberWithCommas(total));
              });
              
              $('.currency').not('input[name="amount[]"]').mask("#.##0", {
                  reverse: true
              });
              // Row baru Amount-nya kosong: tidak perlu init mask (format
              // sendiri via blur handler delegated di atas), biarkan blank
              // agar langsung enak diketik.

              $(".change-amount").change(function(){
                if ($(".amount1").val()) {
                    var amount1 = $(".amount1").val().split(".").join("").replace(",", ".");
                    console.log(amount1);
                } else {
                    var amount1 = 0;
                }
                if ($(".amount2").val()) {
                    var amount2 = $(".amount2").val().split(".").join("").replace(",", ".");
                } else {
                    var amount2 = 0;
                }
                if ($(".amount3").val()) {
                    var amount3 = $(".amount3").val().split(".").join("").replace(",", ".");
                } else {
                    var amount3 = 0;
                }
                if ($(".amount4").val()) {
                    var amount4 = $(".amount4").val().split(".").join("").replace(",", ".");
                } else {
                    var amount4 = 0;
                }
                if ($(".amount5").val()) {
                    var amount5 = $(".amount5").val().split(".").join("").replace(",", ".");
                } else {
                    var amount5 = 0;
                }
                if ($(".amount6").val()) {
                    var amount6 = $(".amount6").val().split(".").join("").replace(",", ".");
                } else {
                    var amount6 = 0;
                }
                if ($(".amount7").val()) {
                    var amount7 = $(".amount7").val().split(".").join("").replace(",", ".");
                } else {
                    var amount7 = 0;
                }
                if ($(".amount8").val()) {
                    var amount8 = $(".amount8").val().split(".").join("").replace(",", ".");
                } else {
                    var amount8 = 0;
                }
                if ($(".amount9").val()) {
                    var amount9 = $(".amount9").val().split(".").join("").replace(",", ".");
                } else {
                    var amount9 = 0;
                }
                if ($(".amount10").val()) {
                    var amount10 = $(".amount10").val().split(".").join("").replace(",", ".");
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
      
          $(".change-amount").change(function(){
                if ($(".amount1").val()) {
                    var amount1 = $(".amount1").val().split(".").join("").replace(",", ".");
                    console.log(amount1);
                } else {
                    var amount1 = 0;
                }
                if ($(".amount2").val()) {
                    var amount2 = $(".amount2").val().split(".").join("").replace(",", ".");
                } else {
                    var amount2 = 0;
                }
                if ($(".amount3").val()) {
                    var amount3 = $(".amount3").val().split(".").join("").replace(",", ".");
                } else {
                    var amount3 = 0;
                }
                if ($(".amount4").val()) {
                    var amount4 = $(".amount4").val().split(".").join("").replace(",", ".");
                } else {
                    var amount4 = 0;
                }
                if ($(".amount5").val()) {
                    var amount5 = $(".amount5").val().split(".").join("").replace(",", ".");
                } else {
                    var amount5 = 0;
                }
                if ($(".amount6").val()) {
                    var amount6 = $(".amount6").val().split(".").join("").replace(",", ".");
                } else {
                    var amount6 = 0;
                }
                if ($(".amount7").val()) {
                    var amount7 = $(".amount7").val().split(".").join("").replace(",", ".");
                } else {
                    var amount7 = 0;
                }
                if ($(".amount8").val()) {
                    var amount8 = $(".amount8").val().split(".").join("").replace(",", ".");
                } else {
                    var amount8 = 0;
                }
                if ($(".amount9").val()) {
                    var amount9 = $(".amount9").val().split(".").join("").replace(",", ".");
                } else {
                    var amount9 = 0;
                }
                if ($(".amount10").val()) {
                    var amount10 = $(".amount10").val().split(".").join("").replace(",", ".");
                } else {
                    var amount10 = 0;
                }
                
                var total  = +amount1 + +amount2 + +amount3 + +amount4 + +amount5 + +amount6 + +amount7 + +amount8 + +amount9 + +amount10;
                $("#sum").val(numberWithCommas(total));
                
          });
          
                    function bindExistingPreviewThumbnails() {
                        $('[id^="preview_"] img').each(function () {
                            $(this)
                                .addClass('preview-thumbnail')
                                .attr('data-preview-src', $(this).attr('src'))
                                .css('cursor', 'pointer');
                        });
                    }

                    bindExistingPreviewThumbnails();

                    $('body').on('click', '.preview-link, .preview-thumbnail, [id^="preview_"] img', function (e) {
                        var src = $(this).attr('data-preview-src') || $(this).find('img').attr('src') || $(this).attr('src');
                        if (!src) return;
                        e.preventDefault();
                        window.openImageLightbox(src);
                    });

                    $('body').on('click', '.remove-existing-attachment', function () {
                        var $btn = $(this);
                        var $item = $btn.closest('.existing-attachment-item');
                        var $preview = $btn.closest('[id^="preview_"]');
                        var attachmentId = String($btn.data('attachment-id') || '');
                        if (attachmentId !== '') {
                            $preview.find('input.keep-attachment-input[value="' + attachmentId + '"]').remove();
                        }
                        $item.remove();
                    });

                    $('body').on('click', '#imgLightboxOverlay, .img-lightbox-close', function (e) {
                        if ($(e.target).is('#imgLightboxOverlay') || $(e.target).is('.img-lightbox-close')) {
                            $('#imgLightboxOverlay').removeClass('active');
                            $('#imgLightboxImage').attr('src', '');
                        }
                    });

                    $(document).on('keydown', function (e) {
                        if (e.key === 'Escape') {
                            $('#imgLightboxOverlay').removeClass('active');
                            $('#imgLightboxImage').attr('src', '');
                        }
                    });

    });
  
</script>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  new Vue({
      el: '#app',
      data: {
        start: null,
        end: null,
        employees: [],
        status: null,
        deletedId: [],
        user_id: null,
        reimburses: @json($data->entertaiments),
        grandtotal: {{$data->nominal_pengajuan}}
      },
      mounted() {
        
        // $(".number-format").maskMoney({ thousands:'.', decimal:',', precision:0});
        // $('.number-format').each('input', () => {
        //     // Update Vue data when input changes
        //     this.amount = $(this).val();
        //   });
        // $(".select2").select2()
        var start = moment().startOf('month');
        var end = moment().endOf('month');
        this.start = start.format('YYYY-MM-DD');
        this.end = end.format('YYYY-MM-DD');
        $(function() {
            $('input.daterange').daterangepicker({
                startDate: start,
                endDate: end,
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
          // self.loadData(startDate,endDate,self.status, self.user_id);
      });
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
          var start = moment().startOf('month');
          var end = moment().endOf('month');
          this.start = start.format('YYYY-MM-DD');
          this.end = end.format('YYYY-MM-DD');
          this.loadData(this.start,this.end,this.status, this.user_id);

        },
        search(){
          this.loadData(this.start,this.end,this.status, this.user_id);
        },
        print(){
          window.open("{{url('/')}}/reimbursement-entertaiment-print?start="+this.start+"&end="+this.end+"&driver="+this.user_id+"&status="+this.status, "_blank")
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
            
            $('#myTable').dataTable({
            processing: false,
            serverSide: false,
            bPaginate: true,
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            bAutoWidth: false,
            pageLength: 5,
            order: [],
            ajax: {
              url:'{{ url("reimbursement-entertaiment") }}',
              data:{
                first:start,
                last:end,
                status:status,
                driver:driver,
              }
            },
            columns: [

                      {
                        data: 'no_reimbursement',
                        name: 'no_reimbursement'
                      },
                      {
                        data: 'created_at',
                        name: 'created_at'
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
                        data: 'action',
                        name: 'action'
                      },

                ],
            });
        },
        calculate(el,item) {
        //   item.total = ((item.toll) ? parseInt(item.toll) : 0) + ((item.parking) ? parseInt(item.parking) : 0) + ((item.gasoline) ? parseInt(item.gasoline) : 0) + ((item.other) ? parseInt(item.other) : 0) 
        //   this.grandtotal = 0
        //   self = this
        //   this.reimburses.forEach(element => {
        //     self.grandtotal += parseInt(element.total)            
        //   });
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
            
            $(".number-format").maskMoney({ thousands:'.', decimal:',', precision:0});
            
            $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0});
            $('.amount-input').on('change', (event) => {
                const index = $(event.target).closest('tr').index();
                this.reimburses[index].toll = ($(event.target).val());
                self.changeAmount(0);
            });
            
            // $('input.form-control').focus(function() {
            //     // Select all text inside the input field
            //     $(this).select();
            // });

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
@if(isset($open_edit_modal) && $open_edit_modal)
<script>
$(document).ready(function () {
    $('.bd-example-modal-lg').modal('show');
});
</script>
@endif
@endpush
@endsection
