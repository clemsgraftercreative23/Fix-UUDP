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
    .attachment-preview {
        display: inline-block;
        margin-right: 8px;
        margin-bottom: 8px;
        text-align: center;
        vertical-align: top;
        max-width: 120px;
    }
    .attachment-preview img {
        max-width: 90px;
        max-height: 90px;
        border: 1px solid #28a745;
        border-radius: 5px;
        display: block;
        margin-bottom: 4px;
        object-fit: cover;
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
                @php
                    $approvalTimes = [1 => null, 2 => null, 3 => null];
                    if (\Illuminate\Support\Facades\Schema::hasTable('activity_logs')) {
                        $approvalLogs = \Illuminate\Support\Facades\DB::table('activity_logs')
                            ->where('module', 'reimbursement')
                            ->where('action', 'approve')
                            ->where('subject_type', 'reimbursement')
                            ->where('subject_id', $data->id)
                            ->orderBy('created_at', 'asc')
                            ->get(['meta_json', 'created_at']);

                        foreach ($approvalLogs as $log) {
                            $meta = json_decode($log->meta_json ?? '', true);
                            $statusMeta = (int) ($meta['status'] ?? 0);
                            if (array_key_exists($statusMeta, $approvalTimes) && empty($approvalTimes[$statusMeta])) {
                                $approvalTimes[$statusMeta] = $log->created_at;
                            }
                        }
                    }
                    $formatApprovalTime = function ($statusCode) use ($approvalTimes) {
                        $raw = $approvalTimes[$statusCode] ?? null;
                        if (empty($raw)) {
                            return '-';
                        }
                        return date('d-m-Y H:i', strtotime((string) $raw));
                    };
                    $detailAttachments = collect();
                    if (\Illuminate\Support\Facades\Schema::hasTable('reimbursement_attachments')) {
                        $detailAttachments = \App\ReimbursementAttachment::where('reimbursement_id', $data->id)
                            ->where('module', 'reimbursement')
                            ->where('detail_type', 'reimbursement_details')
                            ->orderBy('id')
                            ->get()
                            ->groupBy('detail_id');
                    }
                @endphp
                  <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="inputEmail4">Mengetahui Direktur Operasional</label>
                            <input type="text" class="form-control" value="{{$data->mengetahui_op}}" readonly>
                            <small>Tanggal/Jam: {{ $formatApprovalTime(1) }}</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="inputPassword4">Mengetahui Finance</label>
                            <input type="text" class="form-control" value="{{$data->mengetahui_finance}}" readonly >
                            <small>Tanggal/Jam: {{ $formatApprovalTime(2) }}</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="inputPassword4">Menyetujui Direktur Utama</label>
                            <input type="text" class="form-control" value="{{$data->mengetahui_owner}}" readonly >
                            <small>Tanggal/Jam: {{ $formatApprovalTime(3) }}</small>
                        </div>
                        <div class="form-group col-md-12">
                            <button type="button" class="btn btn-info btn-sm" onclick="window.print()">Print</button>
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
                            <td width="200px">
                                @php
                                    $attachments = $detailAttachments->get($row->id, collect());
                                @endphp
                                @if($attachments->isNotEmpty())
                                    @foreach($attachments as $attachment)
                                        @php
                                            $fileName = $attachment->file_name;
                                            $display = $attachment->original_name ?: $fileName;
                                        @endphp
                                        <div class="attachment-preview">
                                            <a href="{{ URL::to('/') }}/images/file_bukti/{{$fileName}}" target="_blank">
                                                <img src="{{ URL::to('/') }}/images/file_bukti/{{$fileName}}" alt="{{ $display }}">
                                            </a>
                                            <div style="font-size:10px;word-break:break-all;">{{ $display }}</div>
                                        </div>
                                    @endforeach
                                @elseif(!empty($row->file))
                                    <div class="attachment-preview">
                                        <a href="{{ URL::to('/') }}/images/file_bukti/{{$row->file}}" target="_blank">
                                            <img src="{{ URL::to('/') }}/images/file_bukti/{{$row->file}}" alt="{{ $row->file }}">
                                        </a>
                                        <div style="font-size:10px;word-break:break-all;">{{ $row->file }}</div>
                                    </div>
                                @else
                                    -
                                @endif
                            </td>

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
