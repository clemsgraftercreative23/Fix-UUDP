@extends('template.app')

@section('content')

<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  <div class="col-md-6"> 
                  <h2 class="card-title clr-green">Master Daftar Rencana </h2>
                  </div> 
                <div class="col-md-6 text-left text-md-right">
                      <form id="syncForm">
                        @csrf
                        <button type="submit" id="syncButton" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i> Sinkronisasi Data</button>
                      </form>
                  </div>
                </div>
                <div class="py-3"></div>
                  <table id="zero-conf" class="display table-responsive-md" style="width:100%">
                      <thead>
                          <tr>
                              <th >No</th>
                              <th >Kode Kelompok</th>
                              <th >Kode Rencana</th>
                              <th >Nama Kelompok Kegiatan</th>
                              <th >Nama Rencana Kegiatan</th>

                          </tr>
                      </thead>
                      <tbody>

                          <?php $no=1; ?>

                          @foreach($rencana as $row)
                            @if (empty($rencana))
                              <tr>
                                <td colspan="6">Upps..! Ada kesalahan tampaknya</td>
                              </tr>
                            @else
                            <tr>
                                <td>{{$no++}}</td>
                                <td>{{$row->id_kelompok}}</td>
                                <td>{{$row->id_daftar}}</td>
                                <td>{{$row->nama_kelompok}}</td>
                                <td>{{$row->nama_rencana}}</td>
                            </tr>
                            @endif
                          @endforeach
                          
                      </tbody>

                  </table>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="modalPassword"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">AMANKAN PASSWORD</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="material-icons">close</i>
                    </button>
                </div>
                <div class="modal-body">
                  <span id="form_result_add"></span>
                  <p>Password Anda masih menggunakan password default dari sistem. <br>
                  Demi keamanan akun, kami menyarankan Anda melakukan perubahan password terlebih dahulu!</p>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Nanti dulu</button>
                      <a href="{!! url('profile') !!}" class="btn btn-primary">Ubah Password</a>
                  </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

  
  @push('scripts')

  <script type="text/javascript">

    $(document).ready(function(){

      <?php if (Auth::user()->status_password != 1) { ?>
            $('#modalPassword').modal('show');
      <?php } ?>

      $('#syncForm').on('submit', function(event){
            event.preventDefault();
            $("#syncButton").prop("disabled", true);
            
            $.ajax({
              url:"syncRencana",
              method:"POST",
              data: new FormData(this),
              contentType: false,
              cache:false,
              processData: false,
              dataType:"json",
            beforeSend: function(){
            $('.loader').css("visibility", "visible");
            },
            success:function(data)
            {
            var html = '';
            if(data.errors)
            {
              alert('Proses Sinkronisasi Gagal! Ada kesalahan tampaknya.');
            }
            if(data.success)
            {
              alert('Sinkronisasi Data Berhasil');
              $('#syncForm')[0].reset();
              location.reload();
            }
            },
            complete: function(){
              $('.loader').css("visibility", "hidden");
            }
            })
        
         });

    });
  </script>

@endpush
@endsection
