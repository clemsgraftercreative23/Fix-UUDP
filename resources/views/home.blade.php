@extends('template.app')

@section('content')

<?php 
function rupiah($angka) {
    return number_format($angka, 0, ',', '.');
}
?>

<style>
    #myTables_wrapper .row:nth-child(2) .col-sm-12 {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #sparkline-chart-1 {
        width: calc(100% + 51px);
        height: 100px;
        margin: 80px -26px -27px -25px;
    }
</style>

<div class="page-content">
    @if(Auth::user()->jabatan!='karyawan')
    <div class="clearfix">
         <a href="{!!url('home')!!}" class="btn btn-success float-left" style="width: 48%;">My Inquiry</a>
         <a href="{!!url('home-all')!!}" class="btn btn-info float-right" style="width: 48%;">All Inquiry</a>
    </div>
    <br><br>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card savings-card">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center justify-content-between">
                        My Reimbursement Overview 
                    </h5>
                    <hr>
                    <br>
                    <div class="savings-stats">
                        <h5>Total Reimbursement: Rp {{ rupiah($total) }}</h5>
                    </div>
                    <br>
                    <div>
                        <canvas id="myChart"></canvas>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card savings-card">
                <div class="card-body">
                    <h5 class="card-title">MY SETTLEMENT OVERVIEW</h5>
                    <hr>
                    <div class="savings-stats">
                        <h2>Rp {{rupiah($settlement)}}</h2>
                        <span>Total settlement reimbursement {{date('Y')}}</span>
                    </div>
                    <div id="sparkline-chart-1"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card card-transactions">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center justify-content-between">
                        My History Reimbursement
                    </h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table  class="table display" style="width: 1080px">
                                    <thead>
                                        <tr>
                                            <th>Inquiry No</th>
                                            <th>Apply Date</th>
                                            <th>Transaction Date</th>
                                            <th>Inquiry By</th>
                                            <th>Remark</th>
                                            <th>Total Inquiry</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reim as $row)
                                        <tr>
                                            <td>{{$row->no_reimbursement}}</td>
                                            <td>{{$row->created_at}}</td>
                                            <td>{{$row->date}}</td>
                                            <td>{{$row->name}}</td>
                                            <td>{{$row->remark}}</td>
                                            <td>{{rupiah($row->nominal_pengajuan)}}</td>
                                            <td>
                                                @if($row->status==1)
                                                    APPROVED HEAD DEPT
                                                @elseif($row->status==2)
                                                    APPROVED HR GA
                                                @elseif($row->status==3)
                                                    APPROVED FINANCE
                                                @elseif($row->status==5)
                                                    SETTLED
                                                @elseif($row->status==9)
                                                    REJECT
                                                @elseif($row->status==0)
                                                    PENDING
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!---CASH ADVANCE-->
    <div class="row">
        <div class="col-lg-8">
            <div class="card savings-card">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center justify-content-between">
                        My Cash Advance Overview 
                    </h5>
                    <hr>
                    <br>
                    <div class="savings-stats">
                        <h5>Total Cash Advance: Rp {{ rupiah($total_cash) }}</h5>
                    </div>
                    <br>
                    <div>
                        <canvas id="myChart1"></canvas>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card savings-card">
                <div class="card-body">
                    <h5 class="card-title">MY SETTLEMENT OVERVIEW</h5>
                    <hr>
                    <div class="savings-stats">
                        <h2>Rp {{rupiah($settlement_cash)}}</h2>
                        <span>Total settlement cash advance {{date('Y')}}</span>
                    </div>
                    <div id="sparkline-chart-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card card-transactions">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center justify-content-between">
                        My History Cash Advance
                    </h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table  class="table display" style="width: 1080px">
                                    <thead>
                                        <tr>
                                            <th>Inquiry No</th>
                                            <th>Apply Date</th>
                                            <th>Project No</th>
                                            <th>Project Name</th>
                                            <th>Inquiry By</th>
                                            <th>Total Inquiry</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cash as $row)
                                        <tr>
                                            <td>{{$row->no_pengajuan}}</td>
                                            <td>{{$row->created_at}}</td>
                                            <td>{{$row->no_project}}</td>
                                            <td>{{$row->nama}}</td>
                                            <td>{{$row->name}}</td>
                                            <td>{{rupiah($row->nominal_pengajuan)}}</td>
                                            <td>
                                               
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

</div>

<!-- Modal -->
<div class="modal fade" id="modalPassword" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">UPDATE PROFILE</h5>
            </div>
            <div class="modal-body">
                <span id="form_result_add"></span>
                <p>Harap perbarui profil Anda segera. Klik tombol 'Perbarui Profil' dan lengkapi dengan detail yang akurat. <br> Ini wajib dilakukan untuk memastikan profil Anda mencerminkan diri Anda dengan tepat!</p>
                <div class="modal-footer">
                    <a href="{!! url('profile') !!}" class="btn btn-primary">Perbaharui Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Modal -->

<script type="text/javascript">
    $(document).ready(function() {
        @if (Auth::user()->status_password != 1)
            $('#modalPassword').modal('show');
        @endif

        load_data();

        function load_data(firsts = '') {
            $('#myTables').dataTable({
                processing: false,
                serverSide: false,
                bPaginate: true,
                bLengthChange: false,
                bFilter: false,
                bInfo: false,
                bAutoWidth: false,
                pageLength: 5,
                order: [],
                ajax: {
                    url: '{{ url("pengajuan") }}',
                    data: { firsts: firsts }
                },
                columns: [
                    { data: 'no_pengajuan', name: 'no_pengajuan' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'no_project', name: 'no_project' },
                    { data: 'nama', name: 'nama' },
                    { data: 'total', name: 'total' },
                    { data: 'action', name: 'action' },
                ],
            });
        }

        $('#firsts').click(function() {
            var firsts = $('#firsts').val();
            $('#myTables').dataTable().fnDestroy();
            load_data(firsts);
        });
    });
</script>
@endsection
