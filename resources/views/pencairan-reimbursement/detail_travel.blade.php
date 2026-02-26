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
                        <h5 class="card-title">DETAIL REIMBURSEMENT TRAVEL</h5><hr>
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
                <table class="table table-bordered mb-2">
                    <tr>
                        @foreach ($data->rates as $item)
                        <th>{{$item->currency}} Rate</th>
                        <td class="bg-secondary">{{number_format($item->rate,0,',','.')}}</td>                        
                        @endforeach
                    </tr>
                </table>
                
                @foreach ($data->travels as $item)
                      
                <table class="table table-bordered mb-2">
                    <tr>
                        <th>Transaction Date</th>
                        <td class="bg-secondary">{{$data->date}}</td>
                        <th>Trip Type</th>
                        <td class="bg-secondary">{{$item->tripType->name}}</td>
                        <th>Hotel At</th>
                        <td class="bg-secondary">{{$item->hotelCondition->name}}</td>
                        <th>Allowance</th>
                        <td class="bg-secondary">{{$item->tripType->currency}} {{number_format($item->allowance,0,',','.')}}</td>
                        <th>Allowance (IDR)</th>
                        <td class="bg-secondary">
                            @php
                                $currency = App\TravelTripRate::where('reimbursement_id',$data->id)->where('currency',$item->tripType->currency)->first();
                                if ($currency) {
                                    $currency = $currency->rate;
                                }

                                if (!$currency && $item->tripType->currency == "IDR") {
                                    $currency = 1;
                                }

                                if (!$currency && $item->tripType->currency == "USD") {
                                    $currency = 16400;
                                }

                                

                                echo number_format($item->allowance * $currency,0,',','.');
                            @endphp
                        </td>
                    </tr>
                    <tr>
                        <th>Purpose</th>
                        <td class="bg-secondary">{{$item->purpose}}</td>
                        <th>Start</th>
                        <td class="bg-secondary">{{$item->start_time}}</td>
                        <th>Arrival</th>
                        <td class="bg-secondary">{{$item->end_time}}</td>
                        <th>Travel Time</th>
                        <td class="bg-secondary" colspan="3">
                             <?php 
                                $start = strtotime($item->start_time);
                                $end = strtotime($item->end_time);
                                $minutes = ($end - $start) / 60;
                                $hours = floor($minutes / 60).' Hour and '.($minutes -   floor($minutes / 60) * 60).' Minutes';
                                echo $hours;
                            ?>
                        </td>
                    </tr>
                </table>
                <table class="table table-bordered mb-2">
                <thead>
                    <th>Cost Type</th>
                    <th>Destination</th>
                    <th>Currency</th>
                    <th>Amount</th>
                    <th>Amount (IDR)</th>
                    <th>Payment</th>
                    <th>Evidence</th>
                </thead>
                @foreach ($item->details as $dt)
                    
                <tr>
                    <td>{{$dt->costType->name}}</td>
                    <td>{{$dt->destination}}</td>
                    <td>{{$dt->currency}}</td>
                    <td>{{$dt->currency}} {{number_format($dt->amount,0,',','.')}}</td>
                    <td>{{number_format($dt->idr_rate,0,',','.')}}</td>
                    
                    <td>{{$dt->payment_type}}</td>
                    <td><a href="{{ URL::to('/') }}/images/file_bukti/{{$dt->evidence}}" target="_blank"><i class="fa fa-file"></i></a></td>
                </tr>
                @endforeach

                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="bg-secondary text-right" colspan="6">{{number_format($item->total,0,',','.')}}</td>
                    </tr>
                </tfoot>
                </table>
                <hr>
                @endforeach

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

                    <h6>ALLOWANCE</h6>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Method</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$metode_allowance}}" />
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
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($allowance,0,',','.')}}" />
                            </div>
                        </div>
                    </div>
                    <hr>

                    <h6>CASH</h6>
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
                                <input readonly type="text" class="form-control" name="bank" value="{{$data->bank}}" />
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
                        <h6>BDC</h6>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Settlement Method</label>
                                    <select class="form-control cst-select" required disabled>
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
                                    <select class="form-control cst-select" required disabled>
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
                                    <input type="text" class="form-control" value="{{$data->user->name}}" readonly required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank Account Number</label>
                                    <input type="text" class="form-control" value="{{$data->user->bankAccount}}" readonly required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank</label>
                                    <input type="text" class="form-control" value="{{$data->user->bankName}}" readonly required/>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input type="text" class="form-control"  value="{{number_format($bdc,0,',','.')}}" readonly required/>
                                </div>
                            </div>

                        </div>

                        <hr>
						@if($allowance!=0)		
                        <h6>ALLOWANCE</h6>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Settlement Method</label>
                                    <select class="form-control cst-select" name="metode_allowance" required>
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
                                    <select class="form-control cst-select" name="akun_perkiraan_allowance" required>
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
                                    <input type="text" class="form-control" name="total_allowance" value="{{number_format($allowance,0,',','.')}}" required/>
                                </div>
                            </div>
                        </div>
                        @endif
						
                        @if($cash!=0)
                        <hr>
                        <h6>CASH</h6>
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
                                    <select class="form-control cst-select" name="akun_perkiraan_cash" required>
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
                                    <input type="text" class="form-control" name="total_cash" value="{{number_format($cash,0,',','.')}}" required/>
                                </div>
                            </div>
                        </div>
                        @endif

                        <br>
                        <hr>
                        <br>
                        <center>
                            @csrf @method('PUT')
                            
                            <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Process Settlement</button>
                        </center>
                    </form>
                    <br>
                    @else
                    
                    @endif
                    <br>
                    <center>
                                                        
                            @if ($data->status == 0 && auth()->user()->jabatan == 'Direktur Operasional')                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                </form>
                            @endif
                            
                            @if ($data->status == 1 && auth()->user()->jabatan == 'Finance')                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                </form>
                            @endif
                            
                            @if ($data->status == 2 && auth()->user()->jabatan == 'Owner')                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                </form>
                            @endif
                            
                            @if ($data->status == 9 && auth()->user()->id == $data->id_user)                                
                                @if($data->travel_type=='Domestic')
                                    <a href="{!!url('edit-travel-inquiry')!!}/{{$data->id}}"  class="btn btn-primary">Edit</a>
                                @else
                                    <a href="{!!url('edit-travel-overseas')!!}/{{$data->id}}"  class="btn btn-primary">Edit</a>
                                @endif
                            @endif
                        </center>
                    <br><br><br>
            </div>

        </div>
    </div>
</div>

  
@push('scripts')
<script type="text/javascript">
  $(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();   
  });

  $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});

  $(document).on('click', '.edit', function(){
      var id = $(this).attr('id');
      console.log(id);
      $.ajax({
      url : '{{ route("getPertanggungjawaban") }}',
        type: 'POST',
        headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data:{id:id},
        success:function(data){
        $('#detailPertanggungjawaban').html(data)
        },
      });
  });

  $(document).on('click', '.change', function(){
      var id = $(this).attr('id');
      console.log(id);
      $.ajax({
      url : '{{ route("changePertanggungjawaban") }}',
        type: 'POST',
        headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data:{id:id},
        success:function(data){
        $('#changePertanggungjawaban').html(data)
        },
      });
  });

  $('select[name="metode"]').on('change', function(){
        var id = $(this).val();
        if(id) {
            $.ajax({
                url: '../getMetode/'+id,
                type:"GET",
                dataType:"json",
                beforeSend: function(){
                    $('.loader').css("visibility", "visible");
                },

                success:function(data) {

                    $('select[name="sumber"]').empty();

                    $.each(data, function(key, value){

                        $('select[name="sumber"]').append('<option value="'+ key +'">' + value + '</option>');

                    });
                },
                complete: function(){
                    $('.loader').css("visibility", "hidden");
                }
            });
        } else {
            $('select[name="sumber"]').empty();
        }

  });

  $('#form_finish').on('submit', function(event){
      event.preventDefault();
      $("#finish_button").prop("disabled", true);
      
      $.ajax({
           url:"../../pertanggungjawaban/finish",
           method:"POST",
           data:new FormData(this),
           contentType: false,
           cache: false,
           processData: false,
           dataType:"json",
           beforeSend: function(){
           $('.loader').css("visibility", "visible");
           $("#finish_button").prop("disabled", true);
           },
           success:function(data)
           {
           var html = '';
           if(data.errors)
           {
           alert('Data gagal disimpan!');
           }
           if(data.success)
           {
           alert('Data berhasil disimpan!');
           location.reload();
           }
           $('#form_result').html(html);
           $('#formModal').modal('hide');
           
           },
           complete: function(){
           $('.loader').css("visibility", "hidden");
           }
       });
        
    });



</script>
@endpush
@endsection
