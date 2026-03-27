<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Driver Reimburesment</title>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt
            
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
<body>
    <div class="report">

        <center>
            <h3>Driver Reimbursement</h3>
            <p>{{$start_date}} - {{$end_date}}</p>
        </center>
        <br>
        <br>
        @if ($user)
            <table>
                <tr>
                    <td>Driver</td>
                    <td>: {{$user->name}}</td>
                </tr>
                @if (count($data) > 0)
                    
                <tr>
                    <td>Car No</td>
                    <td>: {{$user->vehicleNo}}</td>
                </tr>
                @endif
            </table>
            
        @endif
        <table class="table-style">
            <thead>
                <tr>
                    <th rowspan="2">Inquiry Number</th>
                    <th rowspan="2">Apply Date</th>
                    <th rowspan="2">Transaction Date</th>
                    <th colspan="4">Description</th>
                    <th rowspan="2">Total</th>                
                    <th rowspan="2">Payment Type</th>
                    <th rowspan="2">Remark</th>
                </tr>
                <tr>
                    <th>Toll</th>
                    <th>Gasoline</th>
                    <th>Parking</th>
                    <th>Others</th>
                </tr>
            
            @foreach ($detail as $item)
                <tr>
                    <td>{{$item->no_reimbursement}}</td>
                    <td>{{date('Y-M-d', strtotime($item->created_at))}}</td>
                    <td>{{date('Y-M-d', strtotime($item->date))}}</td>
                    <td>{{number_format($item->toll,0,'.','.')}}</td>
                    <td>{{number_format($item->gasoline,0,'.','.')}}</td>
                    <td>{{number_format($item->parking,0,'.','.')}}</td>
                    <td>{{number_format($item->others,0,'.','.')}}</td>
                    <td>{{number_format($item->subtotal,0,'.','.')}}</td>
                    <td>{{$item->payment_type}}</td>
                    <td>{{$item->remark}}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="3">Total</th>
                <td>{{number_format($total_toll,0,'.','.')}}</td>
                <td>{{number_format($total_gasoline,0,'.','.')}}</td>
                <td>{{number_format($total_parking,0,'.','.')}}</td>
                <td>{{number_format($total_others,0,'.','.')}}</td>
                <td>{{number_format($total,0,'.','.')}}</td>
                <td></td>
                <td></td>
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
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($data[0]->mengetahui_op)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{strtoupper($data[0]->mengetahui_op)}}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                    <td style="border-bottom: 1px solid #000">
                        @if($_GET['status']==2 || $_GET['status']==3 || $_GET['status']==5)
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($data[0]->mengetahui_finance)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{strtoupper($data[0]->mengetahui_finance)}}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                    <td style="border-bottom: 1px solid #000">
                        @if($_GET['status']==3 || $_GET['status']==5)
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($data[0]->mengetahui_owner)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{strtoupper($data[0]->mengetahui_owner)}}</center>
                        @endif
                    </td>
                    <th width="2px"></th>
                    
                </tr>
            </thead>
        </table>
        
    </div>



    <script>window.print()</script>
</body>
</html>