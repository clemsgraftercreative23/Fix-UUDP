@if (!empty($travelItem))
@php
    $fmtChecker = function ($v) {
        return number_format((float) $v, 0, ',', '.');
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
    <table class="table table-bordered mb-2" style="max-width: 720px;">
        <tr>
            <td class="bg-secondary" colspan="6"><center><strong>Checker's Sheet BDC</strong></center></td>
        </tr>
        <tbody>
            <tr>
                <td class="bg-secondary"><strong>Total Payment to be paid</strong></td>
                <td class="bg-secondary"><strong>{{ $fmtChecker($totalBdcToPay) }}</strong></td>
                <td class="bg-secondary" colspan="4"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Advanced Paid</td>
                <td class="bg-secondary"></td>
                <td class="bg-secondary" colspan="4"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Total Amount Paid</td>
                <td class="bg-secondary">{{ $fmtChecker($totalBdcToPay) }}</td>
                <td class="bg-secondary" colspan="4"></td>
            </tr>
            @foreach ($checkerRows as $rowLabels)
                <tr>
                    @foreach ($rowLabels as $label)
                        @if ($label === '')
                            <td class="bg-secondary" colspan="2"></td>
                        @else
                            @php
                                $k = $checkerLabelKey[$label] ?? null;
                                $val = $k ? ($bdcCats[$k] ?? 0) : 0;
                            @endphp
                            <td class="bg-secondary">{{ $label }}</td>
                            <td class="bg-secondary">{{ $fmtChecker($val) }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <br>
    <table class="table table-bordered mb-2" style="max-width: 720px;">
        <tr>
            <td class="bg-secondary" colspan="6"><center><strong>Checker's Sheet Cash</strong></center></td>
        </tr>
        <tbody>
            <tr>
                <td class="bg-secondary"><strong>Total Payment to be paid</strong></td>
                <td class="bg-secondary"><strong>{{ $fmtChecker($totalCashToPay) }}</strong></td>
                <td class="bg-secondary" colspan="4"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Advanced Paid</td>
                <td class="bg-secondary"></td>
                <td class="bg-secondary" colspan="4"></td>
            </tr>
            <tr>
                <td class="bg-secondary">Total Amount Paid</td>
                <td class="bg-secondary">{{ $fmtChecker($totalCashToPay) }}</td>
                <td class="bg-secondary" colspan="4"></td>
            </tr>
            @foreach ($checkerRows as $rowLabels)
                <tr>
                    @foreach ($rowLabels as $label)
                        @if ($label === '')
                            <td class="bg-secondary" colspan="2"></td>
                        @else
                            @php
                                $k = $checkerLabelKey[$label] ?? null;
                                $val = $k ? ($cashCats[$k] ?? 0) : 0;
                            @endphp
                            <td class="bg-secondary">{{ $label }}</td>
                            <td class="bg-secondary">{{ $fmtChecker($val) }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
