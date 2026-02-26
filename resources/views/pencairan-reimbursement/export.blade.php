<?php

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Export Settlement.xls");

function nominal($angka){
    
    $hasil_rupiah = number_format($angka,2,'.',',');
    return $hasil_rupiah;
 
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Export Settlement  </title>
    <style type="text/css">
        .table-laporan {
            border-collapse: collapse;
            font-size: 12px;
        }
        .bordernya {
            border: 1px solid black !important;
            
        }
        .bordernyanol {
            border: 0px !important;
        }
        .bordernya > th {
            border-collapse: collapse;
            border: 1px solid black !important;
            text-align: center;
            vertical-align: middle !important;
        }
        .bordernya > td {
            border-collapse: collapse;
            border: 1px solid black !important;
        }
        .bordernyanol > td {
            border-collapse: collapse;
            border: 0px !important;
        }
    </style>
</head>
<body>

    <center>
        <h4>
            EXPORT SETTLEMENT
        </h4>
    </center>

    <table class="table-laporan" style="margin-top: 0px" border="1"><br/>
        <tr bgcolor="grey">
            <th><center>Inquiry No</center></th>
            <th><center>Type</center></th>
            <th><center>Apply Date</center></th>
            <th><center>Transaction Date</center></th>
            <th><center>Employee</center></th>
            <th><center>Total Inquiry</center></th>
            <th><center>Status</center></th>
        </tr>

        @foreach($data as $row)
         <tr>
            <td><center>{{$row->no_reimbursement}}</center></td>
            <td><center>
                @if($row->reimbursement_type==1)
                    DRIVER
                @elseif($row->reimbursement_type==2)
                    TRAVEL
                @else
                    ENTERTAINMENT
                @endif
            </center></td>
            <td><center>{{$row->created_at}}</center></td>
            <td><center>{{$row->date}}</center></td>
            <td><center>{{$row->name}}</center></td>
            <td><center>{{nominal($row->nominal_pengajuan)}}</center></td>
            <td><center>
                @if($row->status==5)
                    SETTLE
                @else
                    PROCESS SETTLEMENT
                @endif
                </center>
            </td>
        </tr>
        @endforeach        

    </table>