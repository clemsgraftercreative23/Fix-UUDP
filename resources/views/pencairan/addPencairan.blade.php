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
    <div class="col-xl">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                <h5 class="card-title">INSERT PENCAIRAN</h5>

                <button type="button" class="close" onclick="history.back()">
                      <i class="material-icons">close</i>
                </button>
                </div>
                
                <hr>
                <p>Sebelum melakukan input data, mohon periksa kembali rincian dari data pengajuan terkait!</p><hr>
                <form>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="inputEmail4">Nomor Inquiry</label>
                            <input type="text" class="form-control" value="{{$pengajuan['0']->no_pengajuan}}" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputPassword4">Nominal Inquiry</label>
                            <input type="text" class="form-control" value="{{number_format($pengajuan['0']->nominal_pengajuan,0,',','.')}}" readonly >
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="inputEmail4">Mengetahui Direktur Operasional</label>
                            <input type="text" class="form-control" value="{{$pengajuan['0']->menyetujui_op}}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="inputPassword4">Mengetahui Finance</label>
                            <input type="text" class="form-control" value="{{$pengajuan['0']->menyetujui}}" readonly >
                        </div>
                        <div class="form-group col-md-4">
                            <label for="inputPassword4">Menyetujui Direktur Utama</label>
                            <input type="text" class="form-control" value="{{$pengajuan['0']->mengetahui}}" readonly >
                        </div>
                    </div>
                    <hr>
                    @foreach($pencairan as $key)
                    @endforeach
                    <p><strong>Bahwa pengajuan tersebut sudah disetujui oleh Direktur Utama, dengan metode pencairan secara : <span style="color: #62d49e">@if($key->nominal==NULL) FULLPAYMENT @else TERMIN @endif</span>, dengan detail sebagai berikut :</strong></p>
                    <div class="respon table-responsive tbl-800">
                    @if($key->nominal==NULL)

                     <table class="table table-bordered"  >
                          <thead>
                              <tr>
                                  <th scope="col">No</th>
                                  <th scope="col">Nominal</th>
                                  <th scope="col">Tgl Jatuh Tempo</th>
                                  <th scope="col">
                                    @if(Auth::user()->jabatan=='Finance')
                                      Aksi
                                    @else
                                      Status
                                    @endif
                                  </th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php
                                  $no=1;$termin=1;
                                  function rupiah($angka){
                                      $hasil_rupiah = number_format($angka,0,',','.');
                                      return $hasil_rupiah;
                                  }
                              ?>
                              <td>1</td>
                              <td>{{$pengajuan['0']->nominal_pengajuan}}</td>
                              <td>{{date('d-m-Y', strtotime($key->date))}}</td>
                              <td>
                                @if(Auth::user()->jabatan=='Finance')
                                  <button type="button" name="edit" data-toggle="modal" data-target="#formModal" id="{{$key->id}}" class="edit btn btn-warning btn-xs"><i class="fas fa-upload"></i></button>
                                @else
                                  Belum ditransfer
                                @endif
                              </td>
                          </tbody>
                      </table>

                     @else

                     <table class="table table-bordered">
                          <thead>
                              <tr>
                                  <th scope="col">No</th>
                                  <th scope="col">Termin ke</th>
                                  <th scope="col">Tgl Jatuh Tempo</th>
                                  <th scope="col">Presentase</th>
                                  <th scope="col">Nominal</th>
                                  <th scope="col">
                                    @if(Auth::user()->jabatan=='Finance')
                                      Aksi
                                    @elseif(Auth::user()->jabatan=='superadmin')
                                    Aksi
                                    @else
                                      Status
                                    @endif
                                  </th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php
                                  $no=1;$termin=1;
                                  function rupiah($angka){
                                      $hasil_rupiah = number_format($angka,0,',','.');
                                      return $hasil_rupiah;
                                  }
                              ?>
                              @foreach($pencairan as $row)
                              <tr>
                                  <th scope="row">{{$no++}}</th>
                                  <td>Termin ke-{{$termin++}}</td>
                                  <td>{{date('d-m-Y', strtotime($row->date))}}</td>
                                  <td>{{$row->nominal}} %</td>
                                  <td>{{rupiah($row->nominal/100 * $pengajuan['0']->nominal_pengajuan)}}</td>
                                  <td>
                                    @if(Auth::user()->jabatan=='Finance')
                                      @if($row->status==0)
                                        <button type="button" name="edit" data-toggle="modal" data-target="#formModal" id="{{$row->id}}" class="edit btn btn-warning btn-xs" title="Upload Bukti Settlement"><i class="fas fa-upload"></i></button>
                                        @if($row->jumlah_notif>0)
                                        <button type="button" name="detail-push"  id="{{$row->id}}" class="detail-push btn btn-default btn-xs" title="Jumlah Push Settlement" data-target="#formModalFinance" data-toggle="modal" data-backdrop="static" data-keyboard="false"><i class="fa fa-info-circle" style="color:red"></i><sup style="color :red">{{$row->jumlah_notif}}</sup></button>
                                        @endif
                                      @else
                                        <button type="button" name="detail" data-toggle="modal" data-target="#formModalDetail" id="{{$row->id}}" class="detail btn btn-success btn-xs"><i class="fas fa-check-square"></i></button>
                                      @endif
                                    @elseif(Auth::user()->jabatan=='superadmin')
                                      @if($row->status==0)
                                        <button type="button" name="edit" data-toggle="modal" data-target="#formModal" id="{{$row->id}}" class="edit btn btn-warning btn-xs"><i class="fas fa-upload"></i></button>
                                      @else
                                        <button type="button" name="detail" data-toggle="modal" data-target="#formModalDetail" id="{{$row->id}}" class="detail btn btn-success btn-xs"><i class="fas fa-check-square"></i></button>
                                      @endif
                                    @else
                                        @if($row->status==0)
                                          Belum ditransfer
                                          @if(Auth::user()->jabatan=='karyawan')
                                            &nbsp;
                                            <button type="button" name="push" data-toggle="modal" data-target="#formModalPush" id="{{$row->id}}" class="push btn btn-success btn-xs">
                                              PUSH <span class="badge badge-light" style="background-color: #f2cea5">{{$row->jumlah_notif}}</span>
                                            </button>
                                          @endif
                                          @if(Auth::user()->jabatan=='superadmin' || Auth::user()->jabatan=='Owner')
                                            @if($row->jumlah_notif_owner>0)
                                            <button type="button" name="detail-push"  id="{{$row->id}}" class="detail-push btn btn-default btn-xs" title="Jumlah Push Settlement" data-target="#formModalFinance" data-toggle="modal" data-backdrop="static" data-keyboard="false"><i class="fa fa-info-circle" style="color:red"></i><sup style="color :red">{{$row->jumlah_notif_owner}}</sup></button>
                                            @endif
                                          @endif
                                        @else
                                          <button type="button" name="detail" data-toggle="modal" data-target="#formModalDetail" id="{{$row->id}}" class="detail btn btn-success btn-xs"><i class="fas fa-check-square"></i></button>
                                        @endif
                                    @endif
                                  </td>
                              </tr>
                              @endforeach
                          </tbody>
                      </table>
                               
                    @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
  <div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">UPLOAD BUKTI TRANSFER</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
              </div>
              <div class="modal-body">
                <span id="form_result"></span>
                  <form id="sample_form">
                  @csrf
                      <input type="hidden" name="action" id="action" />
                      <input type="hidden" name="id" id="id" />
                      <input type="hidden" name="employeeNo" id="employeeNo" />
                      <input type="hidden" name="projectNo" id="no_project" />
                      <div class="form-group">
                          <label>Nama Proyek</label>
                          <input type="text" class="form-control" id="nama_project" readonly>
                      </div>
                      <div class="form-group">
                          <label>Tanggal Transfer</label>
                          
                          <input type="text" class="form-control" id="datepicker" name="transDate"  placeholder="Masukkan Tanggal Transfer" required>
                      </div>
                      <div class="form-group">
                          <label>Metode Settlement</label>
                          <select class="form-control cst-select" name="metode">
                           <option value="">--Pilih Metode Settlement--</option>
                            @foreach($kasbank as $row)
                                <option value="{{$row->kode_perkiraan}}">{{$row->nama}}</option>
                            @endforeach
                          </select>
                      </div>
                      <div class="form-group">
                          <label>Sumber</label>
                          <select class="form-control cst-select" name="sumber">
                              <option value="">--Pilih Sumber--</option>
                          </select>
                      </div>
                      <div class="form-group">
                          <label>Departemen</label>
                          <input type="text" class="form-control" id="departmentName" name="departmentName" value="UMUM PROYEK" readonly>
                      </div>
                      <div class="form-group">
                          <label>Penerima</label>
                          <input type="text" class="form-control" id="penerima" value="{{$karyawan? $karyawan->name : ''}}" name="penerima" placeholder="Penerima Dana" required>
                      </div>
                      <div class="form-group">
                          <label>Bank Penerima</label>
                          <input type="text" class="form-control" id="bank" value="{{$karyawan? $karyawan->bankName : ''}}" name="bank" placeholder="Bank Penerima" required>
                      </div>
                      <div class="form-group">
                          <label>No Rek</label>
                          <input type="text" class="form-control" id="no_rek" value="{{$karyawan? $karyawan->bankAccount : ''}}" name="no_rek" placeholder="No Rekening" required>
                      </div>
                       <div class="form-group">
                          <label>Nominal Transfer</label>
                          <input type="text" class="form-control uang" id="ammount" name="ammount" placeholder="Masukkan nominal" readonly>
                      </div>
                      <div class="form-group">
                          <label>Keterangan</label>
                          <textarea class="form-control" name="description" id="description" placeholder="Masukkan Keterangan (Jika Perlu)" required></textarea>
                      </div>
                      <div class="form-group">
                          <label>Bukti Trasnfer</label>
                          <input type="file" name="file_bukti" class="form-control" required>
                      </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                          <button type="" class="btn btn-primary" name="action_button" id="action_button">Simpan</button>
                      </div>
                </form>

          </div>
      </div>
  </div>
  <!-- End Modal -->

  <!-- Modal Push -->
  <div class="modal fade" id="formModalPush" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">PUSH DANA PENGAJUAN</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
              </div>
              <div class="modal-body">
                <span id="form_push"></span>
                  <form id="sample_push">
                  @csrf
                      <input type="hidden" name="action" id="action" />
                      <input type="hidden" name="id_pencairan" id="id_pencairan" />
                      <input type="hidden" name="id_pengajuan" id="id_pengajuan" />
                      <input type="hidden" name="nominal" id="nominal" />
                      <input type="hidden" name="id_user" value="{{Auth::user()->id}}" />
                      <div class="panel panel-default">
                          <div class="panel-body">Apakah Anda ingin melakukan 'push' permintaan untuk pencairan dana ini ? </div>
                      </div>
                      
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                          <button type="" class="btn btn-primary" name="action_push" id="action_push">Push</button>
                      </div>
                </form>

          </div>
      </div>
  </div>
  <!-- End Modal Push-->


  @push('scripts')

  <script type="text/javascript">

     $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});

     $(document).on('click', '.edit', function(){
          var id = $(this).attr('id');
          $('#form_result').html('');
          $.ajax({
          url:"/pencairan/"+id+"/edit",
          dataType:"json",
          success:function(html){
          $('#id').val(html.data.id);
          $('#employeeNo').val(html.user.username);
          $('#no_project').val(html.project.no_project);
          $('#nama_project').val(html.project.nama);
          $('#ammount').val(html.nilai_trf);
          $('#hidden_id').val(html.data.id);
          $('.modal-title').text("Edit Data");
          $('#action_button').val("Edit");
          $('#action').val("Edit");
          $('#formModal').modal('show');
          }
        })
      });

      $('#sample_form').on('submit', function(event){
            event.preventDefault();
            $("#action_button").prop("disabled", true);
               $.ajax({
               url:"../../storePayment",
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
                 $("#action_button").prop("disabled", false)
                 $('.loader').css("visibility", "hidden");
               }
            })
      });

      $('#sample_push').on('submit', function(event){
            event.preventDefault();
            $("#action_push").prop("disabled", true);
               $.ajax({
               url:"../../storePushNotif",
               method:"POST",
               data: new FormData(this),
               contentType: false,
               cache:false,
               processData: false,
               dataType:"json",
               beforeSend: function(){
               $('.loader').css("visibility", "visible");
               $("#action_push").prop("disabled", true);
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
                 $('#sample_push')[0].reset();
                 alert('Data berhasil disimpan!');
                 location.reload();
               }

             },
               complete: function(){
                 $('.loader').css("visibility", "hidden");
               }
            })
      });

      

      $( function() {
        $('#datepicker').datepicker({ dateFormat: 'dd/mm/yy' }).val();
      } );

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

      $(document).on('click', '.detail', function(){
          var id = $(this).attr('id');
          $.ajax({
          url : '{{ route("getDetailPencairan") }}',
              type: 'POST',
              headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              data:{id:id},
              success:function(data){
              $('#detailPencairan').html(data)
              },
          });
      });

      $(document).on('click', '.push', function(){
          var id = $(this).attr('id');
          console.log(id);
          $('#form_push').html('');
          $.ajax({
          url:"/pencairan/"+id+"/edit",
          dataType:"json",
          success:function(html){
          $('#id_pencairan').val(html.data.id);
          $('#id_pengajuan').val(html.data.id_pengajuan);
          $('#nominal').val(html.data.nominal);
          $('#hidden_id').val(html.data.id);
          $('#action_push').val("Push");
          $('#formModalPush').modal('show');
          }
        })
      });

      $(document).on('click', '.detail-push', function(){
          var id = $(this).attr('id');
          console.log(id);
          $('#form_push_detail').html('');
          $.ajax({
          url:"/pencairan/"+id+"/detailPush",
          dataType:"json",
          success:function(html){
          $('#id_pencairan_detail').val(html.data.id_pencairan);
          $('#id_pengajuan_detail').val(html.data.id_pengajuan);
          $('#nominal_detail').val(html.data.nominal);
          $('#keterangan_detail').val(html.data.keterangan);
          $('#action_finance').val("Push");
          $('#formModalFinance').modal('show');
          }
        })
      });

  </script>

    <!-- Modal -->
    <div class="modal fade" id="formModalDetail" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Detail Settlement</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="material-icons">close</i>
                    </button>
                </div>
                <div id="detailPencairan"></div>

            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!-- Modal Push -->
    <div class="modal fade" id="formModalFinance" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">PUSH DANA PENGAJUAN</h5>
                </div>
                <div class="modal-body">
                  <span id="form_finance"></span>
                  <form id="sample_finance">
                    @csrf
                        <input type="hidden" name="id_pencairan" id="id_pencairan_detail" />
                        <input type="hidden" name="id_pengajuan" id="id_pengajuan_detail" />
                        <input type="hidden" name="id_user" value="{{Auth::user()->id}}" />
                        <textarea class="form-control" name="keterangan" id="keterangan_detail"></textarea>
                        <hr>
                        @if(Auth::user()->jabatan=='Finance')
                          <div class="panel panel-default">
                              <div class="panel-body">Apakah Anda ingin meneruskan 'push' permintaan pencairan dana ini kepada owner? </div>
                          </div>
                          <div class="modal-footer">
                              <a href="javascript:history.go(0)" class="btn btn-secondary">Kembali</a>
                              <button type="" class="btn btn-primary" name="action_finance" id="action_finance">Push</button>
                          </div>
                        @elseif(Auth::user()->jabatan=='Owner' || Auth::user()->jabatan=='superadmin')
                          <div class="modal-footer">
                              <a href="javascript:history.go(0)" class="btn btn-success">OK</a>
                          </div>
                        @endif
                  </form>
            </div>
        </div>
    </div>
    <!-- End Modal Push-->

    <script type="text/javascript">
      
      $('#sample_finance').on('submit', function(event){
            event.preventDefault();
            $("#action_finance").prop("disabled", true);
               $.ajax({
               url:"../../storePushNotifOwner",
               method:"POST",
               data: new FormData(this),
               contentType: false,
               cache:false,
               processData: false,
               dataType:"json",
               beforeSend: function(){
               $('.loader').css("visibility", "visible");
               $("#action_finance").prop("disabled", true);
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
                 $('#sample_finance')[0].reset();
                 alert('Data berhasil disimpan!');
                 location.reload();
               }

             },
               complete: function(){
                 $('.loader').css("visibility", "hidden");
               }
            })
      });
    </script>

  @endpush
  @endsection
