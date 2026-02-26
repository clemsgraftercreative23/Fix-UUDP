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
                <h2 class="card-title clr-green">Karyawan</h2>
                  </div>
                <div class="col-6  text-right">
                <form id="sample_form">
                        @csrf
                        <button type="submit" id="action_button" class="btn btn-primary btn-sm">
                          <i class="fas fa-sync"></i> <span id="idle"><span class="d-none d-sm-inline">Sinkronisasi</span><span class="d-sm-none">Sync</span> Data</span> <span id="loading" style="display:none">Menunggu</span></button>
                      </form>
                </div> 
                </div>
                <div class="py-3"></div>
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
                  <table id="zero-conf" class="display table-responsive" style="width:100%">
                      <thead>
                          <tr>
                              <th>No</th>
                              <th>NIP</th>
                              <th>Nama Karyawan</th>
                              <th>Username</th>
                              <th>Tanggal Bergabung</th>
                              <th>Status</th>
                              <th>Domisili</th>
                              <th>Approval</th>
                              <th>Aksi</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php $no=1;?>
                          @foreach($karyawan as $row)
                            @if (empty($karyawan))
                              <tr>
                                <td colspan="6">Upps..! Ada kesalahan tampaknya</td>
                              </tr>
                            @else
                            <tr>
                                <td>{{$no++}}</td>
                                <td>{{$row->idKaryawan}}</td>
                                <td>{{$row->name}}</td>
                                <td>{{$row->username}}</td>
                                <td>{{$row->joinDate}}</td>
                                <td>{{$row->employeeWorkStatus}}</td>
                                <td>{{$row->domisiliType}}</td>
                                <td>{{$row->nama_approval}}</td>
                                <td><a href="{{url('karyawan/profile/')}}/{{$row->id}}" class="btn btn-primary btn-sm"><i class="fas fa-info-circle"></i></a> <a class="btn btn-danger btn-sm btn-delete"  id="{{$row->id}}" style="cursor: pointer;"><i class="fas fa-trash" style="color:red"></i></a></td>
                            </tr>
                            @endif
                          @endforeach

                      </tbody>

                  </table>
              </div>
          </div>
      </div>

     <!-- Modal -->
      <div class="modal fade" id="formConfirm"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="exampleModalLabel">Delete Employee</h5>
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <i class="material-icons">close</i>
                      </button>
                  </div>
                  <div class="modal-body">
                    <div class="alert alert-danger" role="alert">
                      <p>Do you want to delete this employee ? </p>
                    </div>
                    <form method="post" id="delete_form" class="form-horizontal" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id_delete" id="id_delete" />
                    <!-- <center><span class="loader"><i class="fa fa-spinner fa-3x fa-spin"></i></span></center> -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit"   class="btn btn-danger">Yes</button>
                    </div>
                    </form>
              </div>
          </div>
      </div>
      <!-- End Modal -->

      <!-- Modal -->
      <div class="modal fade" id="modalPassword"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="exampleModalLabel">Change Password</h5>
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <i class="material-icons">close</i>
                      </button>
                  </div>
                  <div class="modal-body">
                    <span id="form_result_add"></span>
                    <p>Your password still uses the default password from the system. <br>
                    For account security, we recommend that you change your password first!</p>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Skip</button>
                        <a href="{!! url('profile') !!}" class="btn btn-primary">Change Passwords</a>
                    </div>
              </div>
          </div>
      </div>
      <!-- End Modal -->

  </div>

  

  

  @push('scripts')

  <script type="text/javascript">
    $(document).ready(function(){

    <?php if (Auth::user()->status_password != 1) { ?>
        $('#modalPassword').modal('show');
    <?php } ?>

    $('#sample_form').on('submit', function(event){
          event.preventDefault();
          $("#action_button").prop("disabled", true);

          $.ajax({
          url:"{{ route('karyawan.store') }}",
          method:"POST",
          data: new FormData(this),
          contentType: false,
          cache:false,
          processData: false,
          dataType:"json",
          beforeSend: function(){
            $("span#loading").show()
            $("span#idle").hide()
            $(".full-loading").fadeIn()
            $('.loader').css("visibility", "visible");
          },
          success:function(data)
          {
          var html = '';
          if(data.errors)
          {
          html = '<div class="alert alert-danger">';
          for(var count = 0; count < data.errors.length; count++)
          {
          html += '<p>' + data.errors[count] + '</p>';
          }
          html += '</div>';
          }
          if(data.success)
          {
          alert('Data Synchronization Successful');
          $('#sample_form')[0].reset();
          location.reload();
          }
          $('#form_result').html(html);
          $('#formModal').modal('hide');
          },
          complete: function(){
            $('.loader').css("visibility", "hidden");
            $(".full-loading").hide()
            $("span#loading").hide()
            $("span#idle").show()
          }
          })

       });
    });



    $(document).on('click', '.btn-delete', function(){
        var id = $(this).attr('id');
        $('#id_delete').val(id);
        $('#formConfirm').modal('show');
        $('.loader').css("visibility", "hidden");
    });

    $('#delete_form').on('submit', function(event){
        event.preventDefault();
          $.ajax({
            url:"delete-karyawan  ",
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
                if(data.status==='success') {
                    swal("Sukses!", "Data berhasil dihapus !", "success").then(function() {
                      location.reload();
                    });  
                } else {
                   swal("Gagal!", "Silakan coba lagi", "error")
                }
            }
        })
         
    });
  </script>

@endpush
@endsection
