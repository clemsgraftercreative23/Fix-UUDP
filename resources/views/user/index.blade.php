@extends('template.app')

@section('content')

<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  <div class="col-6">
                  <h2 class="card-title clr-green">User Aplikasi</h2>
                  </div>
                <div class="col-6 text-right">
                    <a href="{{url('/add_user')}}" class="btn btn-primary btn-sm" >
                    <i class="fas fa-plus-circle"></i> Tambah Data</a>
                  </div>
                </div>
<div class="py-3"></div>
                  <table id="zero-conf" class="display table-responsive" style="width:100%">
                      <thead>
                          <tr>
                              <th width="5%">No</th>
                              <th width="10%">NIP</th>
                              <th width="10%">Nama Karyawan</th>
                              <th width="10%">Jabatan</th>
                              <th width="10%">Department</th>
                              <th width="15%">Aksi</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php $no=1;
                          ?>
                          @foreach($user as $row)
                            @if (empty($user))
                              <tr>
                                <td colspan="6">Upps..! Ada kesalahan tampaknya</td>
                              </tr>
                            @else
                            <tr>
                                <td>{{$no++}}</td>
                                <td>{{$row->username}}</td>
                                <td>{{$row->name}}</td>
                                <td>
                                    @if($row->jabatan=='superadmin')
                                        Admin
                                    @elseif($row->jabatan=='Direktur Operasional')
                                        Head Department
                                    @elseif($row->jabatan=='Finance')
                                        HR GA
                                    @elseif($row->jabatan=='Owner')
                                        Finance
                                    @else
                                        Employee
                                    @endif
                                </td>
                                <td>{{$row->nama_departemen}}</td>
                                <td>
                                  <a href="{{url('/edit_useraplikasi/'.$row->id)}}" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a>
                                  <button class="edit btn btn-danger btn-xs" name="edit" id="{{$row->id}}"><i class="fas fa-trash"></i></button>
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
      <div class="modal-dialog" role="document">
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
                      <input type="hidden" name="hidden_id" id="hidden_id" />
                      <center>Apakah Anda ingin menghapus karyawan ini dari daftar user aplikasi ?</center>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                          <button type="submit" class="btn btn-primary" name="action_button_add" id="action_button_add">Ya</button>
                      </div>
                </form>
                
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

        $('#sample_form_add').on('submit', function(event){
          event.preventDefault();
          $("#action_button_add").prop("disabled", true);
          if($('#action').val() == 'Edit')
          {
             $.ajax({
             url:"user_aplikasi/remove_jabatan",
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
       
        });

        $(document).on('click', '.edit', function(){
            var id = $(this).attr('id');
            $('#form_result_add').html('');
            $.ajax({
            url:"/user_aplikasi/"+id+"/edit",
            dataType:"json",
            success:function(html){
            $('#hidden_id').val(html.data.id);
            $('.modal-title').text("Hapus Data");
            $('#action_button').val("Edit");
            $('#action').val("Edit");
            $('#formModalAdd').modal('show');
            }
          })
        });

        
    });


  </script>

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

  @endpush
  @endsection
