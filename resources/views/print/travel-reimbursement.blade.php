<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Travel Reimbursement</title>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt

        }
        @page {
            size: 'A4';
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
      <table class="table-style table-bordered mb-2">
              @foreach (($data->travels ?? collect()) as $item)              
                <tr>
                    <th>Transaction Date</th>
                    <td class="bg-secondary">{{$item->date}}</td>
                    <th>Trip Type</th>
                    <td class="bg-secondary">{{ optional($item->tripType)->name ?? 'None' }}</td>
                    <th>Hotel At</th>
                    <td class="bg-secondary">{{ optional($item->hotelCondition)->name ?? '-' }}</td>
                    <th>Allowance</th>
                    <td class="bg-secondary">
                        {{-- Was HTML comment but Blade still evaluated {{ $item->tripType->currency }} and crashed when tripType was null --}}
                        @php
                            $currency = App\TravelTripType::where('id', $item->trip_type_id)->first();
                            if ($currency) {
                                $allowance_trip = $currency->allowance;
                                echo number_format($allowance_trip, 0, ',', '.') . ' ' . $currency->currency;
                            } else {
                                echo '0';
                            }

                        @endphp
                    </td>
                    <th>Allowance (IDR)</th>
                    <td class="bg-secondary">
                        <!-- @php
                            $currency = App\TravelTripRate::where('reimbursement_id',$data->id)->where('currency',$item->tripType->currency)->first();
                          
                            if ($currency) {
                                $currency = $currency->rate;
                            }
                            if (!$currency && $item->tripType->currency == "IDR") {
                                $currency = 1;
                            }

                            if (!$currency && $item->tripType->currency == "USD") {
                                $currency = 16400;
                            }


                            echo number_format($item->allowance * $currency,0,',','.');
                        @endphp -->
                        {{number_format($item->allowance,0,',','.')}}
                    </td>
                </tr>
                <tr>
                    <th>Purpose</th>
                    <td class="bg-secondary">{{$item->purpose}}</td>
                    <th>Start</th>
                    <td class="bg-secondary">{{$item->start_time}}</td>
                    <th>Arrival</th>
                    <td class="bg-secondary">{{$item->end_time}}</td>
                    <th>Travel Time</th>
                    <td class="bg-secondary" colspan="3">
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
                <th colspan="2">Cost Type</th>
                <th colspan="2">Destination</th>
                <th colspan="2">Remarks</th>
                <th>Currency</th>
                <th colspan="2">Amount</th>
                <th colspan="2">Amount (IDR)</th>
                <th>Payment</th>
            </tr>
            @foreach (($item->details ?? collect()) as $dt)                
            <tr>
                <td colspan="2">{{ optional($dt->costType)->name ?? '-' }}</td>
                <td colspan="2">{{$dt->destination}}</td>
                <td colspan="2">{{ $data->remark ?? '' }}</td>
                <td>{{$dt->currency}}</td>
                <td colspan="2" align="right">{{$dt->currency}} {{number_format($dt->amount,0,',','.')}}</td>
                <td colspan="2" align="right">{{number_format($dt->idr_rate,0,',','.')}}</td>
              
                <td>{{$dt->payment_type}}</td>
            </tr>
            @endforeach
            
                <tr>
                    <td colspan="2">Total</td>
                    <td class="bg-secondary text-right" align="right" colspan="9">{{number_format($item->total,0,',','.')}}</td>
                    <td style="background: #f0f0f0" >&nbsp;</td>
                </tr>
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