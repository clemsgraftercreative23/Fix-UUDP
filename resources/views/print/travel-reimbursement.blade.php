<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Travel Reimbursement</title>
    <style>
        /*
         * Akar masalah abu-abu hilang saat print: browser default mematikan background
         * (hemat tinta). print-color-adjust: exact meminta background ikut ke PDF/kertas.
         * Lihat opsi dialog "Background graphics" / "Cetak latar belakang" bila masih putih.
         */
        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        * {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
        }

        :root {
            /* Single accent colour, used sparingly (section bands, thin
               rules) -- kept subtle enough to still read fine printed in
               black & white, which is how finance/approvers most often
               print this. */
            --accent: #1e7e34;
            --accent-tint: #eaf6ee;
            --ink-muted: #6c757d;
        }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        @media print {
            /* Keep a day's whole block (header + cost lines + total) and each
               Checker's Sheet together on one page rather than splitting
               awkwardly across a page break. */
            .travel-day-block, .checker-sheet, .signature-block {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        @media print {
            html, body {
                width: 210mm;
                margin: 0;
                padding: 0;
            }

            .report {
                width: 100%;
                max-width: 100%;
            }
        }
        table td, table th {
            padding: 8px;
        }

        .table-style.travel-detail-block td,
        .table-style.travel-detail-block th {
            padding: 3px 4px;
            line-height: 1.2;
            vertical-align: top;
        }

        /* Halaman cetak ini tidak memuat Bootstrap; class bg-secondary perlu warna eksplisit + print */
        .table-style td.bg-secondary,
        .table-style th.bg-secondary {
            background: #e9ecef !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-style {
            border-collapse: collapse; /* Ensures borders are consistent and printable */
            width: 100%; /* Optional: Adjusts table width to fit printable area */
        }

        /* Grid travel + detail: kolom sejajar, garis vertikal kanan lurus */
        .table-style.travel-detail-block {
            table-layout: fixed;
            font-size: 7pt;
        }

        .table-style.travel-detail-block td,
        .table-style.travel-detail-block th {
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .table-style.travel-detail-block .cell-date,
        .table-style.travel-detail-block .cell-hotel {
            white-space: nowrap;
        }

        .table-style.travel-detail-block .cell-label-date,
        .table-style.travel-detail-block .cell-label-hotel {
            white-space: normal;
            line-height: 1.15;
        }

        .table-style.travel-detail-block .cell-purpose,
        .table-style.travel-detail-block .cell-remarks,
        .table-style.travel-detail-block .cell-trip-type,
        .table-style.travel-detail-block .cell-cost-type,
        .table-style.travel-detail-block .cell-destination {
            white-space: normal;
            line-height: 1.2;
        }

        .table-style th,.table-style td {
            border: 1px solid black; /* Defines solid black borders for table, headers, and cells */
            padding: 8px; /* Adjust padding for readability */
        }

        /* Baris subtotal per blok travel (cetak) */
        .table-style tr.travel-section-total-row td,
        .table-style tr.travel-section-total-row th {
            background: #d9d9d9 !important;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Spacer baris — lebih andal di dialog print/PDF daripada padding saja */
        .table-style.travel-detail-block tr.travel-inner-gap td,
        .table-style.travel-detail-block tr.travel-day-gap td {
            height: 14px;
            padding: 0 !important;
            line-height: 0;
            font-size: 0;
            border-top: 0 !important;
            border-bottom: 0 !important;
            background: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-style.travel-detail-block tr.travel-day-gap td {
            height: 22px;
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

        /* --- Redesign (Sep 2026): letterhead-style header, labelled inquiry
           strip, day badges, zebra-striped cost lines, a cleaner Checker's
           Sheet grid, and boxed signature blocks -- same data/columns as
           before, just a clearer visual hierarchy for whoever is scanning
           this on paper or a PDF. --- */

        .report-header {
            text-align: center;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .report-header h1 {
            margin: 0;
            font-size: 16pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .report-header .report-period {
            margin: 4px 0 0;
            font-size: 8.5pt;
            color: var(--ink-muted);
        }

        /* Inquiry No / Inquiry By / Apply Date -- a labelled strip instead of
           a single plain "LABEL : value" text line. */
        .inquiry-strip { border-collapse: collapse; margin: 14px 0 8px; }
        .inquiry-strip td {
            border: 1px solid #ccc;
            border-left: 3px solid var(--accent);
            background: var(--accent-tint);
            padding: 5px 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .inquiry-strip-label {
            display: block;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--ink-muted);
        }
        .inquiry-strip-value {
            display: block;
            font-size: 9pt;
            font-weight: 700;
        }

        /* Day N badge inside the Transaction Date header cell. */
        .day-badge {
            display: inline-block;
            background: var(--accent);
            color: #fff;
            font-size: 6.5pt;
            font-weight: 700;
            border-radius: 3px;
            padding: 1px 5px;
            margin-right: 5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Day header band (Transaction Date / Stay(Hotel)) and Trip Type row
           get the accent tint instead of plain grey, so one day's block is
           visually distinct from the cost-line rows under it. */
        .table-style.travel-detail-block tr.travel-date-header th,
        .table-style.travel-detail-block tr.travel-date-header td.bg-secondary,
        .table-style.travel-detail-block tr.travel-trip-row th,
        .table-style.travel-detail-block tr.travel-trip-row td.bg-secondary {
            background: var(--accent-tint) !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .table-style.travel-detail-block tr.cost-table-head th {
            background: #333 !important;
            color: #fff !important;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        /* Faint zebra striping on cost-line rows only, for scanning a day
           with many lines -- day-header/trip/purpose rows are untouched. */
        .table-style.travel-detail-block tr.travel-line-item-even td {
            background: #f7f8f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Checker's Sheet: a titled band + clean label/value grid instead of
           a flat wall of grey cells. */
        .checker-sheet { border-collapse: collapse; }
        .checker-sheet-title {
            background: #333 !important;
            color: #fff !important;
            text-align: center;
            font-weight: 700;
            font-size: 9pt;
            letter-spacing: 0.3px;
            padding: 6px 8px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .checker-sheet .checker-label {
            background: #f4f5f6 !important;
            color: var(--ink-muted);
            font-size: 7.5pt;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .checker-sheet .checker-value {
            background: #fff !important;
            text-align: right;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .checker-sheet tr.checker-sheet-highlight .checker-label,
        .checker-sheet tr.checker-sheet-highlight .checker-value {
            background: var(--accent-tint) !important;
            font-weight: 700;
            font-size: 9pt;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Signature block: three boxed cards instead of a bare 3-column table
           with hairline dividers, so each approver's slot reads as its own
           unit with clear role + signature area + printed name. */
        .signature-block { border-collapse: separate; border-spacing: 10px 0; width: 100%; margin-top: 10px; }
        .signature-block td.signature-card {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            width: 33%;
            vertical-align: top;
        }
        .signature-role {
            text-align: center;
            font-weight: 700;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .signature-time { text-align: center; font-size: 7pt; color: var(--ink-muted); margin-bottom: 4px; }
        .signature-area { min-height: 70px; text-align: center; }
        .signature-name {
            text-align: center;
            font-weight: 700;
            font-size: 8pt;
            border-top: 1px solid #333;
            margin-top: 4px;
            padding-top: 4px;
        }
    </style>
</head>
@php
    $firstReimbursement = (isset($datas[0]) ? $datas[0] : null);
    $isSettledPrint = ((int) ($firstReimbursement->status ?? 0) === 5);
    $approvalTimes = [1 => null, 2 => null, 3 => null];
    if ($firstReimbursement) {
        $approvalLogs = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->where('module', 'reimbursement-travel')
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

        <div class="report-header">
            <h1>Travel Reimbursement</h1>
            @php
                $periodDays = null;
                try {
                    if (!empty($start_date) && !empty($end_date)) {
                        $periodDays = \Carbon\Carbon::parse($start_date)->startOfDay()->diffInDays(\Carbon\Carbon::parse($end_date)->startOfDay()) + 1;
                    }
                } catch (\Exception $e) {
                    $periodDays = null;
                }
            @endphp
            <p class="report-period">Period: {{$start_date}} &ndash; {{$end_date}}@if($periodDays !== null && $periodDays > 0) &nbsp;({{ $periodDays }} {{ $periodDays === 1 ? "day" : "days" }})@endif</p>
        </div>
        @foreach ($datas as $data)
            @if(($data->travels ?? collect())->count() != 0)
            <table class="inquiry-strip" style="width: 100%">
                <tr>
                    <td>
                        <span class="inquiry-strip-label">Inquiry No</span>
                        <span class="inquiry-strip-value">{{$data->no_reimbursement}}</span>
                    </td>
                    <td>
                        <span class="inquiry-strip-label">Inquiry By</span>
                        <span class="inquiry-strip-value">{{$data->user->name}}</span>
                    </td>
                    <td>
                        <span class="inquiry-strip-label">Apply Date</span>
                        <span class="inquiry-strip-value">{{ date('Y-m-d', strtotime($data->created_at)) }}</span>
                    </td>
                </tr>
            </table>
            @endif
        <table class="table-style table-bordered mb-2">
            <tr>
                @foreach (($data->rates ?? collect()) as $rateRow)
                <th>{{ $rateRow->currency ?? '-' }} Rate</th>
                <td class="bg-secondary">{{ isset($rateRow->rate) ? number_format((float) $rateRow->rate, 2, ',', '.') : '0' }}</td>
                @endforeach
            </tr>
      </table>
      <table class="table-style table-bordered mb-2 travel-detail-block">
              <colgroup>
                  <col style="width:9%">
                  <col style="width:8%">
                  <col style="width:7%">
                  <col style="width:11%">
                  <col style="width:7%">
                  <col style="width:8%">
                  <col style="width:7%">
                  <col style="width:8%">
                  <col style="width:9%">
                  <col style="width:9%">
                  <col style="width:9%">
                  <col style="width:8%">
              </colgroup>
              @foreach (($data->travels ?? collect()) as $item)
                <tbody class="travel-day-block">
                @php
                    $tripTypeModel = optional($item->tripType)->id ? $item->tripType : \App\TravelTripType::where('id', $item->trip_type_id)->first();
                    $allowanceTripAmount = $tripTypeModel ? (float) $tripTypeModel->allowance : 0.0;
                    $tripCurrency = $tripTypeModel
                        ? strtoupper(trim((string) ($tripTypeModel->currency ?? 'IDR')))
                        : 'IDR';
                    $storedAllowanceIdr = (float) ($item->allowance ?? 0);
                    $convRate = null;
                    $ratesColl = $data->rates ?? collect();
                    $rateRow = $ratesColl->first(function ($r) use ($tripCurrency) {
                        return strtoupper(trim((string) ($r->currency ?? ''))) === $tripCurrency;
                    });
                    if ($rateRow && isset($rateRow->rate)) {
                        $convRate = (float) $rateRow->rate;
                    }
                    if (($convRate === null || $convRate === 0.0) && !empty($data->id)) {
                        $dbRateRow = \App\TravelTripRate::where('reimbursement_id', $data->id)->where('currency', $tripCurrency)->first();
                        if ($dbRateRow) {
                            $convRate = (float) $dbRateRow->rate;
                        }
                    }
                    if ($convRate === null || $convRate === 0.0) {
                        if ($tripCurrency === 'IDR') {
                            $convRate = 1.0;
                        } elseif ($tripCurrency === 'USD') {
                            $convRate = 16400.0;
                        } else {
                            $convRate = 1.0;
                        }
                    }
                    $allowanceIdrComputed = $allowanceTripAmount * $convRate;
                    $allowanceIdrDisplay = $storedAllowanceIdr > 0 ? $storedAllowanceIdr : $allowanceIdrComputed;
                @endphp
                <tr class="travel-date-header">
                    <th colspan="2" class="cell-label-date"><span class="day-badge">Day {{ $loop->iteration }}</span> Transaction Date</th>
                    <td colspan="4" class="bg-secondary cell-date">{{$item->date}}</td>
                    <th colspan="2" class="cell-label-hotel">Stay (Hotel)</th>
                    <td colspan="4" class="bg-secondary cell-hotel">{{ optional($item->hotelCondition)->name ?? '-' }}</td>
                </tr>
                <tr class="travel-inner-gap" aria-hidden="true">
                    <td colspan="12">&nbsp;</td>
                </tr>
                <tr class="travel-trip-row">
                    <th colspan="2">Trip Type</th>
                    <td colspan="4" class="bg-secondary cell-trip-type">{{ optional($item->tripType)->name ?? 'None' }}</td>
                    <th colspan="2">Allowance</th>
                    <td colspan="2" class="bg-secondary">
                        @if($tripTypeModel)
                            {{ number_format($allowanceTripAmount, 2, ',', '.') }} {{ $tripCurrency }}
                        @else
                            0
                        @endif
                    </td>
                    <th>Allow. (IDR)</th>
                    <td class="bg-secondary">{{ number_format($allowanceIdrDisplay, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <th colspan="2">Purpose</th>
                    <td class="bg-secondary cell-purpose" colspan="10">{{$item->purpose}}</td>
                </tr>
                <tr>
                    <th>Start</th>
                    <td class="bg-secondary">{{$item->start_time}}</td>
                    <th>Arrival</th>
                    <td class="bg-secondary">{{$item->end_time}}</td>
                    <th colspan="2">Travel Time</th>
                    <td class="bg-secondary" colspan="6">
                        @php
                            if($item->start_time != null && $item->end_time != null) {
                                $start = strtotime($item->start_time);
                                $end = strtotime($item->end_time);
                                $diff = $end - $start;
                                echo date('H:i:s', $diff);
                            }                            
                        @endphp
                    </td>
                </tr>
          {{-- </table>
          <table class="table-style table-bordered mb-2"> --}}
            <tr class="cost-table-head">
                <th colspan="3">Cost Type</th>
                <th colspan="3">Destination</th>
                <th colspan="2">Remarks</th>
                <th>Currency</th>
                <th>Amount</th>
                <th>Amount (IDR)</th>
                <th>Payment</th>
            </tr>
            @foreach (($item->details ?? collect()) as $dt)
            <tr class="travel-line-item {{ $loop->even ? 'travel-line-item-even' : '' }}">
                <td colspan="3" class="cell-cost-type">{{ optional($dt->costType)->name ?? '-' }}</td>
                <td colspan="3" class="cell-destination">{{$dt->destination}}</td>
                <td colspan="2" class="cell-remarks">{{ $dt->remarks ?? '' }}</td>
                <td>{{$dt->currency}}</td>
                <td align="right">{{ number_format((float) $dt->amount, 2, ',', '.') }}</td>
                <td align="right">{{ number_format((float) $dt->idr_rate, 2, ',', '.') }}</td>

                <td>{{$dt->payment_type}}</td>
            </tr>
            @endforeach
            
                @php
                    $sectionTotal = \App\Support\TravelDayTotal::compute($item, $item->details ?? collect());
                @endphp
                <tr class="travel-section-total-row">
                    <td colspan="2">Total</td>
                    <td class="text-right" align="right" colspan="9">{{ number_format($sectionTotal, 2, ',', '.') }}</td>
                    <td>&nbsp;</td>
                </tr>
                </tbody>
                @if(!$loop->last)
                <tbody>
                <tr class="travel-day-gap" aria-hidden="true">
                    <td colspan="12">&nbsp;</td>
                </tr>
                </tbody>
                @endif
            @endforeach
          </table>
          <br>
        @endforeach

        <table class="table-style table-bordered mb-2 checker-sheet">
            <tr>
                <td class="checker-sheet-title" colspan="6">Checker's Sheet &mdash; BDC</td>
            </tr>
            <tr class="checker-sheet-highlight">
                <td colspan="2" class="checker-label">Total Payment to be Paid</td>
                <td colspan="4" class="checker-value">{{number_format($bdc, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td colspan="2" class="checker-label">Advanced Paid</td>
                <td colspan="4" class="checker-value"></td>
            </tr>
            <tr>
                <td colspan="2" class="checker-label">Total Amount Paid</td>
                <td colspan="4" class="checker-value">{{number_format($bdc, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td class="checker-label">Allowance</td>
                <td class="checker-value">{{number_format($allowance_bdc, 2, ',', '.')}}</td>
                <td class="checker-label">SIM Card</td>
                <td class="checker-value">{{number_format($simcard_bdc, 2, ',', '.')}}</td>
                <td class="checker-label">Flight</td>
                <td class="checker-value">{{number_format($flight_bdc, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td class="checker-label">Rental Car</td>
                <td class="checker-value">{{number_format($rentalcar_bdc, 2, ',', '.')}}</td>
                <td class="checker-label">Hotel</td>
                <td class="checker-value">{{number_format($hotel_bdc, 2, ',', '.')}}</td>
                <td class="checker-label">Toll</td>
                <td class="checker-value">{{number_format($toll_bdc, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td class="checker-label">Gasoline</td>
                <td class="checker-value">{{number_format($gasoline_bdc, 2, ',', '.')}}</td>
                <td class="checker-label">Taxi</td>
                <td class="checker-value">{{number_format($taxi_bdc, 2, ',', '.')}}</td>
                <td class="checker-label">Train</td>
                <td class="checker-value">{{number_format($train_bdc, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td class="checker-label" colspan="2"></td>
                <td class="checker-label">PPH23</td>
                <td class="checker-value">{{number_format($tax_bdc, 2, ',', '.')}}</td>
                <td class="checker-label">Others</td>
                <td class="checker-value">{{number_format($others_bdc, 2, ',', '.')}}</td>
            </tr>
        </table>

        <br>

        <table class="table-style table-bordered mb-2 checker-sheet">
            <tr>
                <td class="checker-sheet-title" colspan="6">Checker's Sheet &mdash; Cash</td>
            </tr>
            <?php $paid_total = $allowance_cash + $simcard_cash + $flight_cash + $rentalcar_cash + $hotel_cash + $toll_cash + $gasoline_cash + $taxi_cash + $train_cash + $others_cash;?>
            <tr class="checker-sheet-highlight">
                <td colspan="2" class="checker-label">Total Payment to be Paid</td>
                <td colspan="4" class="checker-value">{{number_format($paid_total, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td colspan="2" class="checker-label">Advanced Paid</td>
                <td colspan="4" class="checker-value"></td>
            </tr>
            <tr>
                <td colspan="2" class="checker-label">Total Amount Paid</td>
                <td colspan="4" class="checker-value">{{number_format($paid_total, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td class="checker-label">Allowance</td>
                <td class="checker-value">{{number_format($allowance_cash, 2, ',', '.')}}</td>
                <td class="checker-label">SIM Card</td>
                <td class="checker-value">{{number_format($simcard_cash, 2, ',', '.')}}</td>
                <td class="checker-label">Flight</td>
                <td class="checker-value">{{number_format($flight_cash, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td class="checker-label">Rental Car</td>
                <td class="checker-value">{{number_format($rentalcar_cash, 2, ',', '.')}}</td>
                <td class="checker-label">Hotel</td>
                <td class="checker-value">{{number_format($hotel_cash, 2, ',', '.')}}</td>
                <td class="checker-label">Toll</td>
                <td class="checker-value">{{number_format($toll_cash, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td class="checker-label">Gasoline</td>
                <td class="checker-value">{{number_format($gasoline_cash, 2, ',', '.')}}</td>
                <td class="checker-label">Taxi</td>
                <td class="checker-value">{{number_format($taxi_cash, 2, ',', '.')}}</td>
                <td class="checker-label">Train</td>
                <td class="checker-value">{{number_format($train_cash, 2, ',', '.')}}</td>
            </tr>
            <tr>
                <td class="checker-label" colspan="2"></td>
                <td class="checker-label">PPH23</td>
                <td class="checker-value">{{number_format($tax_cash, 2, ',', '.')}}</td>
                <td class="checker-label">Others</td>
                <td class="checker-value">{{number_format($others_cash, 2, ',', '.')}}</td>
            </tr>
        </table>

        <br><br>

        <table class="signature-block">
            <tr>
                <td class="signature-card">
                    <div class="signature-role">Head Department</div>
                    <div class="signature-time">{{ $formatApprovalTime(1) }}</div>
                    <div class="signature-area">
                        @if($datas[0]->mengetahui_op != '-')
                            <img src="{!!url('access/images/ttd.png')!!}" style="width:150px;height:75px;object-fit:contain">
                        @endif
                    </div>
                    <div class="signature-name">{{ strtoupper($head_dept) }}</div>
                </td>
                <td class="signature-card">
                    <div class="signature-role">HR GA</div>
                    <div class="signature-time">{{ $formatApprovalTime(2) }}</div>
                    <div class="signature-area">
                        @if($datas[0]->mengetahui_finance != '-')
                            <img src="{!!url('access/images/ttd.png')!!}" style="width:150px;height:75px;object-fit:contain">
                        @endif
                    </div>
                    <div class="signature-name">{{ strtoupper($datas[0]->mengetahui_finance) }}</div>
                </td>
                <td class="signature-card">
                    <div class="signature-role">Finance</div>
                    <div class="signature-time">{{ $formatApprovalTime(3) }}</div>
                    <div class="signature-area">
                        @if($datas[0]->mengetahui_owner != '-')
                            <img src="{!!url('access/images/ttd.png')!!}" style="width:150px;height:75px;object-fit:contain">
                        @endif
                    </div>
                    <div class="signature-name">{{ strtoupper($datas[0]->mengetahui_owner) }}</div>
                </td>
            </tr>
        </table>

        
    </div>



    <script>window.print()</script>
</body>
</html>