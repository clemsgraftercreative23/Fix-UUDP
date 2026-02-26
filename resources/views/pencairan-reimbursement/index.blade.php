@extends('template.app')

@section('content')

<div class="page-content">

<div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  
                  <div class="col-md-12">
                    <div class="row"> 
                        <div class="col-sm-6">
                            <h2 class="card-title clr-green">Settlement Reimbursment UUDP</h2>
                        </div>
                    </div>
                  </div>
                  
                  
                  
                  
                  <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label for="status">Status</label>
                            <select name="status" class="form-control select2 status">
                                <option value="">-Select Status-</option>
                                <option value="3">APPROVED FINANCE</option>
                                <option value="5">SETTLED</option>
                            </select>
                        </div>
                       
                            <div class="col-md-2 mb-2">
                                <label for="type">Type</label>
                                <select name="type" class="form-control select2 type">
                                    <option value="">-Choose Type-</option>
                                    <option value="1">DRIVER</option>
                                    <option value="2">TRAVEL</option>
                                    <option value="3">ENTERTAINMENT</option>
                                </select>
                            </div>
                  
                        @if (auth()->user()->jabatan != "karyawan")
                            <div class="col-md-3 mb-3">
                                <label for="user_id">Employee</label>
                                <select name="user_id" class="form-control select2 user_id">
                                    <option value="">-Choose Employee-</option>
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label for="daterange">Period</label>
                            <input type="text" name="daterange" class="form-control daterange"/>
                        </div>
                        <div class="col-md-2 mb-4">
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <button class="btn btn-primary d-block search-data" style="margin-top:32px"><i class="fa fa-search"></i></button>
                                <button class="btn btn-primary d-block reset-data" style="margin-top:32px"><i class="fas fa-sync-alt fa-fw"></i></button>
                                <button class="btn btn-primary d-block export-data" style="margin-top:32px"><i class="fa fa-download fa-fw"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                  
                  
                  
                </div>
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
                

                  <table id="myTable" class="display" style="width:1080px"  >
                      <thead>
                          <tr>
                              <th>Inquiry No</th>
                              <th>Type</th>
                              <th>Apply Date</th>
                              <th>Transaction Date</th>
                              <th>Employee</th>
                              <th>Total Inquiry</th>
                              <th>Status Inquiry</th>
                          </tr>
                      </thead>
                      <tbody>


                      </tbody>

                  </table>
              </div>
          </div>
      </div>
  </div>
  {{-- < --}}
{{-- </form> --}}
</div>
@include('pengajuan.op')

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



</div>
<!-- Modal -->
<div class="modal fade" id="modalPhoto"  data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Upload Gambar</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <i class="material-icons">close</i>
              </button>
          </div>
          <div class="modal-body">
            <video id="videoElement" autoplay style="width: 100%"></video>
            <canvas id="canvas"></canvas>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button id="captureButton" class="btn btn-success">Capture Image</button>
            </div>
      </div>
  </div>
  </div>

<!-- End Modal -->

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script type="text/javascript">
$(document).ready(function(){
    @if(Auth::user()->status_password != 1)
        $('#modalPassword').modal('show');
    @endif

    load_data();

    function load_data(first = '' , last = '' , status = '', user_id = '', type= '') {
      // function load_data() {
        $('#myTable').dataTable({

          processing: false,
          serverSide: false,
          "bPaginate": true,
          "bLengthChange": false,
          "bFilter": false,
          "bInfo": false,
          "bAutoWidth": false,
          "pageLength": 10,
          "order": [],
           ajax: {
            url:'{{ url("pencairan-reimbursement") }}',
            data:{first:first,last:last,status:status, user_id:user_id, type:type}
           },
           columns: [

                    {
                      data: 'no_reimbursement',
                      name: 'no_reimbursement'
                    },
                    {
                      data: 'reimbursement_type',
                      name: 'reimbursement_type'
                    },
                    {
                      data: 'created_at',
                      name: 'created_at'
                    },
                    {
                      data: 'date',
                      name: 'date'
                    },
                    {
                      data: 'name',
                      name: 'name'
                    },
                    {
                      data: 'nominal_pengajuan',
                      name: 'nominal_pengajuan'
                    },
                    {
                      data: 'action',
                      name: 'action'
                    },

              ],
      });
    }

    var start = moment().startOf('month');
    var end = moment().endOf('month');
    this.start = start.format('YYYY-MM-DD');
    this.end = end.format('YYYY-MM-DD');

    $(function() {
        $('input.daterange').daterangepicker({
            startDate: start,
            endDate: end,
            opens: 'left'
        }, function(start, end, label) {
            console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
        });
    });

    $('select[name="status"]').on('change', function(){
        var status = $(this).val();
        if(status) {
            $.ajax({
                url: 'settlement-user?status='+status+'',
                type:"GET",
                dataType:"json",
                beforeSend: function(){
                
                },
                success:function(data) {
                    $('select[name="user_id"]').empty();
                    $('select[name="user_id"]').append('<option value="">-Select Employee-</option>')
                    $.each(data, function(key, value){
                    $('select[name="user_id"]').append('<option value="'+ value.id +'">' + value.name + '</option>');
                    });


                },
            });
        } else {
            $('select[name="user_id"]').empty();
        }
    });

    $(".search-data").click(function(){
        var dateRange = $('.daterange').val();

        var dates = dateRange.split(' - ');
        var startDate = dates[0];
        var endDate = dates[1];

        var parts = startDate.split("/"); 
        var start = parts[2] + '-' + parts[0] + '-' + parts[1];

        var partsend = endDate.split("/"); 
        var end = partsend[2] + '-' + partsend[0] + '-' + partsend[1];

        if ($.fn.dataTable.isDataTable('#myTable')) {
            $('#myTable').DataTable().clear().destroy();
        }

        var status = $('.status').val();
        var user_id = $('.user_id').val();
        var type = $('.type').val();
        
        load_data(start, end, status, user_id, type);
        
    });

    $(".reset-data").click(function(){
        
        if ($.fn.dataTable.isDataTable('#myTable')) {
            $('#myTable').DataTable().clear().destroy();
        }

        var status = $('.status').val("");
        var user_id = $('.user_id').val("");
        var type = $('.type').val("");

        load_data();
        
    });

    $(".export-data").click(function(){
        
        var dateRange = $('.daterange').val();

        var dates = dateRange.split(' - ');
        var startDate = dates[0];
        var endDate = dates[1];

        var parts = startDate.split("/"); 
        var start = parts[2] + '-' + parts[0] + '-' + parts[1];

        var partsend = endDate.split("/"); 
        var end = partsend[2] + '-' + partsend[0] + '-' + partsend[1];
    

        var status = $('.status').val();
        var user_id = $('.user_id').val();
        var type = $('.type').val();
                
        location.href = 'export-settlement?start='+start+'&end='+end+'&status='+status+'&user_id='+user_id+'&type='+type+'';
        
    });

  });
    
</script>

@endpush
@endsection
