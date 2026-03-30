<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Entertainment Reimbursement</title>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
        }
        @page {
            size: 'A4'
        }
        table td, table th {
            padding: 6px;
        }

        .table-style {
            border-collapse: collapse;
            width: 100%;
        }

        .table-style th,.table-style td {
            border: 1px solid black;
            padding: 6px;
            vertical-align: top;
        }

        .header-meta td {
            border: none;
            padding: 2px 8px 2px 0;
        }

        .evidence-link {
            word-break: break-all;
            font-size: 7pt;
        }
    </style>
</head>
@php
function nominal($angka){
    $hasil_rupiah = number_format((float) $angka,0,'.',',');
    return $hasil_rupiah;
}
function entertainmentStatusLabel($s) {
    $map = [
        0 => 'PENDING',
        1 => 'APPROVED HEAD DEPT',
        2 => 'APPROVED HR GA',
        3 => 'APPROVED FINANCE / PROCESS SETTLEMENT',
        4 => 'REJECTED',
        5 => 'SETTLED',
        9 => 'REJECT',
        10 => 'DRAFT',
    ];
    return $map[(int) $s] ?? (string) $s;
}
$printStatus = request('status');
$signRow = $data->last();
@endphp
<body>
    <div class="report">

        <center>
            <h3>Entertainment Reimbursement</h3>
            <p>{{ request('start') }} - {{ request('end') }}</p>
        </center>
        <br>
        <br>
        @foreach ($data as $row)
        <p>
            <table class="header-meta" style="width: 100%; margin-bottom: 8px;">
                <tr>
                    <td><strong>INQUIRY NO</strong></td>
                    <td>: <strong>{{ $row->no_reimbursement }}</strong></td>
                    <td><strong>APPLY DATE</strong></td>
                    <td>: <strong>{{ date('Y-m-d', strtotime($row->created_at)) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>INQUIRY BY</strong></td>
                    <td>: <strong>{{ $row->name }}</strong></td>
                    <td><strong>TRANSACTION DATE</strong></td>
                    <td>: <strong>{{ $row->date }}</strong></td>
                </tr>
                <tr>
                    <td><strong>NIK</strong></td>
                    <td>: {{ $row->nik ?? '-' }}</td>
                    <td><strong>STATUS</strong></td>
                    <td>: {{ entertainmentStatusLabel($row->reimbursement_status) }}</td>
                </tr>
                <tr>
                    <td><strong>DEPARTMENT</strong></td>
                    <td colspan="3">: {{ $row->nama_departemen ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>REMARK (HEADER)</strong></td>
                    <td colspan="3">: {{ $row->remark_header ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>TOTAL INQUIRY</strong></td>
                    <td colspan="3">: {{ nominal($row->nominal_pengajuan) }}</td>
                </tr>
            </table>

            <table class="table-style table-bordered mb-2">
                <tr>
                    <th>Transaction Date</th>
                    <th>No of Attendance</th>
                    <th>Attendance</th>
                    <th>Position</th>
                    <th>Place</th>
                    <th>Guest</th>
                    <th>Guest Position</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Remark (Detail)</th>
                    <th>Evidence</th>
                </tr>

                @foreach($detail as $item)
                @if($item->reimbursement_id == $row->id_main)
                <tr>
                    <td>{{ $row->date }}</td>
                    <td>{{ $item->empty_zone }}</td>
                    <td>{{ $item->attendance }}</td>
                    <td>{{ $item->position }}</td>
                    <td>{{ $item->place }}</td>
                    <td>{{ $item->guest }}</td>
                    <td>{{ $item->guest_position }}</td>
                    <td>{{ $item->company }}</td>
                    <td>{{ $item->type }}</td>
                    <td>{{ $item->payment_type }}</td>
                    <td>{{ nominal($item->amount) }}</td>
                    <td>{{ $item->remark }}</td>
                    <td class="evidence-link">
                        @if(!empty($item->evidence))
                            <a href="{{ url('images/file_bukti/'.$item->evidence) }}" target="_blank">{{ $item->evidence }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endif
                @endforeach
                <tr>
                    <td colspan="10" align="right"><strong>Total</strong></td>
                    <td><strong>{{ nominal($row->nominal_pengajuan) }}</strong></td>
                    <td></td>
                    <td></td>
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
                <td class="bg-secondary"><strong>{{ number_format($bdc,0,',','.') }}</strong></td>
            </tr>
        </table>

        <br><br>

        <table class="table-style table-bordered mb-2">
            <tr>
                <td class="bg-secondary" colspan="6"><center><strong>Checker's Sheet Cash</strong></center></td>
            </tr>
            <tr>
                <td class="bg-secondary"><strong>Total Payment to be paid</strong></td>
                <td class="bg-secondary"><strong>{{ number_format($total_cash,0,',','.') }}</strong></td>
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
                        @if(in_array((string) $printStatus, ['1','2','3','5'], true))
                            <center><img src="{!! url('access/images/ttd.png') !!}" style="width:200px;height:100px;object-fit:contain"><br>{{ strtoupper($head_dept ?? '') }}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{ strtoupper($head_dept ?? '') }}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                    <td style="border-bottom: 1px solid #000">
                        @if(in_array((string) $printStatus, ['2','3','5'], true))
                            <center><img src="{!! url('access/images/ttd.png') !!}" style="width:200px;height:100px;object-fit:contain"><br>{{ strtoupper($signRow->mengetahui_finance ?? '') }}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{ strtoupper($signRow->mengetahui_finance ?? '') }}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                    <td style="border-bottom: 1px solid #000">
                        @if(in_array((string) $printStatus, ['3','5'], true))
                            <center><img src="{!! url('access/images/ttd.png') !!}" style="width:200px;height:100px;object-fit:contain"><br>{{ strtoupper($signRow->mengetahui_owner ?? '') }}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{ strtoupper($signRow->mengetahui_owner ?? '') }}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                </tr>
            </thead>
        </table>

    <script>window.print()</script>
</body>
</html>
