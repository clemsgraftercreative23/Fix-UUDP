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
        .paid-watermark {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 92px;
            font-weight: 700;
            color: rgba(200, 30, 30, 0.22);
            z-index: 9999;
            pointer-events: none;
            user-select: none;
        }
       
    </style>
</head>
@php
    $firstRow = (isset($data[0]) ? $data[0] : null);
    $firstReimbursementNo = $firstRow->no_reimbursement ?? null;
    $firstReimbursement = $firstReimbursementNo
        ? \App\Reimbursement::where('no_reimbursement', $firstReimbursementNo)->first()
        : null;
    $statusFromQuery = $_GET['status'] ?? null;
    $recordStatus = (int) ($firstReimbursement->status ?? 0);
    $effectivePrintStatus = $recordStatus;
    if ($statusFromQuery !== null && $statusFromQuery !== '' && $statusFromQuery !== 'null') {
        $effectivePrintStatus = (int) $statusFromQuery;
    }
    $isSettledPrint = ($effectivePrintStatus === 5) || ((int) ($firstReimbursement->status ?? 0) === 5);
    $showHeadDeptStamp = in_array($effectivePrintStatus, [1, 2, 3, 5, 11], true)
        || (!empty(optional($firstReimbursement)->mengetahui_op) && optional($firstReimbursement)->mengetahui_op !== '-');
    $showHrGaStamp = in_array($effectivePrintStatus, [2, 3, 5, 11], true)
        || (!empty(optional($firstReimbursement)->mengetahui_finance) && optional($firstReimbursement)->mengetahui_finance !== '-');
    $showFinanceStamp = in_array($effectivePrintStatus, [3, 5], true)
        || (!empty(optional($firstReimbursement)->mengetahui_owner) && optional($firstReimbursement)->mengetahui_owner !== '-');
    $approvalTimes = [1 => null, 2 => null, 3 => null];
    if ($firstReimbursement) {
        $approvalLogs = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->where('module', 'reimbursement-driver')
            ->where('action', 'approve')
            ->where('subject_type', 'reimbursement')
            ->where('subject_id', $firstReimbursement->id)
            ->orderBy('created_at', 'asc')
            ->get(['meta_json', 'created_at']);
        foreach ($approvalLogs as $log) {
            $meta = json_decode($log->meta_json ?? '', true);
            $statusMeta = (int) ($meta['status'] ?? 0);
            if (array_key_exists($statusMeta, $approvalTimes) && empty($approvalTimes[$statusMeta])) {
                $approvalTimes[$statusMeta] = $log->created_at;
            }
        }
    }
    $formatApprovalTime = function ($statusCode) use ($approvalTimes) {
        $raw = $approvalTimes[$statusCode] ?? null;
        if (empty($raw)) {
            return '-';
        }
        return date('d-m-Y H:i', strtotime((string) $raw));
    };
@endphp
<body>
    @if($isSettledPrint)
        <div class="paid-watermark">PAID</div>
    @endif
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
                    <td>: {{$data[0]->vehicleNo}}</td>
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
            </thead>
            @php
                $no = 0;
                $toll = 0;   
                $gasoline = 0;   
                $parking = 0;   
                $others = 0;   
                $total = 0;   
            @endphp
            @foreach ($data as $item)
                @php
                    $no ++;   
                    $toll += $item->toll;   
                    $gasoline += $item->gasoline;   
                    $parking += $item->parking;   
                    $others += $item->others;   
                    $total += $item->total;   
                @endphp
                <tr>
                    <td>{{$item->no_reimbursement}}</td>
                    <td>{{date('Y-M-d', strtotime($item->created_at))}}</td>
                    <td>{{date('Y-M-d', strtotime($item->date))}}</td>
                    <td>{{number_format($item->toll,0,'.','.')}}</td>
                    <td>{{number_format($item->gasoline,0,'.','.')}}</td>
                    <td>{{number_format($item->parking,0,'.','.')}}</td>
                    <td>{{number_format($item->others,0,'.','.')}}</td>
                    <td>{{number_format($item->total,0,'.','.')}}</td>
                    <td>{{$item->payment_type}}</td>
                    <td>{{$item->remark}}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="3">Total</th>
                <td>{{number_format($toll,0,'.','.')}}</td>
                <td>{{number_format($gasoline,0,'.','.')}}</td>
                <td>{{number_format($parking,0,'.','.')}}</td>
                <td>{{number_format($others,0,'.','.')}}</td>
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
                        <center style="margin-bottom: 4px;">{{ $formatApprovalTime(1) }}</center>
                        @if($showHeadDeptStamp)
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
                        <center style="margin-bottom: 4px;">{{ $formatApprovalTime(2) }}</center>
                        @if($showHrGaStamp)
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
                        <center style="margin-bottom: 4px;">{{ $formatApprovalTime(3) }}</center>
                        @if($showFinanceStamp)
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