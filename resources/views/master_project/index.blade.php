@extends('template.app')

@section('content')
<style>
   .form-control{
        border-radius:5px;
    }
    .custom{
        height:2em;
        width:80%;
        border-radius: 5px;
    }
</style>

<script src="https://cdn.rawgit.com/igorescobar/jQuery-Mask-Plugin/1ef022ab/dist/jquery.mask.min.js"></script>

<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  <div class="col-md-6"> 
                    <label class="card-title" style="font-size:20px; color:#62d49e;">Master Projek</label>
                  </div> 
                  <div class="col-6 col-md-3 text-left text-md-right mb-3">
                        <button class="btn btn-primary btn-sm" href="javascript:void(0)" id="create_record"><i class="fa fa-plus-circle" aria-hidden="true"></i> Tambah Data</button>
                  </div>
                  <div class="col-6 col-md-3 text-left text-md-right mb-3">
                      <form id="syncForm">
                        @csrf
                        <button type="submit" id="syncButton" class="btn btn-primary btn-sm">
                        <i class="fas fa-sync"></i> <span class="d-none d-sm-inline">Sinkronisasi</span><span class="d-sm-none">Sync</span> Data</button>
                      </form>
                  </div>
                </div>
                  <div class="py-3"></div>
                  <table id="zero-conf" class="display table-responsive" style="width:100%">
                      <thead>
                          <tr>
                              <th width="5%">No</th>
                              <th width="10%">Kode Projek</th>
                              <th width="20%">Nama Projek</th>
                              <th width="40%">Keterangan</th>
                              <th width="10%">Total</th>
                              <th width="15%">Aksi</th>
                          </tr>
                      </thead>
                      <tbody>

                          <?php $no=1;
                            function rupiah($angka){
                              $hasil_rupiah = number_format($angka,0,',','.');
                              return $hasil_rupiah;
                            }
                          ?>

                          @foreach($project as $row)
                            @if (empty($project))
                              <tr>
                                <td colspan="6">Upps..! Ada kesalahan tampaknya</td>
                              </tr>
                            @else
                            <tr>
                                <td>{{$no++}}</td>
                                <td>{{$row->no_project}}</td>
                                <td>{{$row->nama}}</td>
                                <td>{{$row->keterangan}}</td>
                                <td><strong>{{rupiah($row->total)}}</strong></td>
                                <td>
                                  <button type="button" name="edit" data-toggle="modal" data-target="#formModal" id="{{$row->id}}" class="edit btn btn-warning btn-xs"><i class="fas fa-edit"></i></button>
                                  <button class="delete btn btn-danger btn-xs" name="delete" id="{{$row->id_project}}"><i class="fas fa-trash"></i></button>
                                </td>
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
  <div class="modal fade" id="formModalAdd" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
              </div>
              <div class="modal-body">
                <span id="form_result_add"></span>
                  <form id="sample_form_add">
                  @csrf
                      <input type="hidden" name="action" id="action" />
                      <div class="form-group">
                          <label>Kode Projek</label>
                          <input type="text" class="form-control" id="no_project" name="no_project"  placeholder="Masukkan nomor atau kode projek" required>
                      </div>
                      <div class="form-group">
                          <label>Nama Projek</label>
                          <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama projek" required>
                      </div>
                      <div class="form-group">
                          <label>Deskripsi</label>
                          <textarea class="form-control" name="keterangan" id="keterangan" placeholder="Masukkan Deskripsi Projek" required></textarea>
                      </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                          <button type="submit" class="btn btn-primary" name="action_button_add" id="action_button_add">Simpan</button>
                      </div>
                </form>
                
          </div>
      </div>
  </div>
  <!-- End Modal -->

 <!-- Modal -->
  <div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Edit Data</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
              </div>
              <div id="detailProject"></div>
                
          </div>
      </div>
  </div>
  <!-- End Modal -->

  <!-- Modal Delete -->
  <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Hapus Data</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
              </div>
              <div class="modal-body">
                  Apakah Anda yakin ingin menghapus data ini ?
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                  <button type="button" class="btn btn-primary" name="ok_button" id="ok_button">Ya</button>
              </div>
          </div>
      </div>
  </div>
  <!--End Modal Delete--->

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
      // $('#inc-hide').hide();

      // $(".btn-success").click(function(){ 
      //   var html = $(".clone").html();
      //   $(".increment").last().after(html);
      // });

      // $("body").on("click",".btn-danger",function(){ 
      //   $(this).parents(".control-group").remove();
      // });

      <?php if (Auth::user()->status_password != 1) { ?>
            $('#modalPassword').modal('show');
      <?php } ?>

      $('#create_record').click(function(){
          $('.modal-title').text("Tambah Data");
          $('#sample_form_add')[0].reset();
          $('#action_button_add').val("Add");
          $('#action').val("Add");
          $('#formModalAdd').modal('show');
          $('.loader').css("visibility", "hidden");
          $("#action_button_add").prop("disabled", false);
      });

      $('#syncForm').on('submit', function(event){
            event.preventDefault();
            $("#syncButton").prop("disabled", true);
            
            $.ajax({
              url:"syncProject",
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
              $("#syncButton").prop("disabled", false);
              $('.loader').css("visibility", "hidden");
            }
            })
        
         });


        $('#sample_form_add').on('submit', function(event){
          event.preventDefault();
          $("#action_button_add").prop("disabled", true);
          if($('#action').val() == 'Add')
          {
             $.ajax({
             url:"{{ route('project.store') }}",
             method:"POST",
             data: new FormData(this),
             contentType: false,
             cache:false,
             processData: false,
             dataType:"json",
             beforeSend: function(){
             $('.loader').css("visibility", "visible");
             $("#action_button").prop("disabled", true);
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
               $('#sample_form_add')[0].reset();
               alert('Data berhasil disimpan!');
               location.reload();
             }

           },
             complete: function(){
               $('.loader').css("visibility", "hidden");
             }
             })
           }

        //   if($('#action').val() == "Edit")
        //   {
        //      $.ajax({
        //      url:"{{ route('project.update') }}",
        //      method:"POST",
        //      data:new FormData(this),
        //      contentType: false,
        //      cache: false,
        //      processData: false,
        //      dataType:"json",
        //      beforeSend: function(){
        //      $('.loader').css("visibility", "visible");
        //      $("#action_button").prop("disabled", true);
        //      },
        //      success:function(data)
        //      {
        //      var html = '';
        //      if(data.errors)
        //      {
        //      alert('Data gagal disimpan!');
        //      }
        //      if(data.success)
        //      {
        //      alert('Data berhasil disimpan!');
        //      location.reload();
        //      }
        //      $('#form_result').html(html);
        //      $('#formModal').modal('hide');
             
        //      },
        //      complete: function(){
        //      $('.loader').css("visibility", "hidden");
        //      }
        //      });
        //    }
        });



        $(document).on('click', '.edit', function(){
              var id = $(this).attr('id');
              $.ajax({
              url : '{{ route("getProject") }}',
                type: 'POST',
                headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data:{id:id},
                success:function(data){
                $('#detailProject').html(data)
                },
              });
        });

        var id;

        $(document).on('click', '.delete', function(){
             id = $(this).attr('id');
             console.log(id);
             $('#confirmModal').modal('show');
             $('.loader').css("visibility", "hidden");
             $("#ok_button").prop("disabled", false);
         });

       $('#ok_button').click(function(){
           $.ajax({
           url:"project/destroy/"+id,
           beforeSend:function(){
           $('.loader').css("visibility", "visible");
           $("#ok_button").prop("disabled", true);
           },
           success:function(data)
           {
           setTimeout(function(){
           $('#confirmModal').modal('hide');
           }, 1000);
           $("#body-unset").css("padding-right", "unset");
           alert('Data berhasil dihapus!');
           
           },
           complete: function(){
           $('.loader').css("visibility", "hidden");
           alert('Data berhasil dihapus!');
           $('#confirmModal').modal('hide');
           location.reload();
           }
           })
       });
    });
  </script>

@endpush
@endsection
