@extends('template.app')

@section('content')

<?php function rupiah($angka)
{
    return number_format($angka, 0, ',', '.');
} ?>

<style>
    .form-control{
        border-radius:5px;
    }
    .custom{
        height:2em; 
        width:80%;
        border-radius: 5px;
    }
    .dotted{
    border: dotted 2px #dee2e6;
    }
</style>

<div class="page-content">
    

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <a href="{!!url('pencairan-reimbursement')!!}" class="btn btn-primary" style="float:left;"><i class="fa    fa-arrow-circle-left"></i> Back </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">DETAIL REIMBURSEMENT TRAVEL</h5><hr>
                        <p>Below is the reimbursement data submitted by <b>{{ $data->applicantDisplayName() }}</b>.</p>
                        <hr>
                        @if(session()->has('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                        @endif
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <div class="alert alert-danger">
                                    {{ $error }}
                                </div>
                            @endforeach
                        @endif
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Apply Date</label>
                                <input type="text" class="form-control" value="{{ date('d F Y', strtotime($data->created_at))}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Transaction Date</label>
                                <input type="text" class="form-control" id="date" value="{{ date('d F Y', strtotime($data->date))}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Number</label>
                                <input type="text" class="form-control" value="{{$data->no_reimbursement}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Total</label>
                                <input type="text" class="form-control" value="{{number_format($data->nominal_pengajuan,0,',','.')}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Approved by Head Department</label>
                                <input type="text" class="form-control" value="{{strtoupper($data->mengetahui_op)}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Approved by HR GA</label>
                                <input type="text" class="form-control" id="date" value="{{strtoupper($data->mengetahui_finance)}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Approved by Finance</label>
                                <input type="text" class="form-control" value="{{strtoupper($data->mengetahui_owner)}}" readonly>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="inputEmail4">Status</label>
                                @php
                                    if($data->mengetahui_op=='-') {
                                        $meng = 'HEAD DEPARTMENT';
                                    } else if($data->mengetahui_finance=='-') {
                                        $meng = 'HR GA';
                                    } else if($data->mengetahui_owner=='-') {
                                        $meng = 'FINANCE';
                                    }
                                    
                                    $status = "PENDING";
                                    switch ($data->status) {
                                        case '1':
                                            $status = "APPROVED HEAD DEPARTMENT";
                                            break;
                                        case '2':
                                            $status = "APPROVED HR GA";
                                            break;
                                        case '3':
                                            $status = "PROCESS SETTLEMENT";
                                            break;
                                        case '9':
                                            $status = "REJECTED ".$meng."";
                                            break;
                                        case '5':
                                            $status = "SETTLED";
                                            break;
                                        
                                        default:
                                            # code...
                                            break;
                                    }
                                @endphp
                                <input type="text" class="form-control" value="{{$status}}" readonly>
                            </div>

                            @if ($data->status == 9)
                            <div class="form-group col-md-4">
                                <label for="inputPassword4">Reject Reason</label>
                                <input type="text" class="form-control" value="{{$data->reject_reason}}" readonly >
                            </div>
                            @endif
                        </div>
                        <div class="form-row mt-2">
                            <div class="form-group col-md-12 mb-0">
                                <label for="travel_detail_summary_remarks">Remarks</label>
                                <input type="text" id="travel_detail_summary_remarks" class="form-control" value="{{ $data->remark ?? '' }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body" style="display: block;width: 100%;overflow-x: auto;">
                <hr><span style="color:#66da90;"><h5>Detail Reimbursement</h5></span><hr>
                <table class="table table-bordered mb-2">
                    <tr>
                        @foreach ($data->rates as $item)
                        <th>{{$item->currency}} Rate</th>
                        <td class="bg-secondary">{{number_format($item->rate,0,',','.')}}</td>                        
                        @endforeach
                    </tr>
                </table>
                
                @foreach ($data->travels as $item)
                      
                <table class="table table-bordered mb-2">
                    <tr>
                        <th>Transaction Date</th>
                        <td class="bg-secondary">{{$data->date}}</td>
                        <th>Trip Type</th>
                        <td class="bg-secondary">{{ optional($item->tripType)->name ?? 'None' }}</td>
                        <th>Hotel At</th>
                        <td class="bg-secondary">{{ optional($item->hotelCondition)->name ?? 'Not Stay' }}</td>
                        <th>Allowance</th>
                        <td class="bg-secondary">IDR {{number_format($item->allowance,0,',','.')}}</td>
                        <th>Allowance (IDR)</th>
                        <td class="bg-secondary">{{number_format($item->allowance,0,',','.')}}</td>
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
                             <?php 
                                $start = strtotime($item->start_time);
                                $end = strtotime($item->end_time);
                                $minutes = ($end - $start) / 60;
                                $hours = floor($minutes / 60).' Hour and '.($minutes -   floor($minutes / 60) * 60).' Minutes';
                                echo $hours;
                            ?>
                        </td>
                    </tr>
                </table>
                <table class="table table-bordered mb-2">
                <thead>
                    <th>Cost Type</th>
                    <th>Destination</th>
                    <th>Remarks</th>
                    <th>Currency</th>
                    <th>Amount</th>
                    <th>Amount (IDR)</th>
                    <th>Payment</th>
                    <th>Evidence</th>
                </thead>
                @foreach ($item->details as $dt)
                    
                <tr>
                    <td>{{ optional($dt->costType)->name ?? '-' }}</td>
                    <td>{{$dt->destination}}</td>
                    <td>{{ $data->remark ?? '' }}</td>
                    <td>{{$dt->currency}}</td>
                    <td>{{$dt->currency}} {{number_format($dt->amount,0,',','.')}}</td>
                    <td>{{number_format($dt->idr_rate,0,',','.')}}</td>
                    
                    <td>{{$dt->payment_type}}</td>
                    <td><a href="{{ URL::to('/') }}/images/file_bukti/{{$dt->evidence}}" target="_blank"><i class="fa fa-file"></i></a></td>
                </tr>
                @endforeach

                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="bg-secondary text-right" colspan="7">{{number_format($item->total,0,',','.')}}</td>
                    </tr>
                </tfoot>
                </table>
                <hr>
                @endforeach

            </div>

            <div class="col-lg-12">

                    @if ($data->status == 5)

                    <hr />
                    <span style="color: #66da90;"><h5>Detail Settlement</h5></span>
                    <hr />
                    <h6>BDC</h6>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Method</label>
                                <input readonly type="text" class="form-control" value="{{$data->metode_bdc}}" />
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Name</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->penerima}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Number</label>
                                <input readonly type="text" class="form-control" name="no_rek" value="{{$data->no_rek}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{$data->bank}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Total</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($bdc,0,',','.')}}" />
                            </div>
                        </div>
                    </div>
                    <hr>

                    <h6>ALLOWANCE</h6>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Method</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$metode_allowance}}" />
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Name</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->penerima}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Number</label>
                                <input readonly type="text" class="form-control" name="no_rek" value="{{$data->no_rek}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{$data->bank}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Total</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($allowance,0,',','.')}}" />
                            </div>
                        </div>
                    </div>
                    <hr>

                    <h6>CASH</h6>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Method</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$metode_cash}}" />
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Name</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->penerima}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank Account Number</label>
                                <input readonly type="text" class="form-control" name="no_rek" value="{{$data->no_rek}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bank</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{$data->bank}}" />
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Total</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($cash,0,',','.')}}" />
                            </div>
                        </div>
                    </div>  
                    
                    @endif 


                    @if ($data->status == 3 && in_array(auth()->user()->jabatan, ['Owner', 'superadmin', 'admin']))
                    @php
                        $coaOptions = [
                            '6-1051' => '6-1051 (Transportation)',
                            '6-1055' => '6-1055 (Transportation BIK)',
                            '6-1052' => '6-1052 (Business Trip - Domestic)',
                            '6-1053' => '6-1053 (Business Trip - Overseas)',
                            '6-1054' => '6-1054 (Travel Allowance BIK)',
                            '6-2021' => '6-2021 (Entertainment Fee)',
                            '1-1940' => '1-1940 (Temporary Payment)',
                            '1-1960' => '1-1960 (Flash Fleet BCA)',
                        ];

                        $costTypeLabels = [
                            'allowance' => 'Allowance',
                            'simcard' => 'SIM Card',
                            'flight' => 'Flight',
                            'rentalcar' => 'Rental Car',
                            'hotel' => 'Hotel',
                            'toll' => 'Toll',
                            'gasoline' => 'Gasoline',
                            'taxi' => 'Taxi',
                            'train' => 'Train',
                            'others' => 'Others',
                        ];

                        $bdcBreakdown = [];
                        foreach ($costTypeLabels as $key => $label) {
                            $field = $key . '_bdc';
                            $amount = (int) ($data->{$field} ?? 0);
                            if ($amount > 0) {
                                $bdcBreakdown[] = ['key' => $key, 'label' => $label, 'amount' => $amount];
                            }
                        }

                        $cashBreakdown = [];
                        foreach ($costTypeLabels as $key => $label) {
                            if ($key === 'allowance') {
                                continue;
                            }
                            $field = $key . '_cash';
                            $amount = (int) ($data->{$field} ?? 0);
                            if ($amount > 0) {
                                $cashBreakdown[] = ['key' => $key, 'label' => $label, 'amount' => $amount];
                            }
                        }

                        $allowanceBreakdown = [];
                        $allowanceAmount = (int) ($allowance ?? 0);
                        if ($allowanceAmount > 0) {
                            $allowanceBreakdown[] = ['key' => 'allowance', 'label' => 'Allowance', 'amount' => $allowanceAmount];
                        }

                        $groups = [
                            'BDC' => [
                                'method_name' => 'metode_bdc',
                                'items' => $bdcBreakdown,
                                'total' => array_sum(array_column($bdcBreakdown, 'amount')),
                            ],
                            'ALLOWANCE' => [
                                'method_name' => 'metode_allowance',
                                'items' => $allowanceBreakdown,
                                'total' => array_sum(array_column($allowanceBreakdown, 'amount')),
                            ],
                            'CASH' => [
                                'method_name' => 'metode_cash',
                                'items' => $cashBreakdown,
                                'total' => array_sum(array_column($cashBreakdown, 'amount')),
                            ],
                        ];
                        $breakdownIndex = 0;
                    @endphp

                    <form action="{{url('/').'/pencairan-reimbursement/'.$data->id}}" method="POST">
                        <input type="hidden" name="employeeNo" value="{{$empNo}}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Bank Account Name</label>
                                    <input type="text" class="form-control" name="penerima" value="{{ $data->applicantDisplayName() }}" required/>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Bank Account Number</label>
                                    <input type="text" class="form-control" name="no_rek" value="{{ optional($data->user)->bankAccount ?? '' }}" required/>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Bank</label>
                                    <input type="text" class="form-control" name="bank" value="{{ optional($data->user)->bankName ?? '' }}" required/>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="total_allowance" value="{{$groups['ALLOWANCE']['total']}}">
                        <input type="hidden" name="total_cash" value="{{$groups['CASH']['total']}}">
                        <input type="hidden" name="total_bdc" value="{{$groups['BDC']['total']}}">

                        @foreach ($groups as $groupLabel => $group)
                            @php $isBdcGroup = ($groupLabel === 'BDC'); @endphp
                            @if($group['total'] > 0)
                            <hr>
                            <h6>{{$groupLabel}}</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Settlement Method</label>
                                        @if($isBdcGroup)
                                            <input type="text" class="form-control" value="Excluded from UUDP settlement" readonly>
                                        @else
                                            <select class="form-control cst-select" name="{{$group['method_name']}}" required>
                                                <option value="">--Select Settlement Method--</option>
                                                @foreach($kasbank as $row)
                                                <option value="{{$row->kode_perkiraan}}">{{$row->nama}}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Total Breakdown</label>
                                        <input type="text" class="form-control" value="{{number_format($group['total'],0,',','.')}}" readonly />
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Cost Type</th>
                                            <th style="width: 25%;">Nominal</th>
                                            <th style="width: 45%;">Akun Perkiraan (Accurate)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($group['items'] as $itemBreakdown)
                                        <tr>
                                            <td>{{$itemBreakdown['label']}}</td>
                                            <td>{{number_format($itemBreakdown['amount'],0,',','.')}}</td>
                                            <td>
                                                <input type="hidden" name="breakdown_entries[{{$breakdownIndex}}][group]" value="{{$groupLabel}}">
                                                <input type="hidden" name="breakdown_entries[{{$breakdownIndex}}][cost_key]" value="{{$itemBreakdown['key']}}">
                                                <input type="hidden" name="breakdown_entries[{{$breakdownIndex}}][amount]" value="{{$itemBreakdown['amount']}}">
                                                @if($isBdcGroup)
                                                    <input type="text" class="form-control" value="Excluded from Accurate journal" readonly>
                                                @else
                                                    <select class="form-control cst-select" name="breakdown_entries[{{$breakdownIndex}}][account_no]" required>
                                                        <option value="">--Select Akun Perkiraan--</option>
                                                        @foreach($coaOptions as $coaCode => $coaLabel)
                                                        <option value="{{$coaCode}}">{{$coaLabel}}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </td>
                                        </tr>
                                        @php $breakdownIndex++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        @endforeach

                        <br>
                        <hr>
                        <br>
                        <center>
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Process Settlement</button>
                        </center>
                    </form>
                    <br>
                    @else

                    @endif
                    <br>
                    <center>
                        @php
                            $isSuperadmin = in_array(auth()->user()->jabatan, ['superadmin', 'admin']);
                        @endphp
                        @if ($data->status == 0 && (auth()->user()->jabatan == 'Direktur Operasional' || $isSuperadmin) && ($isSuperadmin || (int) auth()->id() !== (int) $data->id_user))                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 1 && (in_array(auth()->user()->jabatan, ['Finance', 'HR GA']) || $isSuperadmin) && ($isSuperadmin || (int) auth()->id() !== (int) $data->id_user))                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 2 && (auth()->user()->jabatan == 'Owner' || $isSuperadmin) && ($isSuperadmin || (int) auth()->id() !== (int) $data->id_user))                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 9 && (auth()->user()->id == $data->id_user || $isSuperadmin))                                
                            @if($data->travel_type=='Domestic')
                                <a href="{!!url('edit-travel-inquiry')!!}/{{$data->id}}"  class="btn btn-primary">Edit</a>
                            @else
                                <a href="{!!url('edit-travel-overseas')!!}/{{$data->id}}"  class="btn btn-primary">Edit</a>
                            @endif
                        @endif
                        @if ($data->status == 5 && \App\Support\JabatanClassifier::canSyncAccurate(auth()->user()->jabatan))
                            @php
                                $accuratePayloadPreview = json_decode($data->accurate_payload_json ?? '', true);
                                $previewLines = [];
                                if (is_array($accuratePayloadPreview)) {
                                    if (!empty($accuratePayloadPreview['detailAccount']) && is_array($accuratePayloadPreview['detailAccount'])) {
                                        $previewLines = $accuratePayloadPreview['detailAccount'];
                                    } elseif (!empty($accuratePayloadPreview['detailJournalVoucher']) && is_array($accuratePayloadPreview['detailJournalVoucher'])) {
                                        $previewLines = $accuratePayloadPreview['detailJournalVoucher'];
                                    }
                                }
                            @endphp
                            @if (!empty($previewLines))
                                <div class="alert alert-secondary text-left" style="max-width:900px;margin:0 auto 12px auto;">
                                    <strong>Preview akun perkiraan untuk Sync Accurate</strong><br>
                                    <small>Bank No: {{ $accuratePayloadPreview['bankNo'] ?? '-' }}</small>
                                    <div class="table-responsive" style="margin-top:8px;">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Akun Perkiraan</th>
                                                    <th>Amount</th>
                                                    <th>Department</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($previewLines as $line)
                                                    <tr>
                                                        <td>{{ $line['accountNo'] ?? '-' }}</td>
                                                        <td>{{ isset($line['amount']) ? number_format((float) $line['amount'], 0, ',', '.') : '-' }}</td>
                                                        <td>{{ $line['departmentName'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            @if ($data->accurate_sync_status === 'synced')
                                <button type="button" class="btn btn-success" disabled>
                                    Accurate Synced ({{ date('d M Y H:i', strtotime($data->accurate_synced_at)) }})
                                </button>
                                @php $journalDateDisplay = \App\Support\AccurateJournalDate::displayFromPayload($data->accurate_payload_json); @endphp
                                @if ($journalDateDisplay)
                                    <div><small class="text-muted">Tanggal Jurnal: {{ $journalDateDisplay }}</small></div>
                                @endif
                                @if (\App\Support\JabatanClassifier::canReverseAccurateSync(auth()->user()->jabatan))
                                    <div style="margin-top:6px;">
                                        <form action="{{ route('pencairan-reimbursement.reverse-accurate', $data->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin mereverse (membatalkan) sinkronisasi Accurate ini? Entri yang sudah dibuat di Accurate akan dihapus.');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm">Reverse Sync Accurate</button>
                                        </form>
                                    </div>
                                @endif
                            @else
                                @if ($data->accurate_sync_status === 'reversed')
                                    <div class="alert alert-warning text-left" style="max-width:900px;margin:0 auto 12px auto;">
                                        Sinkronisasi sebelumnya (pada {{ date('d M Y H:i', strtotime($data->accurate_synced_at)) }}) telah direverse pada {{ date('d M Y H:i', strtotime($data->accurate_reversed_at)) }}.
                                        Silakan sync ulang di bawah ini jika diperlukan.
                                    </div>
                                @endif
                                <form action="{{ route('pencairan-reimbursement.sync-accurate', $data->id) }}" method="POST" class="js-sync-accurate-form" style="display:inline-flex;align-items:center;gap:6px;vertical-align:middle;">
                                    @csrf
                                    <label for="tanggal_jurnal_{{ $data->id }}" class="mb-0">Tanggal Jurnal</label>
                                    <input type="date" name="tanggal_jurnal" id="tanggal_jurnal_{{ $data->id }}" class="form-control form-control-sm" style="width:auto;" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                    <button type="submit" class="btn btn-warning js-sync-accurate-btn">
                                        <span class="js-btn-label">Sync Accurate</span>
                                    </button>
                                </form>
                                @if (in_array(auth()->user()->jabatan, ['Owner', 'superadmin', 'admin']))
                                    <form action="{{ route('pencairan-reimbursement.reset-settlement', $data->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin mereset settlement ini? Anda akan diminta untuk melakukan settlement ulang.');">
                                        @csrf
                                        <button type="submit" class="btn btn-info">Reset Settlement</button>
                                    </form>
                                @endif
                            @endif
                        @endif
                        </center>
                    <br><br><br>
            </div>

        </div>
    </div>
</div>

  
@push('scripts')
<script type="text/javascript">
  $(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();   
  });

  $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});

  $(document).on('click', '.edit', function(){
      var id = $(this).attr('id');
      console.log(id);
      $.ajax({
      url : '{{ route("getPertanggungjawaban") }}',
        type: 'POST',
        headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data:{id:id},
        success:function(data){
        $('#detailPertanggungjawaban').html(data)
        },
      });
  });

  $(document).on('click', '.change', function(){
      var id = $(this).attr('id');
      console.log(id);
      $.ajax({
      url : '{{ route("changePertanggungjawaban") }}',
        type: 'POST',
        headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data:{id:id},
        success:function(data){
        $('#changePertanggungjawaban').html(data)
        },
      });
  });

  $('select[name="metode"]').on('change', function(){
        var id = $(this).val();
        if(id) {
            $.ajax({
                url: '../getMetode/'+id,
                type:"GET",
                dataType:"json",
                beforeSend: function(){
                    $('.loader').css("visibility", "visible");
                },

                success:function(data) {

                    $('select[name="sumber"]').empty();

                    $.each(data, function(key, value){

                        $('select[name="sumber"]').append('<option value="'+ key +'">' + value + '</option>');

                    });
                },
                complete: function(){
                    $('.loader').css("visibility", "hidden");
                }
            });
        } else {
            $('select[name="sumber"]').empty();
        }

  });

  $('#form_finish').on('submit', function(event){
      event.preventDefault();
      $("#finish_button").prop("disabled", true);
      
      $.ajax({
           url:"../../pertanggungjawaban/finish",
           method:"POST",
           data:new FormData(this),
           contentType: false,
           cache: false,
           processData: false,
           dataType:"json",
           beforeSend: function(){
           $('.loader').css("visibility", "visible");
           $("#finish_button").prop("disabled", true);
           },
           success:function(data)
           {
           var html = '';
           if(data.errors)
           {
           alert('Data gagal disimpan!');
           }
           if(data.success)
           {
           alert('Data berhasil disimpan!');
           location.reload();
           }
           $('#form_result').html(html);
           $('#formModal').modal('hide');
           
           },
           complete: function(){
           $('.loader').css("visibility", "hidden");
           }
       });
        
    });

    $(document).on('submit', '.js-sync-accurate-form', function () {
        var $btn = $(this).find('.js-sync-accurate-btn');
        $btn.prop('disabled', true);
        $btn.find('.js-btn-label').text('Menyinkronkan...');
        $btn.prepend('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>');
    });

</script>
@endpush
@endsection
