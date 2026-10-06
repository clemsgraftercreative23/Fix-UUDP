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
    .nav-tabs-container {
        width: 100%;
        overflow-x: auto;  /* Mengaktifkan scroll horizontal */
        overflow-y: hidden; /* Mencegah scroll vertikal */
        white-space: nowrap; /* Pastikan elemen tidak pindah ke baris baru */
        -webkit-overflow-scrolling: touch; /* Scroll lebih halus di mobile */
    }

    .nav-tabs {
        display: flex; /* Supaya elemen tetap dalam satu baris */
        flex-wrap: nowrap; /* Mencegah pindah ke baris berikutnya */
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .nav-item {
        flex-shrink: 0; /* Pastikan item tidak mengecil */
        margin-right: 15px; /* Beri sedikit jarak antar item */
    }

    .nav-link {
        display: block;
        padding: 10px 15px;
        white-space: nowrap; /* Pastikan teks tidak terpotong */
    }
    .button-container {
        display: flex;
        flex-wrap: nowrap; /* Pastikan tombol tetap dalam satu baris */
        justify-content: flex-end; /* Posisikan tombol ke kanan */
        overflow-x: auto; /* Scroll horizontal jika tidak cukup ruang */
        padding-bottom: 5px; /* Hindari tombol tertutup scrollbar */
        -webkit-overflow-scrolling: touch; /* Scroll lebih halus di mobile */
    }

    .btn {
        white-space: nowrap; /* Pastikan teks tidak turun ke bawah */
        flex-shrink: 0; /* Mencegah tombol mengecil */
    }
  
    @media (max-width: 768px) {
      /* MOBILE ONLY */
      .cost-type-select {
        width: 150px !important;
      }

      .destination-input {
        width: 200px !important;
      }

      .currency-select {
        width: 80px !important;
      }

      .amount-input {
        width: 80px !important;
      }

      .idr-rate-input,
      .tax-input,
      .exchange-rate-input {
        width: 150px !important;
      }

      .payment-select {
        width: 80px !important;
      }
    }

    .rt-create-tabs { margin-bottom: 12px; }
    .rt-create-tabs .nav-tabs { flex-wrap: wrap; border-bottom: 1px solid #dee2e6; }
    .rt-create-tabs .travel-tab { position: relative; display: inline-flex; align-items: center; }
    .rt-create-tabs .travel-tab .nav-link { padding-right: 26px; cursor: pointer; }
    .rt-create-tabs .tab-close-link { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #c0392b; font-weight: 700; text-decoration: none; line-height: 1; }
    .rt-create-tabs .nav-link.active { font-weight: 700; color: #1e7e34; border-color: #dee2e6 #dee2e6 #fff; }
    /* --- Step wizard look (Sep 2026 redesign: single evidence per day) --- */
    .rt-step-title { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
    .rt-step-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; border-radius: 50%; background: #28a745; color: #fff;
        font-weight: 700; font-size: 13px; flex: none;
    }
    .rt-step-title h5 { margin: 0; font-weight: 700; }
    .rt-step-desc { color: #6c757d; font-size: 12.5px; margin: 0 0 14px 36px; }
    .rt-evidence-card { background: #f8f9fb; border: 1px solid #e2e5ea; border-radius: 8px; padding: 16px 18px; margin-bottom: 18px; }
    .rt-dropzone {
        border: 2px dashed #c9d3e0; border-radius: 8px; background: #fff; text-align: center;
        padding: 26px 14px; cursor: pointer; transition: border-color .15s;
    }
    .rt-dropzone:hover, .rt-dropzone.is-dragover { border-color: #28a745; background: #f4fff7; }
    /* Supporting-Proof mode: a different colour so the dropzone itself shows
       which type the next upload will get, not just the buttons above it. */
    .rt-dropzone.is-proof-mode { border-color: #adb5bd; background: #f8f9fa; }
    .rt-dropzone.is-proof-mode:hover, .rt-dropzone.is-proof-mode.is-dragover {
        border-color: #6c757d; background: #f1f3f5;
    }
    .rt-dropzone i { font-size: 26px; color: #8a94a6; margin-bottom: 6px; display: block; }
    .rt-dropzone small { display: block; color: #8a94a6; margin-top: 6px; }
    .rt-ocr-hint { background: #eef7f0; border: 1px solid #cdeadb; border-radius: 8px; padding: 12px 14px; font-size: 12.5px; height: 100%; }
    .rt-ocr-hint .rt-ocr-hint-title { font-weight: 700; color: #1e7e34; margin-bottom: 6px; }
    .rt-ocr-hint ul { list-style: none; padding: 0; margin: 0; }
    .rt-ocr-hint li { margin-bottom: 4px; }
    .rt-ocr-hint li i { color: #28a745; margin-right: 6px; }
    /* Tips used to be a permanent box taking up a whole column (col-md-3) on
       every day card -- moved into an on-demand modal (Sep 2026 feedback:
       "tips yang di card itu di ilangin aja atau dijadiin modal soalnya
       makan tempat") triggered by this small icon next to the step title. */
    .rt-tips-trigger { padding: 0; margin-left: auto; color: #c99a1a; font-size: 15px; }
    .rt-tips-trigger:hover { color: #8a6416; }
    .rt-tips-modal-body { background: #fffbea; border: 1px solid #ffe9a8; border-radius: 8px; padding: 14px 16px; font-size: 13px; }
    .rt-file-chip {
        display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid #d9d9d9;
        border-radius: 6px; padding: 8px 10px; margin-top: 10px; font-size: 12.5px;
    }
    /* Preview thumbnail, enlarged (Sep 2026 feedback: "preview nya itu jelas
       keliatan/informatif") -- big enough to actually recognize the receipt
       at a glance instead of squinting at a 34px icon, with the status
       colour as a border so read/duplicate/pending is visible without
       reading the text next to it. Still clickable to open full-size. */
    .rt-file-chip img.rt-file-thumb, .rt-file-chip .rt-file-pdf-box {
        width: 64px; height: 64px; object-fit: cover; border-radius: 6px; cursor: pointer; flex: none;
        border: 2px solid #d9d9d9; transition: border-color .15s;
    }
    .rt-file-chip .rt-file-pdf-box { display: flex; align-items: center; justify-content: center; background: #f7f7f7; }
    .rt-file-chip .rt-file-pdf-box i { font-size: 24px; color: #dc3545; }
    .rt-file-chip img.rt-file-thumb[data-status="read"], .rt-file-chip .rt-file-pdf-box[data-status="read"] { border-color: #28a745; }
    .rt-file-chip img.rt-file-thumb[data-status="duplicate"], .rt-file-chip .rt-file-pdf-box[data-status="duplicate"] { border-color: #dc3545; }
    .rt-file-chip img.rt-file-thumb[data-status="pending"], .rt-file-chip .rt-file-pdf-box[data-status="pending"] { border-color: #f0ad4e; }
    .rt-file-chip img.rt-file-thumb[data-status="not_receipt"], .rt-file-chip .rt-file-pdf-box[data-status="not_receipt"] { border-color: #f0ad4e; }
    .rt-file-chip-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
    .rt-file-chip .rt-file-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rt-file-chip .rt-file-row-tag, .rt-file-chip .rt-file-doc-type { font-size: 11px; padding: 1px 4px; height: auto; }
    /* Preview button in Step 3 shows which numbered Step 1 file it links to
       (Sep 2026 feedback). */
    .rt-preview-file-btn { position: relative; }
    .rt-preview-file-btn .rt-preview-file-number {
        position: absolute; top: -6px; right: -6px; min-width: 14px; height: 14px; padding: 0 2px;
        border-radius: 7px; background: #28a745; color: #fff; font-size: 9px; font-weight: 700;
        line-height: 14px; text-align: center;
    }
    /* "+N" when the row carries more evidence files than the numbered one --
       the Preview opens all of them (Oct 2026 feedback). Sits on the opposite
       corner so it never covers the file number. */
    .rt-preview-file-btn .rt-preview-file-more {
        position: absolute; bottom: -6px; right: -6px; min-width: 14px; height: 14px; padding: 0 2px;
        border-radius: 7px; background: #6c757d; color: #fff; font-size: 9px; font-weight: 700;
        line-height: 14px; text-align: center;
    }
    /* Row Preview gallery (Oct 2026): every evidence file of one expense row,
       paged in place instead of opened as several tabs that popup blockers
       would inconsistently drop. */
    #rtRowPreviewModal .rt-rowprev-stage {
        position: relative; background: #f1f3f5; border-radius: 6px; text-align: center;
        min-height: 320px; display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    #rtRowPreviewModal .rt-rowprev-stage img { max-width: 100%; max-height: 62vh; object-fit: contain; }
    #rtRowPreviewModal .rt-rowprev-stage iframe { width: 100%; height: 62vh; border: 0; background: #fff; }
    #rtRowPreviewModal .rt-rowprev-nav {
        position: absolute; top: 50%; transform: translateY(-50%); border: none; cursor: pointer;
        background: rgba(0,0,0,.45); color: #fff; width: 36px; height: 36px; border-radius: 50%; font-size: 15px;
    }
    #rtRowPreviewModal .rt-rowprev-nav:hover { background: rgba(0,0,0,.68); }
    #rtRowPreviewModal .rt-rowprev-prev { left: 10px; }
    #rtRowPreviewModal .rt-rowprev-next { right: 10px; }
    #rtRowPreviewModal .rt-rowprev-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 10px; font-size: 12px; }
    #rtRowPreviewModal .rt-rowprev-badge { border-radius: 10px; padding: 1px 8px; font-size: 10.5px; font-weight: 600; }
    #rtRowPreviewModal .rt-rowprev-badge.is-proof { background: #e9ecef; color: #495057; }
    #rtRowPreviewModal .rt-rowprev-badge.is-invoice { background: #d7ebff; color: #0a58ca; }
    #rtRowPreviewModal .rt-rowprev-thumbs { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
    #rtRowPreviewModal .rt-rowprev-thumb {
        width: 46px; height: 46px; border-radius: 4px; border: 2px solid transparent;
        object-fit: cover; cursor: pointer; background: #fff;
    }
    #rtRowPreviewModal .rt-rowprev-thumb.is-active { border-color: #28a745; }
    #rtRowPreviewModal .rt-rowprev-thumb-pdf {
        width: 46px; height: 46px; border-radius: 4px; border: 2px solid transparent; cursor: pointer;
        background: #fff; display: flex; align-items: center; justify-content: center; color: #c0392b;
    }
    #rtRowPreviewModal .rt-rowprev-thumb-pdf.is-active { border-color: #28a745; }
    /* "Take Photo" sits directly under the dropzone so both ways of adding
       evidence are equally visible (Oct 2026 request). */
    .rt-camera-btn { margin-top: 8px; width: 100%; }
    .rt-file-chip .rt-file-remove { color: #dc3545; cursor: pointer; flex: none; }
    .rt-file-chip .rt-file-preview-btn { padding: 2px 6px; font-size: 11px; flex: none; }
    .rt-file-chip .rt-file-ocr-text { font-size: 10.5px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* Per-file OCR detail line (Sep 2026 feedback: "setiap evident yang
       diupload juga muncul OCR result-nya") -- date/merchant/amount for THIS
       specific file, shown inline/sideways in one small row right under its
       "No. Invoice: ..." line, instead of only the day's single latest file
       having any detail shown at all. */
    .rt-file-chip .rt-file-ocr-detail { font-size: 9.5px; line-height: 1.3; color: #6c757d; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; }
    .rt-file-chip .rt-file-ocr-detail:hover { color: #0a58ca; }
    .rt-file-chip .rt-file-ocr-detail b { color: #495057; font-weight: 600; }
    /* Per-file OCR Result modal (Sep 2026 feedback) -- shows the FULL reading
       for one specific file, read-only, so multiple files' results don't
       overwrite each other in the single editable panel (which can only
       ever hold the one no_invoice/merchant_name the day actually submits). */
    .rt-ocr-detail-modal-field { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 12.5px; }
    .rt-ocr-detail-modal-field label { width: 90px; flex: none; color: #6c757d; margin: 0; font-size: 11.5px; }
    .rt-ocr-detail-modal-field b { flex: 1; }
    .rt-reference-wrap { margin-top: 14px; }
    /* Compact OCR Result -- sits beside the dropzone (in the same column the
       "OCR will read" hint occupies before any file is read) instead of a
       full-width block below it. Sep 2026 feedback: "keterangannya kesamping
       aja, dibuat kecil" -- each field is label+value on one line (inline)
       instead of label stacked above the input. Sep 2026 feedback again:
       "resultnya masih replace, harusnya engga" -- .rt-ocr-summary-list
       stacks ONE .rt-ocr-summary-compact panel PER FILE (see readOcrFiles()),
       each editing that file's own chip data, so a second/third file no
       longer silently overwrites what an earlier file's panel showed. */
    .rt-ocr-summary-list { display: flex; flex-direction: column; gap: 8px; max-height: 420px; overflow-y: auto; }
    .rt-ocr-summary-compact { background: #fff; border: 1px solid #d9e6ff; border-radius: 8px; padding: 8px 10px; font-size: 11px; }
    .rt-ocr-summary-compact-head { display: flex; justify-content: space-between; align-items: center; gap: 6px; margin-bottom: 6px; }
    .rt-ocr-summary-compact-head b { color: #0a58ca; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
    .rt-ocr-summary-compact-head .btn { padding: 0px 5px; font-size: 10px; flex: none; }
    .rt-ocr-summary-compact label { font-size: 9.5px; color: #6c757d; margin: 0; flex: none; width: 62px; }
    .rt-ocr-summary-compact .form-control-sm { font-size: 10.5px; padding: 1px 5px; height: auto; }
    .rt-ocr-summary-compact-field { display: flex; align-items: center; gap: 4px; margin-bottom: 3px; }
    .rt-ocr-summary-compact-field input { flex: 1; min-width: 0; }
    /* Marks which ONE file's reading is the day's "primary" -- the one
       actually submitted as reimburse[i][no_invoice]/[merchant_name] (see
       primaryOcrFile()). Every other file's panel is still fully visible and
       editable; this is purely informational. */
    .rt-ocr-summary-compact-primary { margin-top: 4px; font-size: 9.5px; color: #1e7e34; font-weight: 600; }
    .rt-mess-note, .rt-same-trip-note-inline { margin-top: 10px; font-size: 12px; border-radius: 6px; padding: 8px 10px; }
    .rt-mess-note { background: #f1fff5; border: 1px solid #bfe8cd; color: #1e7e34; }

</style>

<div class="page-content" id="app">
    @if ($errors->any())
        @foreach ($errors->all() as $error)
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endforeach
    @endif
    <div class="">
        <form id="travel_reimbursement_form" action="@if(isset($appendTo)){{ route('reimbursement-travel.store-days', $appendTo->id) }}@else{{ route('reimbursement-travel.store') }}@endif" method="POST" enctype="multipart/form-data" style="overflow-y: auto;" @submit="onTravelFormSubmit">
            @csrf
            @if(!isset($appendTo))
            <div class="row">
                <div class="col-xl">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between w-100"><h2 id="exampleModalCenterTitle" class="modal-title maintitle clr-green mb-0">REIMBURSEMENT UUDP - TRAVEL ( Domestic )</h2>
                            <a href="{!!url('reimbursement-travel')!!}" aria-label="Close" class="close"><i class="material-icons">close</i></a></div>
                            <hr>
                            
                            <input type="hidden" name="travel_type" value="Domestic" />
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="">Employee</label>
                                    <input type="text" class="form-control" readonly value="{{auth()->user()->name}}" />
                                </div>
                                <div class="col-md-3">
                                    <label for="">Apply Date</label>
                                    <input type="text" class="form-control" readonly value="{{date('d F Y')}}" />
                                </div>
                                <div class="col-md-3">
                                    <label for="">Purpose Trip</label>
                                    <input type="text" class="form-control" name="remark" value="" required />
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="exampleFormControlInput1">Department</label>
                                        <select name="reimbursement_department_id" id="" class="form-control">
                                            @foreach (\App\Departemen::get() as $item)
                                            <option value="{{$item->id}}" @if(auth()->user()->departmentId == $item->id) selected @endif>{{$item->nama_departemen}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <hr />
                            <div v-for="(dt,i) in rates" :key="'travel-rate-row-'+i" class="row">
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="">Currency</label>
                                            <input type="text" class="form-control" :name="'rates['+i+'][code]'" v-model.trim="dt.code" @blur="dt.code = (dt.code || '').toUpperCase()" />
                                        </div>
                                        <div class="col-md-8">
                                            <label for="">Exchange Rate</label>
                                            <input type="text" class="form-control exchange-rate-input" :name="'rates['+i+'][rate]'" :value="dt.rate" @input="onExchangeRateInput(i, $event)" @focus="onExchangeRateFocus(i, $event)" @blur="onExchangeRateBlur(i, $event)" autocomplete="off" inputmode="decimal" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 text-left" v-if="i > 0" style="padding-top:28px">
                                    <button type="button" class="btn btn-danger btn-sm" @click.prevent="removeRate(i)" title="Delete rate">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <hr />
                            <br />
                            <hr />
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-primary text-right" @click="addRate"><i class="fa fa-plus"></i> Add Rate</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="row">
                <div class="col-xl">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between w-100"><h2 class="modal-title maintitle clr-green mb-0">ADD DAYS &mdash; {{ $appendTo->no_reimbursement }}</h2>
                            <a href="{{ url('reimbursement-travel/add-item/'.$appendTo->id) }}" aria-label="Close" class="close"><i class="material-icons">close</i></a></div>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="">Employee</label>
                                    <input type="text" class="form-control" readonly value="{{ optional(\App\User::find($appendTo->id_user))->name }}" />
                                </div>
                                <div class="col-md-4">
                                    <label for="">Purpose Trip</label>
                                    <input type="text" class="form-control" readonly value="{{ $appendTo->remark }}" />
                                </div>
                                <div class="col-md-4">
                                    <label for="">Exchange Rates</label>
                                    <input type="text" class="form-control" readonly value="{{ collect($appendRates)->map(function ($r) { return $r->currency . ' ' . number_format((float) $r->rate, 2, ',', '.'); })->implode(' | ') }}" />
                                </div>
                            </div>
                            <small class="text-muted">The days you add here are saved into this submission. Employee, purpose and exchange rates are taken from it.</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <br />
            <div class="row">
                <div class="col-xl">
                    <div class="card">
                        <div class="card-body">
                            <h5 style="font-weight:700;">Business Trip Date Range</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Start Date</label>
                                    <!-- rt-range-start/end: the duplicate trip-date check reads these so
                                         the warning can fire as soon as the range is filled in, without
                                         waiting for "Generate Daily Forms" to create the per-day inputs. -->
                                    <input type="date" v-model="rangeStart" class="form-control rt-range-start">
                                </div>
                                <div class="col-md-3">
                                    <label>End Date</label>
                                    <input type="date" v-model="rangeEnd" class="form-control rt-range-end">
                                </div>
                                <div class="col-md-3" style="padding-top:31px;">
                                    <button type="button" class="btn btn-primary" @click="regenerateDaysFromRange">
                                        <i class="fa fa-calendar-check"></i> Generate Daily Forms
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">A form for each day in the date range is created automatically below. Each one can be opened or closed on its own.</small>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <div class="row" v-if="reimburses.length === 0">
                <div class="col-xl">
                    <div class="card">
                        <div class="card-body text-center text-muted" style="padding:24px;">
                            Belum ada form harian. Isi Start Date &amp; End Date di atas lalu klik <b>Generate Daily Forms</b>.
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" v-for="(data,i) in reimburses" :key="'day-'+i">
                <div class="col-xl">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center" style="cursor:pointer;" @click="toggleDayCollapse(i)">
                                <h5 style="margin:0;font-weight:700;">
                                    <i :class="data.collapsed ? 'fa fa-chevron-right' : 'fa fa-chevron-down'"></i>
                                    Day @{{ i + 1 }} Form <small v-if="data.date" class="text-muted">(@{{ data.date }})</small>
                                </h5>
                                <button type="button" v-if="reimburses.length > 1" class="btn btn-outline-danger btn-sm" @click.stop="removeDay(i)" title="Delete this day form"><i class="fa fa-trash"></i> Delete</button>
                            </div>
                            <hr>
                            <div v-show="!data.collapsed">

                            <div class="rt-step-title">
                                <span class="rt-step-badge">1</span>
                                <h5>Upload Evidence (Invoice / Receipt)</h5>
                                <button type="button" class="btn btn-link btn-sm rt-tips-trigger" title="Tips" @click.stop="$('#rtEvidenceTipsModal').modal('show')">
                                    <i class="fa fa-lightbulb"></i>
                                </button>
                            </div>
                            <p class="rt-step-desc">Upload your invoice or receipt to automatically read the information (OCR).</p>
                            <div class="rt-evidence-card">
                                <div class="row">
                                    <div class="col-md-8">
                                        <!-- The Invoice-vs-Proof choice lives on each file chip below, not
                                             here (Oct 2026 feedback: a secretary attaching e.g. a hotel
                                             invoice plus a screenshot of the email approving the trip has to
                                             mark them differently, and the old single radio applied to the
                                             whole day -- it had to be flipped BEFORE browsing and could not
                                             be corrected afterwards). New files default to Invoice /
                                             Receipt, which is what every upload was treated as before. -->
                                        <!-- Pick the type BEFORE browsing (Oct 2026 request): users were
                                             confused by uploading a ticket or email screenshot, seeing OCR
                                             flag it as "not an invoice", and only then finding the dropdown.
                                             The choice applies to the next upload only, so a day can still
                                             mix invoices and proofs, and each chip's own dropdown still
                                             corrects a file afterwards. -->
                                        <div class="rt-upload-type" style="margin-bottom:8px;">
                                            <div style="font-size:12px;color:#6c757d;margin-bottom:6px;">
                                                Pilih jenis dokumen dulu, lalu upload filenya.
                                            </div>
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Jenis dokumen">
                                                <button type="button"
                                                        class="btn"
                                                        :class="(data.nextDocType || 'invoice') === 'invoice' ? 'btn-success' : 'btn-outline-success'"
                                                        @click="setNextDocType(i, 'invoice')">
                                                    <i class="fa fa-file-invoice"></i> Invoice / Receipt
                                                </button>
                                                <button type="button"
                                                        class="btn"
                                                        :class="data.nextDocType === 'proof' ? 'btn-secondary' : 'btn-outline-secondary'"
                                                        @click="setNextDocType(i, 'proof')">
                                                    <i class="fa fa-paperclip"></i> Supporting Proof
                                                </button>
                                            </div>
                                            <small style="display:block;margin-top:6px;color:#6c757d;">
                                                <template v-if="data.nextDocType === 'proof'">
                                                    Bukti pendukung (tiket, email, surat tugas) &mdash; tidak dibaca OCR.
                                                </template>
                                                <template v-else>
                                                    Struk / invoice &mdash; akan dibaca OCR otomatis.
                                                </template>
                                            </small>
                                        </div>
                                        <div class="rt-dropzone"
                                             :class="data.nextDocType === 'proof' ? 'is-proof-mode' : ''"
                                             @click="$refs['dayFileInput'+i][0].click()"
                                             @dragover.prevent="$event.currentTarget.classList.add('is-dragover')"
                                             @dragleave.prevent="$event.currentTarget.classList.remove('is-dragover')"
                                             @drop.prevent="$event.currentTarget.classList.remove('is-dragover'); onDayFileDrop(i, $event)">
                                            <i class="fa fa-cloud-upload-alt"></i>
                                            Drag &amp; drop file here or<br>
                                            <button type="button" class="btn btn-outline-success btn-sm" style="margin-top:8px;" @click.stop="$refs['dayFileInput'+i][0].click()">
                                                Browse File
                                            </button>
                                            <small>
                                                Upload sebagai
                                                <b>@{{ data.nextDocType === 'proof' ? 'Supporting Proof' : 'Invoice / Receipt' }}</b>
                                                &middot; JPG, PNG, PDF (Max 10MB)
                                            </small>
                                        </div>
                                        <!-- Multiple files per day (Sep 2026 redesign): the real <input type=file>
                                             elements actually submitted are created dynamically per file (see
                                             rtAddDayHiddenFile()) so more than one file can be attached without
                                             each new pick replacing the last -- this input is just the click/browse
                                             target and picks up multiple files at once via `multiple`. -->
                                        <input type="file" :ref="'dayFileInput'+i" accept="image/*,.pdf" multiple style="display:none" @change="onDayFileInputChange(i, $event)">
                                        <!-- Take a photo instead of browsing for one. The capture lands in
                                             the very same chip list as an uploaded file, so OCR, the row tag
                                             and the Invoice/Supporting-Proof choice all behave identically. -->
                                        <button type="button" class="btn btn-outline-success btn-sm rt-camera-btn" @click="openDayCamera(i)">
                                            <i class="fa fa-camera"></i> Take Photo
                                        </button>
                                        <input type="hidden" :name="'reimburse['+i+'][merchant_name]'" :value="primaryOcrFile(data) ? primaryOcrFile(data).ocrMerchant : ''">
                                        <input type="hidden" :name="'reimburse['+i+'][no_invoice]'" :value="primaryOcrFile(data) ? primaryOcrFile(data).ocrInvoice : ''">

                                        <div class="rt-file-chip" v-for="(f, fi) in data.dayFiles" :key="f.uid">
                                            <!-- Preview via single click, double-click, OR the explicit eye button
                                                 below -- clicking the thumbnail alone wasn't a discoverable enough
                                                 affordance (Sep 2026 feedback: "apa gak bisa yg diatas dikasih mata?
                                                 atau double klik di attachmentnya"). -->
                                            <img v-if="f.dataUrl" :src="f.dataUrl" class="rt-file-thumb" :data-status="f.ocrStatus" title="Click or double-click to view full size" @click="previewFileChip(f)" @dblclick="previewFileChip(f)">
                                            <div v-else class="rt-file-pdf-box" :data-status="f.ocrStatus" title="Click or double-click to view the PDF" @click="previewFileChip(f)" @dblclick="previewFileChip(f)"><i class="fa fa-file-pdf"></i></div>
                                            <div class="rt-file-chip-body">
                                                <span class="rt-file-name">@{{ fi + 1 }}. @{{ f.name }}</span>
                                                <!-- 1.A: tag this file to a specific Expense Detail row (e.g. file 1 ->
                                                     Hotel, file 2 -> Taksi) instead of it applying to every row. Left
                                                     blank/"Umum" it stays day-level, same as the old single-file
                                                     behaviour (e.g. one combined invoice for the whole day). -->
                                                <select class="form-control form-control-sm rt-file-row-tag" v-model="f.rowTag" @change="onFileRowTagChange(i, f)">
                                                    <option value="">General (all rows)</option>
                                                    <option v-for="(dt, di) in data.details" :value="String(di)">Baris @{{ di + 1 }}@{{ dt.destination ? (' - ' + dt.destination) : '' }}</option>
                                                </select>
                                                <!-- Oct 2026 feedback: more than one evidence file per expense row,
                                                     where the user decides which one is the receipt OCR should read
                                                     and which is only supporting proof ("mana yang buat bukti doang
                                                     mana yang nanti bakal di scan pake OCR"). Switching to
                                                     Supporting Proof drops this file out of the OCR/duplicate
                                                     checks; switching back runs them (see onFileDocTypeChange()). -->
                                                <select class="form-control form-control-sm rt-file-doc-type" v-model="f.docType" @change="onFileDocTypeChange(i, f)" title="Is this the invoice/receipt OCR should read, or just supporting proof?">
                                                    <option value="invoice">Invoice / Receipt (OCR)</option>
                                                    <option value="proof">Supporting Proof (no OCR)</option>
                                                </select>
                                                <!-- No. Invoice/Receipt this SPECIFIC file read, shown as plain text
                                                     instead of only a hover tooltip on the status icon -- with several
                                                     files per day, a tooltip alone doesn't answer "which invoice number
                                                     belongs to which file", and hover doesn't work on a phone anyway. -->
                                                <span v-if="f.ocrStatus === 'pending'" class="rt-file-ocr-text text-muted">Checking receipt...</span>
                                                <span v-else-if="f.ocrStatus === 'read'" class="rt-file-ocr-text" style="color:#1e7e34;">@{{ f.ocrMessage || 'Receipt read' }}</span>
                                                <span v-else-if="f.ocrStatus === 'duplicate'" class="rt-file-ocr-text" style="color:#c0392b;">@{{ f.ocrMessage || 'Invoice has already been used' }}</span>
                                                <!-- OCR ran fine but found nothing receipt-shaped at all (no amount/
                                                     merchant/date/invoice) -- almost certainly the wrong photo was
                                                     uploaded (selfie, random screenshot, etc). Non-blocking, just a
                                                     nudge to re-upload the correct file -- see
                                                     ReceiptOcrVerifier::read()'s 'not_receipt' status. -->
                                                <span v-else-if="f.ocrStatus === 'not_receipt'" class="rt-file-ocr-text" style="color:#a06a1c;">⚠ @{{ f.ocrMessage || 'Not an invoice/receipt, please upload again' }}</span>
                                                <span v-else-if="f.ocrStatus === 'unavailable'" class="rt-file-ocr-text text-muted">OCR not available</span>
                                                <span v-else-if="f.ocrStatus === 'proof'" class="rt-file-ocr-text text-muted">Travel proof (no OCR)</span>
                                                <!-- Every uploaded file's own OCR reading, not just the day's latest
                                                     one (Sep 2026 feedback: "setiap evident yang diupload juga muncul
                                                     OCR result-nya"), compact/inline so it stays cheap on vertical
                                                     space -- and clickable to open the full read-only OCR Result for
                                                     THIS specific file (Sep 2026 feedback: "kek ketimpa gitu, dibikin
                                                     button modal gitu?"), since the editable OCR Result panel on the
                                                     right can only ever reflect one file at a time (the day only has
                                                     one no_invoice/merchant_name field to actually submit). -->
                                                <span v-if="f.ocrStatus === 'read' && (f.ocrDate || f.ocrMerchant || f.ocrAmount)" class="rt-file-ocr-detail" role="button" @click="showOcrDetailModal(f)" :title="'Click to view the full OCR Result of this file'">
                                                    <template v-if="f.ocrDate"><b>@{{ f.ocrDate }}</b> · </template>@{{ f.ocrMerchant || '-' }}<template v-if="f.ocrAmount"> · @{{ f.ocrAmount }} @{{ f.ocrCurrency }}</template>
                                                    <i class="fa fa-search-plus" style="margin-left:3px;"></i>
                                                </span>
                                            </div>
                                            <span v-if="f.ocrStatus === 'pending'" title="Checking receipt..."><i class="fa fa-spinner fa-spin"></i></span>
                                            <span v-else-if="f.ocrStatus === 'read'" style="color:#28a745;" :title="f.ocrMessage || 'Receipt read'"><i class="fa fa-check"></i></span>
                                            <span v-else-if="f.ocrStatus === 'duplicate'" style="color:#dc3545;" :title="f.ocrMessage || 'Invoice has already been used'"><i class="fa fa-exclamation-triangle"></i></span>
                                            <span v-else-if="f.ocrStatus === 'not_receipt'" style="color:#f0ad4e;" :title="f.ocrMessage || 'Not an invoice/receipt, please upload again'"><i class="fa fa-exclamation-triangle"></i></span>
                                            <span v-else-if="f.ocrStatus === 'unavailable'" style="color:#adb5bd;" title="OCR not available"><i class="fa fa-info-circle"></i></span>
                                            <span v-else-if="f.ocrStatus === 'proof'" style="color:#6c757d;" title="Travel proof"><i class="fa fa-route"></i></span>
                                            <!-- Explicit "eye" preview button (Sep 2026 feedback), same affordance
                                                 as Step 3's Preview column -- opens this exact file full-size. -->
                                            <button type="button" class="btn btn-outline-secondary btn-sm rt-file-preview-btn" @click="previewFileChip(f)" title="View this proof">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <i class="fa fa-times rt-file-remove" @click="removeDayFile(i, fi)" title="Delete this file"></i>
                                        </div>

                                        <!-- Same-trip co-traveler (popup "Yes, Continue"): allowance only, linked to the owner's claim. -->
                                        <div class="rt-reference-wrap" v-if="data.sameTripRef">
                                            <input type="hidden" :name="'reimburse['+i+'][reference_invoice]'" :value="data.referenceInvoice">
                                            <div class="alert alert-warning" style="font-size:12px;padding:8px 12px;margin:8px 0 0;">
                                                <i class="fa fa-link"></i> Invoice <b>@{{ data.sameTripRef.no_invoice }}</b> &rarr; refer <b>@{{ data.sameTripRef.owner_name }}</b> &middot; <b>@{{ data.sameTripRef.ticket_number || '-' }}</b> &middot; allowance only
                                                <button type="button" class="btn btn-link btn-sm" @click="clearSameTripRef(i)">Cancel</button>
                                            </div>
                                        </div>
                                        <!-- One-day trip with no expense at all: only Travel Allowance is claimed. Shown only when the submission has exactly 1 day form. -->
                                        <div class="rt-reference-wrap" v-if="i === 0 && reimburses.length === 1">
                                            <input type="hidden" :name="'reimburse['+i+'][allowance_only]'" :value="data.allowanceOnly ? 1 : ''">
                                            <div class="form-check" style="margin-top:8px;">
                                                <input type="checkbox" class="form-check-input" :id="'allowanceOnly'+i" v-model="data.allowanceOnly" :disabled="data.dayFiles.length > 0" @change="onAllowanceOnlyChange(i)">
                                                <label class="form-check-label" :for="'allowanceOnly'+i" style="font-size:13px;">No expense details &mdash; Travel Allowance only (nothing is reimbursed)</label>
                                            </div>
                                            <div v-if="data.dayFiles.length > 0" style="font-size:11px;color:#6c757d;">Remove the proof file first to use this option.</div>
                                        </div>

                                        <!-- Multi-day: claim ONLY Travel Allowance for this day by pointing at an
                                             earlier day's evidence (same submission) instead of re-uploading it. -->
                                        <div class="rt-reference-wrap" v-if="i > 0 && !data.dayFiles.length">
                                            <input type="hidden" :name="'reimburse['+i+'][refer_day]'" :value="data.referDay === null ? '' : data.referDay">
                                            <div v-if="data.referDay !== null" class="alert alert-warning" style="font-size:12px;padding:8px 12px;margin:8px 0 0;">
                                                <i class="fa fa-link"></i> The document of <b>Day @{{ data.referDay + 1 }}</b> is used for this day's <b>Travel Allowance</b>. No expense is claimed today (amount 0).
                                                <button type="button" class="btn btn-link btn-sm" @click="clearReferDay(i)">Cancel</button>
                                            </div>
                                            <div v-else style="margin-top:8px;">
                                                <button type="button" class="btn btn-outline-primary btn-sm" :disabled="!canReferDay(i - 1)" @click="referToDay(i, i - 1)"><i class="fa fa-link"></i> Refer to Previous Day</button>
                                                <button type="button" v-if="i > 1" class="btn btn-outline-primary btn-sm" :disabled="!canReferDay(0)" @click="referToDay(i, 0)"><i class="fa fa-link"></i> Refer to First Day</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Compact OCR Result -- one independent panel PER FILE (Sep 2026
                                             feedback: "resultnya masih replace, harusnya engga" -- this used to be a
                                             single shared panel that only ever showed whichever file was uploaded
                                             last, silently discarding any earlier file's reading from view). Each
                                             panel edits that ONE file's own chip data directly; nothing here
                                             overwrites another file's. The hint reappears only once no file has
                                             been read yet at all. -->
                                        <div class="rt-ocr-summary-list" v-if="readOcrFiles(data).length">
                                            <div class="rt-ocr-summary-compact" v-for="f in readOcrFiles(data)" :key="'ocrsum-'+f.uid">
                                                <div class="rt-ocr-summary-compact-head">
                                                    <b :title="f.name">@{{ f.name }}</b>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="f.ocrEditing = !f.ocrEditing">
                                                        <i class="fa fa-pencil-alt"></i> @{{ f.ocrEditing ? 'Done' : 'Edit' }}
                                                    </button>
                                                </div>
                                                <div class="rt-ocr-summary-compact-field">
                                                    <label title="Transaction Date">Date</label>
                                                    <input type="text" class="form-control form-control-sm" v-model="f.ocrDate" :readonly="!f.ocrEditing">
                                                </div>
                                                <div class="rt-ocr-summary-compact-field">
                                                    <label title="Merchant / Hotel">Merchant</label>
                                                    <input type="text" class="form-control form-control-sm" v-model="f.ocrMerchant" :readonly="!f.ocrEditing">
                                                </div>
                                                <div class="rt-ocr-summary-compact-field">
                                                    <label>Amount</label>
                                                    <input type="text" class="form-control form-control-sm" v-model="f.ocrAmount" :readonly="!f.ocrEditing">
                                                    <input type="text" class="form-control form-control-sm" style="flex:0 0 52px;" v-model="f.ocrCurrency" :readonly="!f.ocrEditing" title="Currency">
                                                </div>
                                                <div class="rt-ocr-summary-compact-field">
                                                    <label title="No. Invoice / Receipt">Invoice</label>
                                                    <input type="text" class="form-control form-control-sm" v-model="f.ocrInvoice" :readonly="!f.ocrEditing" @input="recomputeLocalInvoiceDuplicates()">
                                                </div>
                                                <div class="rt-ocr-summary-compact-primary" v-if="primaryOcrFile(data) && primaryOcrFile(data).uid === f.uid" title="This file's values are submitted as today's No. Invoice/Merchant Name">
                                                    <i class="fa fa-star"></i> Used for this day
                                                </div>
                                            </div>
                                        </div>
                                        <div class="rt-ocr-hint" v-else>
                                            <div class="rt-ocr-hint-title">OCR will read:</div>
                                            <ul>
                                                <li><i class="fa fa-check-circle"></i> No. Invoice / Receipt</li>
                                                <li><i class="fa fa-check-circle"></i> Transaction Date</li>
                                                <li><i class="fa fa-check-circle"></i> Merchant / Hotel Name</li>
                                                <li><i class="fa fa-check-circle"></i> Amount</li>
                                                <li><i class="fa fa-check-circle"></i> Currency</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rt-step-title">
                                <span class="rt-step-badge">2</span>
                                <h5>Request Information</h5>
                            </div>
                            <p class="rt-step-desc">Please complete the additional information below.</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="">Transaction Date</label>
                                    <input type="date" :name="'reimburse['+i+'][date]'" class="form-control" v-model="data.date" required />
                                </div>
                                <div class="col-md-3">
                                    <label for="">Purpose</label>
                                    <input type="text" :name="'reimburse['+i+'][purpose]'" class="form-control" required value="" />
                                </div>
                                <div class="col-md-3">
                                    <label for="">Trip Type</label>
                                    <select :name="'reimburse['+i+'][trip_type_id]'" id="" class="form-control" v-model="data.trip" @change="changeTrip(i)" required>
                                        <option value="" selected disabled>Select...</option>
                                        <option value="0">None</option>
                                        @foreach ($trip_types as $item)
                                        <option value="{{$item->id}}" data-allowance="{{$item->allowance}}" data-rate="{{$item->currency}}">{{$item->name}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="">Hotel </label>
                                       <select :name="'reimburse['+i+'][hotel_condition_id]'" id="" class="form-control" v-model="data.hotel_condition" :disabled="isTripTimeDisabled(data)" required>
                                        <option value="" selected disabled>Select...</option>
                                        @foreach ($hotel_conditions as $item)
                                        <option value="{{$item->id}}" data-allowance="{{$item->allowance}}">{{$item->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="">Start</label>
                                       <input type="time" :name="'reimburse['+i+'][start_time]'" @change="changeTime(i)" v-model="data.start_time" class="form-control" value="" :disabled="isTripTimeDisabled(data)" :required="!isTripTimeDisabled(data)"/>
                                </div>

                                <div class="col-md-3">
                                    <label for="">Arrival</label>
                                       <input type="time" :name="'reimburse['+i+'][end_time]'" @change="changeTime(i)" v-model="data.end_time" class="form-control" value="" :disabled="isTripTimeDisabled(data)" :required="!isTripTimeDisabled(data)"/>
                                </div>

                                <div class="col-md-3">
                                    <label for="">Original Allowance</label>
                                    <input type="text" :name="'reimburse['+i+'][allowance]'" readonly class="form-control number-format" v-model="data.trip_allowance" value="" required/>
                                </div>

                                <div class="col-md-3">
                                    <label for="">Travel Time</label>
                                    <input type="text" :name="'reimburse['+i+'][travel_time]'" readonly class="form-control" v-model="data.travel_time" value="" />
                                </div>
                            </div>
                            <hr />
                            <div class="rt-step-title">
                                <span class="rt-step-badge">3</span>
                                <h5>Expense Details</h5>
                            </div>
                            <p class="rt-step-desc">Add expense details based on the uploaded evidence or add manually.</p>
                            <div class="row">
                                <div class="col-xl">
                                    <div class="alert alert-secondary" style="font-size:12px;padding:8px 12px;" v-if="data.sameTripRef"><i class="fa fa-lock"></i> Expense Details are locked: this invoice belongs to @{{ data.sameTripRef.owner_name }} (@{{ data.sameTripRef.ticket_number || '-' }}), so only the Travel Allowance is claimed today.</div>
                                    <div class="alert alert-secondary" style="font-size:12px;padding:8px 12px;" v-if="data.allowanceOnly"><i class="fa fa-lock"></i> Expense Details are locked: only the Travel Allowance is claimed today, nothing is reimbursed as an expense.</div>
                                    <div class="alert alert-secondary" style="font-size:12px;padding:8px 12px;" v-if="data.referDay !== null"><i class="fa fa-lock"></i> Expense Details are locked because this day uses the document of Day @{{ data.referDay + 1 }} (Travel Allowance only). Cancel the refer in Step 1 to fill in expenses.</div>
                                    <div class="table-responsive" :style="isExpenseLocked(data) ? 'pointer-events:none;opacity:.55;' : ''">
                                        <table class="table full-width" style="width: 100%;">
                                            <thead style="width: 100%;">
                                                <tr>
                                                    <th width="200">Cost Type</th>
                                                    <th>Destination</th>
                                                    <th>Remarks</th>
                                                    <th>Currency</th>
                                                    <th>Amount</th>
                                                    <th>IDR Rate</th>
                                                    <th>Pph23</th>
                                                    <th>Payment</th>
                                                    <th>Preview</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(dt,a) in data.details">
                                                    <td>
                                                        <select :name="'reimburse['+i+'][detail]['+a+'][cost_type_id]'" id="" class="form-control cost-type-select" v-model="dt.cost_type" @change="changeCost(i,a)">
                                                            <option value="" selected disabled>Select...</option>
                                                            @foreach ($types as $item)
                                                            <option value="{{$item->id}}" data-type="{{$item->type}}">{{$item->name}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control destination-input" :name="'reimburse['+i+'][detail]['+a+'][destination]'" />
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control remarks-input" placeholder="e.g. Hotel for 18-20 Aug 2026" :name="'reimburse['+i+'][detail]['+a+'][remarks]'" />
                                                    </td>
                                                    <td>
                                                        <select :name="'reimburse['+i+'][detail]['+a+'][currency]'" class="form-control currency-select" id="" v-model="dt.currency" :required="!isExpenseLocked(data)">
                                                            <option value="" disabled>Select...</option>
                                                            <option v-for="rt in rates" v-if="rt.code" :value="rt.code">@{{rt.code}}</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control amount-input" @change="calculateTotal(i,a)" :name="'reimburse['+i+'][detail]['+a+'][amount]'" v-model="dt.amount" :readonly="isExpenseLocked(data)" />
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control number-format idr-rate-input" readonly :name="'reimburse['+i+'][detail]['+a+'][idr_rate]'" v-model="dt.idr_rate" />
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control number-format tax-input" readonly :name="'reimburse['+i+'][detail]['+a+'][tax]'" v-model="dt.tax" />
                                                    </td>
                                                    <td>
                                                        <select :name="'reimburse['+i+'][detail]['+a+'][payment_type]'" id="" class="form-control payment-select" v-model="dt.payment_type" :required="!isExpenseLocked(data)">
                                                            <option value="" selected disabled>Select...</option>
                                                            <option value="BDC">BDC</option>
                                                            <option value="Cash">Cash</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <!-- Shows which Step 1 file (by number) this row's Preview
                                                             actually opens -- e.g. "1" if it links to file 1 (hotel),
                                                             "2" for file 2 (taxi) -- instead of an unlabelled eye icon
                                                             (Sep 2026 feedback). -->
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rt-preview-file-btn" :disabled="!data.dayFiles.length" @click="previewRowFile(i, a)" :title="previewRowTitle(i, a)">
                                                            <i class="fa fa-eye"></i>
                                                            <span v-if="matchedFileNumberForRow(i, a)" class="rt-preview-file-number">@{{ matchedFileNumberForRow(i, a) }}</span>
                                                            <!-- A row can hold several evidence files now; this says how many
                                                                 more the Preview opens besides the numbered one. -->
                                                            <span v-if="matchedFileCountForRow(i, a) > 1" class="rt-preview-file-more">+@{{ matchedFileCountForRow(i, a) - 1 }}</span>
                                                        </button>
                                                    </td>

                                                    <td>
                                                        <button type="button" v-if="a == 0" @click="addDetail(i)" class="btn btn-success">+</button>
                                                        <button type="button" v-if="a > 0" @click="removeDetail(i,a)" class="btn btn-danger">-</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="">Total</label>
                                    <input type="text" :name="'reimburse['+i+'][total]'" v-model="data.total" readonly class="form-control" value="" />
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl">
                    <span style="color: #62d49e; float: right;" class="warning-upload">
                        The button is disabled until a file is uploaded.
                    </span>
                </div>
            </div>
            <br />
            <div class="button-container">
                @if(isset($appendTo))
                {{-- "Back": adding days to an EXISTING submission, so this returns to that
                     submission rather than discarding anything (Oct 2026 feedback). The
                     plain-create branch below keeps "Cancel" -- there it really does
                     abandon a new submission. --}}
                <a class="btn btn-secondary text-right" href="{{ url('reimbursement-travel/add-item/'.$appendTo->id) }}"><i class="fa fa-arrow-circle-left"></i> Back</a>&nbsp;
                <button class="btn btn-primary" type="submit" id="action_button" name="save_item">ADD DAYS</button>
                @else
                <a class="btn btn-danger text-right" href="{{route('reimbursement-travel.index')}}">CANCEL</a>&nbsp;
                <button class="btn btn-primary" type="submit" id="action_button" name="save">SUBMIT</button>&nbsp;
                <button class="btn btn-warning" type="submit" id="action_button_draft" name="save_draft">DRAFT</button>
                @endif
            </div>
            <br />
            <br />
        </form>

        <!-- Shared across every file chip -- read-only OCR Result for ONE
             specific file (Sep 2026 feedback: with several files per day, the
             single editable OCR Result panel only ever shows the latest one,
             "kek ketimpa" -- this modal lets every file's own reading
             actually be seen). Populated from the Vue root's
             ocrDetailModalChip whenever a file's inline OCR line is clicked
             (see showOcrDetailModal()). MUST stay inside #app (this element)
             -- it uses v-if/@{{ }} bindings, which only compile when Vue's
             el:'#app' template actually contains them; placed outside #app
             (as this was originally, alongside the static-content-only Tips
             modal) it rendered as literal, uncompiled "{{ ... }}" text. -->
        <!-- Row Preview gallery: all evidence files of ONE expense row, paged
             in place. Replaces opening a tab per file, which popup blockers
             dropped inconsistently (Oct 2026 feedback). Lives inside #app so
             its v-if/@{{ }} bindings actually compile. -->
        <div class="modal fade" id="rtRowPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content" v-if="rowPreviewCurrent()">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa fa-images" style="color:#0a58ca;"></i>
                            Evidence &mdash; @{{ rowPreviewIndex + 1 }} of @{{ rowPreviewFiles.length }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <i class="material-icons">close</i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="rt-rowprev-stage">
                            <button type="button" class="rt-rowprev-nav rt-rowprev-prev" v-if="rowPreviewFiles.length > 1" @click="rowPreviewGo(-1)" title="Previous">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <iframe v-if="rowPreviewCurrent().isPdf" :src="rowPreviewSrc(rowPreviewCurrent())"></iframe>
                            <img v-else :src="rowPreviewSrc(rowPreviewCurrent())" :alt="rowPreviewCurrent().name">
                            <button type="button" class="rt-rowprev-nav rt-rowprev-next" v-if="rowPreviewFiles.length > 1" @click="rowPreviewGo(1)" title="Next">
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="rt-rowprev-meta">
                            <b>@{{ rowPreviewCurrent().name }}</b>
                            <span class="rt-rowprev-badge" :class="rowPreviewCurrent().docType === 'proof' ? 'is-proof' : 'is-invoice'">
                                @{{ rowPreviewCurrent().docType === 'proof' ? 'Supporting Proof' : 'Invoice / Receipt' }}
                            </span>
                        </div>
                        <!-- Jump straight to any file instead of paging through. -->
                        <div class="rt-rowprev-thumbs" v-if="rowPreviewFiles.length > 1">
                            <template v-for="(f, n) in rowPreviewFiles">
                                <div v-if="f.isPdf" class="rt-rowprev-thumb-pdf" :class="n === rowPreviewIndex ? 'is-active' : ''" :title="f.name" @click="rowPreviewIndex = n">
                                    <i class="fa fa-file-pdf"></i>
                                </div>
                                <img v-else class="rt-rowprev-thumb" :class="n === rowPreviewIndex ? 'is-active' : ''" :src="rowPreviewSrc(f)" :title="f.name" @click="rowPreviewIndex = n">
                            </template>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="rowPreviewOpenCurrent()">
                            <i class="fa fa-external-link-alt"></i> Open in new tab
                        </button>
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="rtOcrDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content" v-if="ocrDetailModalChip">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa fa-file-alt" style="color:#0a58ca;"></i> OCR Result -- @{{ ocrDetailModalChip.name }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <i class="material-icons">close</i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="rt-ocr-detail-modal-field">
                            <label>Transaction Date</label>
                            <b>@{{ ocrDetailModalChip.ocrDate || '-' }}</b>
                        </div>
                        <div class="rt-ocr-detail-modal-field">
                            <label>Merchant / Hotel</label>
                            <b>@{{ ocrDetailModalChip.ocrMerchant || '-' }}</b>
                        </div>
                        <div class="rt-ocr-detail-modal-field">
                            <label>Amount</label>
                            <b>@{{ ocrDetailModalChip.ocrAmount || '-' }} @{{ ocrDetailModalChip.ocrCurrency }}</b>
                        </div>
                        <div class="rt-ocr-detail-modal-field">
                            <label>No. Invoice / Receipt</label>
                            <b>@{{ ocrDetailModalChip.ocrMessage && ocrDetailModalChip.ocrMessage.indexOf('No. Invoice: ') === 0 ? ocrDetailModalChip.ocrMessage.replace('No. Invoice: ', '') : '-' }}</b>
                        </div>
                        <small class="text-muted">This result is purely the OCR reading of this file. The value actually saved follows the editable "OCR Result" panel next to Step 1.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- End Modal -->

<!-- Shared across every "Form Hari" card -- Tips used to be a permanent
     column-wide box on each one; now opened on demand via the lightbulb
     icon next to "Upload Evidence" (Sep 2026 feedback: it was taking up
     too much space when it's the same static text every time). -->
<div class="modal fade" id="rtEvidenceTipsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-lightbulb" style="color:#c99a1a;"></i> Tips Upload Evidence</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div class="modal-body">
                <div class="rt-tips-modal-body">
                    Use a clear and readable image. Ensure the whole invoice is visible.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalPhoto" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Upload Gambar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div class="modal-body">
                <video id="videoElement" autoplay style="width: 100%;"></video>
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

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.13.4/jquery.mask.min.js"></script>
<script src="{{ asset('js/exchange-rate-parser.js') }}?v={{ @filemtime(public_path('js/exchange-rate-parser.js')) }}"></script>
<script src="{{ asset('js/travel-idr-money.js') }}?v={{ @filemtime(public_path('js/travel-idr-money.js')) }}"></script>
<script src="{{ asset('js/reimbursement-duplicate-check.js') }}?v={{ @filemtime(public_path('js/reimbursement-duplicate-check.js')) }}"></script>
<script type="text/javascript">
$(document).ready(function(){
    @if(Auth::user()->status_password != 1)
        $('#modalPassword').modal('show');
    @endif

    $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:2});

    /**
     * Trip dates to check for duplicates. Prefers the per-day inputs once
     * "Generate Daily Forms" has created them (they're what actually gets
     * submitted, and the user may have edited individual days); before that,
     * expands the Business Trip Date Range into one date per day so the
     * warning can already fire while the user is still filling in the range.
     */
    function rtCollectTripDates($form) {
        var dayDates = $form.find('input[name^="reimburse"][name$="[date]"]').map(function () {
            return $(this).val();
        }).get().filter(function (v) { return !!v; });

        if (dayDates.length) {
            return dayDates;
        }

        var start = $form.find('.rt-range-start').val();
        var end = $form.find('.rt-range-end').val() || start;
        if (!start) {
            return [];
        }

        var out = [];
        var cursor = new Date(start + 'T00:00:00');
        var last = new Date(end + 'T00:00:00');
        if (isNaN(cursor.getTime()) || isNaN(last.getTime()) || last < cursor) {
            return [start];
        }
        // Cap the span so a mistyped year (e.g. 2206) can't spin out here.
        for (var guard = 0; cursor <= last && guard < 366; guard++) {
            var mm = ('0' + (cursor.getMonth() + 1)).slice(-2);
            var dd = ('0' + cursor.getDate()).slice(-2);
            out.push(cursor.getFullYear() + '-' + mm + '-' + dd);
            cursor.setDate(cursor.getDate() + 1);
        }
        return out;
    }

    // Warning (not a block) when this applicant already claimed one of these
    // trip days on another submission. Scoped to the applicant on purpose --
    // two people travelling the same date is normal; the cross-applicant
    // check is the invoice/OCR one, which is a separate, harder gate.
    if (typeof window.bindReimbursementDuplicateChecks === 'function') {
        window.bindReimbursementDuplicateChecks({
            formSelector: '#travel_reimbursement_form',
            checks: [
                {
                    url: '{{ url('/reimbursement/check-duplicate-date') }}',
                    params: function ($form) {
                        var dates = rtCollectTripDates($form);
                        if (!dates.length) return null;
                        return { reimbursement_type: 2, dates: dates };
                    }
                }
            ],
            earlyCheckSelectors: 'input[name^="reimburse"][name$="[date]"], .rt-range-start, .rt-range-end'
        });
    }

    var maxGroup = 10;
    var i = 1;
    
    $("#action_button").prop("disabled", true);
    $("#action_button_draft").prop("disabled", true);
    $("#action_button_item").prop("disabled", true);
    $(".warning-upload").show();
    // Tab label per hari ("Form Hari N (tanggal)") sekarang reaktif lewat
    // Vue (v-model data.date), tidak perlu lagi disinkronkan manual via jQuery.

  });
</script>
@if($travelEntertainmentOcrEnabled ?? false)
<script src="{{ asset('js/reimbursement-ocr-check.js') }}?v={{ @filemtime(public_path('js/reimbursement-ocr-check.js')) }}"></script>
@endif
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  var OCR_CREATE_SUBMIT_SELECTORS = ['#action_button', '#action_button_draft', '#action_button_item'];

  function ocrCreateRowOptions($row) {
    return {
      row: $row,
      badgeContainer: $row.find('[id^="preview_"]').first(),
      submitSelectors: OCR_CREATE_SUBMIT_SELECTORS,
      formScope: $('#travel_reimbursement_form'),
      reimbursementType: 'travel',
      onSameTripOffer: function (offer) {
        if (window.ReimbursementOcrCheck) {
          window.ReimbursementOcrCheck.showSameTripInfoModal(offer);
        }
      }
    };
  }

  function runOcrCheckForCreateRow($row, file) {
    if (!window.ReimbursementOcrCheck) {
      return;
    }
    window.ReimbursementOcrCheck.verifyAndRender(
      Object.assign({ file: file }, ocrCreateRowOptions($row))
    );
  }

  /**
   * Day-level evidence upload (Step 1: Upload Evidence, Sep 2026 redesign,
   * extended for multi-file Sep 2026). Each file uploaded for a day gets its
   * own OCR call (so a hotel receipt AND a taxi receipt on the same day are
   * each checked for duplicates on their own merits) via
   * rtVerifyDayFileEvidence(); only the most-recently-added file's result
   * drives the "OCR Result" summary card auto-fill, matching the previous
   * single-file UX for that part. Mutates the Vue instance's reactive state
   * directly (vm.reimburses[i]) since this lives outside the Vue component
   * definition. no_invoice/reference_reimbursement_id are resolved again
   * server-side at store() time (see TravelReimbursementController::store())
   * -- nothing here needs to be submitted back except the file(s) themselves,
   * their row tags, and the optional reference invoice.
   */
  function rtEnableTravelSubmitButtons() {
    OCR_CREATE_SUBMIT_SELECTORS.forEach(function (sel) { $(sel).prop('disabled', false); });
    $('.warning-upload').hide();
  }

  /**
   * Bootstrap modal replacing the browser's native alert()/confirm() on this form,
   * styled like the same-trip warning modal. opts: {title, message, confirmText,
   * cancelText (omit => single OK button), onYes, onCancel}. Callbacks run after the
   * modal has fully hidden, so a callback can safely open another modal.
   */
  function rtModal(opts) {
    var ID = 'rtGenericModal';
    if (!document.getElementById(ID + 'Style')) {
      var style = document.createElement('style');
      style.id = ID + 'Style';
      style.textContent =
        '#' + ID + ' .modal-content{border-radius:10px;border:none;overflow:hidden;}' +
        '#' + ID + ' .modal-header{flex-direction:column;align-items:center;border-bottom:none;padding:24px 24px 0;}' +
        '#' + ID + ' .rt-modal-icon{width:46px;height:46px;border-radius:50%;background:#fff7e6;color:#e0a800;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px;}' +
        '#' + ID + ' .modal-title{width:100%;text-align:center;font-weight:700;color:#2b3a55;}' +
        '#' + ID + ' .modal-body{padding:14px 24px 4px;text-align:center;}' +
        '#' + ID + ' .rt-modal-message{color:#495057;font-size:14px;margin-bottom:16px;white-space:pre-line;}' +
        '#' + ID + ' .modal-footer{border-top:none;padding:0 24px 24px;justify-content:center;}' +
        '#' + ID + ' .modal-footer .btn{min-width:120px;border-radius:6px;}';
      document.head.appendChild(style);
    }
    if (!$('#' + ID).length) {
      $('body').append(
        '<div class="modal fade" id="' + ID + '" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">' +
          '<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">' +
            '<div class="modal-header"><div class="rt-modal-icon"><i class="fa fa-exclamation-triangle"></i></div><h5 class="modal-title"></h5></div>' +
            '<div class="modal-body"><p class="rt-modal-message"></p></div>' +
            '<div class="modal-footer">' +
              '<button type="button" class="btn btn-secondary js-rt-modal-cancel">Cancel</button>' +
              '<button type="button" class="btn btn-primary js-rt-modal-yes">OK</button>' +
            '</div>' +
          '</div></div>' +
        '</div>'
      );
    }
    var $m = $('#' + ID);
    $m.find('.modal-title').text(opts.title || 'Attention');
    $m.find('.rt-modal-message').text(opts.message || '');
    $m.find('.js-rt-modal-yes').text(opts.confirmText || 'OK');
    var $cancel = $m.find('.js-rt-modal-cancel').text(opts.cancelText || 'Cancel').toggle(!!opts.cancelText);
    var done = false;
    function finish(fn) {
      if (done) { return; }
      done = true;
      $m.find('.js-rt-modal-yes, .js-rt-modal-cancel').off('click');
      $m.one('hidden.bs.modal', function () { if (typeof fn === 'function') { fn(); } });
      $m.modal('hide');
    }
    $m.find('.js-rt-modal-yes').off('click').on('click', function () { finish(opts.onYes); });
    $cancel.off('click').on('click', function () { finish(opts.onCancel); });
    $m.modal('show');
  }

  function rtAlertModal(message, title) {
    rtModal({ title: title || 'Attention', message: message, confirmText: 'OK' });
  }

  /**
   * Evidence upload size cap. Kept in sync with the "Max 10MB" the dropzone
   * advertises and with the server-side check in
   * TravelReimbursementController::storeTravelEvidenceFile().
   */
  var RT_MAX_EVIDENCE_MB = 10;
  var RT_MAX_EVIDENCE_BYTES = RT_MAX_EVIDENCE_MB * 1024 * 1024;

  function rtFormatFileSize(bytes) {
    if (!bytes && bytes !== 0) { return '?'; }
    if (bytes >= 1024 * 1024) { return (bytes / (1024 * 1024)).toFixed(1) + 'MB'; }
    if (bytes >= 1024) { return Math.round(bytes / 1024) + 'KB'; }
    return bytes + 'B';
  }

  /**
   * The actual <input type="file"> elements submitted for a day's evidence
   * are created here, one pair (file + its row-tag) per uploaded file,
   * instead of relying on the single visible dropzone input -- that one only
   * exists to open the file-picker dialog now (see the `multiple` file input
   * in the template) and can't hold more than the browser's last-chosen
   * selection anyway. Kept as plain DOM/jQuery (mirroring the established
   * pattern in reimbursement-travel-upload.js's appendAttachmentInput())
   * rather than trying to make Vue own real File blobs in named inputs,
   * which it can't bind declaratively.
   */
  function rtDayHiddenContainer(i) {
    var $container = $('#rt-day-hidden-' + i);
    if (!$container.length) {
      $container = $('<div class="rt-day-hidden-inputs" style="display:none;"></div>').attr('id', 'rt-day-hidden-' + i);
      $('#travel_reimbursement_form').append($container);
    }
    return $container;
  }

  function rtAddDayHiddenFile(i, uid, file, docType) {
    var $container = rtDayHiddenContainer(i);
    var dt = new DataTransfer();
    dt.items.add(file);
    var $fileInput = $('<input type="file">').attr({ 'class': 'rt-day-hidden-file', 'data-uid': uid, name: 'reimburse[' + i + '][files][]' });
    $fileInput[0].files = dt.files;
    var $tagInput = $('<input type="hidden" value="">').attr({ 'class': 'rt-day-hidden-tag', 'data-uid': uid, name: 'reimburse[' + i + '][file_row_tags][]' });
    var $typeInput = $('<input type="hidden">').val(docType || 'invoice').attr({ 'class': 'rt-day-hidden-type', 'data-uid': uid, name: 'reimburse[' + i + '][file_types][]' });
    $container.append($fileInput).append($tagInput).append($typeInput);
  }

  function rtSetDayHiddenTag(uid, tagValue) {
    $('.rt-day-hidden-tag[data-uid="' + uid + '"]').val(tagValue || '');
  }

  /** Keeps the submitted reimburse[i][file_types][] entry in step with the chip's own Invoice/Proof dropdown. */
  function rtSetDayHiddenType(uid, docType) {
    $('.rt-day-hidden-type[data-uid="' + uid + '"]').val(docType === 'proof' ? 'proof' : 'invoice');
  }

  function rtRemoveDayHiddenFile(uid) {
    $('.rt-day-hidden-file[data-uid="' + uid + '"], .rt-day-hidden-tag[data-uid="' + uid + '"], .rt-day-hidden-type[data-uid="' + uid + '"]').remove();
  }

  /**
   * Modern browsers (Chrome/Edge and others) silently refuse to navigate a
   * window.open() tab straight to a data: URI -- it opens as about:blank
   * instead, with no error -- which is exactly why image previews (which use
   * the FileReader-produced data: URL, `dataUrl`) were opening blank while
   * PDF previews (which already use a blob: URL, `objectUrl`) worked fine.
   * Converting to a blob: URL first, synchronously (no fetch()/Promise, so
   * there's no risk of losing the click's "user activation" before
   * window.open() runs), makes image previews behave exactly like PDFs.
   */
  function dataUrlToBlob(dataUrl) {
    var comma = dataUrl.indexOf(',');
    var meta = dataUrl.slice(0, comma);
    var mimeMatch = /data:([^;]+);base64/.exec(meta);
    var mime = mimeMatch ? mimeMatch[1] : 'application/octet-stream';
    var binary = atob(dataUrl.slice(comma + 1));
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return new Blob([bytes], { type: mime });
  }

  /**
   * Asks the server which of these trip dates this applicant has already
   * claimed and BLOCKS when any of them is already taken -- onProceed() only
   * runs for a clean range (Sep 2026, agreed with the approvers: "nggk usah
   * ada lanjutkan hanya periksa lagi aja"). The applicant has to either fix
   * the range or file the left-out day inside a later business trip, with the
   * reason spelled out in its remarks.
   *
   * Deliberately per-applicant (the endpoint scopes by the logged-in user):
   * two people travelling on the same date is normal -- the cross-applicant
   * rule is the invoice/OCR check, a separate gate.
   *
   * Fail-open on transport errors only: a check that cannot run at all must
   * not wedge the form. A successful check that says "duplicate" always blocks.
   */
  function rtBlockDuplicateTripDates(dates, onProceed) {
    if (!dates || !dates.length) {
      onProceed();
      return;
    }

    $.post('{{ url('/reimbursement/check-duplicate-date') }}', {
      _token: $('meta[name="csrf-token"]').attr('content') || '',
      reimbursement_type: 2,
      dates: dates
    }).then(function (res) {
      if (!res || !res.duplicate) {
        onProceed();
        return;
      }
      // Single button: there is no way to force through from here.
      rtModal({
        title: 'Tanggal Trip Sudah Diajukan',
        message: res.message
          + '\n\nSilakan perbaiki tanggalnya. Kalau ada hari yang terlewat, ajukan lewat business trip berikutnya dan jelaskan alasannya di kolom Remarks.',
        confirmText: 'Periksa Lagi'
      });
    }).catch(function () {
      onProceed();
    });
  }

  function rtVerifyDayFileEvidence(vm, i, chip, file) {
    var entry = vm.reimburses[i];
    chip.ocrStatus = 'pending';

    var formData = new FormData();
    formData.append('receipt', file);
    formData.append('_token', $('meta[name="csrf-token"]').attr('content') || '');
    formData.append('reimbursement_type', 'travel');
    formData.append('date', entry.date || '');
    formData.append('range_from', vm.rangeStart || entry.date || '');
    formData.append('range_to', vm.rangeEnd || vm.rangeStart || entry.date || '');

    $.ajax({
      url: '/reimbursement/verify-day-evidence-ocr',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false
    }).then(function (res) {
      res = res || {};
      chip.ocrStatus = res.duplicate ? 'duplicate' : (res.status || 'unavailable');
      chip.ocrMessage = res.duplicate_message || (res.extracted_no_invoice ? ('No. Invoice: ' + res.extracted_no_invoice) : '');
      // Every file keeps its own reading, editable independently (Sep 2026
      // feedback: "resultnya masih replace, harusnya engga" -- there used to
      // be one shared, editable "OCR Result" panel that only ever reflected
      // the most-recently-added file, silently overwriting whatever the
      // previous file had shown. Now each file's OCR Result panel is its
      // own -- see the v-for over data.dayFiles in the template -- so
      // nothing gets replaced when a second/third file is added.
      chip.ocrDate = res.extracted_transaction_date || '';
      chip.ocrMerchant = res.extracted_merchant_name || '';
      chip.ocrAmount = res.extracted_amount != null ? res.extracted_amount : '';
      chip.ocrCurrency = res.extracted_currency || 'IDR';
      chip.ocrInvoice = res.extracted_no_invoice || '';
      // Same invoice uploaded twice in THIS submission (e.g. once as a PNG,
      // once as a PDF) -- the server-side duplicate checks (against the
      // database) can't catch this since neither file is saved yet. Flags
      // every file sharing that invoice number across the WHOLE submission
      // (any day), reusing the existing 'duplicate' badge/styling -- this is
      // a visual warning only (never blocks Submit on its own, matching
      // every other OCR check here); the real, unbypassable block is
      // TravelReimbursementController::guardAgainstDuplicateInvoiceWithinSubmission().
      vm.recomputeLocalInvoiceDuplicates();

      // The day's own Transaction Date (Step 2) still only auto-fills once,
      // from whichever file resolves first -- it's a single field on the day
      // itself (not per-file), so "replace" doesn't apply the same way; this
      // only ever sets it from empty, never overwrites a value already there
      // (typed manually or auto-filled by an earlier file).
      if (!entry.date && res.extracted_transaction_date) {
        entry.date = res.extracted_transaction_date;
      }

      // A different user's invoice never auto-sets anything -- it goes
      // through the same-trip offer/blocked/duplicate red popups below, and
      // on Yes links allowance-only with Request Information left editable.
      if (res.same_trip_offer && window.ReimbursementOcrCheck) {
        vm.offerSameTripReference(i, chip, res.same_trip_offer);
      } else if (res.same_trip_blocked && window.ReimbursementOcrCheck) {
        window.ReimbursementOcrCheck.showSameTripInfoModal(res.same_trip_blocked);
      } else if (res.duplicate) {
        rtAlertModal(res.duplicate_message || 'The invoice in file "' + file.name + '" has already been used in another submission.', 'Duplicate Invoice');
      }

      rtEnableTravelSubmitButtons();
    }).catch(function () {
      chip.ocrStatus = 'unavailable';
      rtEnableTravelSubmitButtons();
    });
  }

  new Vue({
      el: '#app',
      data: {
        usd_rate: 0,
        idr_rate: 0,
        jpy_rate: 0,
        rangeStart: null,
        activeDay: 0,
        // Files shown in the row Preview gallery modal, and which one is on
        // screen. Populated by previewRowFile(); never submitted.
        rowPreviewFiles: [],
        rowPreviewIndex: 0,
        rangeEnd: null,
        // No day form exists until the user picks a date range and clicks
        // "Generate Daily Forms" (regenerateDaysFromRange builds this list).
        reimburses: [],
        // Day cards parked when a range change pushed their date out of the
        // trip, keyed by date. Re-used if that date comes back in a later
        // range change (Sep 2026 feedback) -- purely in-memory, so it does not
        // survive a page reload, and nothing here is ever submitted: only the
        // days in `reimburses` are.
        stashedDays: {},
        rates: @if(isset($appendTo) && collect($appendRates)->count()) @json(collect($appendRates)->map(function ($r) { return ['code' => strtoupper($r->currency), 'rate' => (float) $r->rate]; })->values()) @else [
            {
                code: 'IDR',
                rate: '1,00'
            }
        ] @endif,
        types : @json($types),
        trip_types : @json($trip_types),
        not_stay_hotel_condition_id : @json($not_stay_hotel_condition_id),
        grandtotal: 0,
        // Which file chip's full OCR Result is currently shown in
        // #rtOcrDetailModal (Sep 2026 feedback) -- read-only, just for
        // viewing; null when the modal isn't open/no file has been clicked.
        ocrDetailModalChip: null
      },
      mounted() {
        this.initSelectForm()
        self = this
        $(".idr-rate-input").maskMoney({ thousands:'.', decimal:',', precision:0, allowZero: true});
        $('.idr-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.idr_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".usd-rate-input").maskMoney({ thousands:'.', decimal:',', precision:2});
        $('.usd-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.usd_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".jpy-rate-input").maskMoney({ thousands:'.', decimal:',', precision:2});
        $('.jpy-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.jpy_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        // Amount starts blank so the user types the nominal directly -- no
        // pre-filled "0,00" to cursor past. No allowZero (3.0.2 has no
        // allowEmpty option; empty stays empty on blur via allowZero:false).
        // The phantom "0,00" maskMoney injects on focus is cleared by the
        // focusin.rtAmountBlank handler below; typing with or without ","
        // both work, and empty is parsed as 0 everywhere.
        // Set default hotel condition for first row before trip type is selected.
        if (this.reimburses.length > 0) {
            this.reimburses[0].hotel_condition = this.not_stay_hotel_condition_id;
            this.reimburses[0].start_time = null;
            this.reimburses[0].end_time = null;
            this.reimburses[0].travel_time = null;
        }
        // Single delegated handler for every amount-input, bound once (namespaced
        // so a stray re-mount can't double-bind it) instead of re-registering a
        // fresh non-delegated handler on every addDetail()/regenerateDaysFromRange()
        // call -- that used to stack duplicate handlers and, worse, the very first
        // one hardcoded reimburses[length-1].details[0], so editing Amount on any
        // day/row other than "last day, first row" silently updated the wrong
        // entry and left that row's IDR Rate stuck at 0. The day (i) and row (a)
        // indices are parsed straight out of the input's own name attribute
        // (reimburse[i][detail][a][amount]) so this is correct regardless of DOM
        // position, day collapse state, or how many rows/days exist.
        $(document).off('change.rtAmountInput').on('change.rtAmountInput', '.amount-input', function (event) {
            var m = /reimburse\[(\d+)\]\[detail\]\[(\d+)\]\[amount\]/.exec($(event.target).attr('name') || '');
            if (!m) {
                return;
            }
            var i = parseInt(m[1], 10);
            var a = parseInt(m[2], 10);
            if (!self.reimburses[i] || !self.reimburses[i].details[a]) {
                return;
            }
            self.reimburses[i].details[a].amount = $(event.target).val();
            self.changeAmount(0);
            self.calculateTotal(i, a);
        });
        // maskMoney 3.0.2 masks on focus/click, so touching a blank Amount
        // injects a phantom "0,00" whose zeros then corrupt whatever is typed
        // (and force the cursor juggling). These run after maskMoney's own
        // handlers (bubble order): editable inputs go back to truly empty,
        // locked (readonly) rows show a plain "0" with no comma. The
        // existing change handler above re-syncs the Vue model on blur.
        $(document).off('focusin.rtAmountBlank click.rtAmountBlank').on('focusin.rtAmountBlank click.rtAmountBlank', '.amount-input', function (event) {
            var $el = $(event.target).closest('.amount-input');
            if (!$el.length || $el.prop('disabled')) {
                return;
            }
            var v = ($el.val() || '').trim();
            if (v === '' || parseTravelMoney(v) !== 0) {
                return;
            }
            $el.val($el.prop('readonly') ? '0' : '');
        });
        this.$nextTick(() => {
            this.syncRatesFromExchangeInputs();
        });
      },
      methods : {
        changeAmount(i) {

        },
        isTripTimeDisabled(row) {
            const trip = row && row.trip !== undefined && row.trip !== null ? String(row.trip) : '';
            return trip === '' || trip === '0';
        },
        /**
         * Normalisasi string angka (koma/titik desimal atau ribuan) ke satu bilangan float, max 2 desimal.
         * Contoh: "139,88" / "12.89" / "16.400,50" / "16400"
         */
        normalizeEuropeanNumberString(s) {
            let x = String(s || '').trim().replace(/\s/g, '');
            if (!x) return '0';
            let neg = false;
            if (x.charAt(0) === '-') {
                neg = true;
                x = x.slice(1);
            } else if (x.charAt(0) === '+') {
                x = x.slice(1);
            }
            if (!x) return '0';
            const lastC = x.lastIndexOf(',');
            const lastD = x.lastIndexOf('.');
            let out;
            if (lastC > lastD) {
                x = x.replace(/\./g, '').replace(',', '.');
                out = x.replace(/[^\d.]/g, '') || '0';
            } else {
                x = x.replace(/,/g, '');
                const idx = x.lastIndexOf('.');
                if (idx === -1) {
                    out = x.replace(/[^\d]/g, '') || '0';
                } else {
                    const intRaw = x.slice(0, idx);
                    const frac = x.slice(idx + 1).replace(/\D/g, '');
                    const intPart = intRaw.replace(/\./g, '');
                    if (frac.length === 3 && /^\d{3}$/.test(frac) && intPart.length >= 1) {
                        out = intPart + frac;
                    } else {
                        out = (intPart || '0') + '.' + frac;
                    }
                }
            }
            if (neg && out !== '0' && out !== '') {
                out = '-' + out;
            }
            return out;
        },
        numericRate(val) {
            if (val === null || val === undefined || val === '') return 0;
            const t = this.normalizeEuropeanNumberString(String(val).trim());
            const n = parseFloat(t);
            if (isNaN(n)) return 0;
            return Math.round(n * 100) / 100;
        },
        /** Kolom Amount: desimal (2 digit), sesuai nominal di bukti/invoice. */
        parseTravelAmountInteger(raw) {
            return this.numericRate(raw);
        },
        formatIdrForPayment(num, paymentType) {
            return formatTravelIdrMoney(roundIdrForPayment(num, paymentType), paymentType || 'Cash');
        },
        formatIdrInteger(num) {
            return this.formatIdrForPayment(num, 'Cash');
        },
        /**
         * Jangan destroy + re-init semua mask: itu mereset nilai tampilan jadi 0/1.
         * Hanya pasang maskMoney pada input yang belum pernah dipasang (node baru / pertama kali).
         * Sebelum tambah baris, syncRatesFromExchangeInputs() agar v-model.lazy tidak kehilangan isian.
         */
        formatRateDisplay(val) {
            if (val === null || val === undefined || val === '') return '';
            if (typeof val === 'string' && val.trim() === '') return '';
            const n = this.numericRate(val);
            if (isNaN(n)) return '';
            return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        logExchangeRate(action, idx, payload) {
            try {
                const code = this.rates[idx] ? (this.rates[idx].code || '') : '';
                console.log('[EXCHANGE_RATE]', {
                    action: action,
                    index: idx,
                    code: code,
                    payload: payload,
                    at: new Date().toISOString()
                });
            } catch (e) {
                console.log('[EXCHANGE_RATE]', action, idx, payload);
            }
        },
        onExchangeRateFocus(idx, event) {
            this.logExchangeRate('focus', idx, { domValue: event && event.target ? event.target.value : '' });
        },
        onExchangeRateInput(idx, event) {
            const raw = event && event.target ? event.target.value : '';
            let v = String(raw).trim().replace(/\s/g, '');
            if (!v) {
                if (this.rates[idx]) this.$set(this.rates[idx], 'rate', '');
                if (event && event.target) event.target.value = '';
                this.logExchangeRate('input', idx, { raw: raw, display: '' });
                return;
            }
            const lastC = v.lastIndexOf(',');
            const lastD = v.lastIndexOf('.');
            if (lastC > lastD) {
                v = v.replace(/\./g, '').replace(',', '.');
            } else {
                v = v.replace(/,/g, '');
            }
            v = v.replace(/[^0-9.]/g, '');
            const firstDot = v.indexOf('.');
            if (firstDot !== -1) {
                v = v.slice(0, firstDot + 1) + v.slice(firstDot + 1).replace(/\./g, '');
            }
            const parts = v.split('.');
            let intPart = parts[0] || '';
            let decPart = parts[1] || '';
            if (decPart.length > 2) {
                decPart = decPart.slice(0, 2);
            }
            const display = parts.length > 1 ? (intPart + '.' + decPart) : intPart;
            if (this.rates[idx]) {
                this.$set(this.rates[idx], 'rate', display);
            }
            if (event && event.target) {
                event.target.value = display;
            }
            this.logExchangeRate('input', idx, { raw: raw, display: display });
        },
        onExchangeRateBlur(idx, event) {
            const current = this.rates[idx] ? this.rates[idx].rate : '';
            const display = this.formatRateDisplay(current);
            if (this.rates[idx]) {
                this.$set(this.rates[idx], 'rate', display);
            }
            if (event && event.target) {
                event.target.value = display;
            }
            this.logExchangeRate('blur', idx, { normalized: display });
        },
        syncRatesFromExchangeInputs() {
            const vm = this;
            vm.rates = vm.rates.map(function(r, idx) {
                const normalizedCode = (r.code || '').trim().toUpperCase();
                const normalizedRate = vm.formatRateDisplay(r.rate);
                vm.logExchangeRate('sync', idx, { code: normalizedCode, rate: normalizedRate });
                return {
                    code: normalizedCode,
                    rate: normalizedRate
                };
            });
        },
        onTravelFormSubmit(e) {
            this.syncRatesFromExchangeInputs();
            if (this.reimburses.length === 0) {
                if (e) {
                    e.preventDefault();
                }
                rtAlertModal('Please fill in the Business Trip Date Range and click Generate Daily Forms first.');
                return false;
            }
            // Client-side mirror of TravelSubmissionValidator::findErrors():
            // incomplete expense rows are reported in a warning modal WITHOUT
            // submitting, so the page never reloads (a reload would wipe the
            // generated day cards and the picked files). Draft buttons stay
            // lenient, exactly like the server.
            var btnName = (e && e.submitter && e.submitter.name) || '';
            if (btnName !== 'save_draft' && btnName !== 'save_item') {
                var badDay = this.firstIncompleteExpenseDay();
                if (badDay.errors.length) {
                    if (e) {
                        e.preventDefault();
                    }
                    if (this.reimburses[badDay.day]) {
                        this.reimburses[badDay.day].collapsed = false;
                    }
                    rtAlertModal(badDay.errors.join('\n'), 'Incomplete Expense Details');
                    return false;
                }
            }
        },
        /** Reads one expense cell straight from the DOM (destination/remarks
            have no v-model, and amount's model can be stale if the field was
            never blurred) for pre-submit completeness checking. */
        expenseCellVal(i, a, field) {
            var el = document.querySelector('[name="reimburse[' + i + '][detail][' + a + '][' + field + ']"]');
            return el ? (el.value || '').trim() : '';
        },
        /**
         * Client-side mirror of TravelSubmissionValidator::findErrors() --
         * same labels, same per-day exemption (refer/allowance-only/
         * same-trip reference). Returns the first offending day only, so the
         * modal stays focused: {day, errors}.
         */
        firstIncompleteExpenseDay() {
            var labels = {
                destination: 'Remarks / tujuan biaya',
                currency: 'Mata uang',
                payment_type: 'Tipe pembayaran',
                amount: 'Jumlah'
            };
            var fields = ['destination', 'currency', 'payment_type', 'amount'];
            var out = { day: -1, errors: [] };
            for (var i = 0; i < this.reimburses.length; i++) {
                var day = this.reimburses[i];
                var refDay = day.referDay;
                var isFree = !!day.allowanceOnly
                    || (refDay !== null && refDay !== undefined && refDay !== '' && !isNaN(refDay) && parseInt(refDay, 10) < i)
                    || ((day.referenceInvoice || '').trim() !== '');
                if (isFree) {
                    continue;
                }
                var dayErrors = [];
                var hasDetail = false;
                var rows = (day.details || []).length;
                for (var a = 0; a < rows; a++) {
                    if (this.expenseCellVal(i, a, 'cost_type_id') === '') {
                        continue;
                    }
                    hasDetail = true;
                    for (var f = 0; f < fields.length; f++) {
                        if (this.expenseCellVal(i, a, fields[f]) === '') {
                            dayErrors.push(labels[fields[f]] + ' pada hari ke-' + (i + 1) + ', baris rincian ke-' + (a + 1) + ' wajib diisi.');
                        }
                    }
                }
                if (!hasDetail) {
                    dayErrors.push('Minimal satu rincian biaya (cost type) pada hari ke-' + (i + 1) + ' harus diisi lengkap.');
                }
                if (dayErrors.length) {
                    out.day = i;
                    out.errors = dayErrors;
                    return out;
                }
            }
            return out;
        },
        getRate(currency, amt) {
            self = this;
            var rate;
            try {
                rate = self.rates.filter(a => a.code == currency)[0].rate;
            } catch (error) {
                rate = currency == "IDR" ? 1 : 0;
            }
            return this.parseTravelAmountInteger(amt) * this.numericRate(rate);
             
        },
        initSelectForm() {
            $(".addFile").on('click', function() {
              let idx = $(this).attr("data-idx"); // Ambil data-idx
              let fileInput = $(this).parent().find(".file-input");

              fileInput.click();

              fileInput.off("change").on("change", function(event) {
                var file = event.target.files[0];

                if (file) {
                  let fileType = file.type;
                  let previewDiv = $("#preview_" + idx);
                  previewDiv.html(""); // Bersihkan preview sebelumnya

                  if (fileType.startsWith("image/")) {
                    // Preview gambar
                    var reader = new FileReader();
                    reader.onload = function(e) {
                      var img = $('<img>').attr('src', e.target.result).css({
                        maxWidth: '100%',
                        maxHeight: '200px',
                        border: '2px solid #28a745',
                        borderRadius: '5px',
                        marginTop: '5px'
                      });
                      previewDiv.append(img);
                    };
                    reader.readAsDataURL(file);
                  } else if (fileType === "application/pdf") {
                    // Preview PDF (ikon + link ke file PDF)
                    var fileURL = URL.createObjectURL(file);
                    var pdfIcon = 'https://cdn-icons-png.flaticon.com/512/337/337946.png'; // Ganti dengan lokal jika perlu
                    var link = $('<a>').attr({
                      href: fileURL,
                      target: '_blank',
                      title: 'Click to view the PDF'
                    }).append(
                      $('<img>').attr({
                        src: pdfIcon,
                        alt: 'PDF File'
                      }).css({
                        maxWidth: '50px',
                        maxHeight: '50px',
                        border: '2px solid #007bff',
                        borderRadius: '5px',
                        marginTop: '5px'
                      })
                    );
                    previewDiv.append(link);
                  } else {
                    // File tidak didukung
                    previewDiv.append('<p style="color:red;">File type not supported</p>');
                  }

                  // Aktifkan tombol aksi
                  $(".warning-upload").hide();
                  $("#action_button").prop("disabled", false);
                  $("#action_button_draft").prop("disabled", false);
                  $("#action_button_item").prop("disabled", false);

                  runOcrCheckForCreateRow($(this).closest('tr'), file);
                }
              });
            });
          
            $(".addCamera").on('click', function() {
                let idx = $(this).attr("data-idx"); // Ambil data-idx
                let $row = $(this).closest('tr');
                let fileInput = $(this).parent().find(".file-input")[0];

                $("#modalPhoto").modal("show");
                const videoElement = $("#videoElement")[0];

                if (navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({
                        video: {
                            width: { ideal: 1280 },   // minta resolusi HD
                            height: { ideal: 720 },
                            facingMode: "environment"
                        }
                    })
                    .then(function(stream) {
                        videoElement.srcObject = stream;

                        $("#captureButton").off("click").on("click", function() {
                            const canvas = document.createElement("canvas");
                            const context = canvas.getContext("2d");

                            // Pakai resolusi HD (fallback kalau kamera support rendah)
                            const outputWidth = videoElement.videoWidth || 1280;
                            const outputHeight = videoElement.videoHeight || 720;
                            canvas.width = outputWidth;
                            canvas.height = outputHeight;

                            context.drawImage(videoElement, 0, 0, outputWidth, outputHeight);

                            // Simpan ke JPEG kualitas 85%
                            canvas.toBlob(function(blob) {
                                const file = new File([blob], "capture.jpg", { type: "image/jpeg" });

                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(file);
                                fileInput.files = dataTransfer.files;

                                $("#preview_" + idx).html(""); // Bersihkan preview sebelumnya
                                var img = $('<img>')
                                    .attr('src', URL.createObjectURL(blob))
                                    .css({ maxWidth: '100%', maxHeight: '200px', border: '2px solid #28a745', borderRadius: '5px' });
                                $("#preview_" + idx).append(img);

                                runOcrCheckForCreateRow($row, file);
                            }, "image/jpeg", 0.85);

                            // Stop kamera setelah capture
                            stream.getTracks().forEach(track => track.stop());
                            $("#modalPhoto").modal("hide");
                            $(".warning-upload").hide();
                            $("#action_button").prop("disabled", false);
                            $("#action_button_draft").prop("disabled", false);
                            $("#action_button_item").prop("disabled", false);
                        });
                    })
                    .catch(err => console.error("Error accessing webcam: " + err));
                }
            });
        },
        // changeTrip(i) {
        //     id = this.reimburses[i].trip
        //     self = this
        //     this.reimburses[i].trip_allowance = self.trip_types.filter(a => a.id == id)[0].allowance.toLocaleString('de-DE')
        //     this.calculateTotal(i,0)
        // },
        changeTrip(i) {
            const id = this.reimburses[i].trip;

            if (String(id) === '0') {
                this.reimburses[i].trip_allowance = '0';
                this.reimburses[i].hotel_condition = this.not_stay_hotel_condition_id;
                this.reimburses[i].start_time = null;
                this.reimburses[i].end_time = null;
                this.reimburses[i].travel_time = null;
                this.calculateTotal(i, 0);
                return;
            }

            const selectedTrip = this.trip_types.find(a => a.id == id);
            if (!selectedTrip) {
                this.reimburses[i].trip_allowance = '0';
                this.reimburses[i].hotel_condition = this.not_stay_hotel_condition_id;
                this.reimburses[i].start_time = null;
                this.reimburses[i].end_time = null;
                this.reimburses[i].travel_time = null;
                this.calculateTotal(i, 0);
                return;
            }
            const currency = selectedTrip.currency; // data-rate
            const allowance = selectedTrip.allowance;
            
            // Kosongkan dulu allowance setiap kali trip_type diubah
            this.reimburses[i].trip_allowance = '';

            if (currency === 'IDR') {
                // Jika IDR langsung pakai allowance saja
                this.reimburses[i].trip_allowance = allowance.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                // Selain IDR, cek apakah currency code sudah ada di rates
                const foundRate = this.rates.find(rate => rate.code == currency);
                if (!foundRate) {
                    alert(`Please enter the USD exchange rate first, below the IDR exchange rate.`);
                    return;
                }

                // Hitung allowance x exchange rate (rate boleh berformat ribuan, mis. 15.000)
                const totalAllowance = allowance * this.numericRate(foundRate.rate);
                this.reimburses[i].trip_allowance = totalAllowance.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Panggil fungsi hitung total jika ada
            this.calculateTotal(i, 0);
        },
        changeTime(i) {

            // Get the input values
            data = this.reimburses[i]
            let time1 = data.start_time;
            let time2 = data.end_time;
            let date1 = new Date('1970-01-01T' + time1 + 'Z');
            let date2 = new Date('1970-01-01T' + time2 + 'Z');
            let timeDifference = Math.abs(date2 - date1);
            let hoursDifference = Math.floor(timeDifference / 1000 / 60 / 60);
            let minutesDifference = Math.floor((timeDifference / 1000 / 60) % 60);
            let differenceMessage = `Time difference: ${hoursDifference} hours and ${minutesDifference} minutes.`;
            this.reimburses[i].travel_time = `${hoursDifference} Hours and ${minutesDifference} minutes.`;
        },
        addRate() {
            this.syncRatesFromExchangeInputs();
            this.rates.push({
                code: null,
                rate: null
            });
            this.$nextTick(() => {
                this.syncRatesFromExchangeInputs();
            });
        },
        removeRate(i) {
            if (i <= 0 || !Array.isArray(this.rates) || this.rates.length <= 1) {
                return;
            }
            this.syncRatesFromExchangeInputs();
            this.rates.splice(i, 1);
            this.$nextTick(() => {
                this.syncRatesFromExchangeInputs();
            });
        },
        addTravel() {
            this.reimburses.push({
                trip: null,
                hotel_condition: null,
                trip_allowance: null,
                travel_time: null,
                date: null,
                dayFiles: [],
                referenceInvoice: '',
                referDay: null,
                allowanceOnly: false,
                // Type applied to the NEXT upload in this day; the chip
                // dropdown still overrides it per file afterwards.
                nextDocType: 'invoice',
                uploadType: 'invoice',
                sameTripRef: null,
                referenceFeedback: { message: '', color: '' },
                ocrStatus: null,
                ocrSummary: null,
                ocrEditing: false,
                messRelation: null,
                details: [
                    {
                        cost_type: null,
                        destination: null,
                        currency: 'IDR',
                        amount: null,
                        tax: null,
                        code: null,
                        idr_rate: null,
                    }
                ],
                total: 0
            });
            self = this
            this.$nextTick(() => {
              self.initSelectForm();

              // Amount input changes are handled by the single delegated
              // change.rtAmountInput listener bound once in mounted() -- no
              // per-call re-bind needed (and re-binding here used to hardcode
              // the wrong day/row, see that listener's comment).
            })

        },
        removeTravel(i) {
            this.reimburses.splice(i, 1)
            this.calculateTotal(i,0)
        },
        addDetail(i) {
            // Only block submitting while this day still has NO evidence at all.
            // Adding a row used to always disable the buttons (back when every
            // row had its own upload cell), which left Submit/Draft stuck off
            // after a user uploaded in Step 1 and THEN added a row -- nothing
            // re-enables them except another upload. A day that is
            // allowance-only / refers to another day / is a same-trip claim
            // needs no evidence either (isExpenseLocked).
            this.refreshSubmitButtonsFor(i);
            this.reimburses[i].details.push({
                cost_type: null,
                destination: null,
                currency: 'IDR',
                amount: null,
                tax: null,
                code: null,
            });
            // Files uploaded BEFORE this row existed stayed "General" because
            // there was no row to auto-tag them to; hand the oldest such file
            // to this brand-new row so the pairing still happens by itself.
            this.autoTagFirstUntaggedFile(i);
            self = this
            this.$nextTick(() => {
              self.initSelectForm();
              // Amount input changes are handled by the single delegated
              // change.rtAmountInput listener bound once in mounted().
            })
        },
        /**
         * Enables Submit/Draft when day i already has evidence (or needs none),
         * disables them otherwise. Used by addDetail() so adding a row no longer
         * strands the buttons in the disabled state after a Step 1 upload.
         */
        refreshSubmitButtonsFor(i) {
            var entry = this.reimburses[i];
            var hasEvidence = !!entry && (
                (entry.dayFiles || []).length > 0 || this.isExpenseLocked(entry)
            );
            if (hasEvidence) {
                rtEnableTravelSubmitButtons();
                return;
            }
            $("#action_button").prop("disabled", true);
            $("#action_button_draft").prop("disabled", true);
            $("#action_button_item").prop("disabled", true);
            $(".warning-upload").show();
        },
        /**
         * Gives the newest (last) Expense Detail row the first still-General
         * file, so uploading files first and adding rows afterwards ends up
         * tagged exactly like adding rows first and uploading afterwards.
         */
        autoTagFirstUntaggedFile(i) {
            var entry = this.reimburses[i];
            if (!entry || !entry.details || !entry.details.length) {
                return;
            }
            var newRowTag = String(entry.details.length - 1);
            var pool = entry.dayFiles || [];
            var alreadyTaken = pool.some(function (f) { return String(f.rowTag) === newRowTag; });
            if (alreadyTaken) {
                return;
            }
            var free = pool.filter(function (f) { return !f.rowTag; })[0];
            if (!free) {
                return;
            }
            free.rowTag = newRowTag;
            rtSetDayHiddenTag(free.uid, free.rowTag);
        },
        calculateTotal(i,a) {
            var self = this;
            try {
                var currency = this.reimburses[i].details[a].currency
                var amount = this.reimburses[i].details[a].amount
                var id = this.reimburses[i].details[a].cost_type
                var paymentType = this.reimburses[i].details[a].payment_type

                // IDR Rate/Pph23 for THIS row must not depend on Trip Type being
                // picked yet -- it used to be computed only after
                // `allowance.toLocaleString(...)` below, and allowance
                // (trip_allowance) is null until a Trip Type is selected, so that
                // line threw a TypeError that the catch block silently swallowed,
                // leaving IDR Rate/Pph23 permanently blank whenever a row's
                // Amount was filled in before its Trip Type. Compute these first,
                // unconditionally.
                var typeMatch = self.types.filter(t => t.id == id)[0]
                var tax = typeMatch ? typeMatch.tax : 0
                var idrVal = roundIdrForPayment(this.getRate(currency, amount), paymentType)
                this.reimburses[i].details[a].idr_rate = this.formatIdrForPayment(idrVal, paymentType)
                this.reimburses[i].details[a].tax = this.formatIdrForPayment(idrVal * tax / 100, paymentType)
                warnLargeTravelAmount('reimburse-' + i + '-' + a, idrVal, paymentType)

                // Total: sum of every row's IDR Rate plus this day's allowance --
                // allowance can still be null/empty here (Trip Type not chosen
                // yet), so treat it as 0 via parseTravelMoney() instead of doing
                // arithmetic directly on null.
                var subtotal = 0
                var hasBdc = (this.reimburses[i].details || []).some(function (d) {
                    return isBdcPayment(d.payment_type);
                });
                this.reimburses[i].details.forEach(element => {
                    subtotal += parseTravelMoney(element.idr_rate)
                });
                var allowance = parseTravelMoney(self.reimburses[i].trip_allowance)
                var total = +subtotal + +allowance
                this.reimburses[i].total = formatTravelDayTotal(total, hasBdc)
            } catch (error) {
                console.error('[calculateTotal] failed for day ' + i + ' row ' + a, error)
            }
        },
        removeDetail(i,a) {
            
            this.reimburses[i].details.splice(a,1)
            this.calculateTotal(i,0)
        },
        changeCost(i,a) {
            id = this.reimburses[i].details[a].cost_type
            self = this
            this.reimburses[i].details[a].code = self.types.filter(a => a.id == id)[0].type
            this.calculateTotal(i,a)
        },
        /** A blank "Form Hari" entry pre-filled with a date -- used for each day generated from the date range. */
        buildBlankDayEntry(dateStr, expanded) {
            return {
                trip: null,
                hotel_condition: null,
                trip_allowance: null,
                travel_time: null,
                start_time: null,
                end_time: null,
                date: dateStr || null,
                collapsed: !expanded,
                dayFiles: [],
                referenceInvoice: '',
                referDay: null,
                allowanceOnly: false,
                // Type applied to the NEXT upload in this day; the chip
                // dropdown still overrides it per file afterwards.
                nextDocType: 'invoice',
                uploadType: 'invoice',
                sameTripRef: null,
                referenceFeedback: { message: '', color: '' },
                ocrStatus: null,
                ocrSummary: null,
                ocrEditing: false,
                messRelation: null,
                details: [
                    {
                        cost_type: null,
                        destination: null,
                        currency: 'IDR',
                        amount: null,
                        tax: null,
                        idr_rate: null,
                        code: null,
                    }
                ],
                total: 0,
            };
        },
        /**
         * True if this "Form Hari" entry has anything a user would be upset
         * to lose -- an uploaded file, a picked Trip Type, a typed reference
         * invoice, or any expense-detail row with a cost type/destination/
         * amount filled in. Purpose isn't checked here (it has no v-model,
         * see the plain <input> in the template -- Vue doesn't track it
         * reactively), but the fields checked cover every case that matters
         * in practice: nobody fills expense rows without first picking a
         * Trip Type or uploading evidence.
         */
        dayHasData(entry) {
            if (entry.dayFiles && entry.dayFiles.length > 0) {
                return true;
            }
            if (entry.sameTripRef) {
                return true;
            }
            if (entry.allowanceOnly) {
                return true;
            }
            if (entry.referDay !== null && entry.referDay !== undefined) {
                return true;
            }
            if (entry.referenceInvoice && String(entry.referenceInvoice).trim() !== '') {
                return true;
            }
            if (entry.trip !== null && entry.trip !== undefined && String(entry.trip).trim() !== '') {
                return true;
            }
            var details = entry.details || [];
            for (var d = 0; d < details.length; d++) {
                var det = details[d] || {};
                if (det.cost_type || (det.destination && String(det.destination).trim() !== '') || (det.amount !== null && det.amount !== undefined && String(det.amount).trim() !== '')) {
                    return true;
                }
            }
            return false;
        },
        /**
         * Date range (Step 0, above the day cards) -> one "Form Hari N" card
         * per calendar day in the range, each with its own full Step 1/2/3
         * (upload evidence, trip type, expense details) -- replacing manual
         * one-at-a-time "Add New Item" clicks. Regenerating replaces
         * `reimburses` entirely, so re-running it after already filling some
         * days in loses that data -- this used to happen silently; now it
         * asks for confirmation first whenever any existing day actually has
         * something in it (see dayHasData()), instead of only in a code
         * comment nobody using the form ever sees.
         */
        regenerateDaysFromRange() {
            if (!this.rangeStart || !this.rangeEnd) {
                rtAlertModal('Please fill in the start date and end date first.');
                return;
            }
            var start = new Date(this.rangeStart + 'T00:00:00');
            var end = new Date(this.rangeEnd + 'T00:00:00');
            if (isNaN(start.getTime()) || isNaN(end.getTime())) {
                rtAlertModal('Invalid date.');
                return;
            }
            if (end < start) {
                rtAlertModal('The end date must be the same as or after the start date.');
                return;
            }

            var days = [];
            var cursor = new Date(start);
            while (cursor <= end) {
                var mm = String(cursor.getMonth() + 1).padStart(2, '0');
                var dd = String(cursor.getDate()).padStart(2, '0');
                days.push(cursor.getFullYear() + '-' + mm + '-' + dd);
                cursor.setDate(cursor.getDate() + 1);
            }
            if (days.length > 15) {
                rtAlertModal('A maximum of 15 days can be generated at once in one submission.');
                return;
            }

            var vm = this;
            // Days whose date survives into the new range keep everything the
            // user already filled in (Sep 2026 feedback: "bisa nggk nggk usah
            // hilang?") -- only dates that drop out of the range are lost, and
            // dates newly brought into it start blank. Matching is by the day's
            // own date, so extending 10-12 to 10-14 keeps 10, 11 and 12 intact.
            // Days currently on screen, plus days parked earlier by a previous
            // range change (see vm.stashedDays) -- so narrowing 10-12 to 11-14
            // and then changing your mind back to 10-12 brings day 10's data
            // back instead of handing you an empty card (Sep 2026 feedback:
            // "klo bisa balik juga boleh"). Nothing is parked across a page
            // reload; this only covers changing the range within one sitting.
            var keptByDate = {};
            var oldIndexByDate = {};
            Object.keys(vm.stashedDays).forEach(function (d) {
                keptByDate[d] = vm.stashedDays[d];
            });
            vm.reimburses.forEach(function (entry, idx) {
                if (entry && entry.date) {
                    keptByDate[entry.date] = entry;
                    oldIndexByDate[entry.date] = idx;
                }
            });
            var generate = function () {
                // A kept day can land on a different index (e.g. extending the
                // range backwards shifts everything down), and the real
                // <input type="file"> elements are named by day index
                // (rt-day-hidden-<i> / reimburse[i][files][]). Re-key them to
                // the new positions, or a kept day's evidence would be
                // submitted under the wrong day -- same bookkeeping removeDay()
                // does when it renumbers.
                // Stamp each on-screen container with its day's date first, so
                // a container can still be found after it has been parked (its
                // id is dropped then, but the date sticks).
                vm.reimburses.forEach(function (entry, idx) {
                    if (entry && entry.date) {
                        $('#rt-day-hidden-' + idx).attr('data-day-date', entry.date);
                    }
                });

                var $moved = $();
                days.forEach(function (d, newIdx) {
                    // On-screen day, or one parked by an earlier range change.
                    var $c = (d in oldIndexByDate)
                        ? $('#rt-day-hidden-' + oldIndexByDate[d])
                        : $('.rt-day-hidden-parked[data-day-date="' + d + '"]').first();
                    if (!$c.length) {
                        return;
                    }
                    $c.attr('data-new-index', newIdx);
                    $moved = $moved.add($c);
                });
                // Containers of days leaving the range are NOT destroyed --
                // they are parked (name blanked so the browser won't submit
                // them) so the day can be restored intact if its date comes
                // back. Only their names are cleared; the File objects and
                // their object URLs stay alive on the stashed entry.
                $('.rt-day-hidden-inputs').not($moved).each(function () {
                    var $c = $(this);
                    $c.removeAttr('id').addClass('rt-day-hidden-parked');
                    $c.find('input').removeAttr('name');
                });
                $moved.each(function () {
                    var $c = $(this);
                    var n = $c.attr('data-new-index');
                    // removeClass: a container coming back from the parked
                    // pool becomes a live one again; the name attributes set
                    // just below are what actually re-arm it for submission.
                    $c.removeAttr('data-new-index')
                      .removeClass('rt-day-hidden-parked')
                      .attr('id', 'rt-day-hidden-' + n);
                    $c.find('.rt-day-hidden-file').attr('name', 'reimburse[' + n + '][files][]');
                    $c.find('.rt-day-hidden-tag').attr('name', 'reimburse[' + n + '][file_row_tags][]');
                    $c.find('.rt-day-hidden-type').attr('name', 'reimburse[' + n + '][file_types][]');
                });

                vm.activeDay = 0;
                vm.reimburses = days.map(function (d, idx) {
                    var existing = keptByDate[d];
                    if (existing) {
                        existing.collapsed = idx !== 0;
                        return existing;
                    }
                    return vm.buildBlankDayEntry(d, idx === 0);
                });

                // Park every day that just left the range (keeping whatever
                // was filled in), and un-park the ones now back on screen.
                var onScreen = {};
                days.forEach(function (d) { onScreen[d] = true; });
                Object.keys(keptByDate).forEach(function (d) {
                    if (onScreen[d]) {
                        vm.$delete(vm.stashedDays, d);
                    } else {
                        vm.$set(vm.stashedDays, d, keptByDate[d]);
                    }
                });

                // referDay points at a day by INDEX, so remap it onto the new
                // positions; a refer whose source day dropped out of the range
                // is cleared rather than left pointing at the wrong day.
                var newIndexByDate = {};
                days.forEach(function (d, idx) { newIndexByDate[d] = idx; });
                vm.reimburses.forEach(function (entry) {
                    if (!entry || entry.referDay === null || entry.referDay === undefined) {
                        return;
                    }
                    var srcDate = null;
                    for (var dt in oldIndexByDate) {
                        if (oldIndexByDate[dt] === entry.referDay) {
                            srcDate = dt;
                            break;
                        }
                    }
                    var remapped = (srcDate !== null && srcDate in newIndexByDate)
                        ? newIndexByDate[srcDate]
                        : null;
                    vm.$set(entry, 'referDay', remapped);
                });

                // amount-input already has @change="calculateTotal(i,a)" bound in the
                // template itself -- only the maskMoney plugin needs (re)binding here.
                vm.$nextTick(function () {
                    vm.initSelectForm();
                });
            };
            var proceed = function () {
                // Only days that actually fall OUTSIDE the new range are lost,
                // so ask only about those -- and name them, instead of warning
                // that everything will be wiped when most of it is kept.
                var dropped = vm.reimburses.filter(function (entry) {
                    return vm.dayHasData(entry) && days.indexOf(entry.date) === -1;
                });
                if (dropped.length) {
                    var labels = dropped.map(function (e) { return e.date || '(tanpa tanggal)'; });
                    rtModal({
                        title: 'Keluarkan Hari Ini dari Trip?',
                        message: 'Form hari berikut sudah terisi tapi tanggalnya di luar range baru, jadi akan dikeluarkan dari pengajuan:\n\n'
                            + labels.join(', ')
                            + '\n\nIsinya disimpan sementara -- kalau tanggal itu dimasukkan lagi ke range sebelum halaman ditutup, isinya kembali seperti semula. Hari lain yang masih di dalam range tetap aman. Lanjutkan?',
                        confirmText: 'Ya, Lanjutkan', cancelText: 'Batal', onYes: generate
                    });
                    return;
                }
                generate();
            };

            // Checked HERE, at Generate (Sep 2026 feedback: "kalau bisa di tahap
            // generate, jangan sampai save atau draft baru ke detect ... terlalu
            // langkahnya kalau sampai save") -- finding out only at Submit means
            // the whole form was filled in for nothing. A hit blocks: the day
            // cards are not generated at all until the range is clean.
            rtBlockDuplicateTripDates(days, proceed);
        },
        /**
         * Deletes one "Form Hari" (e.g. a wrongly-dated card). The hidden file
         * inputs and their names are keyed by day index (see
         * rtAddDayHiddenFile()), so they are re-numbered for every later day
         * to keep reimburse[i][...] contiguous; refer_day pointers follow.
         */
        removeDay(i) {
            if (this.reimburses.length <= 1) {
                return;
            }
            var vm = this;
            var entry = this.reimburses[i];
            var label = 'Day ' + (i + 1) + ' form' + (entry.date ? ' (' + entry.date + ')' : '');
            rtModal({
                title: 'Delete Day Form?',
                message: 'Delete ' + label + '?' + (this.dayHasData(entry) ? ' The data already entered in this form will be lost.' : ''),
                confirmText: 'Yes, Delete', cancelText: 'Cancel',
                onYes: function () { vm.doRemoveDay(i); }
            });
        },
        doRemoveDay(i) {
            var entry = this.reimburses[i];
            if (!entry) {
                return;
            }
            (entry.dayFiles || []).forEach(function (f) {
                if (f.objectUrl) {
                    URL.revokeObjectURL(f.objectUrl);
                }
                rtRemoveDayHiddenFile(f.uid);
            });
            $('#rt-day-hidden-' + i).remove();

            // Re-number hidden inputs of every later day: j -> j-1.
            for (var j = i + 1; j < this.reimburses.length; j++) {
                var $c = $('#rt-day-hidden-' + j);
                $c.attr('id', 'rt-day-hidden-' + (j - 1));
                $c.find('.rt-day-hidden-file').attr('name', 'reimburse[' + (j - 1) + '][files][]');
                $c.find('.rt-day-hidden-tag').attr('name', 'reimburse[' + (j - 1) + '][file_row_tags][]');
                $c.find('.rt-day-hidden-type').attr('name', 'reimburse[' + (j - 1) + '][file_types][]');
            }

            this.reimburses.splice(i, 1);
            if (this.activeDay > i || this.activeDay >= this.reimburses.length) {
                this.activeDay = Math.max(0, Math.min(this.activeDay - (this.activeDay > i ? 1 : 0), this.reimburses.length - 1));
            }
            if (this.reimburses[this.activeDay]) {
                this.reimburses[this.activeDay].collapsed = false;
            }

            // Days that referred to the deleted day lose their refer; later sources shift down by one.
            var vm = this;
            this.reimburses.forEach(function (r) {
                if (r.referDay === null || r.referDay === undefined) {
                    return;
                }
                if (r.referDay === i) {
                    vm.$set(r, 'referDay', null);
                } else if (r.referDay > i) {
                    vm.$set(r, 'referDay', r.referDay - 1);
                }
            });
            this.pruneBrokenRefers();
            this.recomputeLocalInvoiceDuplicates();
            this.$nextTick(function () {
            });
        },
        selectDay(i) {
            this.activeDay = i;
            if (this.reimburses[i]) {
                this.reimburses[i].collapsed = false;
            }
        },
        /** "Add New Item": a blank day (date picked in Step 2), opened as the active tab. */
        addNewDay() {
            if (this.reimburses.length >= 15) {
                alert('A maximum of 15 days is allowed in one submission.');
                return;
            }
            var vm = this;
            this.reimburses.push(this.buildBlankDayEntry(null, true));
            this.activeDay = this.reimburses.length - 1;
            this.$nextTick(function () {
                vm.initSelectForm();
            });
        },
        toggleDayCollapse(i) {
            this.reimburses[i].collapsed = !this.reimburses[i].collapsed;
        },
        /**
         * Step 1: Upload Evidence -- MULTIPLE files per day (Sep 2026 multi-file
         * redesign). Each newly picked/dropped file is APPENDED to dayFiles
         * (not replacing the previous one), read via readAsDataURL for the
         * chip thumbnail + its own OCR check. Each file can optionally be
         * tagged to a specific Expense Detail row (see the row-tag <select>
         * in the template / onFileRowTagChange()) -- untagged files stay
         * "day-level", same as the old single-shared-file behaviour.
         */
        addDayFiles(i, fileList) {
            if (!fileList || !fileList.length) {
                return;
            }
            var vm = this;
            var entry = vm.reimburses[i];
            // FileList is live -- the input gets reset after `change`, so copy it first.
            var files = Array.prototype.slice.call(fileList);

            // Enforce the "Max 10MB" the dropzone advertises. Checked here so
            // it covers both Browse File and drag & drop, and before any
            // refer/allowance state is cleared -- an oversized file must not
            // silently discard the day's existing setup.
            var oversized = files.filter(function (f) {
                return f && f.size > RT_MAX_EVIDENCE_BYTES;
            });
            if (oversized.length) {
                var names = oversized.map(function (f) {
                    return f.name + ' (' + rtFormatFileSize(f.size) + ')';
                }).join(', ');
                rtAlertModal(
                    'Maximum file size is ' + RT_MAX_EVIDENCE_MB + 'MB. '
                    + 'These files were not attached: ' + names + '.',
                    'File Too Large'
                );
                files = files.filter(function (f) {
                    return !f || f.size <= RT_MAX_EVIDENCE_BYTES;
                });
                if (!files.length) {
                    return;
                }
            }
            var addAll = function () {
                files.forEach(function (file) {
                    vm.addSingleDayFile(i, file);
                });
            };
            // Uploading its own evidence replaces any refer / allowance-only state of this day.
            var message = null;
            var clear = null;
            if (entry.sameTripRef) {
                message = 'The reference to the invoice of ' + entry.sameTripRef.owner_name + ' will be cancelled and this day will use its own proof. Continue?';
                clear = function () { vm.clearSameTripRef(i); };
            } else if (entry.allowanceOnly) {
                message = 'The "Travel Allowance only" option will be turned off because you are uploading proof. Continue?';
                clear = function () { vm.$set(entry, 'allowanceOnly', false); };
            } else if (entry.referDay !== null && entry.referDay !== undefined) {
                message = 'The refer to Day ' + (entry.referDay + 1) + ' will be cancelled and Day ' + (i + 1) + ' will use its own invoice. Continue?';
                clear = function () { vm.clearReferDay(i); };
            }
            if (message) {
                rtModal({
                    title: 'Use Your Own Proof?',
                    message: message,
                    confirmText: 'Yes, Continue', cancelText: 'Cancel',
                    onYes: function () { clear(); addAll(); }
                });
                return;
            }
            addAll();
        },
        addSingleDayFile(i, file) {
            if (!file) {
                return;
            }
            var vm = this;
            var entry = vm.reimburses[i];
            var isImage = file.type && file.type.indexOf('image/') === 0;
            var uid = 'day_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);

            // The actual <input type="file"> submitted for this file -- created
            // once per file so several can coexist (see rtAddDayHiddenFile()).
            // The file takes the type chosen above the dropzone before browsing
            // (data.nextDocType); the chip's own dropdown still corrects it
            // afterwards, so a mistake costs one click rather than a re-upload.
            var isProof = entry.nextDocType === 'proof';
            rtAddDayHiddenFile(i, uid, file, isProof ? 'proof' : 'invoice');

            var pushChip = function (dataUrl) {
                var objectUrl = (!isImage) ? URL.createObjectURL(file) : null;
                var chip = {
                    uid: uid,
                    name: file.name,
                    dataUrl: dataUrl,
                    objectUrl: objectUrl,
                    // Drives the gallery modal's <iframe> vs <img> choice; a PDF
                    // rendered into an <img> would just show as broken.
                    isPdf: !isImage,
                    rowTag: '',
                    docType: isProof ? 'proof' : 'invoice',
                    // The picked File itself, so re-marking a Supporting Proof back
                    // to Invoice / Receipt can run OCR on it without the user having
                    // to remove and re-upload the file (onFileDocTypeChange()).
                    // Existing (already-stored) files reopened in edit have no File
                    // object here -- they are re-checked server side on save.
                    rawFile: file,
                    ocrStatus: isProof ? 'proof' : 'pending',
                    ocrMessage: '',
                    // Populated per-file in rtVerifyDayFileEvidence() -- present
                    // here (even blank) so Vue 2's reactivity picks up later
                    // assignments to them (a plain object's properties must
                    // exist at push() time for the array to observe them).
                    ocrDate: '',
                    ocrMerchant: '',
                    ocrAmount: '',
                    ocrCurrency: '',
                    ocrInvoice: '',
                    // Per-file OCR Result panel edit toggle (Sep 2026 feedback:
                    // each file gets its own editable panel, not one shared one
                    // that gets replaced by whichever file was added last).
                    ocrEditing: false
                };
                // Auto-tag to the next row that has no file yet, so the user
                // never has to touch the dropdown (they still can, to override).
                chip.rowTag = vm.nextUntaggedRowTag(entry);
                entry.dayFiles.push(chip);
                rtSetDayHiddenTag(chip.uid, chip.rowTag);
                if (isProof) {
                    rtEnableTravelSubmitButtons();
                } else {
                    rtVerifyDayFileEvidence(vm, i, chip, file);
                }
            };

            if (isImage) {
                var reader = new FileReader();
                reader.onload = function (e) { pushChip(e.target.result); };
                reader.readAsDataURL(file);
            } else {
                pushChip(null);
            }
        },
        /**
         * Auto-tag for a freshly added file (Sep 2026 feedback: "user tidak mau
         * manual set tag nya, maunya otomatis"): returns the index of the first
         * Expense Detail row that no other file is tagged to yet, so file 1 ->
         * Baris 1, file 2 -> Baris 2, and so on without anyone touching the
         * dropdown. Returns '' (General/all rows, the old behaviour) when every
         * row already has its own file -- we never create rows here, so extra
         * files simply stay day-level. The dropdown still works for overriding.
         */
        nextUntaggedRowTag(entry) {
            var rows = (entry.details || []).length;
            if (!rows) {
                return '';
            }
            var taken = {};
            (entry.dayFiles || []).forEach(function (f) {
                if (f.rowTag !== '' && f.rowTag !== null && f.rowTag !== undefined) {
                    taken[String(f.rowTag)] = true;
                }
            });
            for (var r = 0; r < rows; r++) {
                if (!taken[String(r)]) {
                    return String(r);
                }
            }
            return '';
        },
        /** 1.A: which Expense Detail row (if any) this file belongs to -- kept in sync onto the actual submitted hidden input, since Vue can't name a real file input's sibling declaratively here. */
        onFileRowTagChange(i, chip) {
            rtSetDayHiddenTag(chip.uid, chip.rowTag);
        },
        /**
         * Oct 2026: the Invoice-vs-Proof choice is per file and changeable after
         * upload, so it has to keep three things in step -- the submitted
         * reimburse[i][file_types][] entry, this chip's OCR state, and the
         * Submit button's enabled state.
         *
         * Marking a file as Supporting Proof takes it out of the OCR/duplicate
         * checks entirely (its stale reading is cleared, so a previously-read
         * invoice number can't keep flagging a duplicate it no longer claims).
         * Marking it back as Invoice / Receipt re-runs OCR on the held File.
         * Either way the server re-derives everything from file_types[] on
         * submit -- this is the client-side mirror of that, never the authority.
         */
        onFileDocTypeChange(i, chip) {
            var isProof = chip.docType === 'proof';
            rtSetDayHiddenType(chip.uid, isProof ? 'proof' : 'invoice');

            if (isProof) {
                chip.ocrStatus = 'proof';
                chip.ocrMessage = '';
                chip.ocrDate = '';
                chip.ocrMerchant = '';
                chip.ocrAmount = '';
                chip.ocrCurrency = '';
                chip.ocrInvoice = '';
                chip.ocrEditing = false;
                // This file's invoice no longer counts toward the in-page
                // duplicate warning, and the day may now need another file's
                // reading as its primary.
                this.recomputeLocalInvoiceDuplicates();
                rtEnableTravelSubmitButtons();
                return;
            }

            if (chip.rawFile) {
                rtVerifyDayFileEvidence(this, i, chip, chip.rawFile);
                return;
            }

            // An already-stored file reopened in edit: nothing to re-read here,
            // the server checks it on save.
            chip.ocrStatus = 'unavailable';
            rtEnableTravelSubmitButtons();
        },
        /**
         * Opens the webcam in #modalPhoto and adds the captured frame to day i
         * as a normal evidence file.
         *
         * Mirrors Entertainment's camera, which users already know, but routes
         * the result through addSingleDayFile() rather than stuffing a hidden
         * <input type=file> like the old table UI did -- that input and its
         * #preview_<idx> target no longer exist in this form.
         *
         * The stream is always stopped on the way out (capture, Cancel, or the
         * modal being dismissed any other way), otherwise the camera light
         * stays on and the device stays locked for other apps.
         */
        openDayCamera(i) {
            var vm = this;
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                rtAlertModal('This browser cannot access the camera. Please use "Browse File" instead.', 'Camera Unavailable');
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

            // Runs for every close path, including the X and the backdrop.
            $modal.off('hidden.bs.modal.rtcam').on('hidden.bs.modal.rtcam', function () {
                $('#captureButton').off('click.rtcam');
                stopCamera();
            });

            navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'environment' }
            }).then(function (stream) {
                activeStream = stream;
                video.srcObject = stream;
                $modal.modal('show');

                $('#captureButton').off('click.rtcam').on('click.rtcam', function () {
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
                        // A unique name keeps each shot distinct in the chip
                        // list and in the duplicate-file check.
                        var name = 'camera-' + Date.now() + '.jpg';
                        var photo = new File([blob], name, { type: 'image/jpeg' });
                        vm.addSingleDayFile(i, photo);
                        rtEnableTravelSubmitButtons();
                    }, 'image/jpeg', 0.85);

                    $modal.modal('hide');
                });
            }).catch(function () {
                // Permission denied, no camera, or a non-HTTPS origin (browsers
                // only expose getUserMedia on https:// or localhost).
                rtAlertModal('The camera could not be opened. Check the browser\'s camera permission, or use "Browse File" instead.', 'Camera Unavailable');
            });
        },
        /**
         * Choose the type the NEXT upload of this day gets.
         *
         * Set before browsing so a ticket or email screenshot is never run
         * through OCR and flagged "not an invoice". Per-file dropdowns on the
         * chips still override this afterwards.
         */
        setNextDocType(i, type) {
            var entry = this.reimburses[i];
            if (!entry) {
                return;
            }
            this.$set(entry, 'nextDocType', type === 'proof' ? 'proof' : 'invoice');
        },
        onDayFileInputChange(i, event) {
            this.addDayFiles(i, event.target.files);
            // Allow picking the exact same file(s) again later (e.g. after removing one).
            event.target.value = '';
        },
        /** Drag-and-drop doesn't touch the real <input type="file">, so files are read straight from the drop event's own FileList. */
        onDayFileDrop(i, event) {
            var files = event.dataTransfer && event.dataTransfer.files;
            this.addDayFiles(i, files);
        },
        removeDayFile(i, fileIndex) {
            var entry = this.reimburses[i];
            var removed = entry.dayFiles[fileIndex];
            if (removed) {
                if (removed.objectUrl) {
                    URL.revokeObjectURL(removed.objectUrl);
                }
                rtRemoveDayHiddenFile(removed.uid);
            }
            entry.dayFiles.splice(fileIndex, 1);
            this.pruneBrokenRefers();
            if (entry.dayFiles.length === 0) {
                // OCR Result panels are per-file now (see readOcrFiles()) and
                // simply disappear along with their file automatically; only
                // messRelation (Stay(MESS) auto-detection) is day-level state
                // that still needs clearing when the last file is removed.
                this.$set(entry, 'messRelation', null);
            }
            // Removing a file can resolve a same-invoice conflict with a
            // sibling file elsewhere in the submission -- let that sibling's
            // 'duplicate' flag clear if so.
            this.recomputeLocalInvoiceDuplicates();
        },
        /** Opens one file chip in a new tab -- image via its data URL, PDF via a blob object URL (createObjectURL), so both are actually viewable instead of just alert()-ing the file name. */
        previewFileChip(chip) {
            if (!chip) {
                return;
            }
            if (chip.dataUrl) {
                // Convert to a blob: URL first -- window.open() on a raw
                // data: URI opens about:blank in modern browsers instead of
                // the image (see dataUrlToBlob()'s comment above).
                var url = URL.createObjectURL(dataUrlToBlob(chip.dataUrl));
                window.open(url, '_blank');
                // Give the new tab a full minute to actually load the image
                // before freeing the blob -- revoking too early can blank out
                // a tab that hasn't finished rendering yet.
                setTimeout(function () { URL.revokeObjectURL(url); }, 60000);
            } else if (chip.objectUrl) {
                window.open(chip.objectUrl, '_blank');
            } else {
                alert(chip.name);
            }
        },
        /** Opens #rtOcrDetailModal showing this ONE file's full OCR reading, read-only -- lets every uploaded file's result actually be seen instead of only the day's latest file (which is all the single editable OCR Result panel on the right can show, since only one no_invoice/merchant_name is ever submitted per day). */
        showOcrDetailModal(chip) {
            this.ocrDetailModalChip = chip;
            $('#rtOcrDetailModal').modal('show');
        },
        /** Every file for this day that actually got an OCR reading -- what the redesigned per-file OCR Result panel (Sep 2026 feedback) loops over, and what "OCR will read" hint's v-else falls back to when this is empty. */
        readOcrFiles(data) {
            return (data.dayFiles || []).filter(function (f) { return f.ocrStatus === 'read'; });
        },
        /**
         * Flags every file, across the WHOLE submission (every day, not just
         * one), that shares its No. Invoice/Receipt with another file also in
         * this submission -- e.g. the same physical receipt uploaded once as
         * a PNG and again as a PDF (Sep 2026 feedback: "upload invoice yang
         * sama... seharusnya gabisa"). None of the other duplicate checks
         * catch this: they all compare against rows/files already saved in
         * the database, and neither file here has been saved yet. Reuses the
         * existing 'duplicate' status/badge -- visual warning only, never
         * blocks Submit by itself (same fail-open pattern as every other OCR
         * check); the real block is
         * TravelReimbursementController::guardAgainstDuplicateInvoiceWithinSubmission()
         * at save time. Re-run after every OCR read, every manual edit of a
         * file's Invoice field, and every file removal, so a resolved
         * conflict (file removed/corrected) reverts back to 'read' instead of
         * staying stuck on 'duplicate'.
         */
        recomputeLocalInvoiceDuplicates() {
            var groups = {};
            this.reimburses.forEach(function (day) {
                (day.dayFiles || []).forEach(function (f) {
                    if (f.ocrStatus !== 'read' && f.ocrStatus !== 'duplicate') {
                        return;
                    }
                    var inv = String(f.ocrInvoice || '').trim().toUpperCase();
                    if (!inv) {
                        return;
                    }
                    if (!groups[inv]) {
                        groups[inv] = [];
                    }
                    groups[inv].push(f);
                });
            });
            Object.keys(groups).forEach(function (inv) {
                var group = groups[inv];
                if (group.length > 1) {
                    group.forEach(function (f) {
                        f.ocrStatus = 'duplicate';
                        f.ocrMessage = 'This invoice number is the same as ' + (group.length - 1) + ' other file(s) in this submission.';
                    });
                } else if (group[0].ocrStatus === 'duplicate') {
                    // Was flagged, no longer conflicts with anything (sibling
                    // removed/edited) -- revert to a normal "read" file.
                    group[0].ocrStatus = 'read';
                    group[0].ocrMessage = group[0].ocrInvoice ? ('No. Invoice: ' + group[0].ocrInvoice) : '';
                }
            });
        },
        /**
         * Which ONE file's OCR reading becomes the day's own no_invoice/
         * merchant_name/etc (the hidden reimburse[i][...] inputs actually
         * submitted to the server -- see TravelReimbursementController::
         * store(), which still only has one such field per day). Prefers a
         * file explicitly left untagged ("General (all rows)", i.e. meant to
         * apply to the whole day) over one tagged to a specific row, then
         * falls back to the first file read at all. This no longer depends
         * on upload order (previously: whichever was added last), so it
         * doesn't change to a different file's data just because another
         * file got uploaded afterward.
         */
        primaryOcrFile(data) {
            var read = this.readOcrFiles(data);
            if (!read.length) {
                return null;
            }
            return read.filter(function (f) { return !f.rowTag; })[0] || read[0];
        },
        /** Step 3 "Preview" button for a specific Expense Detail row: shows the file tagged to THIS row, falling back to any untagged ("Umum") day-level file -- preserving the old single-shared-file behaviour when nothing has been tagged yet. */
        /**
         * Which Step 1 file (if any) this Expense Detail row's Preview button
         * actually links to -- a row tagged to a specific file wins, else the
         * first untagged ("Umum") file, else (once at least one file exists)
         * the most recently added one, matching previewRowFile()'s own
         * fallback order exactly. Returns null only when there are no files
         * at all yet.
         */
        matchedFileForRow(i, a) {
            return this.matchedFilesForRow(i, a)[0] || null;
        },
        /**
         * EVERY file belonging to this Expense Detail row, not just the first
         * one (Oct 2026 feedback: "di preview tetap 1 gambar aja yang bisa
         * dicek?"). A row can now carry several evidence files -- e.g. the
         * invoice/receipt plus a supporting email screenshot -- and the old
         * single-file lookup silently hid every one after the first.
         *
         * Returns the files tagged to THIS row PLUS the untagged ones, since
         * "General (all rows)" means exactly that -- it covers every row, so it
         * belongs in the row's preview alongside the row's own files. Returning
         * only the tagged ones hid day-level evidence from Preview: on the edit
         * form a saved day-level file comes back as rowTag '' while a saved
         * row-level file comes back as '0', so a row holding one of each showed
         * just a single file even though the chip list showed two (Oct 2026
         * bug report).
         *
         * Order follows the chip list (saved files first, then new uploads), so
         * the Preview opens them in the same order they are displayed. Falls
         * back to the most recently added file when a row matches nothing at
         * all, preserving the pre-tagging behaviour.
         */
        matchedFilesForRow(i, a) {
            var entry = this.reimburses[i];
            var pool = entry.dayFiles || [];
            if (!pool.length) {
                return [];
            }
            var tag = String(a);
            var matched = pool.filter(function (f) {
                return f.rowTag === tag || !f.rowTag;
            });
            return matched.length ? matched : [pool[pool.length - 1]];
        },
        /** 1-based position of matchedFileForRow() within Step 1's file list -- shown on the Preview button (Sep 2026 feedback: "preview nomor 1 nge-link ke file nomor 1 yang diupload") so it's visible at a glance which numbered file a row's Preview actually opens, instead of an unlabelled eye icon. */
        matchedFileNumberForRow(i, a) {
            var entry = this.reimburses[i];
            var matched = this.matchedFileForRow(i, a);
            if (!matched) {
                return null;
            }
            return entry.dayFiles.indexOf(matched) + 1;
        },
        /** How many files this row's Preview will open -- drives the "+N" hint when a row carries more than one evidence file. */
        matchedFileCountForRow(i, a) {
            return this.matchedFilesForRow(i, a).length;
        },
        /** Tooltip for the Preview button, naming every file it opens so it's clear before clicking. */
        previewRowTitle(i, a) {
            var matched = this.matchedFilesForRow(i, a);
            if (!matched.length) {
                return 'No proof linked yet';
            }
            if (matched.length === 1) {
                return 'View proof: ' + matched[0].name;
            }
            return 'View all ' + matched.length + ' evidence files of this row: '
                + matched.map(function (f) { return f.name; }).join(', ');
        },
        /**
         * Shows every file linked to this row in one gallery modal.
         *
         * This used to call window.open() once per file. Only the FIRST of
         * those runs under the click's own user activation -- the staggered
         * ones lose it, so popup blockers allowed some tabs and silently
         * dropped others: "kadang kebuka 1 proof doang kadang kebuka semua"
         * (Oct 2026). A modal renders in-page, so it can never be blocked and
         * every file of the row is always reachable, in the same order as the
         * chip list. A single file opens the same modal -- one predictable
         * behaviour rather than two.
         */
        previewRowFile(i, a) {
            var matched = this.matchedFilesForRow(i, a);
            if (!matched.length) {
                return;
            }
            this.rowPreviewFiles = matched;
            this.rowPreviewIndex = 0;
            $('#rtRowPreviewModal').modal('show');
        },
        /** Gallery navigation; wraps around so paging never dead-ends. */
        rowPreviewGo(step) {
            var total = this.rowPreviewFiles.length;
            if (!total) {
                return;
            }
            this.rowPreviewIndex = ((this.rowPreviewIndex + step) % total + total) % total;
        },
        /** The file currently shown in the gallery modal. */
        rowPreviewCurrent() {
            return this.rowPreviewFiles[this.rowPreviewIndex] || null;
        },
        /**
         * Best displayable URL for a chip, whichever shape it has: a saved
         * attachment (existingUrl), a freshly picked image (dataUrl) or a
         * freshly picked PDF (objectUrl).
         */
        rowPreviewSrc(chip) {
            if (!chip) {
                return '';
            }
            return chip.existingUrl || chip.dataUrl || chip.objectUrl || '';
        },
        /** Opens the currently shown file in its own tab -- a single, user-initiated window.open(), so it is never popup-blocked. */
        rowPreviewOpenCurrent() {
            var chip = this.rowPreviewCurrent();
            if (chip) {
                this.previewFileChip(chip);
            }
        },
        /**
         * Drops every refer whose source day no longer has evidence (its file was
         * removed), cascading through chains (Hari 3 -> 2 -> 1), and tells the user.
         */
        pruneBrokenRefers() {
            var vm = this;
            var cleared = [];
            var changed = true;
            while (changed) {
                changed = false;
                vm.reimburses.forEach(function (r, idx) {
                    if (r.referDay !== null && r.referDay !== undefined && !vm.canReferDay(r.referDay)) {
                        vm.$set(r, 'referDay', null);
                        cleared.push(idx + 1);
                        changed = true;
                    }
                });
            }
            if (cleared.length) {
                rtAlertModal('Refer cancelled for Day ' + cleared.join(', ') + ' because its source document no longer exists. Choose a refer again or upload your own invoice.', 'Refer Cancelled');
            }
        },
        /**
         * Same-trip popup result. Cancel: the uploaded file is discarded, nothing is linked.
         * Yes, Continue: the file is discarded too (the duplicate invoice must not be
         * re-attached) and the day is linked to the owner's claim by invoice number --
         * server-side this goes through resolveReferencedRowInvoice(), expenses stay 0.
         */
        offerSameTripReference(i, chip, offer) {
            var vm = this;
            var dropChip = function () {
                var idx = vm.reimburses[i] ? vm.reimburses[i].dayFiles.indexOf(chip) : -1;
                if (idx > -1) {
                    vm.removeDayFile(i, idx);
                }
            };
            window.ReimbursementOcrCheck.showSameTripConfirmModal(offer, function () {
                dropChip();
                var entry = vm.reimburses[i];
                if (!entry) {
                    return;
                }
                vm.$set(entry, 'referenceInvoice', offer.no_invoice);
                vm.$set(entry, 'sameTripRef', { no_invoice: offer.no_invoice, owner_name: offer.owner_name, ticket_number: offer.ticket_number });
                vm.$set(entry, 'allowanceOnly', false);
                vm.$set(entry, 'referDay', null);
                for (var a = 0; a < entry.details.length; a++) {
                    entry.details[a].amount = '0';
                    vm.calculateTotal(i, a);
                }
                rtEnableTravelSubmitButtons();
            }, dropChip);
        },
        clearSameTripRef(i) {
            this.$set(this.reimburses[i], 'sameTripRef', null);
            this.$set(this.reimburses[i], 'referenceInvoice', '');
        },
        isExpenseLocked(entry) {
            return !!entry.allowanceOnly || !!entry.sameTripRef || (entry.referDay !== null && entry.referDay !== undefined);
        },
        /** "Hanya Travel Allowance": nothing to upload or expense -- zero the rows and let the form be submitted. */
        onAllowanceOnlyChange(i) {
            var entry = this.reimburses[i];
            if (!entry.allowanceOnly) {
                return;
            }
            for (var a = 0; a < entry.details.length; a++) {
                entry.details[a].amount = '0';
                this.calculateTotal(i, a);
            }
            rtEnableTravelSubmitButtons();
        },
        /** A day can be referred to only if it has its own evidence (or itself refers/references one). */
        canReferDay(srcIdx) {
            var src = this.reimburses[srcIdx];
            if (!src) {
                return false;
            }
            return src.dayFiles.length > 0 || src.referDay !== null || $.trim(src.referenceInvoice || '') !== '';
        },
        /** Travel-Allowance-only claim for day i, re-using day srcIdx's evidence (no re-upload). */
        referToDay(i, srcIdx) {
            var entry = this.reimburses[i];
            var label = srcIdx === 0 ? 'pertama' : 'sebelumnya';
            var vm = this;
            rtModal({
                title: 'Document Already Used',
                message: 'This document has already been used.\n\nUse the same document (Day ' + (srcIdx + 1) + ') for the Travel Allowance of Day ' + (i + 1) + '?\n\nNote: the same document cannot be used to claim the same expense more than once, so this day expense amount is set to 0 (' + label + ' day).',
                confirmText: 'Yes, Continue', cancelText: 'Cancel',
                onYes: function () {
                    vm.$set(entry, 'referDay', srcIdx);
                    for (var a = 0; a < entry.details.length; a++) {
                        entry.details[a].amount = '0';
                        vm.calculateTotal(i, a);
                    }
                    rtEnableTravelSubmitButtons();
                }
            });
        },
        clearReferDay(i) {
            this.$set(this.reimburses[i], 'referDay', null);
        },
        checkDayReferenceInvoice(i) {
            var vm = this;
            var entry = vm.reimburses[i];
            var value = $.trim(entry.referenceInvoice || '');

            if (value === '') {
                vm.$set(entry, 'referenceFeedback', { message: '', color: '' });
                return;
            }

            vm.$set(entry, 'referenceFeedback', { message: 'Memeriksa…', color: '#6c757d' });

            $.ajax({
                url: '/reimbursement/check-evidence-reference',
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content') || '',
                    no_invoice: value
                }
            }).then(function (res) {
                res = res || {};
                if (res.found) {
                    vm.$set(entry, 'referenceFeedback', { message: res.message || 'Ditemukan.', color: '#1e7e42' });
                    rtEnableTravelSubmitButtons();
                } else {
                    vm.$set(entry, 'referenceFeedback', { message: res.message || 'Not found.', color: '#c0392b' });
                }
            }).catch(function () {
                vm.$set(entry, 'referenceFeedback', { message: 'Unable to check right now.', color: '#c0392b' });
            });
        }
      },
      watch: {

      },
  });

  // No standalone date-only duplicate popup here -- a duplicate is only
  // flagged when tanggal + No Invoice + Nominal ALL match an existing claim
  // (business decision, Sep 2026), which is checked per cost-line row via
  // the OCR badge (see reimbursement-ocr-check.js) and enforced server-side
  // at save time.

</script>

@endpush
@endsection
