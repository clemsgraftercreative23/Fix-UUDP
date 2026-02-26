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
                        <h5 class="card-title">DETAIL REIMBURSEMENT DRIVER </h5><hr>
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
                        <th>Toll</th>
                        <th>Parking</th>
                        <th>Gasoline</th>
                        <th>Other</th>
                        <th>Payment Type</th>
                        <th>Total</th>
                        <th>Remark</th>
                        <th>Attachment</th>
                    </tr>
                    </thead>
                    <?php $no = 1; ?>
                    @foreach($data->drivers as $row)
                    <tr>
                        <td width="1px">{{$no++}}</td>
                        <td width="200px"><span>{{number_format($row->toll,0,',','.')}}</span></td>
                        <td width="200px"><span>{{number_format($row->parking,0,',','.')}}</span></td>
                        <td width="200px"><span>{{number_format($row->gasoline,0,',','.')}}</span></td>
                        <td width="200px"><span>{{number_format($row->others,0,',','.')}}</span></td>
                        <td width="200px"><span>{{$row->payment_type}}</span></td>
                        <td width="200px"><span>{{number_format($row->subtotal,0,',','.')}}</span></td>
                        <td width="200px"><span>{{$row->remark}}</span></td>
                        <td width="200px"><a href="{{ URL::to('/') }}/images/file_bukti/{{$row->evidence}}" target="_blank"><i class="fa fa-file"></i></a></td>

                    </tr>
                    @endforeach
                </table>
            </div>

            <div class="col-lg-12">

                    @if ($data->status == 5)
                    <hr><span style="color:#66da90;"><h5>Detail Settlement</h5></span><hr>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Method (Cash)</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$metode_cash}}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Name (Cash)</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->penerima}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Number (Cash)</label>
                                <input readonly type="text" class="form-control" name="no_rek" value="{{$data->no_rek}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank (Cash)</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{$data->bank}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Total (Cash)</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{ (!empty($data->total_cash) || $data->total_cash === 0) ? number_format($data->total_cash, 0, ',', '.') : '' }}" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Method (Fleet)</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$metode_fleet}}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Name (Fleet)</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->penerima_fleet}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Number (Fleet)</label>
                                <input readonly type="text" class="form-control" name="no_rek" value="{{$data->no_rek_fleet}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank (Fleet)</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{$data->bank_fleet}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Total (Fleet)</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($data->total_fleet,0,',','.')}}" />
                            </div>
                        </div>
                    </div>
                    @endif 

                    @if ($data->status == 3 && auth()->user()->jabatan == 'Owner')                                
                    <form action="{{url('/').'/pencairan-reimbursement/'.$data->id}}" method="POST">
                        <input type="hidden" name="employeeNo" value="{{$empNo}}">
                      	@if($cash!=0)
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Settlement Method (Cash)</label>
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
                                    <label>Akun Perkiraan (Cash)</label>
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
                                    <label>Bank Account Name (Cash)</label>
                                    <input type="text" class="form-control" name="penerima" value="{{$data->user->name}}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank Account Number (Cash)</label>
                                    <input type="text" class="form-control" name="no_rek" value="{{$data->user->bankAccount}}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank (Cash)</label>
                                    <input type="text" class="form-control" name="bank" value="{{$data->user->bankName}}" required>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total (Cash)</label>
                                    <input type="text" class="form-control" name="total_cash" value="{{rupiah($cash)}}" required readonly>
                                </div>
                            </div>
                        </div>
                      @endif

  					  @if($fleet!=0)
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Settlement Method (Fleet)</label>
                                    <select class="form-control cst-select" name="metode_fleet" required>
                                    <option value="">--Select Settlement Method--</option>
                                    @foreach($kasbank as $row)
                                        <option value="{{$row->kode_perkiraan}}">{{$row->nama}}</option>
                                    @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Akun Perkiraan (Fleet)</label>
                                    <select class="form-control cst-select" name="akun_perkiraan_fleet" required>
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
                                    <label>Bank Account Name (Fleet)</label>
                                    <input type="text" class="form-control" name="penerima_fleet" value="{{$data->user->name}}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank Account Number (Fleet)</label>
                                    <input type="text" class="form-control" name="no_rek_fleet" value="{{$data->user->bankAccount}}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank (Fleet)</label>
                                    <input type="text" class="form-control" name="bank_fleet" value="{{$data->user->bankName}}" required>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Total Fleet (Fleet)</label>
                                    <input type="text" class="form-control" name="total_fleet" value="{{rupiah($fleet)}}" required readonly>
                                </div>
                            </div>
                        </div>
                      	@endif

                        <br>
                        <hr>
                        <br>
                        <center>
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Process Settlement</button>
                        </center>
                        </form>
                        <br>
                    @endif

                  
                    <br>
                    <center>
                        @if (auth()->user()->jabatan == 'Direktur Operasional') 
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    @if($data->status == 0)
                                        @if($data->id_user != auth()->user()->id)
                                        <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                        @endif
                                    @endif
                                </form>
                            
                        @endif

                        @if ($data->status == 9 && auth()->user()->id == $data->id_user) 
                            <!--<button type="button" class="btn btn-primary click-edit"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>-->
                            <button type="button" class="btn btn-primary click-edit"  data-toggle="modal" id="{{Request::segment(2)}}">Edit</button>
                            
                        @endif
                        
                        @if (auth()->user()->jabatan == 'Finance')                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                @if($data->status == 1)
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                @endif
                            </form>
                        @endif
                        
                        @if ($data->status == 2 && auth()->user()->jabatan == 'Owner')                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
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

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>

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

</script>

@endpush
@endsection