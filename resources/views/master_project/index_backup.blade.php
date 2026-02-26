@extends('template.app')

@section('content')
<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p class="card-title" style="color:#62d49e;">Dashboard</p>
                    <label class="card-title" style="font-size:20px; color:#62d49e;">Master Projek</label>
                  </div>
                  <div class="col-md-2">
                  </div>
                  <div class="col-md-2">
                        <button class="btn btn-primary btn-sm" href="javascript:void(0)" id="create_record"><i class="fa fa-refresh" aria-hidden="true"></i>Tambah Data</button>
                  </div>
                  <div class="col-md-2">
                      <form id="syncForm">
                        @csrf
                        <button type="submit" id="syncButton" class="btn btn-primary btn-sm"><i class="fa fa-refresh" aria-hidden="true"></i>Sinkronisasi Data</button>
                      </form>
                  </div>
                </div>

                  <table id="zero-conf" class="display" style="width:100%">
                      <thead>
                          <tr>
                              <th width="5%">No</th>
                              <th width="10%">Kode Projek</th>
                              <th width="20%">Nama Projek</th>
                              <th width="40%">Keterangan</th>
                              <th width="10%">Limit</th>
                              <th width="15%">Aksi</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php $no=1;?>
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
                                <td>{{$row->limit}}</td>
                                <td>
                                  <button type="button" name="edit" id="{{$row->id}}" class="edit btn btn-warning btn-xs"><i class="fas fa-edit"></i></button>
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

  <!--  -->

 <!-- Modal -->
  <div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
              </div>
              <div class="modal-body">
                <span id="form_result"></span>
                  <form id="sample_form">
                  @csrf
                      <input type="hidden" name="hidden_id" id="hidden_id">
                      <input type="hidden" name="action" id="action" />
                      <input type="hidden" name="idmain" id="idmain" />
                      <div class="form-group">
                          <label>Kode Projek</label>
                          <input type="text" class="form-control" id="no_project" name="no_project"  placeholder="Masukkan nomor atau kode projek" required>
                      </div>
                      <div class="form-group">
                          <label>Nama Projek</label>
                          <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama projek" required>
                      </div>
                      <div id="limit-budget">
                        <div class="form-group">
                          <label>Limit Budget</label>
                          <input type="text" class="form-control" id="limit" name="limit" placeholder="Masukkan limit budget">
                        </div>
                      </div>
                      <div class="form-group">
                          <label>Deskripsi</label>
                          <textarea class="form-control" name="keterangan" id="keterangan" placeholder="Masukkan Deskripsi Projek" required></textarea>
                      </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                          <button type="submit" class="btn btn-primary" name="action_button" id="action_button">Simpan</button>
                      </div>
                </form>
          </div>
      </div>
  </div>
  <!-- End Modal -->

  <!-- Modal Delete -->
  <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
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
  
@push('scripts')

  <script type="text/javascript">
    $(document).ready(function(){

      $('#create_record').click(function(){
          $('.modal-title').text("Tambah Data");
          $('#sample_form')[0].reset();
          $('#action_button').val("Add");
          $('#action').val("Add");
          $('#formModal').modal('show');
          $('.loader').css("visibility", "hidden");
          $('#limit-budget').hide();
          $("#action_button").prop("disabled", false);
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
              $('.loader').css("visibility", "hidden");
            }
            })
        
         });

//          $('#sample_form').on('submit', function(event){
//             event.preventDefault();
//             $("#action_button").prop("disabled", true);
            
//             $.ajax({
//               url:"{{ route('project.store') }}",
//               method:"POST",
//               data: new FormData(this),
//               contentType: false,
//               cache:false,
//               processData: false,
//               dataType:"json",
//               beforeSend: function(){
//               $('.loader').css("visibility", "visible");
//             },
//             success:function(data)
//             {
//             var html = '';
//             if(data.errors)
//             {
//               alert('Data gagal disimpan!')
//             }
//             if(data.success)
//             {
//               alert('Data berhasil disimpan!');
//               $('#sample_form')[0].reset();
//               location.reload();
//             }
//               $('#form_result').html(html);
//               $('#formModal').modal('hide');
//             },
//             complete: function(){
//               $('.loader').css("visibility", "hidden");
//             }
//             })

// if($('#action').val() == "Edit")
// {
// $.ajax({
// url:"{{ route('project.update') }}",
// method:"POST",
// data:new FormData(this),
// contentType: false,
// cache: false,
// processData: false,
// dataType:"json",
// beforeSend: function(){
// $('.loader').css("visibility", "visible");
// $("#action_button").prop("disabled", true);
// },
// success:function(data)
// {
// var html = '';
// if(data.errors)
// {
// $('#formModal').modal('hide');
// $("#notif-error").modal('show');
// setTimeout(function() { $("#notif-error").modal('hide'); }, 5000);
// }
// if(data.success)
// {
// $('#sample_form')[0].reset();
// // $('#datable_1').DataTable().ajax.reload();
// }
// $('#form_result').html(html);
// $('#formModal').modal('hide');
// $("#body-unset").css("padding-right", "unset");
// $("#notif").modal('show');
// setTimeout(function() { $("#notif").modal('hide'); }, 5000);
// },
// complete: function(){
// $('.loader').css("visibility", "hidden");
// // $('#datable_1').DataTable().ajax.reload();
// location.reload();
// }
// });
// }
// });


        $('#sample_form').on('submit', function(event){
          event.preventDefault();
          $("#action_button").prop("disabled", true);
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
               $('#sample_form')[0].reset();
               alert('Data berhasil disimpan!');
               location.reload();
             }

           },
             complete: function(){
               $('.loader').css("visibility", "hidden");
             }
             })
           }

          if($('#action').val() == "Edit")
          {
             $.ajax({
             url:"{{ route('project.update') }}",
             method:"POST",
             data:new FormData(this),
             contentType: false,
             cache: false,
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
           }
        });



        $(document).on('click', '.edit', function(){
              var id = $(this).attr('id');
              $('#form_result').html('');
              $('#limit-budget').show();
              $.ajax({
              url:"/project/"+id+"/edit",
              dataType:"json",
              success:function(html){
                  $('#no_project').val(html.data.no_project);
                  $('#nama').val(html.data.nama);
                  $('#keterangan').val(html.data.keterangan);
                  $('#limit').val(html.data.limit);
                  $('#hidden_id').val(html.data.id_project);
                  $('#idmain').val(html.data.id);
                  $('.modal-title').text("Edit Data");
                  $('#action_button').val("Edit");
                  $('#action').val("Edit");
                  $('#formModal').modal('show');
                  $('.loader').css("visibility", "hidden");
                  $("#action_button").prop("disabled", false);
              }
              })
        });

        var id;

        $(document).on('click', '.delete', function(){
             id = $(this).attr('id');
             console.log(id);
             $('#confirmModal').modal('show');
             $('.modal-title').text("Hapus Data");
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
