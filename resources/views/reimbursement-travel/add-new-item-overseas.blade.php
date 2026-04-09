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
        background-color: #e8e8e8;
        display: block;
        padding: 10px 15px;
        white-space: nowrap; /* Pastikan teks tidak terpotong */
    }
    .travel-tab {
        display: flex;
        align-items: center;
    }
    .tab-close-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        margin-left: 6px;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        line-height: 1;
        flex-shrink: 0;
    }
    .tab-close-link:hover {
        color: #fff;
        text-decoration: none;
        opacity: 0.85;
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
    #preview_1 {
        maxWidth: '75px';
        maxHeight: '75px';
        border: '2px solid #28a745';
        borderRadius: '5px';
        marginTop: '5px';
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
      .tax-input {
        width: 150px !important;
      }

      .payment-select {
        width: 80px !important;
      }
    }
    

</style>

<?php 
function rupiah($angka){
    
    $hasil_rupiah = number_format($angka,0,',','.');
    return $hasil_rupiah;
 
}
?>

<div class="page-content" id="app">
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
<div class="">
  
    <form action="{!!url('reimbursement-travel/save-item/'.Request::segment(3).'')!!}" method="POST" enctype="multipart/form-data" style="overflow-y: auto;">
        @csrf 
        <div class="row">
            <div class="col-xl">
                <div class="card">
                    <div class="card-body">
                                    <select :name="'reimburse['+i+'][hotel_condition_id]'" id="" class="form-control" v-model="data.hotel_condition" :disabled="!data.trip">
                        <div class="d-flex justify-content-between w-100"><h2 id="exampleModalCenterTitle" class="modal-title maintitle clr-green mb-0">REIMBURSEMENT UUDP - TRAVEL {{strtoupper($travel_type)}}</h2> 
                        <a href="{!!url('reimbursement-travel')!!}" aria-label="Close" class="close"><i class="material-icons">close</i></a></div>
                        <hr>
                        
                        <div class="row">
                            <input type="hidden" name="travel_type" value="{{$travel_type}}">
                            <div class="col-md-3">
                                <label for="">Employee</label>
                                    <input type="time" :name="'reimburse['+i+'][start_time]'" @change="changeTime(i)" v-model="data.start_time" class="form-control" value="" :disabled="!data.trip" />
                                <input type="hidden" class="form-control" name="id_editor" value="{{auth()->user()->id}}">
                                <input type="hidden" class="form-control" name="id_user" value="{{$data['0']->id_user}}">
                            </div>
                            <div class="col-md-3">
                                    <input type="time" :name="'reimburse['+i+'][end_time]'" @change="changeTime(i)" v-model="data.end_time" class="form-control" value="" :disabled="!data.trip" />
                                <input type="text" class="form-control" name="remark" value="{{ date('d F Y', strtotime($data['0']->created_at)) }}" readonly>
                            </div> 
                            <div class="col-md-3">
                                <label for="">Remark</label>
                                <input type="text" class="form-control" name="remark" value="{{$data['0']->remark}}">
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
                            @if ($data['0']->status == 9)
                            <div class="col-md-3">
                                <label for="inputPassword4">Status</label>
                                <input type="text" class="form-control" value="Rejected" readonly >
                            </div>
                            <div class="col-md-3">
                                <label for="inputPassword4">Reject Reason</label>
                                <input type="text" class="form-control" value="{{$data['0']->reject_reason}}" readonly >
                            </div>
                            @endif
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row fieldGroup">
                                    <input type="hidden" name="id_rate" class="id_rate" value="{{$travel_trip['0']->id}}">
                                    <div class="col-md-3">
                                        <label for="">Currency</label>
                                        <input type="text" class="form-control" name="currency_rate[]" value="{{$travel_trip['0']->currency}}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="">Exchange Rate</label>
                                        <input type="text" class="form-control currency" name="rate[]" value="{{rupiah($travel_trip['0']->rate)}}">
                                    </div>
                                    <div class="col-md-3">
                                        <a class="btn btn-primary btn-sm addMore" style="color:white;margin-top:35px;cursor:pointer"><i class="fa fa-plus"></i></a>
                                    </div>
                                </div>
                                @foreach($travel_trip as $key => $row)
                                    @if($key > 0)
                                    <div class="row fieldGroup">
                                        <input type="hidden" name="id_rate" class="id_rate" value="{{$row->id}}">
                                        <div class="col-md-3">
                                            <label for="">Currency</label>
                                            <input type="text" class="form-control" name="currency_rate[]" value="{{$row->currency}}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="">Exchange Rate</label>
                                            <input type="text" class="form-control currency" name="rate[]" value="{{rupiah($row->rate)}}">
                                        </div>
                                        <div class="col-md-3">
                                            <a class="btn btn-danger btn-sm remove-currency" style="color:white;margin-top:35px;cursor:pointer;background:#f05154"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>                 
                        </div>
                        <br>
                        <hr>
                       
                    </div>
                </div>
            </div>
        </div>
         
        <div class="row" v-for="(data,i) in reimburses">
            <div class="col-xl">
                <div class="card">
                    <div class="card-body">
                        <div class="nav-tabs-container">
                            <ul class="nav nav-tabs">
                                @foreach($data_item as $item)
                                <li class="nav-item">
                                    <div class="travel-tab">
                                        <a class="nav-link" href="{!! url('reimbursement-travel/add-item/'.$data['0']->id.'/'.$item->id.'') !!}"><span class="item-1">{{$item->date}}</span></a>
                                        @if($data['0']->status == 10)
                                        <a class="tab-close-link" href="{{ route('reimbursement-travel.delete-item', [$data['0']->id, $item->id]) }}" onclick="return confirm('Hapus tab ini dan semua datanya?')">x</a>
                                        @endif
                                    </div>
                                </li>
                                @endforeach
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#reimburse-form"><span class="item-new">New Item</span></a>
                                </li>
                                <li class="nav-item">
                                    <button type="submit" class="nav-link" name="save_item" id="action_button_item"><i class="fa fa-plus"></i> &nbsp;Add New Item</button>
                                </li>
                                <!-- <li class="nav-item">
                                    <a class="nav-link" href="{!! url('reimbursement-travel/add-item/'.$data['0']->id.'') !!}"><i class="fa fa-plus"></i> &nbsp;Add New Item</a>
                                </li> -->
                            </ul>
                        </div><hr>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="">Transaction Date</label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label for="">Purpose</label>
                                <input type="text" name="purpose" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label for="">Trip Type</label>
                                <select id="trip_type_id" class="form-control change-type" name="trip_type_id">
                                    <option value="">None</option>
                                    @foreach ($trip_types as $item)
                                        <option value="{{$item->id}}">{!!$item->name!!}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="">Hotel </label>
                                <select id="hotel_condition_id" class="form-control" name="hotel_condition_id" required>
                                    <option value="" selected disabled>Pilih...</option>
                                    @foreach ($hotel_conditions as $item)
                                        <option value="{{$item->id}}">{{$item->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="">Start</label>
                                <input type="time" class="form-control" name="start_time" id="start_time" required>
                            </div>  
                            
                            <div class="col-md-3">
                                <label for="">Arrival</label>
                                <input type="time" class="form-control" name="end_time" id="end_time" required>
                            </div>    
                            
                            <div class="col-md-3">
                                <label for="">Original Allowance</label>
                                <input type="text" class="form-control number-format allowance change-rate currency" name="allowance" id="usd-allowance" readonly required>
                            </div>  
                            <div class="col-md-3">
                                <label for="">Travel Times</label>
                                <input type="text" readonly class="form-control" id="result_time">
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
                                                <input type="hidden" name="id_detail[]" value="{{$travel_detail['0']->id}}">
                                                <select class="form-control cost_type_id0 cost-type-select" name="cost_type_id[]">
                                                    <option value="">Select...</option>
                                                    @foreach ($types as $item)
                                                        <option value="{{$item->id}}">{{$item->name}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control destination-input" name="destination[]">
                                            </td>
                                            <td>
                                                <select class="form-control currency0 currency-select" name="currency[]" style="width:130%">
                                                    <option value="">Select...</option>
                                                    @foreach ($currency as $item)
                                                        <option value="{{$item->currency}}">{{$item->currency}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control currency amount0 change-amount amount-input" name="amount[]">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control currency number-format idr_rate_main change-rate idr-rate-input" name="idr_rate[]" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control currency number-format tax0 tax-input" readonly name="tax[]">
                                            </td>
                                            <td>
                                                <select class="form-control payment-select" name="payment_type[]" style="width:130%">
                                                    <option value="">Select...</option>
                                                    <option value="BDC">BDC</option>
                                                    <option value="Cash">Cash</option>
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
                                                <div id="preview_1">
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-info addMoreDetail"><i class="fa fa-plus"></i></button>
                                            </td>                                                                         
                                        </tr>
                                        
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="">Total</label>
                                <input type="text" readonly class="form-control total-nominal" name="nominal_pengajuan">
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
                          
                            
                            
                        </div>
                       
                    </div>
                </div>
            </div>
        </div>
        <br>
        
        
    </form>

</div>
</div>

<!-- End Modal -->

<!-- Modal -->
<div class="modal fade" id="modalPhoto"  data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Upload Image</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <i class="material-icons">close</i>
              </button>
          </div>
          <div class="modal-body">
            <video id="videoElement" autoplay style="width: 100%"></video>
            <canvas id="canvas"></canvas>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button id="captureButton" class="btn btn-success">Capture Image</button>
            </div>
      </div>
  </div>
  </div>

<!-- End Modal -->


@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.13.4/jquery.mask.min.js"></script>

<script type="text/javascript">
$(document).ready(function(){
    @if(Auth::user()->status_password != 1)
        $('#modalPassword').modal('show');
    @endif

    $("#action_button").prop("disabled", true);
    $("#action_button_draft").prop("disabled", true);
    $(".warning-upload").show();
    
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?<!\.\d*)(?=(\d{3})+(?!\d))/g, ".");
    }
  
   

    function calculateTimeDifference() {
        let start = $("#start_time").val();
        let end = $("#end_time").val();

        if (start && end) {
            let startTime = start.split(":");
            let endTime = end.split(":");

            let startHour = parseInt(startTime[0]);
            let startMinute = parseInt(startTime[1]);

            let endHour = parseInt(endTime[0]);
            let endMinute = parseInt(endTime[1]);

            // Konversi waktu ke menit total
            let startTotalMinutes = startHour * 60 + startMinute;
            let endTotalMinutes = endHour * 60 + endMinute;

            // Jika waktu akhir lebih kecil, anggap keesokan harinya
            if (endTotalMinutes < startTotalMinutes) {
                endTotalMinutes += 24 * 60;
            }

            let diffMinutes = endTotalMinutes - startTotalMinutes;
            let hours = Math.floor(diffMinutes / 60);
            let minutes = diffMinutes % 60;

            let res = $("#result_time").val(hours + " Hour " + minutes + " Minute");
            
        }
    }

    // Event listener untuk setiap perubahan input time
    $("#start_time, #end_time").on("input", function() {
        calculateTimeDifference();
    });

    calculateTimeDifference();
    
    function total_nominal() {
        
        var allowance = $('.allowance').val().split(".").join("");
        
        var idr_main = $('.idr_rate_main').val().split(".").join("");
        
        if ($('.idr_rate_1').val()) {
            var idr_1 = $('.idr_rate_1').val().split(".").join("");    
        } else {
            var idr_1 = 0;
        }
        
        if ($('.idr_rate_2').val()) {
            var idr_2 = $('.idr_rate_2').val().split(".").join("");    
        } else {
            var idr_2 = 0;
        }
        
        if ($('.idr_rate_3').val()) {
            var idr_3 = $('.idr_rate_3').val().split(".").join("");    
        } else {
            var idr_3 = 0;
        }
        
        if ($('.idr_rate_4').val()) {
            var idr_4 = $('.idr_rate_4').val().split(".").join("");    
        } else {
            var idr_4 = 0;
        }
        
        if ($('.idr_rate_5').val()) {
            var idr_5 = $('.idr_rate_5').val().split(".").join("");    
        } else {
            var idr_5 = 0;
        }
        
        if ($('.idr_rate_6').val()) {
            var idr_6 = $('.idr_rate_6').val().split(".").join("");    
        } else {
            var idr_6 = 0;
        }
        
        if ($('.idr_rate_7').val()) {
            var idr_7 = $('.idr_rate_7').val().split(".").join("");    
        } else {
            var idr_7 = 0;
        }
        
        if ($('.idr_rate_8').val()) {
            var idr_8 = $('.idr_rate_8').val().split(".").join("");    
        } else {
            var idr_8 = 0;
        }
        
        if ($('.idr_rate_9').val()) {
            var idr_9 = $('.idr_rate_9').val().split(".").join("");    
        } else {
            var idr_9 = 0;
        }
        
        if ($('.idr_rate_10').val()) {
            var idr_10 = $('.idr_rate_10').val().split(".").join("");    
        } else {
            var idr_10 = 0;
        }
      
    
        var total_append = +allowance + +idr_main + +idr_1 + +idr_2 + +idr_3 + +idr_4 + +idr_5 + +idr_6 + +idr_7 + +idr_8 + +idr_9 + +idr_10; 
        
        $('.total-nominal').val(numberWithCommas(total_append));
    }
    
    //$("#trip_type_id").change(function(){
    //    id = $('#trip_type_id').val();
    //    let usd_rate = $('input[name="rate[]"]').eq(1).val().replace(/\./g, '');
    //    $.ajax({
    //        url:"../../../get-trip-type/"+id,
    //        dataType:"json",
    //        success:function(data){
    //            val = data.data * usd_rate;
    //            $('.allowance ').val(numberWithCommas(val));
    //            total_nominal();
    //        }
    //    })
    // });
  
    $("#trip_type_id").change(function(){
        id = $('#trip_type_id').val();
        if (!id) {
            $('.allowance ').val(numberWithCommas(0));
            total_nominal();
            return;
        }
        let usd_rate = $('input[name="rate[]"]').eq(1).val().replace(/\./g, '');
        $.ajax({
            url:"../../../get-trip-type/"+id,
            dataType:"json",
            success:function(data){
                allowance = data.data[0].allowance;
                type = data.data[0].type;
                currency = data.data[0].currency;
              
              	if(type=='INTERNATIONAL') {
                  val = allowance * usd_rate;
                } else {
                  if(currency=='IDR') {
					val = allowance;
                  } else {
					val = allowance * usd_rate;
                  }
                }
                $('.allowance ').val(numberWithCommas(val));
                total_nominal();
            }
        })
    });
                hotel_condition: null,
    
    $(".change-rate").change(function(){
        total_nominal();
    });
    
    $(".change-type").change(function(){
        
        
        var allowance = $('.allowance').val().split(".").join("");
        
        
        var idr_main = $('.idr_rate_main').val().split(".").join("");
        
        if ($('.idr_rate_1').val()) {
            var idr_1 = $('.idr_rate_1').val().split(".").join("");    
        } else {
            var idr_1 = 0;
        }
        
        if ($('.idr_rate_2').val()) {
            var idr_2 = $('.idr_rate_2').val().split(".").join("");    
        } else {
            var idr_2 = 0;
        }
        
        if ($('.idr_rate_3').val()) {
            var idr_3 = $('.idr_rate_3').val().split(".").join("");    
        } else {
            var idr_3 = 0;
        }
        
        if ($('.idr_rate_4').val()) {
            var idr_4 = $('.idr_rate_4').val().split(".").join("");    
        } else {
            var idr_4 = 0;
        }
        
        if ($('.idr_rate_5').val()) {
            var idr_5 = $('.idr_rate_5').val().split(".").join("");    
        } else {
            var idr_5 = 0;
        }
        
        if ($('.idr_rate_6').val()) {
            var idr_6 = $('.idr_rate_6').val().split(".").join("");    
        } else {
            var idr_6 = 0;
        }
        
        if ($('.idr_rate_7').val()) {
            var idr_7 = $('.idr_rate_7').val().split(".").join("");    
        } else {
            var idr_7 = 0;
        }
        
        if ($('.idr_rate_8').val()) {i
            var idr_8 = $('.idr_rate_8').val().split(".").join("");    
        } else {
            var idr_8 = 0;
        }
        
        if ($('.idr_rate_9').val()) {
            var idr_9 = $('.idr_rate_9').val().split(".").join("");    
        } else {
            var idr_9 = 0;
        }
        
        if ($('.idr_rate_10').val()) {
            var idr_10 = $('.idr_rate_10').val().split(".").join("");    
        } else {
            var idr_10 = 0;
        }
      
    
        var total_append = +allowance + +idr_main + +idr_1 + +idr_2 + +idr_3 + +idr_4 + +idr_5 + +idr_6 + +idr_7 + +idr_8 + +idr_9 + +idr_10; 
        
        $('.total-nominal').val(numberWithCommas(total_append));
        
    });
    
    $(function() {
        $('.currency').maskMoney({
          thousands: '.',
          decimal: ',',
          allowZero: true,
          allowNegative: true,
          precision: 0 // ubah ke 2 kalau butuh angka desimal
        });
        $('.currency').maskMoney('mask');
    });

    $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:0});
    
    $(".type-currency").on("keyup", function(event) {
      var i = event.keyCode;
      if ((i >= 48 && i <= 57) || (i >= 96 && i <= 105)) {
        $(".type-currency").off("keyup");
        console.log("Number pressed. Stopping...");
      } else {
        console.log("Non-number pressed.");
      }
    });
    
    var maxGroup = 10;
    var i = 1;
    var j = 1;
    
    $(".addMore").click(function(){
        $("#action_button").prop("disabled", false);
        $("#action_button_draft").prop("disabled", false);
        $(".warning-upload").hide();
        i++;
        if($('body').find('.fieldGroup').length < maxGroup){
         
          var fieldHTML = '<div class="row fieldGroup"><input type="hidden" class="id_rate" name="id_rate" value="0"><div class="col-md-3"><label for="">Currency</label><input type="text" class="form-control" name="currency_rate[]"></div><div class="col-md-6"><label for="">Exchange Rate</label><input type="text" class="form-control currency" name="rate[]"></div><div class="col-md-3"><a class="btn btn-danger btn-sm remove-currency" style="color:white;margin-top:35px;cursor:pointer;background:#f05154"><i class="fa fa-trash"></i></a></div></div>';
          $('body').find('.fieldGroup:last').after(fieldHTML);
          $(function() {
            $('.currency').maskMoney({
              thousands: '.',
              decimal: ',',
              allowZero: true,
              allowNegative: true,
              precision: 0 // ubah ke 2 kalau butuh angka desimal
            });
            $('.currency').maskMoney('mask');
          });
      } else{
          alert('Maximum '+maxGroup+' groups are allowed.');
      }
    });
    
    // $("body").on("click",".remove-currency",function(){ 
    //    $(this).parents(".fieldGroup").remove();
    // });

    $("body").on("click", ".remove-currency", function () {
        let $group = $(this).closest(".fieldGroup");

        let id_rate = $group.find(".id_rate").val();
        let reim_id = "{{Request::segment('3')}}";
        let rate = $group.find('input[name="rate[]"]').val().replace(/\./g, '');
        let currency = $group.find('input[name="currency_rate[]"]').val();

        
        $.ajax({
            url: '../../../delete-currency-options',
            type: 'POST',
            data: {
                reim_id: reim_id,
                rate: rate,
                currency: currency,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log("Berhasil hapus:", response);
                $group.remove();
            },
            error: function(xhr) {
                console.error("Gagal hapus:", xhr);
            }
        });

        // Kalau langsung hapus tanpa AJAX
        $group.remove();
    });

    
    var count = "{{count($travel_detail)}}" - 1;
    var ct = 1;
    
    
    $(".addMoreDetail").click(function(){
        $("#action_button").prop("disabled", true);
        $("#action_button_draft").prop("disabled", true);
        $(".warning-upload").show();
        i++;
        count++;
        ct++;
        if($('body').find('.fieldGroupDetail').length < maxGroup){
         
          var fieldHTML = '<tr class="fieldGroupDetail"><td><input type="hidden" name="id_detail[]"><select class="form-control cost_type_id'+count+'" name="cost_type_id[]"><option value="">Pilih...</option>@foreach ($types as $item)<option value="{{$item->id}}">{{$item->name}}</option>@endforeach</select></td><td><input type="text" class="form-control" name="destination[]"></td><td><select class="form-control currency'+count+' currency-select" name="currency[]" style="width:130%"><option value="">Pilih...</option>@foreach ($currency as $item)<option value="{{$item->currency}}">{{$item->currency}}</option>@endforeach</select></td><td><input type="text" class="form-control amount-input currency amount'+count+'" name="amount[]"></td><td><input type="text" class="form-control number-format currency idr_rate_'+count+' change-rate" name="idr_rate[]" readonly></td><td><input type="text" class="form-control number-format currency tax'+count+'" readonly name="tax[]"></td><td><select class="form-control" name="payment_type[]" style="width:130%"><option value="">Select...</option><option value="BDC">BDC</option><option value="Cash">Cash</option></select></td><td class="file-proof"><button type="button" data-idx="'+count+'" class="btn btn-success btn-sm addFile"><i class="fa fa-upload"></i></button><button type="button" data-idx="'+count+'" class="btn btn-success btn-sm addCamera"><i class="fa fa-camera"></i></button><input type="file" accept="image/*" name="file[]"  style="display: none;" class="file-input file'+count+'"><input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;"></td><td><div id="preview_'+ct+'"></div></td><td><button type="button" class="btn btn-danger remove-detail"><i class="fa fa-trash"></i></button></td></tr>';
          $('body').find('.fieldGroupDetail:last').after(fieldHTML);
          $(function() {
            $('.currency').maskMoney({
              thousands: '.',
              decimal: ',',
              allowZero: true,
              allowNegative: true,
              precision: 0 // ubah ke 2 kalau butuh angka desimal
            });
            $('.currency').maskMoney('mask');
          });
          
            $(".amount0").change(function(){
                currency = $('.currency0').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount0').val().split(".").join("");
                cost_type = $('.cost_type_id0').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_main').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax0').val(numberWithCommas(tax));
                        } else {
                            $('.tax0').val(0);
                        }
                    }
                })
            });
            
            $(".amount1").change(function(){
                currency = $('.currency1').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount1').val().split(".").join("");
                cost_type = $('.cost_type_id1').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_1').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax1').val(numberWithCommas(tax));
                        } else {
                            $('.tax1').val(0);
                        }
                    }
                })
            });
            
            $(".amount2").change(function(){
                currency = $('.currency2').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount2').val().split(".").join("");
                cost_type = $('.cost_type_id2').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_2').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax2').val(numberWithCommas(tax));
                        } else {
                            $('.tax2').val(0);
                        }
                    }
                })
            });
            
            $(".amount3").change(function(){
                currency = $('.currency3').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount3').val().split(".").join("");
                cost_type = $('.cost_type_id3').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_3').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax3').val(numberWithCommas(tax));
                        } else {
                            $('.tax3').val(0);
                        }
                    }
                })
            });
            
            $(".amount4").change(function(){
                currency = $('.currency4').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount4').val().split(".").join("");
                cost_type = $('.cost_type_id4').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_4').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax4').val(numberWithCommas(tax));
                        } else {
                            $('.tax4').val(0);
                        }
                    }
                })
            });
            
            $(".amount5").change(function(){
                currency = $('.currency5').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount5').val().split(".").join("");
                cost_type = $('.cost_type_id5').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_5').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax5').val(numberWithCommas(tax));
                        } else {
                            $('.tax5').val(0);
                        }
                    }
                })
            });
            
            $(".amount6").change(function(){
                currency = $('.currency6').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount6').val().split(".").join("");
                cost_type = $('.cost_type_id6').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_6').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax6').val(numberWithCommas(tax));
                        } else {
                            $('.tax6').val(0);
                        }
                    }
                })
            });
            
            $(".amount7").change(function(){
                currency = $('.currency7').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount7').val().split(".").join("");
                cost_type = $('.cost_type_id7').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_7').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax7').val(numberWithCommas(tax));
                        } else {
                            $('.tax7').val(0);
                        }
                    }
                })
            });
            
            $(".amount8").change(function(){
                currency = $('.currency8').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount8').val().split(".").join("");
                cost_type = $('.cost_type_id8').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_8').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax8').val(numberWithCommas(tax));
                        } else {
                            $('.tax8').val(0);
                        }
                    }
                })
            });
            
            $(".amount9").change(function(){
                currency = $('.currency9').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount9').val().split(".").join("");
                cost_type = $('.cost_type_id9').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_9').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax9').val(numberWithCommas(tax));
                        } else {
                            $('.tax9').val(0);
                        }
                    }
                })
            });
            
            $(".amount10").change(function(){
                currency = $('.currency10').val();
                id = "{{Request::segment(3)}}";
                amount = $('.amount10').val().split(".").join("");
                cost_type = $('.cost_type_id10').val();
                
                $.ajax({
                    url:"../../../get-currency/"+id+"/"+currency,
                    dataType:"json",
                    success:function(data){
                        val = data.data * amount;
                        $('.idr_rate_10').val(numberWithCommas(val));
                        total_nominal();
                        if(cost_type==3) {
                            tax = val * 2/100;
                            $('.tax10').val(numberWithCommas(tax));
                        } else {
                            $('.tax10').val(0);
                        }
                    }
                })
            });
            
            $(".cost_type_id1").change(function(){
                cost_type = $(".cost_type_id1").val();
                if(cost_type==3) {
                    val = $(".idr_rate_main").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax1').val(numberWithCommas(tax));
                } else {
                    $('.tax1').val(0);
                }
            });
            
            $(".cost_type_id2").change(function(){
                cost_type = $(".cost_type_id2").val();
                if(cost_type==3) {
                    val = $(".idr_rate_2").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax2').val(numberWithCommas(tax));
                } else {
                    $('.tax2').val(0);
                }
            });
            
            $(".cost_type_id3").change(function(){
                
                cost_type = $(".cost_type_id3").val();
                if(cost_type==3) {
                    val = $(".idr_rate_3").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax3').val(numberWithCommas(tax));
                } else {
                    $('.tax3').val(0);
                }
            });
            
            $(".cost_type_id4").change(function(){
                cost_type = $(".cost_type_id4").val();
                if(cost_type==3) {
                    val = $(".idr_rate_4").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax4').val(numberWithCommas(tax));
                } else {
                    $('.tax4').val(0);
                }
            });
            
            $(".cost_type_id5").change(function(){
                cost_type = $(".cost_type_id5").val();
                if(cost_type==3) {
                    val = $(".idr_rate_5").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax5').val(numberWithCommas(tax));
                } else {
                    $('.tax5').val(0);
                }
            });
            
            $(".cost_type_id6").change(function(){
                cost_type = $(".cost_type_id6").val();
                if(cost_type==3) {
                    val = $(".idr_rate_6").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax6').val(numberWithCommas(tax));
                } else {
                    $('.tax6').val(0);
                }
            });
            
            $(".cost_type_id7").change(function(){
                cost_type = $(".cost_type_id7").val();
                if(cost_type==3) {
                    val = $(".idr_rate_7").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax7').val(numberWithCommas(tax));
                } else {
                    $('.tax7').val(0);
                }
            });
            
            $(".cost_type_id8").change(function(){
                cost_type = $(".cost_type_id8").val();
                if(cost_type==3) {
                    val = $(".idr_rate_8").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax8').val(numberWithCommas(tax));
                } else {
                    $('.tax8').val(0);
                }
            });
            
            $(".cost_type_id9").change(function(){
                cost_type = $(".cost_type_id9").val();
                if(cost_type==3) {
                    val = $(".idr_rate_9").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax9').val(numberWithCommas(tax));
                } else {
                    $('.tax9').val(0);
                }
            });
            
            $(".cost_type_id10").change(function(){
                cost_type = $(".cost_type_id10").val();
                if(cost_type==3) {
                    val = $(".idr_rate_10").val().split(".").join("");
                    tax = val * 2/100;
                    $('.tax10').val(numberWithCommas(tax));
                } else {
                    $('.tax10').val(0);
                }
            });
          
          $(".change-rate").change(function(){
            total_nominal();  
          });
          
          
          
      } else{
          alert('Maximum '+maxGroup+' groups are allowed.');
      }
    });
    
    $("body").on("click",".remove-detail",function(){ 
       $("#action_button").prop("disabled", false);
       $("#action_button_draft").prop("disabled", false);
       $(".warning-upload").hide();
       $(this).parents(".fieldGroupDetail").remove();
       total_nominal();
    });
    
    // Objek untuk menyimpan status upload di setiap row
      let uploadStatus = {};

      // Fungsi untuk menangani upload file
      $("body").on("click", ".addFile", function () {
        let btn = $(this);
        let row = btn.closest("tr");
        let idx = row.index();
        let fileInput = row.find(".file-input");

        fileInput.click();

        fileInput.off("change").on("change", function (event) {
          var file = event.target.files[0];

          if (file) {
            $("#action_button").prop("disabled", false);
            $("#action_button_draft").prop("disabled", false);
            $(".warning-upload").hide();

            let previewDiv = row.find("#preview_" + (idx + 1));
            previewDiv.empty();

            let fileType = file.type;

            if (fileType.startsWith("image/")) {
              // Preview gambar
              var reader = new FileReader();
              reader.onload = function (e) {
                previewDiv.append(
                  $('<img>').attr('src', e.target.result).css({
                    maxWidth: '75px',
                    maxHeight: '75px',
                    border: '2px solid #28a745',
                    borderRadius: '5px',
                    marginTop: '5px'
                  })
                );
              };
              reader.readAsDataURL(file);

            } else if (fileType === "application/pdf") {
              // Preview PDF (ikon + link ke file)
              let fileURL = URL.createObjectURL(file);
              let pdfIcon = 'https://cdn-icons-png.flaticon.com/512/337/337946.png'; // Bisa diganti lokal

              previewDiv.append(
                $('<a>').attr({
                  href: fileURL,
                  target: '_blank',
                  title: 'Klik untuk lihat PDF'
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
                )
              );

            } else {
              previewDiv.append('<p style="color:red;">File tidak didukung</p>');
            }
          }
        });
      });



    // Fungsi untuk menangani pengambilan gambar dari kamera
    $("body").on("click", ".addCamera", function () {
        let btn = $(this);
        let row = btn.closest("tr");
        let idx = row.index();
        let fileInput = row.find(".camera-input");

        if (navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices
                .getUserMedia({
                    video: {
                        width: { ideal: 1280 },  // minta HD
                        height: { ideal: 720 },
                        facingMode: { ideal: "environment" }
                    }
                })
                .then(function (stream) {
                    $("#modalPhoto").modal("show");
                    let videoElement = $("#videoElement")[0];
                    videoElement.srcObject = stream;

                    $("#captureButton").off("click").on("click", function () {
                        const canvas = document.createElement("canvas");
                        const context = canvas.getContext("2d");

                        // Atur resolusi keluaran (HD minimal)
                        const outputWidth = 1280;
                        const outputHeight = 720;
                        canvas.width = outputWidth;
                        canvas.height = outputHeight;

                        // Scale dari video ke canvas agar tidak blur
                        context.drawImage(videoElement, 0, 0, outputWidth, outputHeight);

                        // Simpan sebagai JPEG dengan kualitas 0.85 (lebih jernih + kecil)
                        canvas.toBlob(function (blob) {
                            const file = new File([blob], "capture.jpg", { type: "image/jpeg" });

                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            fileInput[0].files = dataTransfer.files;

                            const imageURL = URL.createObjectURL(file);
                            let previewDiv = row.find("#preview_" + (idx + 1)); 
                            previewDiv.empty().append(
                                $('<img>').attr('src', imageURL).css({
                                    maxWidth: '75px',
                                    maxHeight: '75px',
                                    border: '2px solid #28a745',
                                    borderRadius: '5px',
                                    marginTop: '5px'
                                })
                            );

                            stream.getTracks().forEach(track => track.stop());
                            $("#modalPhoto").modal("hide");
                            $("#action_button").prop("disabled", false);
                            $("#action_button_draft").prop("disabled", false);
                            $(".warning-upload").hide();
                        }, "image/jpeg", 0.85); 
                    });
                })
                .catch(function (err) {
                    console.error("Error accessing webcam: " + err);
                });
        }
    });

      
    // EDIT AMOUNT
    
    $(".amount0").change(function(){
        currency = $('.currency0').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount0').val().split(".").join("");
        cost_type = $('.cost_type_id0').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_main').val(numberWithCommas(val));
                total_nominal();
                
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax0').val(numberWithCommas(tax));
                } else {
                    $('.tax0').val(0);
                }
            }
        })
    });
    
    $(".amount1").change(function(){
        currency = $('.currency1').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount1').val().split(".").join("");
        cost_type = $('.cost_type_id1').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_1').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax1').val(numberWithCommas(tax));
                } else {
                    $('.tax1').val(0);
                }
            }
        })
    });
    
    $(".amount2").change(function(){
        currency = $('.currency2').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount2').val().split(".").join("");
        cost_type = $('.cost_type_id2').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_2').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax2').val(numberWithCommas(tax));
                } else {
                    $('.tax2').val(0);
                }
            }
        })
    });
    
    $(".amount3").change(function(){
        currency = $('.currency3').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount3').val().split(".").join("");
        cost_type = $('.cost_type_id3').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_3').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax3').val(numberWithCommas(tax));
                } else {
                    $('.tax3').val(0);
                }
            }
        })
    });
    
    $(".amount4").change(function(){
        currency = $('.currency4').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount4').val().split(".").join("");
        cost_type = $('.cost_type_id4').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_4').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax4').val(numberWithCommas(tax));
                } else {
                    $('.tax4').val(0);
                }
            }
        })
    });
    
    $(".amount5").change(function(){
        currency = $('.currency5').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount5').val().split(".").join("");
        cost_type = $('.cost_type_id5').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_5').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax5').val(numberWithCommas(tax));
                } else {
                    $('.tax5').val(0);
                }
            }
        })
    });
    
    $(".amount6").change(function(){
        currency = $('.currency6').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount6').val().split(".").join("");
        cost_type = $('.cost_type_id6').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_6').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax6').val(numberWithCommas(tax));
                } else {
                    $('.tax6').val(0);
                }
            }
        })
    });
    
    $(".amount7").change(function(){
        currency = $('.currency7').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount7').val().split(".").join("");
        cost_type = $('.cost_type_id7').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_7').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax7').val(numberWithCommas(tax));
                } else {
                    $('.tax7').val(0);
                }
            }
        })
    });
    
    $(".amount8").change(function(){
        currency = $('.currency8').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount8').val().split(".").join("");
        cost_type = $('.cost_type_id8').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_8').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax8').val(numberWithCommas(tax));
                } else {
                    $('.tax8').val(0);
                }
            }
        })
    });
    
    $(".amount9").change(function(){
        currency = $('.currency9').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount9').val().split(".").join("");
        cost_type = $('.cost_type_id9').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_9').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax9').val(numberWithCommas(tax));
                } else {
                    $('.tax9').val(0);
                }
            }
        })
    });
    
    $(".amount10").change(function(){
        currency = $('.currency10').val();
        id = "{{Request::segment(3)}}";
        amount = $('.amount10').val().split(".").join("");
        cost_type = $('.cost_type_id10').val();
        
        $.ajax({
            url:"../../../get-currency/"+id+"/"+currency,
            dataType:"json",
            success:function(data){
                val = data.data * amount;
                $('.idr_rate_10').val(numberWithCommas(val));
                total_nominal();
                if(cost_type==3) {
                    tax = val * 2/100;
                    $('.tax10').val(numberWithCommas(tax));
                } else {
                    $('.tax10').val(0);
                }
            }
        })
    });
    
    
    $(".cost_type_id0").change(function(){
        cost_type = $(".cost_type_id0").val();
        if(cost_type==3) {
            val = $(".idr_rate_main").val().split(".").join("");
            tax = val * 2/100;
            $('.tax0').val(numberWithCommas(tax));
        } else {
            $('.tax0').val(0);
        }
    });
    
    $(".cost_type_id1").change(function(){
        cost_type = $(".cost_type_id1").val();
        if(cost_type==3) {
            val = $(".idr_rate_1").val().split(".").join("");
            tax = val * 2/100;
            $('.tax1').val(numberWithCommas(tax));
        } else {
            $('.tax1').val(0);
        }
    });
    
    $(".cost_type_id2").change(function(){
        cost_type = $(".cost_type_id2").val();
        if(cost_type==3) {
            val = $(".idr_rate_2").val().split(".").join("");
            tax = val * 2/100;
            $('.tax2').val(numberWithCommas(tax));
        } else {
            $('.tax2').val(0);
        }
    });
    
    $(".cost_type_id3").change(function(){
        cost_type = $(".cost_type_id3").val();
        if(cost_type==3) {
            val = $(".idr_rate_3").val().split(".").join("");
            tax = val * 2/100;
            $('.tax3').val(numberWithCommas(tax));
        } else {
            $('.tax3').val(0);
        }
    });
    
    $(".cost_type_id4").change(function(){
        cost_type = $(".cost_type_id4").val();
        if(cost_type==3) {
            val = $(".idr_rate_4").val().split(".").join("");
            tax = val * 2/100;
            $('.tax4').val(numberWithCommas(tax));
        } else {
            $('.tax4').val(0);
        }
    });
    
    $(".cost_type_id5").change(function(){
        cost_type = $(".cost_type_id5").val();
        if(cost_type==3) {
            val = $(".idr_rate_5").val().split(".").join("");
            tax = val * 2/100;
            $('.tax5').val(numberWithCommas(tax));
        } else {
            $('.tax5').val(0);
        }
    });
    
    $(".cost_type_id6").change(function(){
        cost_type = $(".cost_type_id6").val();
        if(cost_type==3) {
            val = $(".idr_rate_6").val().split(".").join("");
            tax = val * 2/100;
            $('.tax6').val(numberWithCommas(tax));
        } else {
            $('.tax6').val(0);
        }
    });
    
    $(".cost_type_id7").change(function(){
        cost_type = $(".cost_type_id7").val();
        if(cost_type==3) {
            val = $(".idr_rate_7").val().split(".").join("");
            tax = val * 2/100;
            $('.tax7').val(numberWithCommas(tax));
        } else {
            $('.tax7').val(0);
        }
    });
    
    $(".cost_type_id8").change(function(){
        cost_type = $(".cost_type_id8").val();
        if(cost_type==3) {
            val = $(".idr_rate_8").val().split(".").join("");
            tax = val * 2/100;
            $('.tax8').val(numberWithCommas(tax));
        } else {
            $('.tax8').val(0);
        }
    });
    
    $(".cost_type_id9").change(function(){
        cost_type = $(".cost_type_id9").val();
        if(cost_type==3) {
            val = $(".idr_rate_9").val().split(".").join("");
            tax = val * 2/100;
            $('.tax9').val(numberWithCommas(tax));
        } else {
            $('.tax9').val(0);
        }
    });
    
    $(".cost_type_id10").change(function(){
        cost_type = $(".cost_type_id10").val();
        if(cost_type==3) {
            val = $(".idr_rate_10").val().split(".").join("");
            tax = val * 2/100;
            $('.tax10').val(numberWithCommas(tax));
        } else {
            $('.tax10').val(0);
        }
    });
    
    
  });
  
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  new Vue({
      el: '#app',
      data: {
        usd_rate: 0,
        idr_rate: 0,
        jpy_rate: 0,
        reimburses: [
            {
                trip: null,
                trip_data: null,
                trip_allowance: null,
                travel_time: null,
                start_time: null,
                end_time: null,
                details: [
                    {
                        cost_type: null,
                        destination: null,
                        currency: null,
                        amount: null,
                        tax: null,
                        idr_rate: null,
                        code: null,
                    }
                ],
                total: 0
            },
        ],
        rates: [
            {
                code: 'IDR',
                rate: 1
            }
        ],
        types : @json($types),
        trip_types : @json($trip_types),
                not_stay_hotel_condition_id : @json($not_stay_hotel_condition_id),
        grandtotal: 0
      },
      mounted() {
        // this.initSelectForm()
        self = this
        $(".idr-rate-input").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.idr-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.idr_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".usd-rate-input").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.usd-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.usd_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".jpy-rate-input").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.jpy-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.jpy_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0, allowZero: true, affixesStay: false, allowNegative: true});
            $('.amount-input').on('change', (event) => {
            self.reimburses[self.reimburses.length - 1].details[0].amount = ($(event.target).val());
            self.changeAmount(0);
            self.calculateTotal(0,0)
        });
        // $('.number-format').maskMoney({ thousands:'.', decimal:',', precision:0});
      
      },
      methods : {
        changeAmount(i) {

        },
        getRate(currency, amt) {
            self = this;
            rate = self.rates.filter(a => a.code == currency)[0].rate
            return parseInt(amt.replaceAll(".","")) * parseInt(`${rate}`.replaceAll(".",""));
             
        },
        
        changeTrip(i) {
            id = this.reimburses[i].trip
            self = this
            if (!id) {
                this.reimburses[i].trip_data = null
                this.reimburses[i].trip_allowance = '0'
                this.reimburses[i].hotel_condition = this.not_stay_hotel_condition_id
                this.reimburses[i].start_time = null
                this.reimburses[i].end_time = null
                this.reimburses[i].travel_time = null
                this.calculateTotal(i,0)
                return
            }
            // alert(self.trip_types.filter(a => a.id == id)[0].allowance)
            this.reimburses[i].trip_data = self.trip_types.filter(a => a.id == id)[0]
            if (!this.reimburses[i].trip_data) {
                this.reimburses[i].trip_allowance = '0'
                this.calculateTotal(i,0)
                return
            }
            this.reimburses[i].trip_allowance = '0'
            // this.reimburses[i].trip_allowance = self.trip_types.filter(a => a.id == id)[0].allowance.toLocaleString('de-DE')
            this.changeAllowance(i)

        },
        changeAllowance(i) {
            this.calculateTotal(i,0)
            
        },
        changeTime(i) {

            // Get the input values
            data = this.reimburses[i]
            let time1 = data.start_time;
            let time2 = data.end_time;

            // Parse the input values to Date objects (using a dummy date)
            let date1 = new Date('1970-01-01T' + time1 + 'Z');
            let date2 = new Date('1970-01-01T' + time2 + 'Z');

            // Calculate the difference in milliseconds
            let timeDifference = Math.abs(date2 - date1);

            // Convert the difference to hours and minutes
            let hoursDifference = Math.floor(timeDifference / 1000 / 60 / 60);
            let minutesDifference = Math.floor((timeDifference / 1000 / 60) % 60);

            // Display the difference
            let differenceMessage = `Time difference: ${hoursDifference} hours and ${minutesDifference} minutes.`;
            this.reimburses[i].travel_time = `${hoursDifference} Hours and ${minutesDifference} minutes.`;
        },
        addRate() {
            this.rates.push({
                code: null,
                rate: null
            })
        },
        addTravel() {
            this.reimburses.push({
                trip_allowance: null,
                travel_time: null,
                trip_data: null,
                hotel_condition: null,
                details: [
                    {
                        cost_type: null,
                        destination: null,
                        currency: null,
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

              $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0, allowZero: true, affixesStay: false, allowNegative: true});
              $('.amount-input').on('change', (event) => {
                self.reimburses[self.reimburses.length - 1].details[0].amount = ($(event.target).val());
                self.changeAmount(0);
                self.calculateTotal(self.reimburses.length - 1,0)
              });
            })

        },
        removeTravel(i) {
            this.reimburses.splice(i, 1)
        },
        addDetail(i) {
            this.reimburses[i].details.push({
                cost_type: null,
                destination: null,
                currency: null,
                amount: null,
                tax: null,
                code: null,
            });
            self = this
            this.$nextTick(() => {
              self.initSelectForm();
              $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0, allowZero: true, affixesStay: false, allowNegative: true});
              $('.amount-input').on('change', (event) => {
                const index = $(event.target).closest('tr').index();
                this.reimburses[i].details[index].amount = ($(event.target).val());
                self.changeAmount(0);
                self.calculateTotal(i,index)

              });
            })
        },
        calculateTotal(i,a) {
            subtotal = 0
            self = this
            currency = this.reimburses[i].details[a].currency
            amount = this.reimburses[i].details[a].amount
            id = this.reimburses[i].details[a].cost_type

            try {
                tax = self.types.filter(a => a.id == id)[0].tax
                this.reimburses[i].details[a].idr_rate = this.getRate(currency, amount).toLocaleString("de-DE")
                this.reimburses[i].details[a].tax = (this.getRate(currency, amount) * tax / 100).toLocaleString('de-DE')
                this.reimburses[i].details.forEach(element => {
                    subtotal += parseInt(element.idr_rate.replaceAll(".",""))
                });
            } catch (error) {
                
            }
      

            // allowance_currency = self.trip_types.filter(a => a.id == self.reimburses[i].trip)[0].currency

            // allowance = self.getRate(allowance_currency,self.reimburses[i].trip_allowance.replaceAll(".",""))

            // subtotal += allowance
            // this.reimburses[i].total = subtotal.toLocaleString('de-DE')
        },        
        removeDetail(i,a) {
            this.reimburses[i].details.splice(a,1)
        },
        changeCost(i,a) {
            id = this.reimburses[i].details[a].cost_type
            self = this
            // alert(self.trip_types.filter(a => a.id == id)[0].allowance)
            this.reimburses[i].details[a].code = self.types.filter(a => a.id == id)[0].type
            this.calculateTotal(i,a)
        }
      },
      watch: {
       
      },
  });


    $(document).on('blur', 'input[name="rate[]"]', function () {
        let $group = $(this).closest('.fieldGroup');

        let id_rate = $group.find('.id_rate').val();
        let rate = $(this).val().replace(/\./g, '');
        let currency = $group.find('input[name="currency_rate[]"]').val();
        let reim_id = "{{Request::segment('3')}}";

        $.ajax({
            url: '../../../update-currency', // Ganti dengan URL yang sesuai
            type: 'POST',
            data: {
                reim_id: reim_id,
                id_rate: id_rate,
                rate: rate,
                currency: currency,
                _token: $('meta[name="csrf-token"]').attr('content') // Laravel CSRF Token
            },
            success: function (response) {
                console.log('Berhasil update:', response);
            },
            error: function (xhr, status, error) {
                console.error('Gagal update:', error);
            }
        });
    });

    $(document).on('focus', '.currency-select', function () {
        let $select = $(this);
        let selectedCurrency = $select.val();
        let reim_id = "{{ Request::segment('3') }}";

        $.ajax({
            url: '../../../get-currency-options',
            type: 'GET',
            data: {
                selected: selectedCurrency,
                reim_id: reim_id
            },
            success: function(response) {
                $select.html(response.options);
            },
            error: function(xhr) {
                console.error('Gagal load currency:', xhr);
            }
        });
    });








</script>

@endpush
@endsection
