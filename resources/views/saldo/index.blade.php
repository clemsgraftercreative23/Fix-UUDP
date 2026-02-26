@extends('template.app')

@section('content')
<script src="https://cdn.rawgit.com/igorescobar/jQuery-Mask-Plugin/1ef022ab/dist/jquery.mask.min.js"></script>

<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  <div class="col-6">
                  <h2 class="card-title clr-green">Saldo Harian</h2>
                  </div> 
                <div class="col-6 text-right">
                        <button class="btn btn-primary btn-sm" href="javascript:void(0)" id="create_record"><i class="fas fa-sync"></i> Tambah Data</button>
                  </div>
                </div>
<div class="py-3"></div>
                  <table id="zero-conf" class="display table-responsive" style="width:100%">
                      <thead>
                          <tr>
                              <th >No</th>
                              <th >Tanggal</th>
                              <th >Bank</th>
                              <th >Saldo</th>
                              <th >Aksi</th>
                          </tr>
                      </thead>
                      <tbody>

                          <?php $no=1; ?>

                          @foreach($saldo as $row)
                            @if (empty($saldo))
                              <tr>
                                <td colspan="6">Upps..! Ada kesalahan tampaknya</td>
                              </tr>
                            @else
                            <tr>
                                <td>{{$no++}}</td>
                                <td>{{$row->tanggal}}</td>
                                <td>{{$row->bank}}</td>
                                <td>{{$row->saldo}}</td>
                                <td>
                                    <button type="button" name="edit" data-toggle="modal" data-target="#formModal" id="{{$row->id}}" class="edit btn btn-warning btn-xs"><i class="fas fa-edit"></i></button>
                                    <button class="delete btn btn-danger btn-xs" name="delete" id="{{$row->id}}"><i class="fas fa-trash"></i></button>
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
                <span id="form_result"></span>
                  <form id="sample_form">
                  @csrf
                      <input type="hidden" name="action" id="action" />
                      <div class="form-group">
                          <label>Tanggal</label>
                          <input type="text" class="form-control" id="datepicker" name="tanggal"  placeholder="Masukkan tanggal saldo" required>
                      </div>
                      <div class="form-group">
                          <label>Bank Sumber</label>
                          <select class="custom-select form-control select-reset" name="bank" required>
                            <option value="">--Pilih Bank--</option>
                            @foreach($bank as $row)
                                <option value="{{$row->nama_list}}">{{$row->nama_list}}</option>
                            @endforeach
                          </select>
                      </div>
                      <div class="form-group">
                          <label>Saldo</label>
                          <input type="text" class="form-control uang" id="saldo" name="saldo" placeholder="Masukkan saldo" required>
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
  
  @push('scripts')

  <script type="text/javascript">

    $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});
    $( function() {
      $('#datepicker').datepicker({ dateFormat: 'dd/mm/yy' }).val();
    } );

    $(document).ready(function(){

      <?php if (Auth::user()->status_password != 1) { ?>
            $('#modalPassword').modal('show');
      <?php } ?>

      $('#create_record').click(function(){
          $('.modal-title').text("Tambah Data");
          $('#sample_form')[0].reset();
          $('#action_button').val("Add");
          $('#action').val("Add");
          $('#formModalAdd').modal('show');
          $('.loader').css("visibility", "hidden");
          $("#action_button").prop("disabled", false);
      });

      $('#sample_form').on('submit', function(event){
            event.preventDefault();
            $("#action_button").prop("disabled", true);
            
            $.ajax({
              url:"{{ route('saldoharian.store') }}",
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
              alert('Tambah Data Gagal! Ada kesalahan tampaknya.');
            }
            if(data.success)
            {
              alert('Tambah Data Berhasil');
              $('#sample_form')[0].reset();
              location.reload();
            }
            },
            complete: function(){
              $('.loader').css("visibility", "hidden");
            }
            })
        
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
             url:"saldoharian/destroy/"+id,
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
  <!-- End Modal Delete -->

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
