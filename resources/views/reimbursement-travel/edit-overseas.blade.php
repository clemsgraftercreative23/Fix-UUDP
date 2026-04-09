@extends('template.app')

@section('content')

<?php 
function rupiah($angka){
	
	$hasil_rupiah = number_format($angka,0,',','.');
	return $hasil_rupiah;
 
}
?>

<div class="page-content" id="app">
<div class="">
    <form action="{!!url('update-travel-inq/'.$data['0']->id.'')!!}" method="POST" enctype="multipart/form-data">
        @csrf 
        <div class="row">
            <div class="col-xl">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">REIMBURSEMENT UUDP - TRAVEL</h5>
                        
                        <div class="row">
                            <input type="hidden" name="travel_type" value="Overseas">
                            <div class="col-md-3">
                                <label for="">Employee</label>
                                <input atype="text" class="form-control" readonly value="{{auth()->user()->name}}">
                            </div>
                            <div class="col-md-3">
                                <label for="">Apply Date</label>
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
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row fieldGroup">
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
                                <br>
                                @foreach($travel_trip as $key => $row)
                                @if($key > 0)
                                <div class="row fieldGroup">
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
        <!--<div class="row">-->
        <!--    <div class="col-xl text-right">-->
        <!--        <button type="button" class="btn btn-primary text-right" @click="addTravel"><i class="fa fa-plus"></i> Add New</button>-->
        <!--    </div>-->
        <!--</div>-->
        <br>
        <div class="row" v-for="(data,i) in reimburses">
            <div class="col-xl">
                <div class="card">
                    <div class="card-body">
                        
                        <!--<div class="row">-->
                        <!--    <div class="col-xl text-right">-->
                        <!--        <button class="btn btn-danger text-right" @click="removeTravel(i)"><i class="fa fa-trash"></i> Remove</button>-->
                        <!--    </div>-->
                        <!--</div>-->
                        <div class="row">
                            <div class="col-md-3">
                                <label for="">Transaction Date</label>
                                <input type="date" name="date" class="form-control" required value="{{$data['0']->date}}">
                            </div>
                            <div class="col-md-3">
                                <label for="">Purpose</label>
                                <input type="text" name="purpose" class="form-control" required value="{{$data_travel['0']->purpose}}">
                            </div>
                            <div class="col-md-3">
                                <label for="">Trip Type</label>
                                <select id="trip_type_id" class="form-control change-type" name="trip_type_id">
                                    <option value="" selected disabled>Select...</option>
                                    @foreach ($trip_types as $item)
                                        <option value="{{$item->id}}" @if($item->id == $data_travel['0']->trip_type_id) selected @endif>{{$item->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="">Hotel </label>
                                <select id="hotel_condition_id" class="form-control" name="hotel_condition_id">
                                    <option value="" selected disabled>Select...</option>
                                    @foreach ($hotel_conditions as $item)
                                        <option value="{{$item->id}}" @if($item->id == $data_travel['0']->hotel_condition_id) selected @endif>{{$item->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="">Start</label>
                                <input type="time" class="form-control" name="start_time" value="{{$data_travel['0']->start_time}}">
                            </div>  
                            
                            <div class="col-md-3">
                                <label for="">Arrival</label>
                                <input type="time" class="form-control" name="end_time" value="{{$data_travel['0']->end_time}}">
                            </div>    
                            
                            <div class="col-md-3">
                                <label for="">Allowance</label>
                                <select class="form-control" name="type_allowance" id="type-allowance" required></select>
                                <input type="hidden" class="form-control number-format allowance change-rate currency" value="{{rupiah($data_travel['0']->allowance)}}" readonly>
                            </div>    
                            <?php 
                                $start = strtotime($data_travel['0']->start_time);
                                $end = strtotime($data_travel['0']->end_time);
                                $minutes = ($end - $start) / 60;
                                $hours = floor($minutes / 60).' Hour and '.($minutes -   floor($minutes / 60) * 60).' Minutes';
                            ?>
                            
                            <div class="col-md-3">
                                <label for="">Travel Times</label>
                                <input type="text" readonly class="form-control" value="{{$hours}}">
                            </div>                     
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xl">
                                <table class="table full-width" style="width: 100%;overflow-x: auto;white-space: nowrap;display:block">
                                    <thead style="width: 100%">
                                        <tr>
                                            <th width="200">Cost Type</th>
                                            <th width="200">Destination</th>
                                            <th width="200">Currency</th>
                                            <th width="200">Amount</th>
                                            <th width="200">IDR Rate</th>
                                            <th width="200">Pph23</th>
                                            <th width="200">Payment</th>
                                            <th width="200">File</th>
                                            <th width="200">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="fieldGroupDetail">
                                            <td>
                                                <input type="hidden" name="id_detail[]" value="{{$travel_detail['0']->id}}">
                                                <select class="form-control cost_type_id0" name="cost_type_id[]">
                                                    <option value="">Select...</option>
                                                    @foreach ($types as $item)
                                                        <option value="{{$item->id}}" @if($travel_detail['0']->cost_type_id == $item->id) selected @endif>{{$item->name}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" name="destination[]" value="{{$travel_detail['0']->destination}}">
                                            </td>
                                            <td>
                                                <select class="form-control currency0" name="currency[]" style="width:130%">
                                                    <option value="">Select...</option>
                                                    @foreach ($currency as $item)
                                                        <option value="{{$item->currency}}" @if($item->currency == $travel_detail['0']->currency) selected @endif>{{$item->currency}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control currency amount0 change-amount" value="{{rupiah($travel_detail['0']->amount)}}" name="amount[]">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control currency number-format idr_rate_main change-rate" value="{{rupiah($travel_detail['0']->idr_rate)}}" name="idr_rate[]" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control currency number-format tax0" readonly value="{{rupiah($travel_detail['0']->tax)}}" name="tax[]">
                                            </td>
                                            <td>
                                                <select class="form-control" name="payment_type[]" style="width:130%">
                                                    <option value="">Select...</option>
                                                    <option value="BDC" @if($travel_detail['0']->payment_type=='BDC') selected @endif>BDC</option>
                                                    <option value="Cash" @if($travel_detail['0']->payment_type=='Cash') selected @endif>Cash</option>
                                                </select>
                                            </td>
                                            <td>
                                                <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                                                    <i class="fa fa-upload"></i>
                                                  </button>
                                                  
                                                  <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" >
                                                    <i class="fa fa-camera"></i>
                                                  </button>
                                                  <input type="file" accept="image/*" name="file[]"  style="display: none; " class="file-input">
                                                  <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                                                  <div id="preview_1"></div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-info addMoreDetail"><i class="fa fa-plus"></i></button>
                                            </td>                                                                         
                                        </tr>
                                        
                                        @foreach ($travel_detail as $key => $row)
                                        @if($key > 0)
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
                                                <select class="form-control currency{{$key}}" name="currency[]" style="width:130%">
                                                    <option value="">Select...</option>
                                                    @foreach ($currency as $item)
                                                        <option value="{{$item->currency}}" @if($item->currency == $row->currency) selected @endif>{{$item->currency}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control amount{{$key}} currency change-amount" value="{{rupiah($row->amount)}}" name="amount[]">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control number-format currency idr_rate_{{$key}} change-rate" value="{{rupiah($row->idr_rate)}}" name="idr_rate[]" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control number-format currency tax{{$key}}" readonly value="{{rupiah($row->tax)}}" name="tax[]">
                                            </td>
                                            <td>
                                                <select class="form-control" name="payment_type[]" style="width:130%">
                                                    <option value="">Select...</option>
                                                    <option value="BDC" @if($row->payment_type=='BDC') selected @endif>BDC</option>
                                                    <option value="Cash" @if($row->payment_type=='Cash') selected @endif>Cash</option>
                                                </select>
                                            </td>
                                            <td>
                                                <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                                                    <i class="fa fa-upload"></i>
                                                  </button>
                                                  
                                                  <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" >
                                                    <i class="fa fa-camera"></i>
                                                  </button>
                                                  <input type="file" accept="image/*" name="file[]"  style="display: none; " class="file-input">
                                                  <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                                                  <div id="preview_1"></div>
                                            </td>
                                            <td>
                                                <!--<button type="button" class="btn btn-info"><i class="fa fa-plus"></i></button>-->
                                                <button type="button" class="btn btn-danger remove-detail"><i class="fa fa-trash"></i></button>
                                            </td>                                                                         
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>i
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="">Total</label>
                                <input type="text" readonly class="form-control total-nominal" name="nominal_pengajuan" value="{{rupiah($data['0']->nominal_pengajuan)}}">
                            </div>  
                                       
                        </div>
                       
                    </div>
                </div>
            </div>
        </div>
        <br>
        
        <div class="row">
            <div class="col-xl text-right">
                <a class="btn btn-secondary text-right" href="{{route('reimbursement-travel.index')}}"><i class="fa fa-save"></i> BACK</a>&nbsp;&nbsp;&nbsp;
                <button class="btn btn-primary text-right"><i class="fa fa-save"></i> SUBMIT</button>
            </div>
        </div>
    </form>

</div>
</div>

<!-- End Modal -->

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
            <canvas id="canvas"></canvas>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
    
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?<!\.\d*)(?=(\d{3})+(?!\d))/g, ".");
    }
    
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
    
    var id_type = "{{$data_travel['0']->trip_type_id}}";
    $.ajax({
        url:"../../get-trip-type-overseas/"+id_type,
        dataType:"json",
        success:function(data){
            var dataArray = JSON.parse(data.data);
            var $select = $('#type-allowance');
            $select.empty();
            $select.append($('<option></option>').val("").text("-Select Type Allowance-"));
            $.each(dataArray, function(index, item) {
                $select.append($('<option></option>').val(item.fee).text(''+item.name+' - USD '+item.fee+''));
            });
            $select.prop('selectedIndex', 0);
        }
    })
    
    const notStayHotelConditionId = @json($not_stay_hotel_condition_id);

    function syncNoneTripTypeFields() {
        const tripTypeId = $('#trip_type_id').val();
        const hotelSelect = $('#hotel_condition_id');
        const startInput = $('input[name="start_time"]');
        const endInput = $('input[name="end_time"]');

        if (!tripTypeId) {
            const notStayOption = hotelSelect.find('option').filter(function () {
                return $(this).text().trim().toLowerCase() === 'not stay';
            }).first();

            if (notStayOption.length) {
                hotelSelect.val(notStayOption.val());
            } else if (notStayHotelConditionId) {
                hotelSelect.val(notStayHotelConditionId);
            }

            hotelSelect.prop('disabled', true);
            startInput.prop('disabled', true).val('');
            endInput.prop('disabled', true).val('');
            return;
        }

        hotelSelect.prop('disabled', false);
        startInput.prop('disabled', false);
        endInput.prop('disabled', false);
    }

    $("#trip_type_id").change(function(){
        
        id = $('#trip_type_id').val();
        syncNoneTripTypeFields();
        if (!id) {
            return;
        }
        
        $.ajax({
            url:"../../get-trip-type-overseas/"+id,
            dataType:"json",
            success:function(data){
                var dataArray = JSON.parse(data.data);
                var $select = $('#type-allowance'); 
                $select.empty();
                $select.append($('<option></option>').val("").text("-Select Type Allowance-"));
                $.each(dataArray, function(index, item) {
                    $select.append($('<option></option>').val(item.fee).text(''+item.name+' - USD '+item.fee+''));
                });
            }
        })
    });

    id = "{{Request::segment(2)}}";
    rate =  $(this).val();
    
    $.ajax({
        url:"../../get-travel-trip-rates/"+id,
        dataType:"json",
        success:function(data){
            usd = data.data;
            allowance = $('.allowance ').val();
            val = allowance * usd; 
            $('.allowance ').val(numberWithCommas(val));
            total_nominal();
        }
    })
    
    $("#type-allowance").change(function(){
        id = "{{Request::segment(2)}}";
        rate =  $(this).val();
        
        $.ajax({
            url:"../../get-travel-trip-rates/"+id,
            dataType:"json",
            success:function(data){
                usd = data.data;
                val = rate * usd; 
                $('.allowance ').val(numberWithCommas(val));
                total_nominal();
            }
        })
    });
    
    
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
    
    $('.currency').mask("#.##0", {
      reverse: true
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
        i++;
        if($('body').find('.fieldGroup').length < maxGroup){
         
          var fieldHTML = '<br><div class="row fieldGroup"><div class="col-md-3"><label for="">Currency</label><input type="text" class="form-control" name="currency_rate[]"></div><div class="col-md-6"><label for="">Exchange Rate</label><input type="text" class="form-control currency" name="rate[]"></div><div class="col-md-3"><a class="btn btn-danger btn-sm remove-currency" style="color:white;margin-top:35px;cursor:pointer;background:#f05154"><i class="fa fa-trash"></i></a></div></div>';
          $('body').find('.fieldGroup:last').after(fieldHTML);
          $('.currency').mask("#.##0", {
              reverse: true
          });
      } else{
          alert('Maximum '+maxGroup+' groups are allowed.');
      }
    });
    
    $("body").on("click",".remove-currency",function(){ 
       $(this).parents(".fieldGroup").remove();
    });
    
    var count = "{{count($travel_detail)}}" - 1;
    
    
    $(".addMoreDetail").click(function(){
        i++;
        count++;
        if($('body').find('.fieldGroupDetail').length < maxGroup){
         
          var fieldHTML = '<tr class="fieldGroupDetail"><td><input type="hidden" name="id_detail[]"><select class="form-control cost_type_id'+count+'" name="cost_type_id[]"><option value="">Select...</option>@foreach ($types as $item)<option value="{{$item->id}}">{{$item->name}}</option>@endforeach</select></td><td><input type="text" class="form-control" name="destination[]"></td><td><select class="form-control currency'+count+'" name="currency[]" style="width:130%"><option value="">Select...</option>@foreach ($currency as $item)<option value="{{$item->currency}}">{{$item->currency}}</option>@endforeach</select></td><td><input type="text" class="form-control amount-input currency amount'+count+'" name="amount[]"></td><td><input type="text" class="form-control number-format currency idr_rate_'+count+' change-rate" name="idr_rate[]" readonly></td><td><input type="text" class="form-control number-format currency tax'+count+'" readonly name="tax[]"></td><td><select class="form-control" name="payment_type[]" style="width:130%"><option value="">Select...</option><option value="BDC">BDC</option><option value="Cash">Cash</option></select></td><td><button type="button" data-idx="1" class="btn btn-success btn-sm addFile"><i class="fa fa-upload"></i></button><button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" ><i class="fa fa-camera"></i></button><input type="file" accept="image/*" name="file[]"  style="display: none; " class="file-input"><input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;"><div id="preview_1"></div></td><td><button type="button" class="btn btn-danger remove-detail"><i class="fa fa-trash"></i></button></td></tr>';
          $('body').find('.fieldGroupDetail:last').after(fieldHTML);
          $('.currency').mask("#.##0", {
              reverse: true
          });
          
            $(".amount0").change(function(){
                currency = $('.currency0').val();
                id = "{{Request::segment(2)}}";
                amount = $('.amount0').val().split(".").join("");
                cost_type = $('.cost_type_id0').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount1').val().split(".").join("");
                cost_type = $('.cost_type_id1').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount2').val().split(".").join("");
                cost_type = $('.cost_type_id2').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount3').val().split(".").join("");
                cost_type = $('.cost_type_id3').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount4').val().split(".").join("");
                cost_type = $('.cost_type_id4').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount5').val().split(".").join("");
                cost_type = $('.cost_type_id5').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount6').val().split(".").join("");
                cost_type = $('.cost_type_id6').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount7').val().split(".").join("");
                cost_type = $('.cost_type_id7').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount8').val().split(".").join("");
                cost_type = $('.cost_type_id8').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount9').val().split(".").join("");
                cost_type = $('.cost_type_id9').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
                id = "{{Request::segment(2)}}";
                amount = $('.amount10').val().split(".").join("");
                cost_type = $('.cost_type_id10').val();
                
                $.ajax({
                    url:"../../get-currency/"+id+"/"+currency,
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
       $(this).parents(".fieldGroupDetail").remove();
       total_nominal();
    });
    
    $("body").on("click",".addFile",function(){ 
        
        $(this).parent().find(".file-input").click();
          $(this).parent().find(".file-input").change(function(event) {
            var file = event.target.files[0];
            
            if (file) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    $('#preview_'+$(this).parent().find(".addFile").data('idx')).empty(); // Clear previous preview
                    
                    var img = $('<img>');
                    img.attr('src', e.target.result);
                    img.css({ maxWidth: '100%', maxHeight: '200px' }); // Adjust height as needed
                    $('#preview_'+$(this).parent().find(".addFile").data('idx')).append(img);
                };
              
              reader.readAsDataURL(file);
          }
        })
        
    });
    
    $("body").on("click",".addCamera",function(){ 
        
        idx = $(this).data('idx')
        fileInput = $(this).parent().find(".file-input")[0]; 
        $("#modalPhoto").modal('show')
        const videoElement = $('#videoElement')[0];
        const canvas = $('#canvas')[0];
        const context = canvas.getContext('2d');
        // Access the webcam
        if (navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: {
              facingMode: { ideal: "environment" }
            } })
            .then(function(stream) {
                videoElement.srcObject = stream;
                $('#captureButton').on('click', function() {
                    canvas.width = videoElement.videoWidth * 1;
                    canvas.height = videoElement.videoHeight * 1;
                    context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function(blob) {
                        const file = new File([blob], "capture.png", { type: "image/png" });

                        // Display the captured image in the preview div
                        const dataURL = URL.createObjectURL(file);
                  
                        // Create a DataTransfer to add the file to the input element
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fileInput.files = dataTransfer.files;
                        console.log(fileInput)
                    }, 'image/png'); 
                    
                    stream.getTracks().forEach(function(track) {
                        return track.stop();
                    });
                    $("#modalPhoto").modal('hide')
                });
            })
            .catch(function(err) {
                console.error("Error accessing webcam: " + err);
            });
        }
    });
    
    // EDIT AMOUNT
    
    $(".amount0").change(function(){
        currency = $('.currency0').val();
        id = "{{Request::segment(2)}}";
        amount = $('.amount0').val().split(".").join("");
        cost_type = $('.cost_type_id0').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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

    syncNoneTripTypeFields();
    
    $(".amount1").change(function(){
        currency = $('.currency1').val();
        id = "{{Request::segment(2)}}";
        amount = $('.amount1').val().split(".").join("");
        cost_type = $('.cost_type_id1').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount2').val().split(".").join("");
        cost_type = $('.cost_type_id2').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount3').val().split(".").join("");
        cost_type = $('.cost_type_id3').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount4').val().split(".").join("");
        cost_type = $('.cost_type_id4').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount5').val().split(".").join("");
        cost_type = $('.cost_type_id5').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount6').val().split(".").join("");
        cost_type = $('.cost_type_id6').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount7').val().split(".").join("");
        cost_type = $('.cost_type_id7').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount8').val().split(".").join("");
        cost_type = $('.cost_type_id8').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount9').val().split(".").join("");
        cost_type = $('.cost_type_id9').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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
        id = "{{Request::segment(2)}}";
        amount = $('.amount10').val().split(".").join("");
        cost_type = $('.cost_type_id10').val();
        
        $.ajax({
            url:"../../get-currency/"+id+"/"+currency,
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

@endpush
@endsection
