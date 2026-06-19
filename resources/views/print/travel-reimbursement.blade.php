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

        @page {
            size: A4 portrait;
            margin: 8mm;
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

        <center>
            <h3>Travel Reimbursement</h3>
            <p>{{$start_date}} - {{$end_date}}</p>
            
        </center>
        <br>
        <br>
        @foreach ($datas as $data)
        <p>
            @if(($data->travels ?? collect())->count() != 0)
            <table style="width: 100%">
                <tr>
                    <td>INQUIRY NO : <b>{{$data->no_reimbursement}}</b></td>
                    
                    <td>INQUIRY BY : <b>{{$data->user->name}}</b></td>
                    
                    <td>APPLY DATE : <b>{{ date('Y-m-d', strtotime($data->created_at)) }}</b></td>
                </tr>
            </table>
            @endif
        </p>
        <table class="table-style table-bordered mb-2">
            <tr>
                @foreach (($data->rates ?? collect()) as $rateRow)
                <th>{{ $rateRow->currency ?? '-' }} Rate</th>
                <td class="bg-secondary">{{ isset($rateRow->rate) ? number_format((float) $rateRow->rate, 0, ',', '.') : '0' }}</td>
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
                    <th colspan="2" class="cell-label-date">Transaction Date</th>
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
                            {{ number_format($allowanceTripAmount, 0, ',', '.') }} {{ $tripCurrency }}
                        @else
                            0
                        @endif
                    </td>
                    <th>Allow. (IDR)</th>
                    <td class="bg-secondary">{{ number_format($allowanceIdrDisplay, 0, ',', '.') }}</td>
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
            <tr>
                <th colspan="3">Cost Type</th>
                <th colspan="3">Destination</th>
                <th colspan="2">Remarks</th>
                <th>Currency</th>
                <th>Amount</th>
                <th>Amount (IDR)</th>
                <th>Payment</th>
            </tr>
            @foreach (($item->details ?? collect()) as $dt)                
            <tr>
                <td colspan="3" class="cell-cost-type">{{ optional($dt->costType)->name ?? '-' }}</td>
                <td colspan="3" class="cell-destination">{{$dt->destination}}</td>
                <td colspan="2" class="cell-remarks">{{ $data->remark ?? '' }}</td>
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
                    <td class="text-right" align="right" colspan="9">{{ number_format($sectionTotal, 0, ',', '.') }}</td>
                    <td>&nbsp;</td>
                </tr>
                @if(!$loop->last)
                <tr class="travel-day-gap" aria-hidden="true">
                    <td colspan="12">&nbsp;</td>
                </tr>
                @endif
            @endforeach
          </table>
          <br>
        @endforeach

        <table class="table-style table-bordered mb-2">
            <tr>
                <td class="bg-secondary" colspan="6"><center><strong>Checker's Sheet BDC</strong></center></td>
            </tr>
            <tr>
                <td class="bg-secondary"><strong>Total Payment to be paid</strong></td>
                <td class="bg-secondary"><strong>{{number_format($bdc,0,',','.')}}</strong></td>
                <td class="bg-secondary" colspan="6"><span style="float: left"></span></td>
            </tr>
            <tr>
                <td class="bg-secondary">Advanced Paid</td>
                <td class="bg-secondary"></td>
                <td class="bg-secondary" colspan="6"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Total Amount Paid</td>
                <td class="bg-secondary">{{number_format($bdc,0,',','.')}}</td>
                <td class="bg-secondary" colspan="6"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Allowance</td>
                <td class="bg-secondary">{{number_format($allowance_bdc,0,',','.')}}</td>
                <td class="bg-secondary">SIM Card</td>
                <td class="bg-secondary">{{number_format($simcard_bdc,0,',','.')}}</td>
                <td class="bg-secondary">Flight</td>
                <td class="bg-secondary">{{number_format($flight_bdc,0,',','.')}}</td>
            </tr>
            <tr>
                <td class="bg-secondary">Rental Car</td>
                <td class="bg-secondary">{{number_format($rentalcar_bdc,0,',','.')}}</td>
                <td class="bg-secondary">Hotel</td>
                <td class="bg-secondary">{{number_format($hotel_bdc,0,',','.')}}</td>
                <td class="bg-secondary">Toll</td>
                <td class="bg-secondary">{{number_format($toll_bdc,0,',','.')}}</td>
            </tr>
            <tr>
                <td class="bg-secondary">Gasoline</td>
                <td class="bg-secondary">{{number_format($gasoline_bdc,0,',','.')}}</td>
                <td class="bg-secondary">Taxi</td>
                <td class="bg-secondary">{{number_format($taxi_bdc,0,',','.')}}</td>
                <td class="bg-secondary">Train</td>
                <td class="bg-secondary">{{number_format($train_bdc,0,',','.')}}</td>
            </tr>
            <tr>
                <td class="bg-secondary" colspan="2"></td>
                <td class="bg-secondary">PPH23</td>
                <td class="bg-secondary">{{number_format($tax_bdc,0,',','.')}}</td>
                <td class="bg-secondary">Others</td>
                <td class="bg-secondary">{{number_format($others_bdc,0,',','.')}}</td>
            </tr>
        </table>

        <br><br>

        <table class="table-style table-bordered mb-2">
            <tr>
                <td class="bg-secondary" colspan="6"><center><strong>Checker's Sheet Cash</strong></center></td>
            </tr>
            <?php $paid_total = $allowance_cash + $simcard_cash + $flight_cash + $rentalcar_cash + $hotel_cash + $toll_cash + $gasoline_cash + $taxi_cash + $train_cash + $others_cash;?>
            <tr>
                <td class="bg-secondary"><strong>Total Payment to be paid</strong></td>
                <td class="bg-secondary"><strong>{{number_format($paid_total,0,',','.')}}</strong></td>
                <td class="bg-secondary" colspan="5"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Advanced Paid</td>
                <td class="bg-secondary"></td>
                <td class="bg-secondary" colspan="5"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Total Amount Paid</td>
                <td class="bg-secondary"> {{number_format($paid_total,0,',','.')}}</td>
                <td class="bg-secondary" colspan="5"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Allowance</td>
                <td class="bg-secondary">{{number_format($allowance_cash,0,',','.')}}</td>
                <td class="bg-secondary">SIM Card</td>
                <td class="bg-secondary">{{number_format($simcard_cash,0,',','.')}}</td>
                <td class="bg-secondary">Flight</td>
                <td class="bg-secondary">{{number_format($flight_cash,0,',','.')}}</td>
            </tr>
            <tr>
                <td class="bg-secondary">Rental Car</td>
                <td class="bg-secondary">{{number_format($rentalcar_cash,0,',','.')}}</td>
                <td class="bg-secondary">Hotel</td>
                <td class="bg-secondary">{{number_format($hotel_cash,0,',','.')}}</td>
                <td class="bg-secondary">Toll</td>
                <td class="bg-secondary">{{number_format($toll_cash,0,',','.')}}</td>
            </tr>
            <tr>
                <td class="bg-secondary">Gasoline</td>
                <td class="bg-secondary">{{number_format($gasoline_cash,0,',','.')}}</td>
                <td class="bg-secondary">Taxi</td>
                <td class="bg-secondary">{{number_format($taxi_cash,0,',','.')}}</td>
                <td class="bg-secondary">Train</td>
                <td class="bg-secondary">{{number_format($train_cash,0,',','.')}}</td>
            </tr>
            <tr>
                <td class="bg-secondary" colspan="2"></td>
                <td class="bg-secondary">PPH23</td>
                <td class="bg-secondary">{{number_format($tax_cash,0,',','.')}}</td>
                <td class="bg-secondary">Others</td>
                <td class="bg-secondary">{{number_format($others_cash,0,',','.')}}</td>
            </tr>
        </table>

        <br><br><br><br><br>

        <table style="width: 100%">
            <thead>
                <tr>
                    <th>
                        Head Department 
                        
                    </th>
                    <th width="2px"></th>
                    <th>
                        HR GA 
                        
                    </th>
                    <th width="2px"></th>

                    <th>
                        Finance 
                        
                    </th>
                </tr>
                <tr>
                    
                    <td style="border-bottom: 1px solid #000">
                        <center style="margin-bottom: 4px;">{{ $formatApprovalTime(1) }}</center>
                        @if($datas[0]->mengetahui_op != '-')
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($head_dept)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{$head_dept}}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                    <td style="border-bottom: 1px solid #000">
                        <center style="margin-bottom: 4px;">{{ $formatApprovalTime(2) }}</center>
                        @if($datas[0]->mengetahui_finance != '-')
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($datas[0]->mengetahui_finance)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{$datas[0]->mengetahui_finance}}</center>
                        @endif
                    </td>
                    <th width="2px"></th>

                    <td style="border-bottom: 1px solid #000">
                        <center style="margin-bottom: 4px;">{{ $formatApprovalTime(3) }}</center>
                        @if($datas[0]->mengetahui_owner != '-')
                            <center><img src="{!!url('access/images/ttd.png')!!}" style="width:200px;height:100px;object-fit:contain"><br>{{strtoupper($datas[0]->mengetahui_owner)}}</center>
                        @else
                            <br>
                            <br>
                            <br>
                            &nbsp;
                            <center>{{$datas[0]->mengetahui_owner}}</center>
                        @endif
                    </td>
                    
                </tr>
            </thead>
        </table>

        
    </div>



    <script>window.print()</script>
</body>
</html>