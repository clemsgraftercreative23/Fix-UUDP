{{-- Per-segment travel: checker summary by payment (BDC vs Cash), aligned with finance cost_type_id mapping. --}}
@if (!empty($travelItem))
@php
    $fmtChecker = function ($v) {
        return number_format((float) $v, 2, ',', '.');
    };
    $payIs = function ($raw, $want) {
        return strcasecmp(trim((string) ($raw ?? '')), $want) === 0;
    };
    $catsEmpty = [
        'allowance' => 0.0,
        'simcard' => 0.0,
        'flight' => 0.0,
        'rentalcar' => 0.0,
        'hotel' => 0.0,
        'toll' => 0.0,
        'gasoline' => 0.0,
        'taxi' => 0.0,
        'train' => 0.0,
        'pph23' => 0.0,
        'others' => 0.0,
    ];
    $bdcCats = $catsEmpty;
    $cashCats = $catsEmpty;
    $costIdToKey = [
        1 => 'hotel',
        2 => 'taxi',
        3 => 'rentalcar',
        4 => 'flight',
        5 => 'toll',
        6 => 'train',
        7 => 'gasoline',
        8 => 'simcard',
        9 => 'others',
    ];
    $sumBdcIdr = 0.0;
    $sumCashIdr = 0.0;
    foreach ($travelItem->details ?? [] as $dt) {
        $idr = (float) ($dt->idr_rate ?? 0);
        $tax = (float) ($dt->tax ?? 0);
        $cid = (int) ($dt->cost_type_id ?? 0);
        $key = $costIdToKey[$cid] ?? 'others';
        if ($payIs($dt->payment_type ?? '', 'BDC')) {
            $bdcCats[$key] += $idr;
            $bdcCats['pph23'] += $tax;
            $sumBdcIdr += $idr;
        } elseif ($payIs($dt->payment_type ?? '', 'Cash')) {
            $cashCats[$key] += $idr;
            $cashCats['pph23'] += $tax;
            $sumCashIdr += $idr;
        }
    }
    $allowanceIdr = (float) ($travelItem->allowance ?? 0);
    $cashCats['allowance'] = $allowanceIdr;

    $totalBdcToPay = $sumBdcIdr;
    $totalCashToPay = $sumCashIdr + $allowanceIdr;

    $checkerRows = [
        ['Allowance', 'SIM Card', 'Flight'],
        ['Rental Car', 'Hotel', 'Toll'],
        ['Gasoline', 'Taxi', 'Train'],
        ['PPH23', 'Others', ''],
    ];
    $checkerLabelKey = [
        'Allowance' => 'allowance',
        'SIM Card' => 'simcard',
        'Flight' => 'flight',
        'Rental Car' => 'rentalcar',
        'Hotel' => 'hotel',
        'Toll' => 'toll',
        'Gasoline' => 'gasoline',
        'Taxi' => 'taxi',
        'Train' => 'train',
        'PPH23' => 'pph23',
        'Others' => 'others',
    ];
@endphp
<div class="mt-3 mb-2">
    <h6 class="mb-2" style="color:#66da90;">Checker's Sheet BDC</h6>
    <table class="table table-bordered table-sm mb-2" style="max-width:720px;">
        <tbody>
            <tr>
                <th width="40%">Total Payment to be paid</th>
                <td class="bg-secondary text-right">{{ $fmtChecker($totalBdcToPay) }}</td>
            </tr>
            <tr>
                <th>Advanced Paid</th>
                <td class="bg-secondary"></td>
            </tr>
            <tr>
                <th>Total Amount Paid</th>
                <td class="bg-secondary text-right">{{ $fmtChecker($totalBdcToPay) }}</td>
            </tr>
        </tbody>
    </table>
    <table class="table table-bordered table-sm mb-3" style="max-width:720px;">
        <tbody>
            @foreach ($checkerRows as $rowLabels)
                <tr>
                    @foreach ($rowLabels as $label)
                        @if ($label === '')
                            <td></td>
                        @else
                            @php
                                $k = $checkerLabelKey[$label] ?? null;
                                $val = $k ? ($bdcCats[$k] ?? 0) : 0;
                            @endphp
                            <td>
                                <div><strong>{{ $label }}</strong></div>
                                <div class="text-right">{{ $fmtChecker($val) }}</div>
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <h6 class="mb-2" style="color:#66da90;">Checker's Sheet Cash</h6>
    <table class="table table-bordered table-sm mb-2" style="max-width:720px;">
        <tbody>
            <tr>
                <th width="40%">Total Payment to be paid</th>
                <td class="bg-secondary text-right">{{ $fmtChecker($totalCashToPay) }}</td>
            </tr>
            <tr>
                <th>Advanced Paid</th>
                <td class="bg-secondary"></td>
            </tr>
            <tr>
                <th>Total Amount Paid</th>
                <td class="bg-secondary text-right">{{ $fmtChecker($totalCashToPay) }}</td>
            </tr>
        </tbody>
    </table>
    <table class="table table-bordered table-sm mb-2" style="max-width:720px;">
        <tbody>
            @foreach ($checkerRows as $rowLabels)
                <tr>
                    @foreach ($rowLabels as $label)
                        @if ($label === '')
                            <td></td>
                        @else
                            @php
                                $k = $checkerLabelKey[$label] ?? null;
                                $val = $k ? ($cashCats[$k] ?? 0) : 0;
                            @endphp
                            <td>
                                <div><strong>{{ $label }}</strong></div>
                                <div class="text-right">{{ $fmtChecker($val) }}</div>
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
