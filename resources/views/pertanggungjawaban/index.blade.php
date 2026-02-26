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
                <div class="col-md-9">
                    <div class="row">
                    <div class="col-lg-3"> 
                    <h2 class="card-title clr-green">Settlement UUDP</h2>
                  </div>
                  <div class="col-md-4 col-lg-3">
                     <p> Total Dana Diterima: </p>
                     <label class="card-title" id="totaldana" style="font-size:15px; color:#62d49e;">Rp. 0</label>
                  </div>
                  <div class="col-md-4 col-lg-3">
                    <p  > Sudah Dilaporkan: </p>
                     <label class="card-title" id="dilaporkan" style="font-size:15px; color:#62d49e;">Rp. 0</label>
                  </div>
                  <div class="col-md-4 col-lg-3">
                     <p > Belum Dilaporkan: </p>
                     <label class="card-title" id="belumdilaporkan" style="font-size:15px; color:#62d49e;">Rp. 0</label>
                  </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                <div class="dropdown">
                      <select class="btn-outline-success btn-sm btn-block dropdown-toggle" style="border-radius: 7px; border: 1px solid #5fd0a5;   padding: 7px 15px; font-size: 12px; height: 37px;" id="first" name="first">
                        <option value="">First Month</option>
                        <option value="01">Januari</option>
                        <option value="02">Februari</option>
                        <option value="03">Maret</option>
                        <option value="04">April</option>
                        <option value="05">Mei</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">Agustus</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                      </select>

                    </div>

                </div>
                   
                </div>

                  <table id="myTable" class="display" style="width:1200px">
                      <thead>
                          <tr>
                              <th>Inquiry No</th>
                              <th>Project ID</th>
                              <th>Mengetahui</th>
                              <th>Menyetujui</th>
                              <th>Total Inquiry</th>
                              <th>Accountability Report</th>
                              <th>Laporan / Selesai</th>
                          </tr>
                      </thead>
                      <tbody>
                      </tbody>
                  </table>

              </div>
          </div>
      </div>
  </div>

  <!-- Modal Report -->
  <div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel"><span style="color:#66da90;">LAPORAN PERTANGGUNGJAWABAN KEGIATAN</span></h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
              </div>
              <div id="addPertanggungjawaban"></div>
          </div>
      </div>
  </div>
  <!-- End Modal Report-->

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

  <script>

      $(document).ready(function(){
        
      <?php if (Auth::user()->status_password != 1) { ?>
        $('#modalPassword').modal('show');
      <?php } ?>

      load_data();
      function load_data(first = '') {
      $('#myTable').dataTable({

        processing: false,
        serverSide: false,
        "bPaginate": true,
        "bLengthChange": false,
        "bFilter": false,
        "bInfo": false,
        "bAutoWidth": false,
        "pageLength": 5,
         ajax: {
          url:'{{ url("pertanggungjawaban") }}',
          data:{first:first}

         },
         columns: [
                {
                    data: 'no_pengajuan',
                    name: 'no_pengajuan',
                    render: function(data, type, row, meta) {
                    if (type === 'display') {
                        data = '<a href="insertPertanggungjawaban/' + data + '">' + data + '</a>';
                    }
                    return data;
                    }
                },
                {
                  data: 'no_project',
                  name: 'no_project'
                },
                {
                  data: 'mengetahui',
                  name: 'mengetahui'
                },
                {
                  data: 'menyetujui',
                  name: 'menyetujui'
                },
                {
                  data: 'nominal_pengajuan',
                  name: 'nominal_pengajuan'
                },
                {
                  data: 'pertanggungjawaban',
                  name: 'pertanggungjawaban'
                },
                {
                  data: 'action',
                  name: 'action'
                },
            ],
      });
      }

      totaldana();
        function totaldana(first = '',last = ''){

          $.ajax({
              url: '/pertanggungjawaban/totaldana/',
              type:"GET",
              dataType:"json",
              data:{first:first},

              success:function(data) {
      document.getElementById('totaldana').innerHTML = 'Rp. '+data[0];

              },

          });
        }

        dilaporkan();
          function dilaporkan(first = '',last = ''){

            $.ajax({
                url: '/pertanggungjawaban/dilaporkan/',
                type:"GET",
                dataType:"json",
                data:{first:first},

                success:function(data) {
        document.getElementById('dilaporkan').innerHTML = 'Rp. '+data[0];

                },

            });
          }

          belumdilaporkan();
            function belumdilaporkan(first = '',last = ''){

              $.ajax({
                  url: '/pertanggungjawaban/belumdilaporkan/',
                  type:"GET",
                  dataType:"json",
                  data:{first:first},

                  success:function(data) {
          document.getElementById('belumdilaporkan').innerHTML = 'Rp. '+data[0];

                  },

              });
            }

      $('#first').click(function(){
        var first = $('#first').val();
        // var last = $('#last').val();
        $('#myTable').dataTable().fnDestroy();
        load_data(first);
        totaldana(first);
        dilaporkan(first);
        belumdilaporkan(first);

       });



      });



      $(document).on('click', '.report', function(){
        var id = $(this).attr('id');
        $.ajax({
        url : '{{ route("addPertanggungjawaban") }}',
          type: 'POST',
          headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          data:{id:id},
          success:function(data){
          $('#addPertanggungjawaban').html(data)
          },
        });
      });


  </script>

  @endpush
  @endsection
