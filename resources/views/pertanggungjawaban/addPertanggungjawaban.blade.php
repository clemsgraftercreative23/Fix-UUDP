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
            <div class="d-flex justify-content-between">
                <h5 class="card-title">INSERT PERTANGGUNGJAWABAN</h5>

                <button type="button" class="close" onclick="history.back()">
                      <i class="material-icons">close</i>
                </button>
            </div> 
            <hr>
                <p>Sebelum melakukan input data, mohon periksa kembali rincian dari data pengajuan terkait!</p>

                <hr>
                <div class="row">
                    <div class="col-md-6 ">
                        <label>Buat Pertanggungjawaban</label>
                    </div>
                    <div class="col-md-6 ">
                        <div class="row">
                            <div class="col-sm-6 ">
                            Tanggal : <input type="text" class="form-control cst-select my-2" readonly   value="{{date('d-m-Y', strtotime($pengajuan['0']->created_at))}}" >
                            </div>
                            <div class="col-sm-6">
                            Nomor : <input type="text" class="form-control cst-select my-2" readonly   value="{{$pengajuan['0']->no_pengajuan}}">  
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- <table border="0">
                      <thead>
                          <tr>
                              <td width="400px"><label>Buat Pertanggungjawaban</label></td>
                              <td width="60px">Tanggal : </td>
                              <td width="240px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{date('d-m-Y', strtotime($pengajuan['0']->created_at))}}"></td>
                              <td width="60px">Nomor : </td>
                              <td width="340px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{$pengajuan['0']->no_pengajuan}}"></td>
                          </tr>
                      </thead>
                  </table> -->
                  <hr>
                <div class="respon table-responsive">

                
                  <table class="w-100" style="border-collapse:collapse; min-width:600px">
                      <thead>
                          <tr>
                              <td  class="dotted">
                                  <div class="p-3">
                                      <b class="h5 clr-green">Total Dana Diterima : <br>
                                      Rp. {{number_format($pengajuan['0']->nominal_pengajuan,2,',','.')}}</b> 
</div>
                              </td>
                              <td   class="dotted">
                                  <div class="p-3">
                                      <b class="h5 clr-green">Sudah Dilaporkan : <br> Rp. {{number_format($dana_dilaporkan['0']->dana,2,',','.')}} </b> 
</div>
                              </td>
                              <td  class="dotted">
                                  
                              <div class="p-3">
                                      
                                      <?php $sum = $pengajuan['0']->nominal_pengajuan - $dana_dilaporkan['0']->dana;?>
                                      <b class="h5 clr-green"> Kelebihan / Kekurangan : <br>  Rp. {{number_format($sum,2,',','.')}} 
                                        <!-- @if($sum > 0) (Sisa) @elseif($sum < 0) (Kurang) @endif -->
</b> 

</div>
                              </td>
                          </tr>
                      </thead>
                  </table>
                  </div>
                  <div class="py-2"></div>
                  <hr><label>Rincian Kegiatan</label> 
                   <div class="respon table-responsive">
                   <table class="table table-bordered tbl-mini">
                          <thead>
                            <tr>
                                <th >No</th>
                                <th >Induk Kegiatan</th>
                                <th >Sub Kegiatan</th>
                                <th >Nominal Inquiry</th>
                                <th >Keterangan</th>
                                <th >Aksi</th>
                            </tr>
                          </thead>
                          <tbody>
                                <?php $no=1;?>
                                @foreach($list_pengajuan as $row)
                                <tr>
                                    <td width="1px">{{$no++}}</td>
                                    <td width="100px"><strong>{{$row->nama_kelompok}}</strong></td>
                                    <td width="200px"><strong>{{$row->nama_daftar}}</strong></td>
                                    <td width="200px"><strong>{{number_format($row->nominal_pengajuan,0,',','.')}}</strong></td>
                                    <td width="200px"><strong>{{$row->keterangan}}</strong></td>
                                    <td>
                                      @if($row->status_pertanggungjawaban==1)
                                        <button type="button" name="change" data-toggle="modal" data-target="#formModalChange" id="{{$row->id_main}}" class="change btn btn-success btn-xs" data-backdrop="static" data-keyboard="false"><i class="fas fa-check"></i></button>
                                      @else
                                        <button type="button" name="edit" data-toggle="modal" data-target="#formModal" id="{{$row->id_main}}" class="edit btn btn-warning btn-xs"><i class="fas fa-upload"></i></button>
                                      @endif
                                    </td>
                                </tr>
                                @endforeach
                          </tbody>
                  </table>
                   </div>
                  @if($sum!=0)
                    <div class="py-3">
                      <a class="btn btn-primary" data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
                      Klik jika ada kelebihan/kekurangan
                      </a>
</div>
                  @endif
                  <div class="collapse" id="collapseExample">
                    <div class="card card-body">
                      <form id="form_finish">
                      @csrf
                      <input type="hidden" name="id_pengajuan" value="{{$id_pengajuan}}">
                      <input type="hidden" name="number" value="{{$pengajuan['0']->no_pengajuan}}">
                     <div class="respon table-responsive">
                     <table class="table table-bordered tbl-mini" style="min-width:600px; width:100%">
                          <thead>
                              <tr>
                                  <td>Jenis</td>
                                  <td scope="col">
                                    @if($sum<0)
                                      <input type="text" name="jenis_pengembalian" class="form-control" value="kurang" readonly style="background-color: #ffffff;">
                                    @else
                                      <input type="text" name="jenis_pengembalian" class="form-control" value="sisa" readonly style="background-color: #ffffff;">
                                    @endif
                                  </td>
                              </tr>
                              <tr>
                                  <td>Nominal @if($sum<0) Kekurangan @else Kelebihan @endif</td>
                                  <td scope="col"><input type="text" class="form-control" name="nominal_sisa" value="{{number_format($sum,0,',','.')}}" readonly style="background-color: #ffffff;"></td>
                              </tr>
                              <tr>
                                  <td>Metode Pengembalian</td>
                                  <td scope="col">
                                      <select class="form-control  cst-select" name="metode" @if($sum!=0) required @endif>
                                         <option value="">--Pilih Metode Settlement--</option>
                                          @foreach($kasbank as $row)
                                              <option value="{{$row->kode_perkiraan}}">{{$row->nama}}</option>
                                          @endforeach
                                      </select>
                                  </td>
                              </tr>
                              <tr>
                                  <td>Sumber</td>
                                  <td scope="col">
                                      <select class="form-control cst-select" name="sumber" @if($sum!=0) required @endif>
                                          <option value="">--Pilih Sumber--</option>
                                      </select>
                                  </td>
                              </tr>
                          </thead>
                      </table>
                     </div>
                    </div>
                  </div>
                  <div class="respon table-responsive">
                  <table class="table table-bordered tbl-mini" style="min-width:600px; width:100%">
                    <thead>
                        <tr>
                            <td>Tanggal Pertanggungjawaban</td>
                            <td scope="col">
                                <input type="text" class="form-control" name="tanggal_pertanggungjawaban" value="<?=date('d-m-Y')?>" readonly style="background-color: #ffffff;">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Judul Pertanggungjawaban<br>
                                <small><i>(Masukkan judul singkat untuk laporan ini)</i></small>
                            </td>
                            <td scope="col"><input type="text" class="form-control" name="judul" required></td>
                        </tr>
                    </thead>
                </table>
                  </div>

                <!--KEBUTUHAN ACCURATE-->
                <input type="hidden" name="nominal_total" value="{{$pengajuan['0']->nominal_pengajuan}}">
                <input type="hidden" name="employeeNo" value="{{$username}}">
                <input type="hidden" name="projectNo" value="{{$no_project}}">
                <!--KEBUTUHAN ACCURATE-->

                <hr><br>
                    <center>
                        <a href="{!!url('pertanggungjawaban')!!}" class="btn btn-primary" data-dismiss="modal">Kembali</a>
                        @if($count_status==0)
                        <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Simpan</button>
                        @else
                        <button type="button" class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Lengkapi laporan terlebih dahulu!">Simpan</button>
                        @endif
                    </center>
                  </form>
                <br><br>
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
      // $("#finish_button").prop("disabled", true);

      $.ajax({
           url:"{{url('/')}}/pertanggungjawaban-finish",
           method:"POST",
           data:new FormData(this),
           contentType: false,
           cache: false,
           processData: false,
           dataType:"json",
           beforeSend: function(){
           $('.loader').css("visibility", "visible");
           // $("#finish_button").prop("disabled", true);
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
