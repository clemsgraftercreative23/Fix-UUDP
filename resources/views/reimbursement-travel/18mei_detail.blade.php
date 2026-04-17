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
                <a href="{!!url('reimbursement-travel')!!}" class="btn btn-primary" style="float:left;"><i class="fa fa-arrow-circle-left"></i> Back </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">DETAIL REIMBURSEMENT TRAVEL {{$data->travel_type}}</h5><hr>
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
                        <td class="bg-secondary">
                            <!-- {{$data->date}} -->
                            {{$item->date}}
                        </td>
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
                    <br>
                    <center>
                                                        
                            @if ($data->status == 0 && auth()->user()->jabatan == 'Direktur Operasional' && $data->id_user != auth()->user()->id)                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <a href="{!!url('reimbursement-travel/add-item')!!}/{{$data->id}}/{{$item->id}}"  class="btn btn-warning">Edit</a>
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                </form>
                            @endif
                            
                            @if ($data->status == 1 && auth()->user()->jabatan == 'Finance' && $data->id_user != auth()->user()->id)                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                </form>
                            @endif
                            
                            @if ($data->status == 2 && auth()->user()->jabatan == 'Owner' && $data->id_user != auth()->user()->id)                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                </form>
                            @endif
                            
                            @if ($data->status == 9 && auth()->user()->id == $data->id_user)                                
                                @if($data->travel_type=='Domestic')
                                    <!--
                                    <a href="{!!url('edit-travel-inquiry')!!}/{{$data->id}}"  class="btn btn-primary">Edit</a>
									-->
                      				<a href="{!!url('reimbursement-travel/add-item')!!}/{{$data->id}}/{{$item->id}}"  class="btn btn-primary">Edit</a>
                                @else
                                    <a href="{!!url('edit-travel-overseas')!!}/{{$data->id}}"  class="btn btn-primary">Edit</a>
                                @endif
                            @endif

                            @if ($data->status == 10 && auth()->user()->id == $data->id_user) 
                                @if($data->travel_type=='Domestic')
                                    <a href="{!!url('reimbursement-travel/add-item')!!}/{{$data->id}}/{{$item->id}}"  class="btn btn-primary">Edit</a>
                                @else
                                    <!-- <a href="{!!url('edit-travel-overseas')!!}/{{$data->id}}"  class="btn btn-primary">Edit</a> -->
                                    <a href="{!!url('reimbursement-travel/add-item')!!}/{{$data->id}}/{{$item->id}}"  class="btn btn-primary">Edit</a>
                                @endif
                            @endif
                        </center>
                    <br><br><br>
            </div>

        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-lg" id="formModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <form method="post" id="sample_form" action="{{ url('/') . '/reimbursement-driver/' . $data->id }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="deletedId" :model="deletedId">

        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div class="d-flex justify-content-between w-100">
                        <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle">Edit Reimbursement UUDP</h2>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <i class="material-icons">close</i>
                        </button>
                    </div>
                </div>

                <div class="modal-body py-3">
                    <div class="row my-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="userName">Nama Lengkap</label>
                                <input type="hidden" name="id_user" value="{{ Auth::user()->id }}">
                                <input type="email" class="form-control" id="userName" readonly style="border-radius: 10px;" placeholder="Nama Lengkap" value="{{ Auth::user()->name }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nikKaryawan">NIK Karyawan</label>
                                <input type="email" class="form-control" id="nikKaryawan" readonly style="border-radius: 10px;" value="{{ Auth::user()->idKaryawan }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date">Tanggal</label>
                                <input type="date" class="form-control date-picker" name="date" id="date" style="border-radius: 10px;" value="{{ $data->date }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select name="reimbursement_department_id" id="department" class="form-control">
                                    @foreach (\App\Departemen::get() as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_departemen }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="remark">Remark</label>
                                <input type="text" class="form-control" name="remark_parent" id="remark" style="border-radius: 10px;" value="{{ $data->remark }}">
                            </div>
                        </div>

                        <hr>

                        <label class="modal-title clr-green" id="reimbursementDetailsTitle">Rincian Reimbursement</label>
                        <div class="respon respon-big table-responsive">
                            <table id="dynamic_field" class="table" cellpadding="3" cellspacing="3" align="center" width="1400">
                                <thead>
                                    <tr>
                                        <th align="center" width="50">No.</th>
                                        <th align="center">Toll</th>
                                        <th align="center">Parking</th>
                                        <th align="center" width="15%">Gasoline</th>
                                        <th align="center">Other</th>
                                        <th align="center">Total</th>
                                        <th align="center" width="100px">Bukti</th>
                                        <th align="center">Remark</th>
                                        <th align="center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, i) in reimburses">
                                        <td>@{{ i + 1 }}</td>
                                        <td>
                                            <input type="text" class="form-control amount-toll" @keyup="calculate(i, item)" name="toll[]" v-model="item.toll" placeholder="Toll">
                                            <input type="hidden" class="form-control" name="id[]" v-model="item.id">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control amount-parking" @keyup="calculate(i, item)" name="parking[]" v-model="item.parking" placeholder="Parking">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control amount-gasoline" @keyup="calculate(i, item)" name="gasoline[]" v-model="item.gasoline" placeholder="Gasoline">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control amount-other" @keyup="calculate(i, item)" name="other[]" v-model="item.other" placeholder="Other">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control amount-total" @keyup="calculate(i, item)" name="total[]" readonly v-model="item.subtotal" placeholder="Total">
                                        </td>
                                        <td class="file-proof">
                                            <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                                                <i class="fa fa-upload"></i>
                                            </button>
                                            <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                                                <i class="fa fa-camera"></i>
                                            </button>
                                            <input type="file" accept="image/*" name="file[]" class="file-input" style="display: none;">
                                            <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                                            <div id="preview_1"></div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" required name="remark[]" v-model="item.remark" placeholder="Remark">
                                        </td>
                                        <td>
                                            <button v-if="i == 0" @click="addReimbursement" type="button" name="add" id="add" class="btn btn-success full-width">+</button>
                                            <button v-if="i > 0" @click="removeReimbursement(i)" type="button" name="remove" id="remove" class="btn btn-danger full-width">-</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <br>

                        <label class="modal-title" id="nominalTitle" style="color:green; font-size:10px;">Nominal</label>
                        <div class="form-group">
                            <label for="totalInquiry">Total Inquiry</label>
                            <input type="text" v-model="grandtotal" class="form-control number-format" id="totalInquiry" style="border-radius: 10px;" name="total_pengajuan" readonly placeholder="">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">BATALKAN</button>
                        <button type="submit" class="btn btn-primary">AJUKAN SEKARANG</button>
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
                    <button class="btn btn-secondary" type="button" data-dismiss="modal" aria-label="Close">Back</button>
                    <button class="btn btn-primary" type="submit">Save</button>
                </div>
                  
            </div>
        </form>
    </div>
</div>
<!-- End Modal Change-->
  
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
