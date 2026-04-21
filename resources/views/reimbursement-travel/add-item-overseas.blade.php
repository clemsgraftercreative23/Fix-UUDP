@extends('template.app')

@section('content')

<style>
    /* Hanya modal kamera: jangan timpa .modal-dialog global (merusak #previewImageModal). */
    #modalPhoto .modal-dialog {
        max-width: 100%;
        margin: 0 auto;
    }

    #modalPhoto .modal-content {
        max-height: 100vh; 
        overflow-y: auto; 
    }

    #modalPhoto .modal-body {
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
    #rt-travel-item-pane.rt-pane-loading {
        opacity: 0.55;
        pointer-events: none;
    }
    button.nav-link.travel-item-link {
        border: none;
        cursor: pointer;
        font: inherit;
        text-align: inherit;
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

function rate_input($angka){
    if ($angka === null || $angka === '') return '';
    if (!is_numeric($angka)) return (string) $angka;
    return number_format((float) $angka, 2, ',', '.');
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
    <!--@if(auth()->user()->jabatan=='karyawan')
    <form action="{!!url('reimbursement-travel/update-item/'.Request::segment(3).'/'.Request::segment(4).'')!!}" method="POST" enctype="multipart/form-data" style="overflow-y: auto;">
    @else
    <form action="{!!url('reimbursement-travel/update-item-approval/'.Request::segment(3).'/'.Request::segment(4).'')!!}" method="POST" enctype="multipart/form-data" style="overflow-y: auto;">
    @endif -->
  
    @if($data['0']->status!=9)
    	<form action="{!!url('reimbursement-travel/update-item/'.Request::segment(3).'/'.Request::segment(4).'')!!}" method="POST" enctype="multipart/form-data" style="overflow-y: auto;">
    @else
        <form id="reimbursement-form" action="{!!url('reimbursement-travel/update-item-reject/'.Request::segment(3).'/'.Request::segment(4).'')!!}" method="POST" enctype="multipart/form-data" style="overflow-y: auto;">
    @endif
          
        @csrf 
        <div class="row">
            <div class="col-xl">
                <div class="card">
                    <div class="card-body">
                        
                        <div class="d-flex justify-content-between w-100"><h2 id="exampleModalCenterTitle" class="modal-title maintitle clr-green mb-0">REIMBURSEMENT UUDP - TRAVEL {{strtoupper($travel_type)}}</h2> 
                        <a href="{!!url('reimbursement-travel')!!}" aria-label="Close" class="close"><i class="material-icons">close</i></a></div>
                        <hr>
                        
                        <div class="row">
                            <input type="hidden" name="travel_type" value="{{$travel_type}}">
                            <div class="col-md-3">
                                <label for="">Employee</label>
                                <input type="text" class="form-control" readonly value="{{auth()->user()->name}}">
                                <input type="hidden" class="form-control" name="id_editor" value="{{auth()->user()->id}}">
                                <input type="hidden" class="form-control" name="id_user" value="{{$data['0']->id_user}}">
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
                                @php
                                    $travelTripRatesSorted = collect($travel_trip ?? [])->values()->sort(function ($a, $b) {
                                        $aIdr = strtoupper((string) ($a->currency ?? '')) === 'IDR';
                                        $bIdr = strtoupper((string) ($b->currency ?? '')) === 'IDR';
                                        if ($aIdr !== $bIdr) {
                                            return $aIdr ? -1 : 1;
                                        }
                                        return ((int) ($a->id ?? 0)) <=> ((int) ($b->id ?? 0));
                                    })->values();
                                @endphp
                                @foreach($travelTripRatesSorted as $row)
                                <div class="row fieldGroup">
                                    <input type="hidden" name="id_rate" class="id_rate" value="{{ $row->id }}">
                                    <div class="col-md-3">
                                        <label for="">Currency</label>
                                        <input type="text" class="form-control" name="currency_rate[]" value="{{ $row->currency }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="">Exchange Rate</label>
                                        <input type="text" inputmode="decimal" class="form-control exchange-rate-input" name="rate[]" value="{{ rate_input($row->rate) }}">
                                    </div>
                                    <div class="col-md-3">
                                        @if($loop->first)
                                        <a class="btn btn-primary btn-sm addMore" style="color:white;margin-top:35px;cursor:pointer"><i class="fa fa-plus"></i></a>
                                        @else
                                        <a class="btn btn-danger btn-sm remove-currency" style="color:white;margin-top:35px;cursor:pointer;background:#f05154"><i class="fa fa-trash"></i></a>
                                        @endif
                                    </div>
                                </div>
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
                        <div id="rt-travel-item-pane"
                             v-once
                             data-main-id="{{ $data['0']->id }}"
                             data-travel-id="{{ $data_travel['0']->id }}"
                             data-rt-href-prefix="{!! url('reimbursement-travel/add-item/'.$data['0']->id.'/') !!}">
                            @include('reimbursement-travel.partials.travel-item-pane')
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
              <h5 class="modal-title" id="exampleModalLabel">Upload Gambar</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <i class="material-icons">close</i>
              </button>
          </div>
          <div class="modal-body">
            <video id="videoElement" autoplay style="width: 100%"></video>
            <canvas id="canvas"></canvas>
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button id="captureButton" class="btn btn-success">Capture Image</button>
          </div>
      </div>
  </div>
</div>

<!-- End Modal -->

<!-- Modal Preview Image -->
<div class="modal fade" id="previewImageModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="background: transparent; border: 0; box-shadow: none;">
            <div class="modal-header" style="border: 0;">
                <h5 class="modal-title text-white">Preview Gambar</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1; font-size: 2rem;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center pt-0" style="max-height: 80vh; overflow:auto;">
                <img id="previewImageModalSrc" src="" alt="Preview" style="max-width: 100%; max-height: 80vh; border-radius: 8px;">
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

    $(".warning-upload").hide();
    
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
    $(document).on("input", "#rt-travel-item-pane #start_time, #rt-travel-item-pane #end_time", function() {
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

    window.rtNumberWithCommas = numberWithCommas;
    window.rtTotalNominalTravel = total_nominal;
    window.rtCalculateTimeDifference = calculateTimeDifference;

    $(document).on('change', '#rt-travel-item-pane input[name="amount[]"]', function () {
        var $tr = $(this).closest('tr');
        var currency = $tr.find('select[name="currency[]"]').val();
        var id = "{{ Request::segment(3) }}";
        var amountStr = (($(this).val() || '').split('.').join(''));
        var amount = parseFloat(amountStr) || 0;
        var cost_type = $tr.find('select[name="cost_type_id[]"]').val();
        if (!currency) {
            return;
        }
        $.ajax({
            url: "../../../get-currency/" + id + "/" + currency,
            dataType: "json",
            success: function (data) {
                var val = data.data * amount;
                $tr.find('input[name="idr_rate[]"]').val(numberWithCommas(val));
                total_nominal();
                var tax;
                if (cost_type == 3) {
                    tax = val * 2 / 100;
                    $tr.find('input[name="tax[]"]').val(numberWithCommas(tax));
                } else {
                    $tr.find('input[name="tax[]"]').val(numberWithCommas(0));
                }
            }
        });
    });

    $(document).on('change', '#rt-travel-item-pane select[name="cost_type_id[]"]', function () {
        var cost_type = $(this).val();
        var $tr = $(this).closest('tr');
        var $idr = $tr.find('input[name="idr_rate[]"]');
        var val = (($idr.val() || '').split('.').join(''));
        if (cost_type == 3) {
            var tax = parseFloat(val) * 2 / 100;
            $tr.find('input[name="tax[]"]').val(numberWithCommas(isNaN(tax) ? 0 : tax));
        } else {
            $tr.find('input[name="tax[]"]').val(numberWithCommas(0));
        }
    });
    
    // $("#trip_type_id").change(function(){
        
    //     id = $('#trip_type_id').val();
        
    //     $.ajax({
    //         url:"../../../get-trip-type/"+id,
    //         dataType:"json",
    //         success:function(data){
    //             val = data.data;
    //             $('.allowance ').val(numberWithCommas(val));
    //             total_nominal();
    //         }
    //     })
    // });

    const notStayHotelConditionId = @json($not_stay_hotel_condition_id);

    function syncNoneTripTypeFields() {
        const tripTypeId = $('#trip_type_id').val();
        const hotelSelect = $('#hotel_condition_id');
        const startInput = $('#start_time');
        const endInput = $('#end_time');

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

    $(document).on('change', '#rt-travel-item-pane #trip_type_id', function () {
	    const id = $('#trip_type_id').val();
        syncNoneTripTypeFields();
        if (!id) {
            $('.allowance').val(numberWithCommas(0));
            total_nominal();
            return;
        }

	    $.ajax({
	        url: "../../../get-trip-type/" + id,
	        dataType: "json",
	        success: function (data) {
                allowance = data.data[0].allowance;
                type = data.data[0].type;
                currency = data.data[0].currency;
              
              	// Ambil semua input currency dan rate
	            const currencyInputs = document.querySelectorAll('[name="currency_rate[]"]');
	            const rateInputs = document.querySelectorAll('[name="rate[]"]');
	            let usdRate = 1; // Default
	            // Cari nilai rate untuk USD
	            currencyInputs.forEach((input, index) => {
	                if (input.value.trim().toUpperCase() === 'USD') {
	                    let rawRate = rateInputs[index]?.value || '1';
                        usdRate = parseExchangeRateNumber(rawRate) || 1;
	                }
	            });
              
                if(type=='INTERNATIONAL') {
                    allowanceInIDR = allowance * usdRate;
                } else {
                  if(currency=='IDR') {
					allowanceInIDR = allowance;
                  } else {
					allowanceInIDR = allowance * usdRate;
                  }
                }
	            
	            $('.allowance').val(numberWithCommas(allowanceInIDR));
	            // Jalankan fungsi total_nominal
	            total_nominal();
	        }
	    });
    });

    syncNoneTripTypeFields();

    
    $(document).on('change', '.change-rate', function(){
        total_nominal();
    });
    
    $(document).on('change', '#rt-travel-item-pane .change-type', function(){
        var tripType = $(this).val();
        if (!tripType) {
            $('.allowance').val(numberWithCommas(0));
            total_nominal();
            return;
        }

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
        
    });
    
    $(function() {
      $('.currency').maskMoney({
        thousands: '.',
        decimal: ',',
        allowZero: true,
        allowNegative: true,
        precision: 2
      });
      $('.currency').maskMoney('mask');
    });

    $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:2});
    
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

    function normalizeEuropeanNumberString(raw) {
        var x = String(raw || '').trim().replace(/\s/g, '');
        if (!x) return '0';
        var lastC = x.lastIndexOf(',');
        var lastD = x.lastIndexOf('.');
        if (lastC > lastD) {
            x = x.replace(/\./g, '').replace(',', '.');
            return (x.replace(/[^\d.]/g, '') || '0');
        }
        x = x.replace(/,/g, '');
        var idx = x.lastIndexOf('.');
        if (idx === -1) {
            return (x.replace(/[^\d]/g, '') || '0');
        }
        var intRaw = x.slice(0, idx);
        var frac = x.slice(idx + 1).replace(/\D/g, '');
        var intPart = intRaw.replace(/\./g, '');
        if (frac.length === 3 && /^\d{3}$/.test(frac) && intPart.length >= 1) {
            return intPart + frac;
        }
        return (intPart || '0') + '.' + frac;
    }

    function sanitizeExchangeRateInput(value, finalize) {
        var s = (value || '').toString().trim().replace(/\s/g, '');
        if (!s) return '';
        var lastC = s.lastIndexOf(',');
        var lastD = s.lastIndexOf('.');
        if (lastC > lastD) {
            s = s.replace(/\./g, '').replace(',', '.');
        } else {
            s = s.replace(/,/g, '');
        }
        s = s.replace(/[^0-9.]/g, '');
        var firstDot = s.indexOf('.');
        if (firstDot !== -1) {
            s = s.slice(0, firstDot + 1) + s.slice(firstDot + 1).replace(/\./g, '');
        }
        var parts = s.split('.');
        var intPart = parts[0] || '';
        var decPart = parts[1] || '';
        if (decPart.length > 2) {
            decPart = decPart.slice(0, 2);
        }
        if (finalize && intPart.length > 1) {
            intPart = intPart.replace(/^0+/, '') || '0';
        }
        if (finalize && parts.length > 1 && parts[1] === '' && s.slice(-1) === '.') {
            return intPart;
        }
        return parts.length > 1 ? (intPart + '.' + decPart) : intPart;
    }

    function normalizeExchangeRateValue(value) {
        var s = sanitizeExchangeRateInput(value, true);
        if (s === '') return '0,00';
        var canonical = normalizeEuropeanNumberString(s);
        var n = parseFloat(canonical);
        if (isNaN(n)) return '0,00';
        n = Math.round(n * 100) / 100;
        return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function parseExchangeRateNumber(value) {
        var canonical = normalizeEuropeanNumberString(String(value || '').trim());
        var n = parseFloat(canonical);
        if (isNaN(n)) return 0;
        return Math.round(n * 100) / 100;
    }

    $(document).on('input', 'input.exchange-rate-input[name="rate[]"]', function () {
        this.value = sanitizeExchangeRateInput(this.value, false);
    });

    $(document).on('blur', 'input.exchange-rate-input[name="rate[]"]', function () {
        this.value = normalizeExchangeRateValue(this.value);
    });
    
    $(".addMore").click(function(){
        $("#action_button").prop("disabled", false);
        $("#action_button_draft").prop("disabled", false);
        $(".warning-upload").hide();
        i++;
        if($('body').find('.fieldGroup').length < maxGroup){
         
          var fieldHTML = '<br><div class="row fieldGroup"><input type="hidden" class="id_rate" name="id_rate" value="0"><div class="col-md-3"><label for="">Currency</label><input type="text" class="form-control" name="currency_rate[]"></div><div class="col-md-6"><label for="">Exchange Rate</label><input type="text" inputmode="decimal" class="form-control exchange-rate-input" name="rate[]"></div><div class="col-md-3"><a class="btn btn-danger btn-sm remove-currency" style="color:white;margin-top:35px;cursor:pointer;background:#f05154"><i class="fa fa-trash"></i></a></div></div>';
          $('body').find('.fieldGroup:last').after(fieldHTML);
          $(function() {
            $('.currency').maskMoney({
              thousands: '.',
              decimal: ',',
              allowZero: true,
              allowNegative: true,
              precision: 2
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
        let rate = normalizeExchangeRateValue($group.find('input[name="rate[]"]').val());
        let currency = $group.find('input[name="currency_rate[]"]').val();

        
        $.ajax({
            url: '../../../delete-currency-options',
            type: 'POST',
            data: {
                reim_id: reim_id,
                id_rate: id_rate,
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
    var ct = "{{count($travel_detail)}}";

    window.rtTravelDetailMaxGroup = maxGroup;

    window.rtTravelAppendDetailRow = function (options) {
        options = options || {};
        var silent = !!options.silent;
        var $root = $('#rt-travel-item-pane');
        if (!$root.length) {
            $root = $('body');
        }
        var currentLen = $root.find('.fieldGroupDetail').length;
        if (currentLen >= maxGroup) {
            if (!silent) {
                alert('Maximum '+maxGroup+' groups are allowed.');
            }
            return false;
        }
        if (!silent) {
            $("#action_button").prop("disabled", true);
            $("#action_button_draft").prop("disabled", true);
            $(".warning-upload").show();
            i++;
        }
        count++;
        ct++;
        var rowTemplate = $.trim($('#rt-detail-row-template').html() || '');
        if (!rowTemplate) {
            return false;
        }
        var fieldHTML = rowTemplate
            .replace(/__IDX__/g, String(count))
            .replace(/__PREVIEW__/g, String(ct));
        $root.find('.fieldGroupDetail:last').after(fieldHTML);
        $(function() {
            $('.currency').maskMoney({
              thousands: '.',
              decimal: ',',
              allowZero: true,
              allowNegative: true,
              precision: 2
            });
            $('.currency').maskMoney('mask');
        });
        return true;
    };

    $(".addMoreDetail").click(function(){
        window.rtTravelAppendDetailRow({});
    });
    
     $("body").on("click",".remove-detail",function(){ 
         $("#action_button").prop("disabled", false);
         $("#action_button_draft").prop("disabled", false);
         $(".warning-upload").hide();

         var $row = $(this).closest(".fieldGroupDetail");
         var $tbody = $row.closest("tbody");
         var rowCount = $tbody.find('.fieldGroupDetail').length;

         if (rowCount <= 1) {
             $row.find('input[name="id_detail[]"]').val('');
             $row.find('select[name="cost_type_id[]"]').val('');
             $row.find('input[name="destination[]"]').val('');
             $row.find('select[name="currency[]"]').val('');
             $row.find('input[name="amount[]"]').val('');
             $row.find('input[name="idr_rate[]"]').val('');
             $row.find('input[name="tax[]"]').val('0');
             $row.find('select[name="payment_type[]"]').val('');
             $row.find('input.file-input[type="file"], input.camera-input[type="file"]').val('');
             $row.find('[id^="preview_"]').empty();
         } else {
             $row.remove();
         }

         total_nominal();
     });
    
    // Objek untuk menyimpan status upload di setiap row
      let uploadStatus = {};

            function getPreviewDivFromRow(row) {
                return row.find('[id^="preview_"]').first();
            }

            function createPreviewImage(src) {
                return $('<img>')
                    .attr('src', src)
                    .attr('data-preview-src', src)
                    .addClass('preview-thumbnail')
                    .css({
                        maxWidth: '75px',
                        maxHeight: '75px',
                        border: '2px solid #28a745',
                        borderRadius: '5px',
                        marginTop: '5px',
                        cursor: 'pointer'
                    });
            }

            function bindExistingPreviewThumbnails() {
                $('[id^="preview_"] img').each(function () {
                    const src = $(this).attr('src');
                    if (!src || src.indexOf('flaticon.com') !== -1) return;
                    $(this)
                        .addClass('preview-thumbnail')
                        .attr('data-preview-src', src)
                        .css('cursor', 'pointer');
                });
            }

            bindExistingPreviewThumbnails();

            $('body').on('click', '.preview-thumbnail', function () {
                var src = $(this).attr('data-preview-src') || $(this).attr('src');
                if (!src) return;
                var safeSrc = src;
                var isDataOrBlob = /^data:|^blob:/i.test(src);
                if (!isDataOrBlob) {
                    var sep = src.indexOf('?') === -1 ? '?' : '&';
                    safeSrc = src + sep + 'v=' + Date.now();
                }
                var $modalImg = $('#previewImageModalSrc');
                $modalImg.off('error.rtPreview').on('error.rtPreview', function () {
                    $('#previewImageModal').modal('hide');
                    if (!isDataOrBlob && src) {
                        window.open(src, '_blank');
                    }
                });
                $modalImg.attr('src', safeSrc);
                $('#previewImageModal').modal('show');
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

      // Fungsi untuk menangani upload file
      $("body").on("click", ".addFile", function () {
        let btn = $(this);
        let row = btn.closest("tr");
        let fileInput = row.find(".file-input");

        fileInput.click();

        fileInput.off("change").on("change", function (event) {
          var file = event.target.files[0];

          if (file) {
            $("#action_button").prop("disabled", false);
            $("#action_button_draft").prop("disabled", false);
            $(".warning-upload").hide();

            let previewDiv = getPreviewDivFromRow(row);

            let fileType = file.type;

            if (fileType.startsWith("image/")) {
              // Preview gambar
              var reader = new FileReader();
              reader.onload = function (e) {
                                previewDiv.append(createPreviewImage(e.target.result));
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
                            let previewDiv = getPreviewDivFromRow(row);
                            previewDiv.append(createPreviewImage(imageURL));

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
        grandtotal: 0
      },
      mounted() {
        // this.initSelectForm()
        self = this
        $(".idr-rate-input").maskMoney({ thousands:'.', decimal:',', precision:2});
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

        $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:2, allowZero: true, affixesStay: false, allowNegative: true});
            $('.amount-input').on('change', (event) => {
            self.reimburses[self.reimburses.length - 1].details[0].amount = ($(event.target).val());
            self.changeAmount(0);
            self.calculateTotal(0,0)
        });
        // $('.number-format').maskMoney({ thousands:'.', decimal:',', precision:2});
      
      },
      methods : {
        changeAmount(i) {

        },
        getRate(currency, amt) {
            self = this;
            rate = self.rates.filter(a => a.code == currency)[0].rate
            return parseInt(amt.replaceAll(".","")) * parseInt(`${rate}`.replaceAll(".",""));
             
        },
        // initSelectForm() {
        //   $(".addFile").on('click',function(){
        //     $(this).parent().find(".file-input").click();
        //     $(this).parent().find(".file-input").change(function(event) {
        //         var file = event.target.files[0];
                
        //         if (file) {
        //             var reader = new FileReader();
                    
        //             reader.onload = function(e) {
        //                 $('#preview_'+$(this).parent().find(".addFile").data('idx')).empty(); // Clear previous preview
                        
        //                 var img = $('<img>');
        //                 img.attr('src', e.target.result);
        //                 img.css({ maxWidth: '100%', maxHeight: '200px' }); // Adjust height as needed
        //                 $('#preview_'+$(this).parent().find(".addFile").data('idx')).append(img);
        //             };
                    
        //             reader.readAsDataURL(file);
        //         }
        //     })
        //     }) 
        //     $(".addCamera").on('click',function(){
        //     idx = $(this).data('idx')
        //     fileInput = $(this).parent().find(".file-input")[0]; 
        //     $("#modalPhoto").modal('show')
        //     const videoElement = $('#videoElement')[0];
        //     const canvas = $('#canvas')[0];
        //     const context = canvas.getContext('2d');

        //     // Access the webcam
        //     if (navigator.mediaDevices.getUserMedia) {
        //         navigator.mediaDevices.getUserMedia({ video: {
        //             facingMode: { ideal: "environment" }
        //         } })
        //             .then(function(stream) {
        //                 videoElement.srcObject = stream;
        //                 $('#captureButton').on('click', function() {
        //                     canvas.width = videoElement.videoWidth * 0.3;
        //                     canvas.height = videoElement.videoHeight * 0.3;
        //                     context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
        //                     canvas.toBlob(function(blob) {
        //                         const file = new File([blob], "capture.png", { type: "image/png" });

        //                         // Display the captured image in the preview div
        //                         const dataURL = URL.createObjectURL(file);
                            
        //                         // Create a DataTransfer to add the file to the input element
        //                         const dataTransfer = new DataTransfer();
        //                         dataTransfer.items.add(file);
        //                         fileInput.files = dataTransfer.files;
        //                         console.log(fileInput)
        //                     }, 'image/png'); 
                            
        //                     stream.getTracks().forEach(function(track) {
        //                         return track.stop();
        //                     });
        //                     $("#modalPhoto").modal('hide')

        //                 });
        //             })
        //             .catch(function(err) {
        //                 console.error("Error accessing webcam: " + err);
        //             });
        //     }

        //     // Capture the image when the button is clicked
            
        //     })
        // },
        
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
              // self.initSelectForm();

              $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:2, allowZero: true, affixesStay: false, allowNegative: true});
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
              // self.initSelectForm();
              $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:2, allowZero: true, affixesStay: false, allowNegative: true});
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

    $(document).on('blur', 'input.exchange-rate-input[name="rate[]"]', function () {
        let $group = $(this).closest('.fieldGroup');

        let id_rate = $group.find('.id_rate').val();
        let rate = normalizeExchangeRateValue($(this).val());
        $(this).val(rate);
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
<script src="{{ asset('js/reimbursement-travel-tabs.js') }}"></script>

@endpush
@endsection
