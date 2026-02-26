<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Entertainment Reimburesment</title>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
        }
        @page {
            size: 'A4'
        }
        table td, table th {
            padding: 8px;
        }

        .table-style {
            border-collapse: collapse; /* Ensures borders are consistent and printable */
            width: 100%; /* Optional: Adjusts table width to fit printable area */
        }

        .table-style th,.table-style td {
            border: 1px solid black; /* Defines solid black borders for table, headers, and cells */
            padding: 8px; /* Adjust padding for readability */
        }
       
    </style>
</head>
<?php 
function nominal($angka){
    
    $hasil_rupiah = number_format($angka,0,'.',',');
    return $hasil_rupiah;
 
}
?>
<body>
    <div class="report">

        <center>
            <h3>Entertainment Reimbursement</h3>
            <p>{{$_GET['start']}} - {{$_GET['end']}}</p>
        </center>
        <br>
        <br>
        @foreach ($data as $row)
        <p>
            <table style="width: 100%">
                <tr>
                    <td>INQUIRY NO : <b>{{$row->no_reimbursement}}</b></td>
                    
                    <td>INQUIRY BY : <b>{{$row->name}}</b></td>
                    
                    <td>APPLY DATE : <b>{{ date('Y-m-d', strtotime($row->created_at)) }}</b></td>
                    
                </tr>
            </table>
            

            <table class="table-style table-bordered mb-2">
                <tr>
                    <th>Transaction Date</th>
                    <th>Empty Zone</th>
                    <th>Attendance</th>
                    <th>Position</th>
                    <th>Place</th>
                    <th>Guest</th>
                    <th>Guest Position</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>Payment</th>
                    <th>Amount</th>
                </tr>

                @foreach($detail as $item)
                @if($item->reimbursement_id == $row->id_main)
                <tr>
                    <td>{{$row->date}}</td>
                    <td>{{$item->empty_zone}}</td>
                    <td>{{$item->attendance}}</td>
                    <td>{{$item->position}}</td>
                    <td>{{$item->place}}</td>
                    <td>{{$item->guest}}</td>
                    <td>{{$item->guest_position}}</td>
                    <td>{{$item->company}}</td>
                    <td>{{$item->type}}</td>
                    <td>{{$item->payment_type}}</td>
                    <td>{{nominal($item->amount)}}</td>
                </tr>
                @endif
                @endforeach
                <tr>
                    <td colspan="10"><span style="float: right;">Total</span></td>
                    <td>{{nominal($row->nominal_pengajuan)}}</td>
                </tr>
            </table>

            <br>
            <br>
        @endforeach
        </p>

        <table class="table-style table-bordered mb-2">
            <tr>
                <td class="bg-secondary" colspan="6"><center><strong>Checker's Sheet BDC</strong></center></td>
            </tr>
            <tr>
                <td class="bg-secondary"><strong>Total Payment to be paid</strong></td>
                <td class="bg-secondary"><strong>{{number_format($bdc,0,',','.')}}</strong></td>
            </tr>
        </table>

        <br><br>

        <table class="table-style table-bordered mb-2">
            <tr>
                <td class="bg-secondary" colspan="6"><center><strong>Checker's Sheet Cash</strong></center></td>
            </tr>
            <tr>
                <td class="bg-secondary"><strong>Total Payment to be paid</strong></td>
                <td class="bg-secondary"><strong>{{number_format($total_cash,0,',','.')}}</strong></td>
            </tr>
        </table>

        <br><br><br><br>

        <table style="width: 100%">
            <thead>
                <tr>
                    <th>Head Department </th>
                    <th width="2px"></th>
                    <th>HR GA </th>
                    <th width="2px"></th>
                    <th>Finance</th>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #000">
                        @if($_GET['status']==1 || $_GET['status']==2 || $_GET['status']==3 || $_GET['status']==5)
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($head_dept)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{strtoupper($head_dept)}}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                    <td style="border-bottom: 1px solid #000">
                        @if($_GET['status']==2 || $_GET['status']==3 || $_GET['status']==5)
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($row->mengetahui_finance)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{strtoupper($row->mengetahui_finance)}}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                    <td style="border-bottom: 1px solid #000">
                        @if($_GET['status']==3 || $_GET['status']==5)
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($row->mengetahui_owner)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{strtoupper($row->mengetahui_owner)}}</center>
                        @endif
                    </td>
                    <th width="2px"></th>
                    
                </tr>
            </thead>
        </table>

        


    <script>window.print()</script>
</body>
</html>