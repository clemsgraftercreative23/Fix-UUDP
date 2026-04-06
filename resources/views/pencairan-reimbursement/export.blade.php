<?php

header('Content-type: application/vnd-ms-excel');
header('Content-Disposition: attachment; filename=Export_Settlement_Detail.xls');

function export_nominal($angka)
{
    return number_format((float) $angka, 0, '.', ',');
}

function export_date_ymd($value)
{
    if (empty($value)) {
        return '-';
    }
    try {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    } catch (\Exception $e) {
        return (string) $value;
    }
}

function export_status_label($row)
{
    $meng = 'HEAD DEPARTMENT';
    if ($row->mengetahui_op == '-') {
        $meng = 'HEAD DEPARTMENT';
    } elseif ($row->mengetahui_finance == '-') {
        $meng = 'HR GA';
    } elseif ($row->mengetahui_owner == '-') {
        $meng = 'FINANCE';
    }

    switch ((string) $row->status) {
        case '1':
            return 'APPROVED HEAD DEPARTMENT';
        case '2':
            return 'APPROVED HR GA';
        case '3':
            return 'PROCESS SETTLEMENT';
        case '9':
            return 'REJECTED ' . $meng;
        case '5':
            return 'SETTLED';
        default:
            return 'PENDING';
    }
}

function export_type_name($type)
{
    if ((int) $type === 1) {
        return 'DRIVER';
    }
    if ((int) $type === 2) {
        return 'TRAVEL';
    }
    return 'ENTERTAINMENT';
}

function export_user_nik($user)
{
    if (!$user) {
        return '-';
    }
    $nik = $user->nikNo ?? null;
    if ($nik !== null && $nik !== '') {
        return $nik;
    }
    $idK = $user->idKaryawan ?? null;
    if ($idK !== null && $idK !== '') {
        return $idK;
    }
    return '-';
}

function export_department_name($row)
{
    if ($row->department && !empty($row->department->nama_departemen)) {
        return $row->department->nama_departemen;
    }
    return '-';
}

function export_ent_amount($line)
{
    $raw = $line->amount ?? 0;
    if (is_numeric($raw)) {
        return (float) $raw;
    }
    return (float) str_replace(['.', ','], ['', '.'], (string) $raw);
}

$periodLine = '-';
if (!empty($periodStart) && !empty($periodEnd)) {
    $periodLine = export_date_ymd($periodStart) . ' - ' . export_date_ymd($periodEnd);
}

$logoAbsoluteUrl = url('access/images/logo.png');
$exportedAt = \Carbon\Carbon::now()->format('Y-m-d H:i');

?>

<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="utf-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets><x:ExcelWorksheet>
                <x:Name>Settlement</x:Name>
                <x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions>
            </x:ExcelWorksheet></x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <title>Export Settlement Detail</title>
    <style type="text/css">
        body {
            font-family: Calibri, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 12px;
            background: #fff;
        }
        .report-section {
            page-break-after: always;
            margin-bottom: 8px;
        }
        .report-section:last-child {
            page-break-after: auto;
        }
        /* Watermark di dalam sel (bukan absolute) — Excel tidak menganggapnya gambar mengambang */
        .wm-in-cell {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 6px;
        }
        .wm-in-cell td {
            border: 1px solid #c8e6c9;
            background: #f9fbf9;
            text-align: center;
            vertical-align: middle;
            padding: 2px 4px;
            mso-height-source: userset;
        }
        .wm-in-cell img {
            display: block;
            margin: 0 auto;
        }
        .table-laporan {
            border-collapse: collapse;
            width: 100%;
        }
        .hdr-brand {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 14px;
            border: 1px solid #2e7d32;
            table-layout: fixed;
        }
        .hdr-brand td {
            vertical-align: middle;
            padding: 10px 14px;
            background: #fff;
        }
        /* Sel logo: ukuran sel + img pakai atribut width/height (Excel patuh ini, bukan max-width) */
        .hdr-brand .logo-cell {
            width: 72px;
            max-width: 72px;
            min-width: 72px;
            height: 30px;
            padding: 2px 4px !important;
            overflow: hidden;
            text-align: center;
            vertical-align: middle;
            border-right: 3px solid #2e7d32;
            background: #f1f8f4;
            mso-height-source: userset;
        }
        .hdr-brand .logo-cell img {
            display: block;
            margin: 0 auto;
        }
        .company-line {
            font-size: 15px;
            font-weight: bold;
            color: #1b5e20;
            letter-spacing: 0.3px;
            line-height: 1.25;
        }
        .doc-line {
            font-size: 11px;
            color: #37474f;
            margin-top: 6px;
            font-weight: 600;
        }
        .meta-line {
            font-size: 10px;
            color: #546e7a;
            margin-top: 4px;
        }
        .bordernya {
            border-collapse: collapse;
            width: 100%;
        }
        .bordernya th,
        .bordernya td {
            border: 1px solid #37474f;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .bordernya thead th,
        .bordernya th.hdr-fill {
            background: #2e7d32;
            color: #fff;
            font-weight: bold;
            text-align: center;
        }
        .bordernya .th-sub {
            background: #388e3c;
            color: #fff;
            font-weight: bold;
        }
        .bordernya .label-cell {
            background: #e8f5e9;
            font-weight: 600;
            color: #1b5e20;
            width: 14%;
        }
        .bordernya .total-row td,
        .bordernya .total-row th {
            background: #c8e6c9;
            font-weight: bold;
        }
        .bordernya .checker-head th {
            background: #43a047;
            color: #fff;
            text-align: left;
            padding-left: 10px;
        }
        .bordernya .sig-head th {
            background: #2e7d32;
            color: #fff;
        }
        .tr-alt td {
            background: #f9fbe7;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .title-block {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #2e7d32;
        }
        .title-block td {
            padding: 8px 12px;
            background: #f1f8f4;
        }
        .title-main {
            font-size: 13px;
            font-weight: bold;
            color: #1b5e20;
            text-align: center;
        }
        .title-sub {
            font-size: 11px;
            color: #37474f;
            text-align: center;
            padding-top: 4px;
        }
        .spacer { height: 20px; }
        .sig { height: 56px; }
    </style>
</head>
<body>

@foreach ($data as $row)
    @php
        $typeName = export_type_name($row->reimbursement_type);
        $title = $typeName === 'ENTERTAINMENT'
            ? 'Entertainment Reimbursement'
            : ($typeName === 'DRIVER' ? 'Driver Reimbursement' : 'Travel Reimbursement');
        $colMain = (int) $row->reimbursement_type === 3 ? 13 : ((int) $row->reimbursement_type === 1 ? 9 : 13);
    @endphp

    <div class="report-section">
        <div class="report-inner">

            {{-- Watermark: satu baris satu sel — gambar kecil, in-cell (bukan floating) --}}
            <table class="wm-in-cell" cellspacing="0" cellpadding="0">
                <tr>
                    <td align="center" valign="middle" height="22"
                        style="height:22px;text-align:center;vertical-align:middle;">
                        <!-- <img src="{{ $logoAbsoluteUrl }}" alt=""
                             width="250" height="100"
                             style="width:250px;height:100px;opacity:0.12;filter:Alpha(Opacity=12);-ms-filter:'progid:DXImageTransform.Microsoft.Alpha(Opacity=12)';"> -->
                    </td>
                </tr>
            </table>

            <table class="hdr-brand" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="logo-cell" width="72" height="30" align="center" valign="middle"
                        style="width:72px;height:30px;overflow:hidden;text-align:center;vertical-align:middle;">
                        <img src="{{ $logoAbsoluteUrl }}" alt=""
                             width="250" height="33"
                             style="width:250px;height:33px;display:block;margin:0 auto;">
                    </td>
                    <td>
                        <div class="company-line">PT. SUMITOMO FORESTRY INDONESIA</div>
                        <div class="doc-line">Settlement Reimbursement UUDP — Laporan Detail (Pencairan)</div>
                        <div class="meta-line">
                            Periode filter: {{ $periodLine }}
                            &nbsp;|&nbsp; Diekspor: {{ $exportedAt }}
                            &nbsp;|&nbsp; No. Inquiry: {{ $row->no_reimbursement }}
                        </div>
                    </td>
                </tr>
            </table>

            <table class="title-block table-laporan" cellspacing="0">
                <tr>
                    <td class="title-main">{{ $title }}</td>
                </tr>
                <tr>
                    <td class="title-sub">Periode transaksi / pengajuan (filter): {{ $periodLine }}</td>
                </tr>
            </table>

            <table class="table-laporan bordernya" style="margin-bottom: 12px;">
                <tr>
                    <td class="label-cell" colspan="2">INQUIRY NO</td>
                    <td colspan="4">{{ $row->no_reimbursement }}</td>
                    <td class="label-cell" colspan="2">APPLY DATE</td>
                    <td colspan="{{ $colMain - 8 }}">{{ export_date_ymd($row->created_at) }}</td>
                </tr>
                <tr>
                    <td class="label-cell" colspan="2">INQUIRY BY</td>
                    <td colspan="4">{{ optional($row->user)->name ?? '-' }}</td>
                    <td class="label-cell" colspan="2">TRANSACTION DATE</td>
                    <td colspan="{{ $colMain - 8 }}">{{ export_date_ymd($row->date) }}</td>
                </tr>
                <tr>
                    <td class="label-cell" colspan="2">NIK</td>
                    <td colspan="4">{{ export_user_nik($row->user) }}</td>
                    <td class="label-cell" colspan="2">STATUS</td>
                    <td colspan="{{ $colMain - 8 }}">{{ export_status_label($row) }}</td>
                </tr>
                <tr>
                    <td class="label-cell" colspan="2">DEPARTMENT</td>
                    <td colspan="4">{{ export_department_name($row) }}</td>
                    <td class="label-cell" colspan="2">TYPE</td>
                    <td colspan="{{ $colMain - 8 }}">{{ $typeName }}</td>
                </tr>
                <tr>
                    <td class="label-cell" colspan="2">REMARK (HEADER)</td>
                    <td colspan="{{ $colMain - 2 }}">{{ $row->remark ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label-cell" colspan="2">TOTAL INQUIRY</td>
                    <td colspan="{{ $colMain - 2 }}"><strong>{{ export_nominal($row->nominal_pengajuan) }}</strong></td>
                </tr>
            </table>

    @if ((int) $row->reimbursement_type === 3)
        @php
            $entLines = $row->entertaiments ?? collect();
            $sumEnt = $entLines->sum(function ($line) {
                return export_ent_amount($line);
            });
            $sumBdcDetail = $entLines->filter(function ($line) {
                return strtoupper(trim((string) ($line->payment_type ?? ''))) === 'BDC';
            })->sum(function ($line) {
                return export_ent_amount($line);
            });
            $sumCashDetail = $entLines->filter(function ($line) {
                return strtoupper(trim((string) ($line->payment_type ?? ''))) === 'CASH';
            })->sum(function ($line) {
                return export_ent_amount($line);
            });
            $totalBdcSheet = $row->total_bdc !== null && $row->total_bdc !== '' ? (float) $row->total_bdc : $sumBdcDetail;
            $totalCashSheet = $row->total_cash !== null && $row->total_cash !== '' ? (float) $row->total_cash : $sumCashDetail;
        @endphp

        <table class="table-laporan bordernya" style="margin-bottom: 8px;">
            <thead>
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
            </thead>
            <tbody>
                @forelse ($entLines as $idx => $line)
                    <tr class="{{ ($idx % 2) === 1 ? 'tr-alt' : '' }}">
                        <td class="text-center">{{ export_date_ymd($line->date ?? null) }}</td>
                        <td>{{ $line->empty_zone ?? '' }}</td>
                        <td>{{ $line->attendance ?? '' }}</td>
                        <td>{{ $line->position ?? '' }}</td>
                        <td>{{ $line->place ?? '' }}</td>
                        <td>{{ $line->guest ?? '' }}</td>
                        <td>{{ $line->guest_position ?? '' }}</td>
                        <td>{{ $line->company ?? '' }}</td>
                        <td>{{ $line->type ?? '' }}</td>
                        <td>{{ $line->payment_type ?? '' }}</td>
                        <td class="text-right">{{ export_nominal(export_ent_amount($line)) }}</td>
                        <td>{{ $line->remark ?? '' }}</td>
                        <td>{{ $line->evidence ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center">No detail rows</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="10" class="text-right">Total</td>
                    <td class="text-right">{{ export_nominal($sumEnt) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <table class="table-laporan bordernya" style="margin-bottom: 6px;">
            <tr>
                <th colspan="13" class="text-left checker-head">Checker's Sheet BDC</th>
            </tr>
            <tr>
                <td colspan="10">Total Payment to be paid</td>
                <td class="text-right">{{ export_nominal($totalBdcSheet) }}</td>
                <td colspan="2"></td>
            </tr>
        </table>
        <table class="table-laporan bordernya" style="margin-bottom: 16px;">
            <tr>
                <th colspan="13" class="text-left checker-head">Checker's Sheet Cash</th>
            </tr>
            <tr>
                <td colspan="10">Total Payment to be paid</td>
                <td class="text-right">{{ export_nominal($totalCashSheet) }}</td>
                <td colspan="2"></td>
            </tr>
        </table>
    @elseif ((int) $row->reimbursement_type === 1)
        @php
            $drvLines = $row->drivers ?? collect();
            $sumDrv = $drvLines->sum(function ($line) {
                return (float) ($line->subtotal ?? 0);
            });
            $sumFleet = $drvLines->filter(function ($line) {
                return strtoupper(trim((string) ($line->payment_type ?? ''))) === 'FLEET';
            })->sum(function ($line) {
                return (float) ($line->subtotal ?? 0);
            });
            $sumCashDrv = $drvLines->filter(function ($line) {
                return strtoupper(trim((string) ($line->payment_type ?? ''))) === 'CASH';
            })->sum(function ($line) {
                return (float) ($line->subtotal ?? 0);
            });
            $sheetFleet = $row->total_fleet !== null && $row->total_fleet !== '' ? (float) $row->total_fleet : $sumFleet;
            $sheetCashDrv = $row->total_cash !== null && $row->total_cash !== '' ? (float) $row->total_cash : $sumCashDrv;
        @endphp

        <table class="table-laporan bordernya" style="margin-bottom: 8px;">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Toll</th>
                    <th>Parking</th>
                    <th>Gasoline</th>
                    <th>Other</th>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Remark (Detail)</th>
                    <th>Evidence</th>
                </tr>
            </thead>
            <tbody>
                @php $noD = 1; @endphp
                @forelse ($drvLines as $idx => $line)
                    <tr class="{{ ($idx % 2) === 1 ? 'tr-alt' : '' }}">
                        <td class="text-center">{{ $noD++ }}</td>
                        <td class="text-right">{{ export_nominal($line->toll ?? 0) }}</td>
                        <td class="text-right">{{ export_nominal($line->parking ?? 0) }}</td>
                        <td class="text-right">{{ export_nominal($line->gasoline ?? 0) }}</td>
                        <td class="text-right">{{ export_nominal($line->others ?? 0) }}</td>
                        <td>{{ $line->payment_type ?? '' }}</td>
                        <td class="text-right">{{ export_nominal($line->subtotal ?? 0) }}</td>
                        <td>{{ $line->remark ?? '' }}</td>
                        <td>{{ $line->evidence ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No detail rows</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="6" class="text-right">Total</td>
                    <td class="text-right">{{ export_nominal($sumDrv) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <table class="table-laporan bordernya" style="margin-bottom: 6px;">
            <tr>
                <th colspan="9" class="text-left checker-head">Checker's Sheet Fleet</th>
            </tr>
            <tr>
                <td colspan="6">Total Payment to be paid</td>
                <td class="text-right">{{ export_nominal($sheetFleet) }}</td>
                <td colspan="2"></td>
            </tr>
        </table>
        <table class="table-laporan bordernya" style="margin-bottom: 16px;">
            <tr>
                <th colspan="9" class="text-left checker-head">Checker's Sheet Cash</th>
            </tr>
            <tr>
                <td colspan="6">Total Payment to be paid</td>
                <td class="text-right">{{ export_nominal($sheetCashDrv) }}</td>
                <td colspan="2"></td>
            </tr>
        </table>
    @else
        @php
            $travelRows = $row->travels ?? collect();
            $allDetails = collect();
            foreach ($travelRows as $tv) {
                foreach ($tv->details ?? [] as $dt) {
                    $allDetails->push(['travel' => $tv, 'dt' => $dt]);
                }
            }
            $sumTravelIdr = $allDetails->sum(function ($pair) {
                return (float) ($pair['dt']->idr_rate ?? 0);
            });
            $sumBdcT = $allDetails->filter(function ($pair) {
                return strtoupper(trim((string) ($pair['dt']->payment_type ?? ''))) === 'BDC';
            })->sum(function ($pair) {
                return (float) ($pair['dt']->idr_rate ?? 0);
            });
            $sumCashT = $allDetails->filter(function ($pair) {
                return strtoupper(trim((string) ($pair['dt']->payment_type ?? ''))) === 'CASH';
            })->sum(function ($pair) {
                return (float) ($pair['dt']->idr_rate ?? 0);
            });
            $totalBdcT = $row->total_bdc !== null && $row->total_bdc !== '' ? (float) $row->total_bdc : $sumBdcT;
            $totalCashT = $row->total_cash !== null && $row->total_cash !== '' ? (float) $row->total_cash : $sumCashT;
        @endphp

        @forelse ($travelRows as $tv)
            <table class="table-laporan bordernya" style="margin-bottom: 6px;">
                <tr>
                    <th class="th-sub">Trip — Transaction Date</th>
                    <td>{{ export_date_ymd($tv->date ?? $row->date) }}</td>
                    <th class="th-sub">Trip Type</th>
                    <td>{{ optional($tv->tripType)->name ?? '-' }}</td>
                    <th class="th-sub">Hotel</th>
                    <td>{{ optional($tv->hotelCondition)->name ?? '-' }}</td>
                    <th class="th-sub">Allowance</th>
                    <td colspan="2" class="text-right">{{ export_nominal($tv->allowance ?? 0) }}</td>
                </tr>
                <tr>
                    <th class="th-sub">Purpose</th>
                    <td colspan="8">{{ $tv->purpose ?? '-' }}</td>
                </tr>
            </table>
            <table class="table-laporan bordernya" style="margin-bottom: 12px;">
                <thead>
                    <tr>
                        <th>Cost Type</th>
                        <th>Destination</th>
                        <th>Currency</th>
                        <th>Amount</th>
                        <th>Amount (IDR)</th>
                        <th>Tax</th>
                        <th>Payment</th>
                        <th>Remarks</th>
                        <th>Evidence</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tv->details ?? [] as $idx => $dt)
                        <tr class="{{ ($idx % 2) === 1 ? 'tr-alt' : '' }}">
                            <td>{{ optional($dt->costType)->name ?? '-' }}</td>
                            <td>{{ $dt->destination ?? '' }}</td>
                            <td>{{ $dt->currency ?? '' }}</td>
                            <td class="text-right">{{ export_nominal($dt->amount ?? 0) }}</td>
                            <td class="text-right">{{ export_nominal($dt->idr_rate ?? 0) }}</td>
                            <td class="text-right">{{ export_nominal($dt->tax ?? 0) }}</td>
                            <td>{{ $dt->payment_type ?? '' }}</td>
                            <td>{{ $dt->remarks ?? '' }}</td>
                            <td>{{ $dt->evidence ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No cost lines for this trip</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="7" class="text-right">Trip subtotal</td>
                        <td class="text-right" colspan="2">{{ export_nominal($tv->total ?? 0) }}</td>
                    </tr>
                </tbody>
            </table>
        @empty
            <table class="table-laporan bordernya" style="margin-bottom: 16px;">
                <tr>
                    <td class="text-center">No travel segments</td>
                </tr>
            </table>
        @endforelse

        @if ($travelRows->isNotEmpty())
            <table class="table-laporan bordernya" style="margin-bottom: 6px;">
                <tr>
                    <th colspan="9" class="text-left checker-head">Summary — all trips (IDR from details)</th>
                </tr>
                <tr class="total-row">
                    <td colspan="6" class="text-right">Total (detail IDR sum)</td>
                    <td class="text-right" colspan="3">{{ export_nominal($sumTravelIdr) }}</td>
                </tr>
            </table>

            <table class="table-laporan bordernya" style="margin-bottom: 6px;">
                <tr>
                    <th colspan="9" class="text-left checker-head">Checker's Sheet BDC</th>
                </tr>
                <tr>
                    <td colspan="6">Total Payment to be paid</td>
                    <td class="text-right" colspan="3">{{ export_nominal($totalBdcT) }}</td>
                </tr>
            </table>
            <table class="table-laporan bordernya" style="margin-bottom: 16px;">
                <tr>
                    <th colspan="9" class="text-left checker-head">Checker's Sheet Cash</th>
                </tr>
                <tr>
                    <td colspan="6">Total Payment to be paid</td>
                    <td class="text-right" colspan="3">{{ export_nominal($totalCashT) }}</td>
                </tr>
            </table>
        @endif
    @endif

            <table class="table-laporan bordernya" style="margin-bottom: 24px;">
                <tr>
                    <th class="text-center sig-head" style="width:33%">Head Department</th>
                    <th class="text-center sig-head" style="width:33%">HR GA</th>
                    <th class="text-center sig-head" style="width:33%">Finance</th>
                </tr>
                <tr class="sig">
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">{{ strtoupper($row->mengetahui_op ?? '-') }}</td>
                    <td class="text-center">{{ strtoupper($row->mengetahui_finance ?? '-') }}</td>
                    <td class="text-center">{{ strtoupper($row->mengetahui_owner ?? '-') }}</td>
                </tr>
            </table>

        </div>
    </div>
@endforeach

</body>
</html>
