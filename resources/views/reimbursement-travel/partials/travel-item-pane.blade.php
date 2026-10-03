@php
if (!function_exists('rt_travel_pane_rupiah')) {
    function rt_travel_pane_rupiah($angka)
    {
        return number_format((float) $angka, 2, ',', '.');
    }
}
if (!function_exists('rt_travel_pane_amount_int')) {
    /** Tampilan kolom Amount: desimal (2 digit) dengan pemisah ribuan, sesuai nominal di bukti/invoice. */
    function rt_travel_pane_amount_int($angka)
    {
        return number_format((float) $angka, 2, ',', '.');
    }
}
if (!function_exists('rt_travel_pane_attachment_cache_bust')) {
    function rt_travel_pane_attachment_cache_bust($fileName)
    {
        $fileName = trim((string) $fileName);
        if ($fileName === '') {
            return '';
        }
        $path = public_path('images/file_bukti/' . $fileName);
        if (is_file($path)) {
            return '?v=' . (int) filemtime($path);
        }

        return '?v=' . time();
    }
}
if (!function_exists('rt_travel_detail_attachments')) {
    function rt_travel_detail_attachments($detailId, $legacyEvidence = '', $reimbursementId = 0, $destination = '', $costTypeId = 0)
    {
        return \App\Support\TravelAttachmentResolver::rowsForDetail(
            (int) $reimbursementId,
            (int) $detailId,
            (string) $legacyEvidence,
            (string) $destination,
            (int) $costTypeId
        );
    }
}
if (!function_exists('rt_travel_pane_render_attachments')) {
    /** Preview lampiran per baris detail; $rowIndex = indeks array form (0, 1, 2…). */
    function rt_travel_pane_render_attachments(array $attachments, int $rowIndex, bool $canEditAttachments, string $previewId)
    {
        $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        echo '<div id="' . e($previewId) . '">';
        if ($canEditAttachments && count($attachments) > 0) {
            echo '<input type="hidden" name="keep_attachment_ids_present[' . (int) $rowIndex . ']" value="1" class="keep-attachment-present-marker">';
        }
        foreach ($attachments as $att) {
            $file = $att['file_name'] ?? '';
            $name = $att['original_name'] ?? $file;
            $attId = (int) ($att['id'] ?? 0);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($attId > 0) {
                echo '<input type="hidden" name="keep_attachment_ids[' . (int) $rowIndex . '][]" value="' . (int) $attId . '" class="keep-attachment-input">';
            }
            echo '<div class="existing-attachment-item preview-card">';
            if ($canEditAttachments && $file !== '') {
                echo '<button type="button" class="btn remove-existing-attachment preview-card-remove" data-attachment-id="' . (int) $attId . '" data-legacy-file="' . e($file) . '">&times;</button>';
            }
            if ($file !== '' && in_array($ext, $imageExt, true)) {
                $imgUrl = url('images/file_bukti/' . $file) . rt_travel_pane_attachment_cache_bust($file);
                echo '<img src="' . e($imgUrl) . '" class="preview-thumbnail preview-card-thumb" data-preview-src="' . e($imgUrl) . '">';
            } else {
                $fileUrl = url('images/file_bukti/' . $file) . rt_travel_pane_attachment_cache_bust($file);
                echo '<a href="' . e($fileUrl) . '" target="_blank"><img src="https://cdn-icons-png.flaticon.com/512/337/337946.png" class="preview-card-thumb preview-card-thumb-icon" alt="File"></a>';
            }
            echo '<a href="' . e(url('images/file_bukti/' . $file)) . '" target="_blank" class="preview-card-name" title="' . e($name) . '">' . e($name) . '</a>';
            echo '</div></div>';
        }
        echo '</div>';
    }
}
if (!function_exists('rt_travel_pane_day_total')) {
    function rt_travel_pane_day_total($travelRow, $travelDetails)
    {
        return \App\Support\TravelDayTotal::compute($travelRow, $travelDetails);
    }
}
$taxFirstExtra = !empty($is_overseas) ? ' tax-input' : '';
// Some travel rows have no reimbursement_travel_details yet (e.g. new tab); avoid $travel_detail[0] errors.
$rtRow0 = (isset($travel_detail[0]) && $travel_detail[0])
    ? $travel_detail[0]
    : (object) [
        'id' => '',
        'cost_type_id' => null,
        'destination' => '',
        'remarks' => '',
        'currency' => '',
        'amount' => 0,
        'idr_rate' => 0,
        'tax' => 0,
        'payment_type' => '',
        'evidence' => '',
    ];
$rtDayTotal = rt_travel_pane_day_total($data_travel['0'], $travel_detail);
// Day that only claims the Travel Allowance by referencing another claim/day: Expense Details are locked.
$rtDayLocked = !empty($data_travel['0']->reference_reimbursement_id);
@endphp
@php
    $statusInt = (int) ($data[0]->status ?? 0);
    $jabatan = (string) (auth()->user()->jabatan ?? '');
    $canManageTabs = false;
    if ($statusInt === 10) {
        $canManageTabs = true;
    } elseif ($statusInt === 0 && in_array($jabatan, ['Direktur Operasional', 'superadmin', 'admin'], true)) {
        $canManageTabs = true;
    } elseif ($statusInt === 1 && in_array($jabatan, ['Finance', 'Finance Supervisor', 'HR', 'HR GA', 'superadmin', 'admin'], true)) {
        $canManageTabs = true;
    } elseif ($statusInt === 2 && in_array($jabatan, ['Owner', 'Finance Supervisor', 'superadmin', 'admin'], true)) {
        $canManageTabs = true;
    } elseif ($statusInt === 11 && in_array($jabatan, ['Owner', 'Finance Manager', 'superadmin', 'admin'], true)) {
        $canManageTabs = true;
    } elseif ($statusInt === 3 && in_array($jabatan, ['Owner', 'Finance Manager', 'superadmin', 'admin'], true)) {
        $canManageTabs = true;
    } elseif ($statusInt === 9) {
        $canManageTabs = (int) ($data[0]->id_user ?? 0) === (int) auth()->id() || in_array($jabatan, ['superadmin', 'admin'], true);
    }
    $canEditAttachments = false;
    if (in_array($jabatan, ['superadmin', 'admin'], true)) {
        $canEditAttachments = true;
    } elseif ($statusInt === 10 || $statusInt === 9) {
        $canEditAttachments = (int) ($data[0]->id_user ?? 0) === (int) auth()->id();
    } elseif ($statusInt === 0 && in_array($jabatan, ['Direktur Operasional', 'superadmin', 'admin'], true)) {
        $canEditAttachments = true;
    } elseif (in_array($jabatan, ['Finance', 'HR', 'HR GA', 'superadmin', 'admin'], true) && in_array($statusInt, [1, 2], true)) {
        $canEditAttachments = true;
    } elseif ($jabatan === 'Finance Supervisor' && in_array($statusInt, [1, 2], true)) {
        $canEditAttachments = true;
    } elseif ($jabatan === 'Owner' && in_array($statusInt, [2, 11], true)) {
        $canEditAttachments = true;
    } elseif ($jabatan === 'Finance Manager' && $statusInt === 11) {
        $canEditAttachments = true;
    }
@endphp
<style>
    #rt-travel-item-pane .rt-step-title { display: flex; align-items: center; gap: 10px; margin: 18px 0 4px; }
    #rt-travel-item-pane .rt-step-badge { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: #28a745; color: #fff; font-weight: 700; font-size: 13px; flex: none; }
    #rt-travel-item-pane .rt-step-title h5 { margin: 0; font-weight: 700; }
    #rt-travel-item-pane .rt-step-desc { color: #6c757d; font-size: 12.5px; margin: 0 0 14px 36px; }
    /* Expense rows stack downward as cards (fields wrap in a grid) instead of one wide row scrolling to the right. */
    #rt-travel-item-pane table.rt-expense-table,
    #rt-travel-item-pane table.rt-expense-table tbody { display: block !important; width: 100% !important; overflow: visible !important; white-space: normal !important; }
    #rt-travel-item-pane table.rt-expense-table thead { display: none; }
    #rt-travel-item-pane table.rt-expense-table tr.fieldGroupDetail { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px 16px; border: 1px solid #e2e5ea; border-radius: 8px; background: #fff; padding: 14px 16px; margin-bottom: 12px; }
    #rt-travel-item-pane table.rt-expense-table tr.fieldGroupDetail > td { display: block; border: 0 !important; padding: 0 !important; min-width: 0; }
    #rt-travel-item-pane table.rt-expense-table tr.fieldGroupDetail > td::before { content: attr(data-label); display: block; font-size: 12px; color: #6c757d; margin-bottom: 4px; }
    #rt-travel-item-pane table.rt-expense-table td select,
    #rt-travel-item-pane table.rt-expense-table td input.form-control { width: 100% !important; }
    #rt-travel-item-pane table.rt-expense-table td.file-proof,
    #rt-travel-item-pane table.rt-expense-table td[data-label="Preview"] { grid-column: span 2; }
    #rt-travel-item-pane table.rt-expense-table td[data-label="Action"] { grid-column: 1 / -1; text-align: right; }
    #rt-travel-item-pane table.rt-expense-table td[data-label="Action"]::before { display: none; }
    @media (max-width: 991px) { #rt-travel-item-pane table.rt-expense-table tr.fieldGroupDetail { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575px) { #rt-travel-item-pane table.rt-expense-table tr.fieldGroupDetail { grid-template-columns: 1fr; } #rt-travel-item-pane table.rt-expense-table td.file-proof, #rt-travel-item-pane table.rt-expense-table td[data-label="Preview"] { grid-column: auto; } }
    #rt-travel-item-pane .rt-expense-locked { pointer-events: none; opacity: .55; }
</style>
<div class="nav-tabs-container">
    <ul class="nav nav-tabs">
        @foreach($data_item as $item)
        <li class="nav-item">
            <div class="travel-tab">
                <button type="button"
                        class="nav-link travel-item-link @if($item->id == Request::segment(4)) active @endif"
                        data-rt-item-url="{!! url('reimbursement-travel/add-item/'.$data['0']->id.'/'.$item->id.'') !!}"
                        data-rt-tab="1"
                        data-travel-id="{{ $item->id }}"><span class="item-1">{{ $item->date ?: (isset($item->created_at) ? date('Y-m-d', strtotime($item->created_at)) : '') }}</span></button>
                @if($canManageTabs)
                <a class="tab-close-link" href="{{ route('reimbursement-travel.delete-item', [$data['0']->id, $item->id]) }}" onclick="return confirm('Hapus tab ini dan semua datanya?')">x</a>
                @endif
            </div>
        </li>
        @endforeach
        @if($canManageTabs)
        <li class="nav-item">
            <a class="nav-link" href="{{ url('reimbursement-travel/add-days/'.$data['0']->id) }}"><i class="fa fa-plus"></i> &nbsp;Add New Item</a>
        </li>
        @endif
    </ul>
</div>
<div class="rt-step-title"><span class="rt-step-badge">1</span><h5>Request Information</h5></div>
<p class="rt-step-desc">Please complete the additional information below.</p>
<div class="row">
    <input type="hidden" name="active_travel_id" value="{{ $data_travel['0']->id }}">
    <div class="col-md-3">
        <label for="">Transaction Date</label>
        <input type="date" name="date" class="form-control" required value="{{$data_travel['0']->date}}">
    </div>
    <div class="col-md-3">
        <label for="">Purpose</label>
        <input type="text" name="purpose" class="form-control" required value="{{$data_travel['0']->purpose}}">
    </div>
    <div class="col-md-3">
        <label for="">Trip Type</label>
        <select id="trip_type_id" class="form-control change-type" name="trip_type_id">
            <option value="">None</option>
            @foreach ($trip_types as $item)
                <option value="{{$item->id}}" @if($item->id == $data_travel['0']->trip_type_id) selected @endif>{!!$item->name!!}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label for="">Hotel </label>
        <select id="hotel_condition_id" class="form-control" name="hotel_condition_id" required>
            <option value="" selected disabled>Select...</option>
            @foreach ($hotel_conditions as $item)
                <option value="{{$item->id}}" @if($item->id == $data_travel['0']->hotel_condition_id) selected @endif>{{$item->name}}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label for="">Start</label>
        <input type="time" class="form-control" name="start_time" value="{{$data_travel['0']->start_time}}" id="start_time" required>
    </div>

    <div class="col-md-3">
        <label for="">Arrival</label>
        <input type="time" class="form-control" name="end_time" value="{{$data_travel['0']->end_time}}" id="end_time" required>
    </div>

    <div class="col-md-3">
        <label for="">Original Allowance</label>
        <input type="text" class="form-control number-format allowance change-rate currency" name="allowance" value="{{ rt_travel_pane_rupiah($data_travel['0']->allowance) }}" readonly required>
    </div>
    <?php
        $start = strtotime($data_travel['0']->start_time);
        $end = strtotime($data_travel['0']->end_time);
        $minutes = ($end - $start) / 60;
        $hours = floor($minutes / 60).' Hour and '.($minutes - floor($minutes / 60) * 60).' Minutes';
    ?>

    <div class="col-md-3">
        <label for="">Travel Times</label>
        <input type="text" readonly class="form-control" value="{{$hours}}" id="result_time">
    </div>
</div>
<hr>
<div class="rt-step-title"><span class="rt-step-badge">2</span><h5>Expense Details</h5></div>
<p class="rt-step-desc">Add expense details based on the uploaded proof or add manually. Proof is uploaded per row.</p>
@if($rtDayLocked)
<div class="alert alert-secondary" style="font-size:12px;padding:8px 12px;"><i class="fa fa-lock"></i> Expense Details are locked: this day only claims the Travel Allowance (it uses another claim's document), so no expense is reimbursed.</div>
@endif
<div class="row">
    <div class="col-xl @if($rtDayLocked) rt-expense-locked @endif">
        <table class="table full-width rt-expense-table" style="width: 100%;">
            <thead style="width: 100%">
                <tr>
                    <th width="200">Cost Type</th>
                    <th width="200">Destination</th>
                    <th width="200">Remarks</th>
                    <th width="200">Currency</th>
                    <th width="200">Amount</th>
                    <th width="200">IDR Rate</th>
                    <th width="200">Pph23</th>
                    <th width="200">Payment</th>
                    <th width="200">File</th>
                    <th width="200">Preview</th>
                    <th width="200">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="fieldGroupDetail" data-row-index="0" {!! !empty($rtRow0->reference_reimbursement_id ?? null) ? 'data-reference-status="found"' : '' !!}>
                    <td data-label="Cost Type">
                        <input type="hidden" name="id_detail[]" value="{{ $rtRow0->id }}">
                        <select class="form-control cost_type_id0 cost-type-select" name="cost_type_id[]">
                            <option value="">Select...</option>
                            @foreach ($types as $item)
                                <option value="{{$item->id}}" @if($rtRow0->cost_type_id == $item->id) selected @endif>{{$item->name}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td data-label="Destination">
                        <input type="text" class="form-control destination-input" name="destination[]" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="{{ $rtRow0->destination }}">
                    </td>
                    <td data-label="Remarks">
                        <input type="text" class="form-control remarks-input" name="remarks[]" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="e.g. Hotel for 18-20 Aug 2026" value="{{ $rtRow0->remarks ?? '' }}">
                    </td>
                    <td data-label="Currency">
                        <select class="form-control currency0 currency-select" name="currency[]" style="width:130%">
                            <option value="">Select...</option>
                            @foreach ($currency as $item)
                                <option value="{{$item->currency}}" @if($item->currency == $rtRow0->currency) selected @endif>{{$item->currency}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td data-label="Amount">
                        <input type="text" class="form-control currency amount0 change-amount" value="{{ rt_travel_pane_amount_int($rtRow0->amount) }}" name="amount[]">
                    </td>
                    <td data-label="IDR Rate">
                        <input type="text" class="form-control currency number-format idr_rate_main change-rate idr-rate-input" value="{{ rt_travel_pane_rupiah($rtRow0->idr_rate) }}" name="idr_rate[]" readonly>
                    </td>
                    <td data-label="Pph23">
                        <input type="text" class="form-control currency number-format tax0{{ $taxFirstExtra }}" readonly value="{{ rt_travel_pane_rupiah($rtRow0->tax) }}" name="tax[]">
                    </td>
                    <td data-label="Payment">
                        <select class="form-control payment-select" name="payment_type[]" style="width:130%">
                            <option value="">Select...</option>
                            <option value="BDC" @if($rtRow0->payment_type=='BDC') selected @endif>BDC</option>
                            <option value="Cash" @if($rtRow0->payment_type=='Cash') selected @endif>Cash</option>
                        </select>
                    </td>
                    <td class="file-proof" data-label="File">
                        <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                            <i class="fa fa-upload"></i>
                        </button>
                        <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                            <i class="fa fa-camera"></i>
                        </button>
                        <input type="file" accept="image/*,.pdf,application/pdf" name="file[]" style="display: none;" class="file-input file1">
                        <input type="file" accept="image/*,.pdf,application/pdf" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                        <div class="reference-invoice-wrap" style="display:none;">
                            <input type="text" class="form-control form-control-sm reference-invoice-input" name="reference_invoice[]" placeholder="atau No. Invoice rekan" autocomplete="off" value="{{ !empty($rtRow0->reference_reimbursement_id ?? null) ? $rtRow0->no_invoice : '' }}">
                            <div class="reference-invoice-feedback" style="font-size:11px; margin-top:2px;"></div>
                        </div>
                    </td>
                    <td data-label="Preview">
                        @php
                            $attachments = rt_travel_detail_attachments(
                                $rtRow0->id ?? 0,
                                $rtRow0->evidence ?? '',
                                $data[0]->id ?? 0,
                                $rtRow0->destination ?? '',
                                $rtRow0->cost_type_id ?? 0
                            );
                            rt_travel_pane_render_attachments($attachments, 0, $canEditAttachments, 'preview_1');
                        @endphp
                    </td>
                    <td data-label="Action">
                        <button type="button" class="btn btn-info addMoreDetail"><i class="fa fa-plus"></i></button>
                        <button type="button" class="btn btn-danger remove-detail" style="margin-left:6px;"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>

                @foreach ($travel_detail as $key => $row)
                @if($key > 0)
                <?php $n = $key + 1;?>
                <tr class="fieldGroupDetail" data-row-index="{{ (int) $key }}" {!! !empty($row->reference_reimbursement_id ?? null) ? 'data-reference-status="found"' : '' !!}>
                    <td data-label="Cost Type">
                        <input type="hidden" name="id_detail[]" value="{{$row->id}}">
                        <select class="form-control cost_type_id{{$key}} cost-type-select" name="cost_type_id[]">
                            <option value="">Select...</option>
                            @foreach ($types as $item)
                                <option value="{{$item->id}}" @if($row->cost_type_id == $item->id) selected @endif>{{$item->name}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td data-label="Destination">
                        <input type="text" class="form-control destination-input" name="destination[]" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="{{$row->destination}}">
                    </td>
                    <td data-label="Remarks">
                        <input type="text" class="form-control remarks-input" name="remarks[]" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="e.g. Hotel for 18-20 Aug 2026" value="{{$row->remarks ?? ''}}">
                    </td>
                    <td data-label="Currency">
                        <select class="form-control currency{{$key}} currency-select" name="currency[]" style="width:130%">
                            <option value="">Select...</option>
                            @foreach ($currency as $item)
                                <option value="{{$item->currency}}" @if($item->currency == $row->currency) selected @endif>{{$item->currency}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td data-label="Amount">
                        <input type="text" class="form-control amount{{$key}} amount-input currency change-amount" value="{{ rt_travel_pane_amount_int($row->amount) }}" name="amount[]">
                    </td>
                    <td data-label="IDR Rate">
                        <input type="text" class="form-control number-format currency idr_rate_{{$key}} change-rate idr-rate-input" value="{{ rt_travel_pane_rupiah($row->idr_rate) }}" name="idr_rate[]" readonly>
                    </td>
                    <td data-label="Pph23">
                        <input type="text" class="form-control number-format currency tax{{$key}}{{ $taxFirstExtra }} tax-input" readonly value="{{ rt_travel_pane_rupiah($row->tax) }}" name="tax[]">
                    </td>
                    <td data-label="Payment">
                        <select class="form-control payment-select" name="payment_type[]" style="width:130%">
                            <option value="">Select...</option>
                            <option value="BDC" @if($row->payment_type=='BDC') selected @endif>BDC</option>
                            <option value="Cash" @if($row->payment_type=='Cash') selected @endif>Cash</option>
                        </select>
                    </td>
                    <td class="file-proof" data-label="File">
                        <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                            <i class="fa fa-upload"></i>
                        </button>
                        <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                            <i class="fa fa-camera"></i>
                        </button>
                        <input type="file" accept="image/*,.pdf,application/pdf" name="file[]" style="display: none;" class="file-input file1">
                        <input type="file" accept="image/*,.pdf,application/pdf" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                        <div class="reference-invoice-wrap" style="display:none;">
                            <input type="text" class="form-control form-control-sm reference-invoice-input" name="reference_invoice[]" placeholder="atau No. Invoice rekan" autocomplete="off" value="{{ !empty($row->reference_reimbursement_id ?? null) ? $row->no_invoice : '' }}">
                            <div class="reference-invoice-feedback" style="font-size:11px; margin-top:2px;"></div>
                        </div>
                    </td>
                    <td data-label="Preview">
                        @php
                            $attachments = rt_travel_detail_attachments(
                                $row->id ?? 0,
                                $row->evidence ?? '',
                                $data[0]->id ?? 0,
                                $row->destination ?? '',
                                $row->cost_type_id ?? 0
                            );
                            rt_travel_pane_render_attachments($attachments, (int) $key, $canEditAttachments, 'preview_' . $n);
                        @endphp
                    </td>
                    <td data-label="Action">
                        <button type="button" class="btn btn-danger remove-detail"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script type="text/template" id="rt-detail-row-template">
<tr class="fieldGroupDetail" data-row-index="__IDX__">
    <td data-label="Cost Type">
        <input type="hidden" name="id_detail[]" value="">
        <select class="form-control cost_type_id__IDX__ cost-type-select" name="cost_type_id[]">
            <option value="">Select...</option>
            @foreach ($types as $item)
                <option value="{{$item->id}}">{{$item->name}}</option>
            @endforeach
        </select>
    </td>
    <td data-label="Destination">
        <input type="text" class="form-control destination-input" name="destination[]" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="">
    </td>
    <td data-label="Remarks">
        <input type="text" class="form-control remarks-input" name="remarks[]" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="e.g. Hotel for 18-20 Aug 2026" value="">
    </td>
    <td data-label="Currency">
        <select class="form-control currency__IDX__ currency-select" name="currency[]" style="width:130%">
            <option value="">Select...</option>
            @foreach ($currency as $item)
                <option value="{{$item->currency}}">{{$item->currency}}</option>
            @endforeach
        </select>
    </td>
    <td data-label="Amount">
        <input type="text" class="form-control amount__IDX__ amount-input currency change-amount" name="amount[]" value="">
    </td>
    <td data-label="IDR Rate">
        <input type="text" class="form-control number-format currency idr_rate___IDX__ change-rate idr-rate-input" name="idr_rate[]" readonly value="">
    </td>
    <td data-label="Pph23">
        <input type="text" class="form-control number-format currency tax__IDX__{{ $taxFirstExtra }} tax-input" readonly name="tax[]" value="">
    </td>
    <td data-label="Payment">
        <select class="form-control payment-select" name="payment_type[]" style="width:130%">
            <option value="">Select...</option>
            <option value="BDC">BDC</option>
            <option value="Cash">Cash</option>
        </select>
    </td>
    <td class="file-proof" data-label="File">
        <button type="button" data-idx="__IDX__" class="btn btn-success btn-sm addFile">
            <i class="fa fa-upload"></i>
        </button>
        <button type="button" data-idx="__IDX__" class="btn btn-success btn-sm addCamera">
            <i class="fa fa-camera"></i>
        </button>
        <input type="file" accept="image/*,.pdf,application/pdf" name="file[]" style="display: none;" class="file-input file__IDX__">
        <input type="file" accept="image/*,.pdf,application/pdf" name="proof[]" capture="camera" class="camera-input" style="display: none;">
        <div class="reference-invoice-wrap" style="display:none;">
            <input type="text" class="form-control form-control-sm reference-invoice-input" name="reference_invoice[]" placeholder="atau No. Invoice rekan" autocomplete="off" value="">
            <div class="reference-invoice-feedback" style="font-size:11px; margin-top:2px;"></div>
        </div>
    </td>
    <td data-label="Preview">
        <div id="preview___PREVIEW__"></div>
    </td>
    <td data-label="Action">
        <button type="button" class="btn btn-danger remove-detail"><i class="fa fa-trash"></i></button>
    </td>
</tr>
</script>

<hr>
<div class="row">
    <div class="col-md-3">
        <label for="">Total</label>
        <input type="text" readonly class="form-control total-nominal" name="nominal_pengajuan" value="{{ rt_travel_pane_rupiah($rtDayTotal) }}">
    </div>
    <div class="col-md-9">
        <br><span style="color:#62d49e; float: right; display: none;" class="warning-upload">
        The button is disabled until a file is uploaded.</span>
    </div>
</div>

<div class="button-container">
    <a class="btn btn-secondary text-right js-rt-discard-edit" data-rt-main-id="{{ $data['0']->id }}" href="{!!url('reimbursement-travel/'.Request::segment(3).'')!!}"><i class="fa fa-back"></i>Cancel</a>&nbsp;
    @if($data['0']->status==0)
        <button class="btn btn-warning" type="submit" id="action_button" name="save">Update</button>&nbsp;
    @endif
    @if($data['0']->status==9)
        <button class="btn btn-warning" type="submit" id="action_button_draft" name="save_draft" formnovalidate>Draft</button>&nbsp;
        <button class="btn btn-primary" type="submit" id="action_button_submit" name="save_again">Submit</button>
    @endif

    @if($data['0']->status==10)
        <button class="btn btn-warning" type="submit" id="action_button_draft" name="save_draft" formnovalidate>Draft</button>&nbsp;
        <button class="btn btn-primary" type="submit" id="action_button" name="save">Submit</button>
    @endif

    @if(
        ((auth()->user()->jabatan == 'Finance' || auth()->user()->jabatan == 'HR' || auth()->user()->jabatan == 'HR GA') && in_array((int) $data['0']->status, [1, 2], true))
        || (auth()->user()->jabatan == 'Finance Supervisor' && in_array((int) $data['0']->status, [1, 2], true))
    )
        <button class="btn btn-warning" type="submit" id="edit_finance" name="edit_finance">Update</button>&nbsp;
    @endif

    @if((auth()->user()->jabatan == 'Owner' && in_array((int) $data['0']->status, [2, 11], true))
        || (auth()->user()->jabatan == 'Finance Manager' && (int) $data['0']->status === 11))
        <button class="btn btn-warning" type="submit" id="edit_owner" name="edit_owner">Update</button>&nbsp;
    @endif
</div>
