@extends('template.app')

@section('content')

<?php function rupiah($angka)
{
    return number_format($angka, 0, ',', '.');
} ?>

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
</style>

<div class="page-content">
    

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <a href="{!!url('pencairan-reimbursement')!!}" class="btn btn-primary" style="float:left;"><i class="fa    fa-arrow-circle-left"></i> Back </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">DETAIL REIMBURSEMENT ENTERTAINMENT</h5><hr>
                        <p>Below is the reimbursement data submitted by <b>{{$data->user->name}}</b>.</p><hr>
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
                                <input type="text" class="form-control" value="{{number_format($data->nominal_pengajuan,0,',','.')}}" readonly>
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
                                <label for="inputEmail4">Approved by Finance</label>
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
                                    switch ($data->status) {
                                        case '1':
                                            $status = "APPROVED HEAD DEPARTMENT";
                                            break;
                                        case '2':
                                            $status = "APPROVED HR GA";
                                            break;
                                        case '3':
                                            $status = "PROCESS SETTLEMENT";
                                            break;
                                        case '9':
                                            $status = "REJECTED ".$meng."";
                                            break;
                                        case '5':
                                            $status = "SETTLED";
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
                        <td>Empty zone</td>
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
                        <td>{{number_format($row->amount,0,'.',',')}}</td>
                        <td width="200px"><a href="{{ URL::to('/') }}/images/file_bukti/{{$row->evidence}}" target="_blank"><i class="fa fa-file"></i></a></td>
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
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($bdc,0,',','.')}}" />
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
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($cash,0,',','.')}}" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Total</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($cash,0,',','.')}}" />
                            </div>
                        </div>
                    </div>
                    @endif 


                    @if ($data->status == 3 && auth()->user()->jabatan == 'Owner')
                    <form action="{{url('/').'/pencairan-reimbursement/'.$data->id}}" method="POST">
                        <input type="hidden" name="employeeNo" value="{{$empNo}}">
                        <h6>BDC</h6><br>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Settlement Method</label>
                                    <select class="form-control cst-select" readonly required>
                                        <option value="">--Select Settlement Method--</option>
                                        @foreach($kasbank as $row)
                                        <option value="{{$row->kode_perkiraan}}">{{$row->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Akun Perkiraan (Accurate)</label>
                                    <select class="form-control cst-select" readonly required>
                                        <option value="">--Select Akun Perkiraan--</option>
                                        <option value="6-1051">6-1051 (Transportation)</option>
                                        <option value="6-1055">6-1055 (Transportation BIK)</option>
                                        <option value="6-1052">6-1052 (Business Trip - Domestic)</option>
                                        <option value="6-1053">6-1053 (Business Trip - Overseas)</option>
                                        <option value="6-1054">6-1054 (Travel Allowance BIK)</option>
                                        <option value="6-2021">6-2021 (Entertainment Fee)</option>
                                        <option value="1-1940">1-1940 (Temporary Payment)</option>
                                        <option value="1-1960">1-1960 (Flash Fleet BCA)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank Account Name</label>
                                    <input type="text" class="form-control" readonly value="{{$data->user->name}}" required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank Account Number</label>
                                    <input type="text" class="form-control" readonly value="{{$data->user->bankAccount}}" required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank</label>
                                    <input type="text" class="form-control" readonly  value="{{$data->user->bankName}}" required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input type="text" class="form-control" readonly value="{{number_format($bdc,0,',','.')}}" required/>
                                </div>
                            </div>

                        </div>
						@if($cash!=0)	
                        <h6>Cash</h6><br>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Settlement Method</label>
                                    <select class="form-control cst-select" name="metode_cash" required>
                                        <option value="">--Select Settlement Method--</option>
                                        @foreach($kasbank as $row)
                                        <option value="{{$row->kode_perkiraan}}">{{$row->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Akun Perkiraan (Accurate)</label>
                                    <select class="form-control cst-select" name="akun_perkiraan" required>
                                        <option value="">--Select Akun Perkiraan--</option>
                                        <option value="6-1051">6-1051 (Transportation)</option>
                                        <option value="6-1055">6-1055 (Transportation BIK)</option>
                                        <option value="6-1052">6-1052 (Business Trip - Domestic)</option>
                                        <option value="6-1053">6-1053 (Business Trip - Overseas)</option>
                                        <option value="6-1054">6-1054 (Travel Allowance BIK)</option>
                                        <option value="6-2021">6-2021 (Entertainment Fee)</option>
                                        <option value="1-1940">1-1940 (Temporary Payment)</option>
                                        <option value="1-1960">1-1960 (Flash Fleet BCA)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank Account Name</label>
                                    <input type="text" class="form-control" name="penerima" value="{{$data->user->name}}" required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank Account Number</label>
                                    <input type="text" class="form-control" name="no_rek" value="{{$data->user->bankAccount}}" required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank</label>
                                    <input type="text" class="form-control" name="bank" value="{{$data->user->bankName}}" required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input type="text" class="form-control" name="total" value="{{number_format($cash,0,',','.')}}" readonly required/>
                                </div>
                            </div>
                        </div>
                        @endif
                        <hr>

                        
                        <center>
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Proses Settlement</button>
                        </center>
                    </form>
                    <br>
                    @endif
                    <br>
                    <center>
                        @if ($data->status == 0 && auth()->user()->jabatan == 'Direktur Operasional')                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 9 && auth()->user()->id == $data->id_user) 
                            <button type="button" class="btn btn-primary"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                        @endif
                        
                        @if ($data->status == 1 && auth()->user()->jabatan == 'Finance')                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 2 && auth()->user()->jabatan == 'Owner')                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        @if ($data->status == 5 && auth()->user()->jabatan == 'Owner')
                            @if (!empty($data->accurate_synced_at))
                                <button type="button" class="btn btn-success" disabled>
                                    Accurate Synced ({{ date('d M Y H:i', strtotime($data->accurate_synced_at)) }})
                                </button>
                            @else
                                <form action="{{ route('pencairan-reimbursement.sync-accurate', $data->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">Sync Accurate</button>
                                </form>
                            @endif
                        @endif
                    </center>
                    <br><br><br>
            </div>

        </div>
    </div>
</div>


  
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.13.4/jquery.mask.min.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();   
        
        $('.currency').mask("#.##0", {
          reverse: true
        }); 
      
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?<!\.\d*)(?=(\d{3})+(?!\d))/g, ".");
        }
        
        
       var maxGroup = 10;
       var count = "{{count($detail)}}";
       
       $(".addMore").click(function(){
            count++;
            if($('body').find('.fieldGroup').length < maxGroup){
             
              var fieldHTML = '<tr class="fieldGroup"><td><input type="text" class="form-control" name="empty_zone[]" placeholder=""></td><td><input type="text" class="form-control" name="attendance[]" placeholder=""></td><td><input type="text" class="form-control" name="position[]" placeholder=""></td><td><input type="text" class="form-control" name="place[]" placeholder=""></td><td><input type="text" class="form-control" name="guest[]" placeholder=""></td><td><input type="text" class="form-control" name="guest_position[]" placeholder=""></td><td><input type="text" class="form-control" name="company[]" placeholder=""></td><td><input type="text" class="form-control" name="type[]" placeholder=""></td><td><select class="form-control" name="payment_type[]" style="width:130%"><option value="">Select...</option><option value="BDC">BDC</option><option value="Cash">Cash</option></select></td><td><input type="text" class="form-control amount-input currency amount'+count+' change-amount" name="amount[]"  placeholder=""></td><td class="file-proof"><button type="button" data-idx="1" class="btn btn-success btn-sm addFile"><i class="fa fa-upload"></i></button><button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" ><i class="fa fa-camera"></i></button><input type="file" accept="image/*" name="file[]"  style="display: none; " class="file-input"><input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;"><div id="preview_1"></div></td><td><input type="text" class="form-control" name="remark[]" placeholder="Remark"></td><td><button  type="button" name="add" id="add" class="btn btn-danger full-width remove-item">-</button></td></tr>';
              
              $('body').find('.fieldGroup:last').after(fieldHTML);
              
              $("body").on("click",".remove-item",function(){ 
                 $(this).parents(".fieldGroup").remove();
                 
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
                        canvas.width = videoElement.videoWidth * 0.3;
                        canvas.height = videoElement.videoHeight * 0.3;
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
          console.log("hehe")
          $(".addFile").on('click',function(){
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
    }) 
    $(".addCamera").on('click',function(){
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
                      canvas.width = videoElement.videoWidth * 0.3;
                      canvas.height = videoElement.videoHeight * 0.3;
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
                      // // Convert the canvas image to a data URL and display it
                      // const dataURL = canvas.toDataURL('image/png');
                      // // $('#preview_'+idx).html('<img src="' + dataURL + '" alt="Captured Image" width="320">');
                      // fileInput = $(this).parent().find(".file-input")
                      // console.log(fileInput)
                      // fileInput.value = dataURL;
                      // console.log(dataURL)
                      // // Cleanup
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

      // Capture the image when the button is clicked
      
    })
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
@endpush
@endsection
