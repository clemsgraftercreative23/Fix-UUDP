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
    .modal-dialog {
        max-width: 100%;
        margin: 0 auto;
    }

    .modal-content {
        max-height: 100vh; 
        overflow-y: auto; 
    }

    .modal-body {
        overflow-y: auto;
        max-height: 90vh; 
    }

    /* Table cell padding for better separation */
    .table-bordered td {
        padding-left: 12px;
        padding-right: 12px;
    }

    @media (max-width: 768px) {
        select[name="payment_type[]"] {
            min-width: 100px;
            margin-right: 8px;
        }
        .amount-input {
            min-width: 100px;
            margin-left: 8px;
        }
    }

    .img-lightbox-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.85);
        z-index: 20000;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .img-lightbox-overlay.active {
        display: flex;
    }

    .img-lightbox-content {
        position: relative;
        max-width: 95vw;
        max-height: 90vh;
    }

    .img-lightbox-content img {
        max-width: 95vw;
        max-height: 90vh;
        border-radius: 8px;
    }

    .img-lightbox-close {
        position: absolute;
        right: -12px;
        top: -12px;
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 999px;
        background: #fff;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
    }

    .preview-thumbnail {
        cursor: pointer !important;
    }
</style>

<div class="page-content" id="app">   

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <a href="{!!url('reimbursement-entertaiment')!!}" class="btn btn-primary" style="float:left;"><i class="fa    fa-arrow-circle-left"></i> Back </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">DETAIL REIMBURSEMENT ENTERTAINMENT</h5><hr>
                        <p>Below is the reimbursement data submitted by <b>{{$data->user->name}}</b>.</p>
                        @php
                          $isApproverRole = in_array(auth()->user()->jabatan, ['Direktur Operasional', 'Finance', 'Owner', 'superadmin'], true);
                        @endphp
                        @if($isApproverRole && in_array((int) $data->status, [0, 1, 2], true))
                        <div class="alert alert-info mb-0 mt-2" role="alert">
                          Verifikasi bertahap: status <strong>PENDING</strong> = tunggu Head Department; setelah itu HR GA lalu Finance. Anda juga bisa memproses dari halaman <a href="{{ url('reimbursement-entertaiment-approval') }}" class="alert-link">Approval (bulk)</a>.
                        </div>
                        @elseif(auth()->id() == $data->id_user && in_array((int) $data->status, [0, 1, 2], true))
                        <div class="alert alert-secondary mb-0 mt-2" role="alert">
                          Ini pengajuan Anda. Tombol <strong>Approve</strong> hanya untuk verifikator. Silakan tunggu proses dari Head Department / HR GA / Finance.
                        </div>
                        @endif
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
                                        case '10':
                                            $status = "DRAFT";
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
                          <div>
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
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th width="5%">No</th>
                        <td>No of Attendance</td>
                        <td>Attendance</td>
                        <td>Position</td>
                        <td>Place</td>
                        <td>Guest</td>
                        <td>Guest Position</td>
                        <td>Company</td>
                        <td>Type</td>
                        <td>Payment</td>
                        <td>Amount</td>
                        <td>Attachment</td>
                    </tr>
                    </thead>
                    <?php $no = 1; ?>
                    @foreach($data->entertaiments as $row)
                    <tr>
                        <td width="1px">{{$no++}}</td>
                        <td>{{$row->empty_zone}}</td>
                        <td>{{$row->attendance}}</td>
                        <td>{{$row->position}}</td>
                        <td>{{$row->place}}</td>
                        <td>{{$row->guest}}</td>
                        <td>{{$row->guest_position}}</td>
                        <td>{{$row->company}}</td>
                        <td>{{$row->type}}</td>
                        <td>{{$row->payment_type}}</td>
                        <td>{{number_format($row->amount,0,'.',',')}}</td>
                        <td width="200px"><a href="{{ URL::to('/') }}/images/file_bukti/{{$row->evidence}}" target="_blank"><i class="fa fa-file"></i></a></td>
                    </tr>
                    @endforeach
                </table>
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
                                <input readonly type="text" class="form-control"/>
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

                    <h6>Cash</h6>
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
                                <input readonly type="text" class="form-control" name="bank" value="{{number_format($cash,0,',','.')}}" />
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
                    <br>
                    <center>
                        @if ($data->status == 0 && (auth()->user()->jabatan == 'Direktur Operasional' || auth()->user()->jabatan == 'superadmin'))                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-warning"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 9 && auth()->user()->id == $data->id_user) 
                            <button type="button" class="btn btn-primary"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                        @endif

                        @if ($data->status == 10 && auth()->user()->id == $data->id_user) 
                            <button type="button" class="btn btn-primary"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                        @endif
                        
                        @if ($data->status == 1 && (auth()->user()->jabatan == 'Finance' || auth()->user()->jabatan == 'superadmin'))                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-warning"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                        
                        @if ($data->status == 2 && (auth()->user()->jabatan == 'Owner' || auth()->user()->jabatan == 'superadmin'))                                
                            <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-warning"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>
                                <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>&nbsp;&nbsp;
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                            </form>
                        @endif
                    </center>
                    <br><br><br>
            </div>

        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-lg" id="formModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="overflow-y: auto">
    @if (auth()->user()->jabatan == 'karyawan') 
        <form method="post" id="sample_form" action="{{url('/')."/reimbursement-entertaiment/".$data->id}}" enctype="multipart/form-data">
    @else
        <form method="post" id="sample_form" action="{{url('/')."/reimbursement-entertaiment/update-approval/".$data->id}}" enctype="multipart/form-data">
    @endif
    @csrf
    @method('PUT')
    <input type="hidden" name="deletedId" :model="deletedId">
      <div class="modal-dialog modal-xl" style="max-width: 100%;margin: 19;top: 19;bottom: 19;left: 19;right: 19;display: flex;">
          <div class="modal-content">
              <div class="modal-header border-bottom"  >
              <div class="d-flex justify-content-between w-100">
                    <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle">Edit Reimbursement UUDP</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
                </div>
              </div>

              <div class="modal-body py-3">
              <div class="row my-3"> 
                
                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">Employee</label>
                       <input type="hidden" name="id_user" value="{{Auth::user()->id}}">
                       <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" placeholder="Nama Lengkap" value="{{Auth::user()->name}}">
                     </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">NIK</label>
                       <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" value="{{Auth::user()->idKaryawan}}">
                     </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">Apply Date</label>
                       <input type="text" class="form-control"  style="border-radius: 10px;" value="{{ date('d F Y', strtotime($data->created_at))}}" readonly>
                     </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                       <label for="exampleFormControlInput1">Transaction Date</label>
                       <input type="date" class="form-control date-picker" name="date" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{$data->date}}" required>
                     </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Department</label>
                      <select name="reimbursement_department_id" id="" class="form-control">
                        @foreach (\App\Departemen::get() as $item)
                            <option value="{{$item->id}}">{{$item->nama_departemen}}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Remark</label>
                      <input type="text" class="form-control date-picker" name="remark_parent" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{$data->remark}}">
                    </div>
                  </div>
                  <hr>
                </div>
                <label class="modal-title clr-green" id="exampleModalCenterTitle">Detail Reimbursement</label>
                <div class="respon respon-big table-responsive">
                    
                     <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3 align=center width="1400">
                      <thead>
                          <tr>
                              <td>No of Attendance</td>
                              <td>Attendance</td>
                              <td>Position</td>
                              <td>Place</td>
                              <td>Guest</td>
                              <td>Guest Position</td>
                              <td>Company</td>
                              <td>Type</td>
                              <td>Payment</td>
                              <td>Amount</td>
                              <td width="100">Evidence</td>
                              <td>Preview</td>
                              <td>Remark</td>
                              <td align="center" >Action</td>
                          </tr>
                      </thead>
                      <tbody>
                        <tr class="fieldGroup">
                              <td>
                                <input type="hidden" name="id_detail[]" value="{{$detail['0']->id}}">
                                <input type="text" class="form-control" name="empty_zone[]" required value="{{$detail['0']->empty_zone}}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="attendance[]" required value="{{$detail['0']->attendance}}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="position[]" required value="{{$detail['0']->position}}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="place[]" required value="{{$detail['0']->place}}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="guest[]" required value="{{$detail['0']->guest}}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="guest_position[]" required value="{{$detail['0']->guest_position}}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="company[]" required value="{{$detail['0']->company}}">
                              </td>
                              <td>
                                <input type="text" class="form-control" name="type[]" required value="{{$detail['0']->type}}">
                              </td>
                               <td>
                                    <select class="form-control" name="payment_type[]" style="width:100%">
                                        <option value="">Select...</option>
                                        <option value="BDC" @if($detail['0']->payment_type=='BDC') selected @endif>BDC</option>
                                        <option value="Cash" @if($detail['0']->payment_type=='Cash') selected @endif>Cash</option>
                                    </select>
                                </td>
                                <td>
                                <input type="text" class="form-control amount-input amount1 currency change-amount" name="amount[]" placeholder="Amount" required value="{{rupiah($detail['0']->amount)}}">
                                </td>
                                <td class="file-proof">
                                    <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                                        <i class="fa fa-upload"></i>
                                    </button>
                                    <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                                        <i class="fa fa-camera"></i>
                                    </button>
                                    <input type="file" accept="image/*" name="file[]" style="display: none;" class="file-input file1">
                                    <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                                </td>
                                <td>
                                    <div id="preview_1">
                                        <a href="{!!url('images/file_bukti/'.$detail['0']->evidence.'')!!}" target="_blank" class="preview-link" data-preview-src="{!!url('images/file_bukti/'.$detail['0']->evidence.'')!!}">
                                            <img src="{!!url('images/file_bukti/'.$detail['0']->evidence.'')!!}" class="preview-thumbnail" data-preview-src="{!!url('images/file_bukti/'.$detail['0']->evidence.'')!!}" onclick="openImageLightbox(this.getAttribute('data-preview-src') || this.src)" style="max-width: 75px; max-height: 75px; border: 2px solid rgb(40, 167, 69); border-radius: 5px; margin-top: 5px; cursor: pointer;">
                                        </a>
                                    </div>
                                </td>
                              
                              <td>
                                <input type="text" class="form-control" name="remark[]" placeholder="Remark"  value="{{$detail['0']->remark}}">
                              </td>
                              <td>
                                <button type="button" name="add" id="add" class="btn btn-success full-width addMore">+</button>
                              </td>
                        </tr>
                        <?php $numb = 1;?>
                            @foreach ($detail as $key => $row)
                            @if($key > 0)
                            <?php $numb++ ?>
                            <tr class="fieldGroup">
                                  <td>
                                    <input type="hidden" name="id_detail[]" value="{{$row->id}}">
                                    <input type="text" class="form-control" name="empty_zone[]" required value="{{$row->empty_zone}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="attendance[]" required value="{{$row->attendance}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="position[]" required value="{{$row->position}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="place[]" required value="{{$row->place}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="guest[]" required value="{{$row->guest}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="guest_position[]" required value="{{$row->guest_position}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="company[]" required value="{{$row->company}}">
                                  </td>
                                  <td>
                                    <input type="text" class="form-control" name="type[]" required value="{{$row->type}}">
                                  </td>
                                  <td>
                                        <select class="form-control" name="payment_type[]" style="width:100%">
                                            <option value="">Select...</option>
                                            <option value="BDC" @if($row->payment_type=='BDC') selected @endif>BDC</option>
                                            <option value="Cash" @if($row->payment_type=='Cash') selected @endif>Cash</option>
                                        </select>
                                  </td>
                                  <td>
                                    <input type="text" class="form-control amount{{$numb}} currency change-amount" name="amount[]" placeholder="Amount" required value="{{rupiah($row->amount)}}">
                                  </td>
                                  <td class="file-proof">
                                        <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                                            <i class="fa fa-upload"></i>
                                        </button>
                                        <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera">
                                            <i class="fa fa-camera"></i>
                                        </button>
                                        <input type="file" accept="image/*" name="file[]" style="display: none;" class="file-input file1">
                                        <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                                    </td>
                                    <td>
                                        <div id="preview_{{$numb}}">
                                            <a href="{!!url('images/file_bukti/'.$row->evidence.'')!!}" target="_blank" class="preview-link" data-preview-src="{!!url('images/file_bukti/'.$row->evidence.'')!!}">
                                                <img src="{!!url('images/file_bukti/'.$row->evidence.'')!!}" class="preview-thumbnail" data-preview-src="{!!url('images/file_bukti/'.$row->evidence.'')!!}" onclick="openImageLightbox(this.getAttribute('data-preview-src') || this.src)" style="max-width: 75px; max-height: 75px; border: 2px solid rgb(40, 167, 69); border-radius: 5px; margin-top: 5px; cursor: pointer;">
                                            </a>
                                        </div>
                                    </td>
                                  
                                  <td>
                                    <input type="text" class="form-control" name="remark[]" placeholder="Remark"  value="{{$row->remark}}">
                                  </td>
                                  <td>
                                    <button type="button" class="btn btn-danger full-width remove-item">-</button>
                                  </td>
                            </tr>
                            
                            
                            @endif
                        @endforeach
                      </tbody>
                    </table>
                        
                </div>
                <br>
                <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Nominal</label>
                <div class="form-group">
                <label for="exampleFormControlInput1">Total Inquiry</label>
                <input type="text" class="form-control number-format" id="sum" style="border-radius: 10px;" name="total_pengajuan" readonly placeholder="" value="{{rupiah($data->nominal_pengajuan)}}">
                </div>

              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                  <button   class="btn btn-warning" type="submit" name="save_draft">Draft</button>
                  <button   class="btn btn-primary" type="submit" name="save">Submit</button>
              </div>
          </div>
      </div>
  </div>
</form>
</div>


<!-- Modal Edit-->
<div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Insert Pertanggungjawaban</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div id="detailPertanggungjawaban"></div>
              
        </div>
    </div>
</div>
<!-- End Modal Edit-->

<!-- Modal Change-->
<div class="modal fade" id="formModalChange" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Pertanggungjawaban</h5>
                <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button> -->
            </div>
            <div id="changePertanggungjawaban"></div>
              
        </div>
    </div>
</div>
<!-- End Modal Change-->

<!-- Modal Change-->
<div class="modal fade" id="modalReject" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{url('/reimbursement/reject/'.$data->id)}}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Reject Reason</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="material-icons">close</i>
                    </button>
                </div>
                <div class="modal-body">
    
                    <div id="changePertanggungjawaban">
                        <div class="form-group">
                            <label for="">Reason</label>
                            <textarea name="reason" id="" cols="30" rows="10" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal" aria-label="Close">Cancel</button>
                    <button class="btn btn-primary" type="submit">Submit</button>
                </div>
                  
            </div>
        </form>
    </div>
</div>
<!-- End Modal Change-->

<!-- Modal -->
<div class="modal fade" id="modalPhoto"  data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Upload Gambar</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <i class="material-icons">close</i>
              </button>
          </div>
          <div class="modal-body">
            <video id="videoElement" autoplay style="width: 100%"></video>
            <!-- <canvas id="canvas"></canvas> -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button id="captureButton" class="btn btn-success">Capture Image</button>
            </div>
      </div>
  </div>
  </div>
</div>

<!-- End Modal -->

<!-- Modal Preview Image -->
<div class="modal fade" id="previewImageModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content" style="background: transparent; border: 0; box-shadow: none;">
          <div class="modal-header" style="border: 0;">
              <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1; font-size: 2rem;">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <div class="modal-body text-center pt-0">
              <img id="previewImageModalSrc" src="" alt="Preview" style="max-width: 100%; max-height: 80vh; border-radius: 8px;">
          </div>
      </div>
  </div>
</div>
<!-- End Modal Preview Image -->

<!-- Custom Lightbox -->
<div id="imgLightboxOverlay" class="img-lightbox-overlay">
    <div class="img-lightbox-content">
        <button type="button" class="img-lightbox-close" aria-label="Close">&times;</button>
        <img id="imgLightboxImage" src="" alt="Preview Besar">
    </div>
</div>
<!-- End Custom Lightbox -->
  
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.13.4/jquery.mask.min.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        window.openImageLightbox = function (src) {
            if (!src) return;
            $('#imgLightboxImage').attr('src', src);
            $('#imgLightboxOverlay').addClass('active');
        };

        $('[data-toggle="tooltip"]').tooltip();   
        
        $('.currency').mask("#.##0", {
          reverse: true
        }); 
      
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?<!\.\d*)(?=(\d{3})+(?!\d))/g, ".");
        }
        
        
       var maxGroup = 10;
       var count = "{{count($detail)}}";
       
       $(".addMore").click(function(){
            count++;
            $(".modal-body").animate(
              {
                scrollTop: $(".modal-body")[0].scrollHeight,
              },
              500
            );
            if($('body').find('.fieldGroup').length < maxGroup){

              var fieldHTML = '<tr class="fieldGroup"><td><input type="text" class="form-control" name="empty_zone[]" placeholder=""></td><td><input type="text" class="form-control" name="attendance[]" placeholder=""></td><td><input type="text" class="form-control" name="position[]" placeholder=""></td><td><input type="text" class="form-control" name="place[]" placeholder=""></td><td><input type="text" class="form-control" name="guest[]" placeholder=""></td><td><input type="text" class="form-control" name="guest_position[]" placeholder=""></td><td><input type="text" class="form-control" name="company[]" placeholder=""></td><td><input type="text" class="form-control" name="type[]" placeholder=""></td><td><select class="form-control" name="payment_type[]" style="width:100%"><option value="">Select...</option><option value="BDC">BDC</option><option value="Cash">Cash</option></select></td><td><input type="text" class="form-control amount-input currency amount'+count+' change-amount" name="amount[]"  placeholder=""></td><td class="file-proof"><button type="button" data-idx="'+count+'" class="btn btn-success btn-sm addFile"><i class="fa fa-upload"></i></button><button type="button" data-idx="'+count+'" class="btn btn-success btn-sm addCamera"><i class="fa fa-camera"></i></button><input type="file" accept="image/*" name="file[]"  style="display: none;" class="file-input file'+count+'"><input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;"></td><td><div id="preview_'+count+'"></div></td><td><input type="text" class="form-control" name="remark[]" placeholder="Remark"></td><td><button  type="button" name="add" id="add" class="btn btn-danger full-width remove-item">-</button></td></tr>';

              $('body').find('.fieldGroup:last').after(fieldHTML);
              
              $("body").on("click",".remove-item",function(){ 
                 $(this).parents(".fieldGroup").remove();
                 
                    if ($(".amount1").val()) {
                        var amount1 = $(".amount1").val().split(".").join("");
                    } else {
                        var amount1 = 0;
                    }
                    if ($(".amount2").val()) {
                        var amount2 = $(".amount2").val().split(".").join("");
                    } else {
                        var amount2 = 0;
                    }
                    if ($(".amount3").val()) {
                        var amount3 = $(".amount3").val().split(".").join("");
                    } else {
                        var amount3 = 0;
                    }
                    if ($(".amount4").val()) {
                        var amount4 = $(".amount4").val().split(".").join("");
                    } else {
                        var amount4 = 0;
                    }
                    if ($(".amount5").val()) {
                        var amount5 = $(".amount5").val().split(".").join("");
                    } else {
                        var amount5 = 0;
                    }
                    if ($(".amount6").val()) {
                        var amount6 = $(".amount6").val().split(".").join("");
                    } else {
                        var amount6 = 0;
                    }
                    if ($(".amount7").val()) {
                        var amount7 = $(".amount7").val().split(".").join("");
                    } else {
                        var amount7 = 0;
                    }
                    if ($(".amount8").val()) {
                        var amount8 = $(".amount8").val().split(".").join("");
                    } else {
                        var amount8 = 0;
                    }
                    if ($(".amount9").val()) {
                        var amount9 = $(".amount9").val().split(".").join("");
                    } else {
                        var amount9 = 0;
                    }
                    if ($(".amount10").val()) {
                        var amount10 = $(".amount10").val().split(".").join("");
                    } else {
                        var amount10 = 0;
                    }
                    
                    var total  = +amount1 + +amount2 + +amount3 + +amount4 + +amount5 + +amount6 + +amount7 + +amount8 + +amount9 + +amount10;
                    $("#sum").val(numberWithCommas(total));
              });
              
              $('.currency').mask("#.##0", {
                  reverse: true
              });
              
              $(".change-amount").change(function(){
                if ($(".amount1").val()) {
                    var amount1 = $(".amount1").val().split(".").join("");
                    console.log(amount1);
                } else {
                    var amount1 = 0;
                }
                if ($(".amount2").val()) {
                    var amount2 = $(".amount2").val().split(".").join("");
                } else {
                    var amount2 = 0;
                }
                if ($(".amount3").val()) {
                    var amount3 = $(".amount3").val().split(".").join("");
                } else {
                    var amount3 = 0;
                }
                if ($(".amount4").val()) {
                    var amount4 = $(".amount4").val().split(".").join("");
                } else {
                    var amount4 = 0;
                }
                if ($(".amount5").val()) {
                    var amount5 = $(".amount5").val().split(".").join("");
                } else {
                    var amount5 = 0;
                }
                if ($(".amount6").val()) {
                    var amount6 = $(".amount6").val().split(".").join("");
                } else {
                    var amount6 = 0;
                }
                if ($(".amount7").val()) {
                    var amount7 = $(".amount7").val().split(".").join("");
                } else {
                    var amount7 = 0;
                }
                if ($(".amount8").val()) {
                    var amount8 = $(".amount8").val().split(".").join("");
                } else {
                    var amount8 = 0;
                }
                if ($(".amount9").val()) {
                    var amount9 = $(".amount9").val().split(".").join("");
                } else {
                    var amount9 = 0;
                }
                if ($(".amount10").val()) {
                    var amount10 = $(".amount10").val().split(".").join("");
                } else {
                    var amount10 = 0;
                }
                
                var total  = +amount1 + +amount2 + +amount3 + +amount4 + +amount5 + +amount6 + +amount7 + +amount8 + +amount9 + +amount10;
                $("#sum").val(numberWithCommas(total));
                
             });
             
            } else{
              alert('Maximum '+maxGroup+' groups are allowed.');
            }
          });
      
          $(".change-amount").change(function(){
                if ($(".amount1").val()) {
                    var amount1 = $(".amount1").val().split(".").join("");
                    console.log(amount1);
                } else {
                    var amount1 = 0;
                }
                if ($(".amount2").val()) {
                    var amount2 = $(".amount2").val().split(".").join("");
                } else {
                    var amount2 = 0;
                }
                if ($(".amount3").val()) {
                    var amount3 = $(".amount3").val().split(".").join("");
                } else {
                    var amount3 = 0;
                }
                if ($(".amount4").val()) {
                    var amount4 = $(".amount4").val().split(".").join("");
                } else {
                    var amount4 = 0;
                }
                if ($(".amount5").val()) {
                    var amount5 = $(".amount5").val().split(".").join("");
                } else {
                    var amount5 = 0;
                }
                if ($(".amount6").val()) {
                    var amount6 = $(".amount6").val().split(".").join("");
                } else {
                    var amount6 = 0;
                }
                if ($(".amount7").val()) {
                    var amount7 = $(".amount7").val().split(".").join("");
                } else {
                    var amount7 = 0;
                }
                if ($(".amount8").val()) {
                    var amount8 = $(".amount8").val().split(".").join("");
                } else {
                    var amount8 = 0;
                }
                if ($(".amount9").val()) {
                    var amount9 = $(".amount9").val().split(".").join("");
                } else {
                    var amount9 = 0;
                }
                if ($(".amount10").val()) {
                    var amount10 = $(".amount10").val().split(".").join("");
                } else {
                    var amount10 = 0;
                }
                
                var total  = +amount1 + +amount2 + +amount3 + +amount4 + +amount5 + +amount6 + +amount7 + +amount8 + +amount9 + +amount10;
                $("#sum").val(numberWithCommas(total));
                
          });
          
          // Objek untuk menyimpan status upload di setiap row
          let uploadStatus = {};

                    function getPreviewDivFromRow(row) {
                        return row.find('[id^="preview_"]').first();
                    }

                    function createPreviewImage(src) {
                        return $('<a>')
                            .attr('href', src)
                            .attr('target', '_blank')
                            .attr('data-preview-src', src)
                            .addClass('preview-link')
                            .append(
                                $('<img>')
                                    .attr('src', src)
                                    .attr('data-preview-src', src)
                                    .addClass('preview-thumbnail')
                                    .css({
                                        maxWidth: '75px',
                                        maxHeight: '75px',
                                        border: '2px solid #28a745',
                                        borderRadius: '5px',
                                        marginTop: '5px',
                                        cursor: 'pointer'
                                    })
                            );
                    }

                    function bindExistingPreviewThumbnails() {
                        $('[id^="preview_"] img').each(function () {
                            $(this)
                                .addClass('preview-thumbnail')
                                .attr('data-preview-src', $(this).attr('src'))
                                .css('cursor', 'pointer');
                        });
                    }

                    bindExistingPreviewThumbnails();

                    $('body').on('click', '.preview-link, .preview-thumbnail, [id^="preview_"] img', function (e) {
                        var src = $(this).attr('data-preview-src') || $(this).find('img').attr('src') || $(this).attr('src');
                        if (!src) return;
                        e.preventDefault();
                        window.openImageLightbox(src);
                    });

                    $('body').on('click', '#imgLightboxOverlay, .img-lightbox-close', function (e) {
                        if ($(e.target).is('#imgLightboxOverlay') || $(e.target).is('.img-lightbox-close')) {
                            $('#imgLightboxOverlay').removeClass('active');
                            $('#imgLightboxImage').attr('src', '');
                        }
                    });

                    $(document).on('keydown', function (e) {
                        if (e.key === 'Escape') {
                            $('#imgLightboxOverlay').removeClass('active');
                            $('#imgLightboxImage').attr('src', '');
                        }
                    });

          // Fungsi untuk menangani upload file
          $("body").on("click", ".addFile", function () {
            let btn = $(this);
            let row = btn.closest("tr"); // Ambil baris terkait
            let idx = row.index(); // Dapatkan indeks baris
            let fileInput = row.find(".file-input"); // Ambil input file di baris ini

            fileInput.click();

            fileInput.off("change").on("change", function (event) {
              var file = event.target.files[0];

              if (file) {
                var reader = new FileReader();
                $("#action_button").prop("disabled", false);
                $(".warning-upload").hide();

                reader.onload = function (e) {
                                    let previewDiv = getPreviewDivFromRow(row);
                                    previewDiv.empty().append(createPreviewImage(e.target.result));

                  btn.find("i").removeClass("fa-upload").addClass("fa-check");
                };

                reader.readAsDataURL(file);
              }
            });
          });


          // Fungsi untuk menangani pengambilan gambar dari kamera
          $("body").on("click", ".addCamera", function () {
              let btn = $(this);
              let row = btn.closest("tr");
              let idx = row.index();
              let fileInput = row.find(".camera-input");

              if (navigator.mediaDevices.getUserMedia) {
                  navigator.mediaDevices
                      .getUserMedia({
                          video: {
                              facingMode: { ideal: "environment" }, // kamera belakang
                              width: { ideal: 1920 },  // minta resolusi Full HD
                              height: { ideal: 1080 },
                              focusMode: "continuous", // auto focus (didukung beberapa device)
                              exposureMode: "continuous"
                          }
                      })
                      .then(function (stream) {
                          $("#modalPhoto").modal("show");
                          let videoElement = $("#videoElement")[0];
                          videoElement.srcObject = stream;

                          $("#captureButton").off("click").on("click", function () {
                              const canvas = document.createElement("canvas");
                              const context = canvas.getContext("2d");

                              // Gunakan resolusi asli kamera biar proporsional
                              const videoWidth = videoElement.videoWidth;
                              const videoHeight = videoElement.videoHeight;
                              canvas.width = videoWidth;
                              canvas.height = videoHeight;

                              // Render dengan kualitas tinggi
                              context.imageSmoothingEnabled = true;
                              context.imageSmoothingQuality = "high";
                              context.drawImage(videoElement, 0, 0, videoWidth, videoHeight);

                              // Simpan sebagai JPEG dengan kualitas tinggi (0.92 - 0.95)
                              canvas.toBlob(function (blob) {
                                  const file = new File([blob], "capture.jpg", { type: "image/jpeg" });

                                  const dataTransfer = new DataTransfer();
                                  dataTransfer.items.add(file);
                                  fileInput[0].files = dataTransfer.files;

                                  const imageURL = URL.createObjectURL(file);
                                  let previewDiv = getPreviewDivFromRow(row);
                                  previewDiv.empty().append(createPreviewImage(imageURL));
                                  btn.find("i").removeClass("fa-camera").addClass("fa-check");

                                  // stop kamera
                                  stream.getTracks().forEach(track => track.stop());
                                  $("#modalPhoto").modal("hide");
                                  $("#action_button").prop("disabled", false);
                                  $("#action_button_draft").prop("disabled", false);
                                  $(".warning-upload").hide();
                              }, "image/jpeg", 0.92); // lebih jernih
                          });
                      })
                      .catch(function (err) {
                          console.error("Error accessing webcam: " + err);
                      });
              }
          });

        
    });
  
</script>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  new Vue({
      el: '#app',
      data: {
        start: null,
        end: null,
        employees: [],
        status: null,
        deletedId: [],
        user_id: null,
        reimburses: @json($data->entertaiments),
        grandtotal: {{$data->nominal_pengajuan}}
      },
      mounted() {
        
        // $(".number-format").maskMoney({ thousands:'.', decimal:',', precision:0});
        // $('.number-format').each('input', () => {
        //     // Update Vue data when input changes
        //     this.amount = $(this).val();
        //   });
        // $(".select2").select2()
        var start = moment().startOf('month');
        var end = moment().endOf('month');
        this.start = start.format('YYYY-MM-DD');
        this.end = end.format('YYYY-MM-DD');
        $(function() {
            $('input.daterange').daterangepicker({
                startDate: start,
                endDate: end,
                opens: 'left'
            }, function(start, end, label) {
                console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
            });
        });
        self = this
        self.loadData(self.start,self.end,self.status, self.user_id);
        $("input.daterange").on('apply.daterangepicker', function(ev, picker) {
          var startDate = picker.startDate.format('YYYY-MM-DD');
          var endDate = picker.endDate.format('YYYY-MM-DD');
          self.start = startDate
          self.end = endDate
          console.log("Selected date range: " + startDate + ' to ' + endDate);
          // self.loadData(startDate,endDate,self.status, self.user_id);
      });
      },
      methods: {
        // searchStatus(){
        //   self = this
        //   // this.loadData(this.start,this.end,this.status, this.user_id);
        //   $.ajax({
        //     url: `{{url("/")}}/reimbursement-user?status=${self.status}&reimbursement_type=3`,
        //     methods: 'GET',
        //     success: function(e) {
        //       console.log(e)
              
        //       self.employees = e.data
        //     }
        //   })

        // },
        searchDriver(){

        },
        reset(){
          this.status = null
          this.user_id = null
          var start = moment().startOf('month');
          var end = moment().endOf('month');
          this.start = start.format('YYYY-MM-DD');
          this.end = end.format('YYYY-MM-DD');
          this.loadData(this.start,this.end,this.status, this.user_id);

        },
        search(){
          this.loadData(this.start,this.end,this.status, this.user_id);
        },
        print(){
          window.open("{{url('/')}}/reimbursement-entertaiment-print?start="+this.start+"&end="+this.end+"&driver="+this.user_id+"&status="+this.status, "_blank")
        },
        
        changeAmount(i){
          subtotal = 0;
          this.reimburses.forEach(element => {
              subtotal += parseInt(element.amount.replaceAll(".",""))
          });
          this.grandtotal = subtotal.toLocaleString('de-DE')
          // $(".number-format").trigger('blur')

        },
        initSelectForm() {
          console.log("hehe")
          $(".addFile").on('click',function(){
      $(this).parent().find(".file-input").click();
      $(this).parent().find(".file-input").change(function(event) {
        var file = event.target.files[0];
        
        if (file) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                $('#preview_'+$(this).parent().find(".addFile").data('idx')).empty(); // Clear previous preview
                
                var img = $('<img>');
                img.attr('src', e.target.result);
                img.css({ maxWidth: '100%', maxHeight: '200px' }); // Adjust height as needed
                $('#preview_'+$(this).parent().find(".addFile").data('idx')).append(img);
            };
            
            reader.readAsDataURL(file);
        }
    })
    }) 
    $(".addCamera").on('click',function(){
      idx = $(this).data('idx')
      fileInput = $(this).parent().find(".file-input")[0]; 
      $("#modalPhoto").modal('show')
      const videoElement = $('#videoElement')[0];
      const canvas = $('#canvas')[0];
      const context = canvas.getContext('2d');

      // Access the webcam
      if (navigator.mediaDevices.getUserMedia) {
          navigator.mediaDevices.getUserMedia({ video: {
            facingMode: { ideal: "environment" }
          } })
              .then(function(stream) {
                  videoElement.srcObject = stream;
                  $('#captureButton').on('click', function() {
                      canvas.width = videoElement.videoWidth * 0.3;
                      canvas.height = videoElement.videoHeight * 0.3;
                      context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                      canvas.toBlob(function(blob) {
                          const file = new File([blob], "capture.png", { type: "image/png" });

                          // Display the captured image in the preview div
                          const dataURL = URL.createObjectURL(file);
                     
                          // Create a DataTransfer to add the file to the input element
                          const dataTransfer = new DataTransfer();
                          dataTransfer.items.add(file);
                          fileInput.files = dataTransfer.files;
                          console.log(fileInput)
                      }, 'image/png'); 
                      // // Convert the canvas image to a data URL and display it
                      // const dataURL = canvas.toDataURL('image/png');
                      // // $('#preview_'+idx).html('<img src="' + dataURL + '" alt="Captured Image" width="320">');
                      // fileInput = $(this).parent().find(".file-input")
                      // console.log(fileInput)
                      // fileInput.value = dataURL;
                      // console.log(dataURL)
                      // // Cleanup
                      stream.getTracks().forEach(function(track) {
                          return track.stop();
                      });
                      $("#modalPhoto").modal('hide')

                  });
              })
              .catch(function(err) {
                  console.error("Error accessing webcam: " + err);
              });
      }

      // Capture the image when the button is clicked
      
    })
        },
        loadData(start = null,end = null, status= null, driver= null) {
          try {
            $('#myTable').dataTable().fnDestroy();
            
          } catch (error) {
            
          }
            
            $('#myTable').dataTable({
            processing: false,
            serverSide: false,
            bPaginate: true,
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            bAutoWidth: false,
            pageLength: 5,
            order: [],
            ajax: {
              url:'{{ url("reimbursement-entertaiment") }}',
              data:{
                first:start,
                last:end,
                status:status,
                driver:driver,
              }
            },
            columns: [

                      {
                        data: 'no_reimbursement',
                        name: 'no_reimbursement'
                      },
                      {
                        data: 'created_at',
                        name: 'created_at'
                      },
                      {
                        data: 'no_project',
                        name: 'no_project'
                      },
                      {
                        data: 'nominal_pengajuan',
                        name: 'nominal_pengajuan'
                      },
                      {
                        data: 'action',
                        name: 'action'
                      },

                ],
            });
        },
        calculate(el,item) {
        //   item.total = ((item.toll) ? parseInt(item.toll) : 0) + ((item.parking) ? parseInt(item.parking) : 0) + ((item.gasoline) ? parseInt(item.gasoline) : 0) + ((item.other) ? parseInt(item.other) : 0) 
        //   this.grandtotal = 0
        //   self = this
        //   this.reimburses.forEach(element => {
        //     self.grandtotal += parseInt(element.total)            
        //   });
        },
        addReimbursement() {
          this.reimburses.push({
            id: null,
              empty_zone: null,
              attendance: null,
              position: null,
              place: null,
              guest: null,
              guest_position: null,
              company: null,
              type: null,
              amount: null,
              total: 0,
              remark: null,
              evidence: null
            })
            this.initSelectForm()
            
            $(".number-format").maskMoney({ thousands:'.', decimal:',', precision:0});
            
            $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0});
            $('.amount-input').on('change', (event) => {
                const index = $(event.target).closest('tr').index();
                this.reimburses[index].toll = ($(event.target).val());
                self.changeAmount(0);
            });
            
            // $('input.form-control').focus(function() {
            //     // Select all text inside the input field
            //     $(this).select();
            // });

            self = this

            this.$nextTick(() => {
              self.initSelectForm();

              $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-input').on('change', (event) => {
                const index = $(event.target).closest('tr').index();
                this.reimburses[index].amount = ($(event.target).val());
                self.changeAmount(0);

              });
            })
        },
        removeReimbursement(i) {
          this.reimburses.splice(i,1)
          self = this
          this.reimburses.forEach(element => {
            self.grandtotal += parseInt(element.total)            
          });
        }
      },
      watch: {
        reimburses(newValue, oldValue) {
          console.log(`Count changed from ${oldValue} to ${newValue}`);
          for (let i = 0; i < newValue.length; i++) {
            const element = newValue[i];
          }
          // Additional logic based on count change
        }
      },
  });
  

  // function this.initSelectForm() {
     
  // }

</script>
@endpush
@endsection
