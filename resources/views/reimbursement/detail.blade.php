@extends('template.app')
@section('content')

<style type="text/css">
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
<script src="https://cdn.rawgit.com/igorescobar/jQuery-Mask-Plugin/1ef022ab/dist/jquery.mask.min.js"></script>

<div class="page-content">

  <div class="row">
    <div class="col-xl">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">RINCIAN REIMBURSEMENT</h5><hr>
                <p>Berikut merupakan data reimbursement yang diajukan.</p><hr>
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
                <hr>
                <table border="0">
                      <thead>
                          <tr>
                              <td width="400px"><span style="color:#66da90;"><h4>Rincian Reimbursement</h4></span></td>
                              <td width="60px">Tanggal : </td>
                              <td width="240px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{$data->created_at->format('d M Y')}}"></td>
                              <td width="60px">Nomor : </td>
                              <td width="340px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{$data->no_reimbursement}}"></td>
                              <td width="60px">Total : </td>
                              <td width="340px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{number_format($data->nominal_pengajuan,0,',','.')}}"></td>
                          </tr>
                      </thead>
                  </table> 
                  <hr>
                  <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="inputEmail4">Mengetahui Direktur Operasional</label>
                            <input type="text" class="form-control" value="{{$data->mengetahui_op}}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="inputPassword4">Mengetahui Finance</label>
                            <input type="text" class="form-control" value="{{$data->mengetahui_finance}}" readonly >
                        </div>
                        <div class="form-group col-md-4">
                            <label for="inputPassword4">Menyetujui Direktur Utama</label>
                            <input type="text" class="form-control" value="{{$data->mengetahui_owner}}" readonly >
                        </div>
                    </div>

                  <hr><span style="color:#66da90;"><h5>Rincian Reimbursement</h5></span><hr>
                  <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Alokasi Kegiatan</th>
                            <th width="20%">Nominal</th>
                            <th width="37%">Inquiry Note</th>
                            <th width="37%">Attachment</th>
                        </tr>
                        </thead>
                        <?php $no=1;?>
                        @foreach($data->details as $row)
                        <tr>
                            <td width="1px">{{$no++}}</td>
                            <td width="100px"><strong>{{ $row->id_kelompok == null ? $row->note_kelompok : $row->kelompok->nama}}</strong></td>
                            <td width="200px"><strong>{{number_format($row->nominal_pengajuan,0,',','.')}}</strong></td>
                            <td width="200px"><strong>{{$row->catatan}}</strong></td>
                            <td width="200px"><a href="{{ URL::to('/') }}/images/file_bukti/{{$row->file}}" target="_blank"><i class="fa fa-file"></i></a></td>

                        </tr>
                        @endforeach
                  </table>

                  
                        <hr><br>
                        <center>
                            @if ($data->status == 0 && auth()->user()->jabatan == 'Direktur Operasional')                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <a href="{!!url('reimbursement')!!}" class="btn btn-secondary">Kembali</a>&nbsp;&nbsp;
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Setujui</button>
                                </form>
                            @endif
                            
                            @if ($data->status == 1 && auth()->user()->jabatan == 'Finance')                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <a href="{!!url('reimbursement')!!}" class="btn btn-secondary">Kembali</a>&nbsp;&nbsp;
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Setujui</button>
                                </form>
                            @endif
                            
                            @if ($data->status == 2 && auth()->user()->jabatan == 'Owner')                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <a href="{!!url('reimbursement')!!}" class="btn btn-secondary">Kembali</a>&nbsp;&nbsp;
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Setujui</button>
                                </form>
                            @endif
                        </center>

            </div>
        </div>
    </div>
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
