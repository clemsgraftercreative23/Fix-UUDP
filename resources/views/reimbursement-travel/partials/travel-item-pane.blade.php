@php
if (!function_exists('rt_travel_pane_rupiah')) {
    function rt_travel_pane_rupiah($angka)
    {
        return number_format((float) $angka, 0, ',', '.');
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
        'currency' => '',
        'amount' => 0,
        'idr_rate' => 0,
        'tax' => 0,
        'payment_type' => '',
        'evidence' => '',
    ];
@endphp
<div class="nav-tabs-container">
    <ul class="nav nav-tabs">
        @foreach($data_item as $item)
        <li class="nav-item">
            <div class="travel-tab">
                <button type="button"
                        class="nav-link travel-item-link @if($item->id == Request::segment(4)) active @endif"
                        data-rt-item-url="{!! url('reimbursement-travel/add-item/'.$data['0']->id.'/'.$item->id.'') !!}"
                        data-rt-tab="1"
                        data-travel-id="{{ $item->id }}"><span class="item-1">{{$item->date}}</span></button>
                @if($data['0']->status == 10)
                <a class="tab-close-link" href="{{ route('reimbursement-travel.delete-item', [$data['0']->id, $item->id]) }}" onclick="return confirm('Hapus tab ini dan semua datanya?')">x</a>
                @endif
            </div>
        </li>
        @endforeach
        @if($data['0']->status==10)
        <li class="nav-item">
            <button type="submit" class="nav-link" name="save_item" id="action_button_item"><i class="fa fa-plus"></i> &nbsp;Add New Item</button>
        </li>
        @endif
    </ul>
</div><hr>
<div class="row">
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
            <option value="" selected disabled>Pilih...</option>
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
<div class="row">
    <div class="col-xl">
        <table class="table full-width" style="width: 100%;overflow-x: auto;white-space: nowrap;display:block">
            <thead style="width: 100%">
                <tr>
                    <th width="200">Cost Type</th>
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
                <tr class="fieldGroupDetail">
                    <td>
                        <input type="hidden" name="id_detail[]" value="{{ $rtRow0->id }}">
                        <select class="form-control cost_type_id0 cost-type-select" name="cost_type_id[]">
                            <option value="">Select...</option>
                            @foreach ($types as $item)
                                <option value="{{$item->id}}" @if($rtRow0->cost_type_id == $item->id) selected @endif>{{$item->name}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control destination-input" name="destination[]" value="{{ $rtRow0->destination }}">
                    </td>
                    <td>
                        <select class="form-control currency0 currency-select" name="currency[]" style="width:130%">
                            <option value="">Select...</option>
                            @foreach ($currency as $item)
                                <option value="{{$item->currency}}" @if($item->currency == $rtRow0->currency) selected @endif>{{$item->currency}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control currency amount0 change-amount" value="{{ rt_travel_pane_rupiah($rtRow0->amount) }}" name="amount[]">
                    </td>
                    <td>
                        <input type="text" class="form-control currency number-format idr_rate_main change-rate idr-rate-input" value="{{ rt_travel_pane_rupiah($rtRow0->idr_rate) }}" name="idr_rate[]" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control currency number-format tax0{{ $taxFirstExtra }}" readonly value="{{ rt_travel_pane_rupiah($rtRow0->tax) }}" name="tax[]">
                    </td>
                    <td>
                        <select class="form-control payment-select" name="payment_type[]" style="width:130%">
                            <option value="">Select...</option>
                            <option value="BDC" @if($rtRow0->payment_type=='BDC') selected @endif>BDC</option>
                            <option value="Cash" @if($rtRow0->payment_type=='Cash') selected @endif>Cash</option>
                        </select>
                    </td>
                    <td class="file-proof">
                        <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                            <i class="fa fa-upload"></i>
                        </button>
                        <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                            <i class="fa fa-camera"></i>
                        </button>
                        <input type="file" accept="image/*" name="file[]" style="display: none;" class="file-input file1">
                        <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                    </td>
                    <td>
                        @php
                            $file = $rtRow0->evidence ?? '';
                            $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        @endphp

                        <div id="preview_1">
                            @if($file !== '' && in_array($ext, $imageExt))
                                <img src="{{ url('images/file_bukti/'.$file) }}"
                                     style="max-width:75px; max-height:75px; border:2px solid #28a745; border-radius:5px; margin-top:5px;">
                            @elseif($file !== '')
                                <a href="{{ url('images/file_bukti/'.$file) }}" target="_blank">
                                    <img src="https://cdn-icons-png.flaticon.com/512/337/337946.png"
                                         style="max-width:75px; max-height:75px; border:2px solid #dc3545; border-radius:5px; margin-top:5px;">
                                </a>
                            @endif
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-info addMoreDetail"><i class="fa fa-plus"></i></button>
                    </td>
                </tr>

                @foreach ($travel_detail as $key => $row)
                @if($key > 0)
                <?php $n = $key + 1;?>
                <tr class="fieldGroupDetail">
                    <td>
                        <input type="hidden" name="id_detail[]" value="{{$row->id}}">
                        <select class="form-control cost_type_id{{$key}}" name="cost_type_id[]">
                            <option value="">Select...</option>
                            @foreach ($types as $item)
                                <option value="{{$item->id}}" @if($row->cost_type_id == $item->id) selected @endif>{{$item->name}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="destination[]" value="{{$row->destination}}">
                    </td>
                    <td>
                        <select class="form-control currency{{$key}} currency-select" name="currency[]" style="width:130%">
                            <option value="">Select...</option>
                            @foreach ($currency as $item)
                                <option value="{{$item->currency}}" @if($item->currency == $row->currency) selected @endif>{{$item->currency}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control amount{{$key}} currency change-amount" value="{{ rt_travel_pane_rupiah($row->amount) }}" name="amount[]">
                    </td>
                    <td>
                        <input type="text" class="form-control number-format currency idr_rate_{{$key}} change-rate" value="{{ rt_travel_pane_rupiah($row->idr_rate) }}" name="idr_rate[]" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control number-format currency tax{{$key}}" readonly value="{{ rt_travel_pane_rupiah($row->tax) }}" name="tax[]">
                    </td>
                    <td>
                        <select class="form-control" name="payment_type[]" style="width:130%">
                            <option value="">Select...</option>
                            <option value="BDC" @if($row->payment_type=='BDC') selected @endif>BDC</option>
                            <option value="Cash" @if($row->payment_type=='Cash') selected @endif>Cash</option>
                        </select>
                    </td>
                    <td class="file-proof">
                        <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                            <i class="fa fa-upload"></i>
                        </button>
                        <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                            <i class="fa fa-camera"></i>
                        </button>
                        <input type="file" accept="image/*" name="file[]" style="display: none;" class="file-input file1">
                        <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                    </td>
                    <td>
                        @php
                            $file = $row->evidence ?? '';
                            $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        @endphp

                        <div id="preview_{{$n}}">
                            @if(in_array($ext, $imageExt))
                                <img src="{{ url('images/file_bukti/'.$file) }}"
                                     style="max-width:75px; max-height:75px; border:2px solid #28a745; border-radius:5px; margin-top:5px;">
                            @else
                                <a href="{{ url('images/file_bukti/'.$file) }}" target="_blank">
                                    <img src="https://cdn-icons-png.flaticon.com/512/337/337946.png"
                                         style="max-width:75px; max-height:75px; border:2px solid #dc3545; border-radius:5px; margin-top:5px;">
                                </a>
                            @endif
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-detail"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<hr>
<div class="row">
    <div class="col-md-3">
        <label for="">Total</label>
        <input type="text" readonly class="form-control total-nominal" name="nominal_pengajuan" value="{{ rt_travel_pane_rupiah($data_travel['0']->total) }}">
    </div>
    <div class="col-md-9">
        <br><span style="color:#62d49e; float: right;" class="warning-upload">
        The button is disabled until a file is uploaded.</span>
    </div>
</div>

<div class="button-container">
    <a class="btn btn-secondary text-right" href="{!!url('reimbursement-travel/'.Request::segment(3).'')!!}"><i class="fa fa-back"></i>Cancel</a>&nbsp;
    @if($data['0']->status==0)
        <button class="btn btn-warning" type="submit" id="action_button" name="save">Update</button>&nbsp;
    @endif
    @if($data['0']->status==9)
        <button class="btn btn-warning" type="submit" id="action_button" name="save">Update</button>&nbsp;
        <button class="btn btn-primary" type="submit" id="action_button_submit" name="save_again">Submit</button>
    @endif

    @if($data['0']->status==10)
        <button class="btn btn-warning" type="submit" id="action_button_draft" name="save_draft">Draft</button>&nbsp;
        <button class="btn btn-primary" type="submit" id="action_button" name="save">Submit</button>
    @endif

    @if(auth()->user()->jabatan == 'Finance' && $data['0']->status==1)
        <button class="btn btn-warning" type="submit" id="edit_finance" name="edit_finance">Update</button>&nbsp;
    @endif

    @if(auth()->user()->jabatan == 'Owner' && $data['0']->status==2)
        <button class="btn btn-warning" type="submit" id="edit_owner" name="edit_owner">Update</button>&nbsp;
    @endif
</div>
