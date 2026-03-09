@extends('template.app')
@section('content')

<style>
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
  
  .upload-success {
    border: 2px solid #28a745 !important; /* Warna hijau terang */
    box-shadow: 0 0 5px #28a745; /* Efek glow hijau */
  }
  
  #preview_1 {
    maxWidth: '75px';
    maxHeight: '75px';
    border: '2px solid #28a745';
    borderRadius: '5px';
    marginTop: '5px';
  }
</style>

<div class="page-content" id="app">

@if(auth()->user()->jabatan=='Owner' || auth()->user()->jabatan=='Finance')
<div class="clearfix">
     <a href="{!!url('reimbursement-driver')!!}" class="btn btn-success float-left" style="width: 48%;">My Inquiry</a>
     <a href="{!!url('reimbursement-driver-approval')!!}" class="btn btn-info float-right" style="width: 48%;">Approval</a>
</div>
@else

  @if($check_approval > 0)
  <div class="clearfix">
       <a href="{!!url('reimbursement-driver')!!}" class="btn btn-success float-left" style="width: 48%;">My Inquiry</a>
       <a href="{!!url('reimbursement-driver-approval')!!}" class="btn btn-info float-right" style="width: 48%;">Approval</a>
  </div>
  @endif

@endif

<br>

<div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  
                  <div class="col-md-12">
                    <div class="row"> 
                        <div class="col-sm-6">
                            <h2 class="card-title clr-green">Reimbursement Driver @if($check_approval > 0) (My Inquiry) @endif</h2>
                        </div>
                    </div>
                  </div>
                  
                  <div class="col-md-12">
                    <div class="row">
                      
                      	<div class="col-md-1 mb-3">
                            <label for="status">Show</label>
                            <select id="show-data" class="form-control select2">
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label for="status">Status</label>
                            <select name="status" class="form-control select2 status" @change="searchStatus" v-model="status">
                                <option value="1">APPROVED HEAD DEPT</option>
                                <option value="2">APPROVED HR GA</option>
                                <option value="3">APPROVED FINANCE</option>
                                <option value="5">SETTLED</option>
                                <option value="9">REJECT</option>
                                <option value="0">PENDING</option>
                                <option value="10">DRAFT</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="payment_type">Payment Type</label>
                            <select name="payment_type" class="form-control select2 payment-type" v-model="payment_type">
                                <option value="ALL">ALL</option>
                                <option value="Cash">Cash</option>
                                <option value="Fleet">Fleet</option>
                            </select>
                        </div>
                        <!-- @if (auth()->user()->jabatan != "karyawan")
                            <div class="col-md-3 mb-3">
                                <label for="user_id">Employee</label>
                                <select name="user_id" @change="searchDriver" class="form-control select2" v-model="user_id">
                                    <option v-for="item in employees" :value="item.id">@{{item.name}}</option>
                                </select>
                            </div>
                        @endif -->
                        <div class="col-md-3 mb-3">
                            <label for="daterange">Period</label>
                            <input type="text" name="daterange" class="form-control daterange"/>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <button class="btn btn-primary d-block" @click="search()" style="margin-top:32px"><i class="fa fa-search" title="Search"></i></button>
                                <button class="btn btn-primary d-block" @click="reset()" style="margin-top:32px"><i class="fas fa-sync-alt fa-fw" title="Reset"></i></button>
                                <button class="btn btn-primary d-block" @click="print()" style="margin-top:32px"><i class="fa fa-print" title="Print"></i></button>
                                <button type="button" class="btn btn-primary btn-sm w-100" data-toggle="modal" data-target=".bd-example-modal-lg" style="margin-top:32px">
                                <i class="fa fa-plus-circle" aria-hidden="true"></i> Create Inquiry
                            </button>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
                  
                  
                  
                </div>
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
                

                  <table id="myTable" class="display" style="width:1080px"  >
                      <thead>
                          <tr>
                              <th><div class="form-check"><input class="form-check-input" type="checkbox" value="" id="checkAll"></div></th>
                              <th>Inquiry No</th>
                              <th>Apply Date</th>
                              <th>Transaction Date</th>
                              <th>Inquiry By</th>
                              <th>Total Inquiry</th>
                              <th>Status Inquiry</th>
                          </tr>
                      </thead>
                      <tbody>


                      </tbody>

                  </table>
              </div>
          </div>
      </div>
  </div>
  <div class="modal fade bd-example-modal-lg" id="formModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="overflow-y: auto">
    <form method="post" id="sample_form" action="{{url('/')."/reimbursement-driver"}}" enctype="multipart/form-data">
    @csrf
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header border-bottom"  >
              <div class="d-flex justify-content-between w-100">
                    <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle">Buat Reimbursement Driver</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
                </div>
              </div>

              <div class="modal-body py-3">
              <div class="row my-3"> 
                
                  <div class="col-md-3">
                    <div class="form-group">
                     <label for="exampleFormControlInput1">Employee </label>
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
                     <input type="text" class="form-control date-picker" style="border-radius: 10px;" value="{{date('d F Y')}}" readonly>
                   </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                     <label for="exampleFormControlInput1">Transaction Date</label>
                     <input type="date" class="form-control date-picker" name="date" id="exampleFormControlInput1" style="border-radius: 10px;" required>
                   </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Department</label>
                      <select name="reimbursement_department_id" id="" class="form-control">
                        @foreach (\App\Departemen::get() as $item)
                            <option value="{{$item->id}}" @if(auth()->user()->departmentId == $item->id) selected @endif>{{$item->nama_departemen}}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Remark</label>
                      <input type="text" class="form-control date-picker" name="remark_parent" id="exampleFormControlInput1" style="border-radius: 10px;" value="" required>
                    </div>
                  </div>

                  <hr>
               
                </div>
                <label class="modal-title clr-green" id="exampleModalCenterTitle">Detail Reimbursement</label>
<div class="respon respon-big table-responsive">
                <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3
            align=center width="1400">
                  <thead>
                      <tr>
                          <th align="center" width="50">No.</th>
                          <th align="center" >Toll</th>
                          <th align="center" >Parking</th>
                          <th align="center" width="15%">Gasoline</th>
                          <th align="center" >Other</th>
                          <th align="center" >Total</th>
                          <th align="center" >Payment Type</th>
                          <th align="center" width="100px">Evidence</th>
                          <th align="center" >Preview</th>
                          <th align="center" >Remark</th>
                          <th align="center" >Action</th>

                      </tr>
                  </thead>
                  <tbody>
                    <tr class="fieldGroup">
                        <td>1</td>
                        <td>
                            <input type="hidden" name="id_detail[]">
                            <input type="text" class="form-control amount-toll currency toll1 change-price" name="toll[]" placeholder="Toll" value="0" required>
                        </td>
                        <td>
                            <input type="text" class="form-control amount-parking currency parking1 change-price" name="parking[]" placeholder="Parking" value="0" required>
                        </td>
                        <td>
                            <input type="text" class="form-control amount-gasoline currency gasoline1 change-price" name="gasoline[]" value="0" placeholder="Gasoline" required>
                        </td>
                        <td>
                            <input type="text" class="form-control amount-other currency others1 change-price" name="others[]" placeholder="Other" value="0" required>
                        </td>
                        <td>
                            <input type="text" class="form-control amount-total currency subtotal1 change-price" name="total[]" readonly placeholder="Total">
                        </td>
                        <td>
                            <select name="payment_type[]" class="form-control" required>
                                <option value="" selected disabled>Select...</option>
                                <option value="Cash">Cash</option>
                                <option value="Fleet">Fleet</option>
                            </select>
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
                            <div id="preview_1"></div>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="remark[]" placeholder="Remark">
                        </td>
                        <td>
                            <button type="button" name="add" id="add" class="btn btn-success addMore">+</button>
                        </td>
                    </tr>
                </tbody>
                </table>

                </div>
                <br>
                <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Nominal</label>
                <div class="form-group">
                <label for="exampleFormControlInput1">Total Inquiry</label>
                <input type="text" v-model="grandtotal" class="form-control number-format" id="sum" style="border-radius: 10px;" name="total_pengajuan" readonly placeholder="">
                </div>
              </div>

              <span style="color:#62d49e;text-align:right;" class="warning-upload">The button is disabled until a file is uploaded.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
              <div class="modal-footer">
                  <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                  <button class="btn btn-primary" type="submit" id="action_button" name="save">Submit</button>
                  <button class="btn btn-warning" type="submit" id="action_button_draft" name="save_draft">Draft</button>
              </div>
          </div>
      </div>
  </div>
</form>
</div>

<!-- Modal -->
<div class="modal fade" id="modalPassword"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">AMANKAN PASSWORD</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div class="modal-body">
              <span id="form_result_add"></span>
              <p>Password Anda masih menggunakan password default dari sistem. <br>
              Demi keamanan akun, kami menyarankan Anda melakukan perubahan password terlebih dahulu!</p>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Nanti dulu</button>
                  <a href="{!! url('profile') !!}" class="btn btn-primary">Ubah Password</a>
              </div>
        </div>
    </div>
</div>



</div>
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

<!-- End Modal -->

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.13.4/jquery.mask.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    @if(Auth::user()->status_password != 1)
        $('#modalPassword').modal('show');
    @endif

    function numberWithCommas(x) {
        return x.toString().replace(/\B(?<!\.\d*)(?=(\d{3})+(?!\d))/g, ".");
    }

    $("#action_button").prop("disabled", true);
    $("#action_button_draft").prop("disabled", true);
    $(".warning-upload").show();


    $('.currency').mask("#.##0", {
      reverse: true
    }); 

    $("#checkAll").click(function(){
        $('input:checkbox').not(this).prop('checked', this.checked);
    });

    function change_price() {
        var toll1 = $(".toll1").val().split(".").join("");
        var parking1 = $(".parking1").val().split(".").join("");
        var gasoline1 = $(".gasoline1").val().split(".").join("");
        var others1 = $(".others1").val().split(".").join("");
        var subtotal1 = +toll1 + +parking1 + +gasoline1 + +others1;
        $(".subtotal1").val(numberWithCommas(subtotal1));
    
        if ($(".toll2").val()) {
            var toll2 = $(".toll2").val().split(".").join("");
        } else {
            var toll2 = 0;
        }
        if ($(".parking2").val()) {
            var parking2 = $(".parking2").val().split(".").join("");
        } else {
            var parking2 = 0;
        }
        if ($(".gasoline2").val()) {
            var gasoline2 = $(".gasoline2").val().split(".").join("");
        } else {
            var gasoline2 = 0;
        }
        if ($(".others2").val()) {
            var others2 = $(".others2").val().split(".").join("");
        } else {
            var others2 = 0;
        }
        var subtotal2 = +toll2 + +parking2 + +gasoline2 + +others2;
        $(".subtotal2").val(numberWithCommas(subtotal2));

        if ($(".toll3").val()) {
            var toll3 = $(".toll3").val().split(".").join("");
        } else {
            var toll3 = 0;
        }
        if ($(".parking3").val()) {
            var parking3 = $(".parking3").val().split(".").join("");
        } else {
            var parking3 = 0;
        }
        if ($(".gasoline3").val()) {
            var gasoline3 = $(".gasoline3").val().split(".").join("");
        } else {
            var gasoline3 = 0;
        }
        if ($(".others3").val()) {
            var others3 = $(".others3").val().split(".").join("");
        } else {
            var others3 = 0;
        }
        var subtotal3 = +toll3 + +parking3 + +gasoline3 + +others3;
        $(".subtotal3").val(numberWithCommas(subtotal3));

        if ($(".toll4").val()) {
            var toll4 = $(".toll4").val().split(".").join("");
        } else {
            var toll4 = 0;
        }
        if ($(".parking4").val()) {
            var parking4 = $(".parking4").val().split(".").join("");
        } else {
            var parking4 = 0;
        }
        if ($(".gasoline4").val()) {
            var gasoline4 = $(".gasoline4").val().split(".").join("");
        } else {
            var gasoline4 = 0;
        }
        if ($(".others4").val()) {
            var others4 = $(".others4").val().split(".").join("");
        } else {
            var others4 = 0;
        }
        var subtotal4 = +toll4 + +parking4 + +gasoline4 + +others4;
        $(".subtotal4").val(numberWithCommas(subtotal4));

        if ($(".toll5").val()) {
            var toll5 = $(".toll5").val().split(".").join("");
        } else {
            var toll5 = 0;
        }
        if ($(".parking5").val()) {
            var parking5 = $(".parking5").val().split(".").join("");
        } else {
            var parking5 = 0;
        }
        if ($(".gasoline5").val()) {
            var gasoline5 = $(".gasoline5").val().split(".").join("");
        } else {
            var gasoline5 = 0;
        }
        if ($(".others5").val()) {
            var others5 = $(".others5").val().split(".").join("");
        } else {
            var others5 = 0;
        }
        var subtotal5 = +toll5 + +parking5 + +gasoline5 + +others5;
        $(".subtotal5").val(numberWithCommas(subtotal5));

        if ($(".toll6").val()) {
            var toll6 = $(".toll6").val().split(".").join("");
        } else {
            var toll6 = 0;
        }
        if ($(".parking6").val()) {
            var parking6 = $(".parking6").val().split(".").join("");
        } else {
            var parking6 = 0;
        }
        if ($(".gasoline6").val()) {
            var gasoline6 = $(".gasoline6").val().split(".").join("");
        } else {
            var gasoline6 = 0;
        }
        if ($(".others6").val()) {
            var others6 = $(".others6").val().split(".").join("");
        } else {
            var others6 = 0;
        }
        var subtotal6 = +toll6 + +parking6 + +gasoline6 + +others6;
        $(".subtotal6").val(numberWithCommas(subtotal6));

        if ($(".toll7").val()) {
            var toll7 = $(".toll7").val().split(".").join("");
        } else {
            var toll7 = 0;
        }
        if ($(".parking7").val()) {
            var parking7 = $(".parking7").val().split(".").join("");
        } else {
            var parking7 = 0;
        }
        if ($(".gasoline7").val()) {
            var gasoline7 = $(".gasoline7").val().split(".").join("");
        } else {
            var gasoline7 = 0;
        }
        if ($(".others7").val()) {
            var others7 = $(".others7").val().split(".").join("");
        } else {
            var others7 = 0;
        }
        var subtotal7 = +toll7 + +parking7 + +gasoline7 + +others7;
        $(".subtotal7").val(numberWithCommas(subtotal7));

        if ($(".toll8").val()) {
            var toll8 = $(".toll8").val().split(".").join("");
        } else {
            var toll8 = 0;
        }
        if ($(".parking8").val()) {
            var parking8 = $(".parking8").val().split(".").join("");
        } else {
            var parking8 = 0;
        }
        if ($(".gasoline8").val()) {
            var gasoline8 = $(".gasoline8").val().split(".").join("");
        } else {
            var gasoline8 = 0;
        }
        if ($(".others8").val()) {
            var others8 = $(".others8").val().split(".").join("");
        } else {
            var others8 = 0;
        }
        var subtotal8 = +toll8 + +parking8 + +gasoline8 + +others8;
        $(".subtotal8").val(numberWithCommas(subtotal8));

        if ($(".toll9").val()) {
            var toll9 = $(".toll9").val().split(".").join("");
        } else {
            var toll9 = 0;
        }
        if ($(".parking9").val()) {
            var parking9 = $(".parking9").val().split(".").join("");
        } else {
            var parking9 = 0;
        }
        if ($(".gasoline9").val()) {
            var gasoline9 = $(".gasoline9").val().split(".").join("");
        } else {
            var gasoline9 = 0;
        }
        if ($(".others9").val()) {
            var others9 = $(".others9").val().split(".").join("");
        } else {
            var others9 = 0;
        }
        var subtotal9 = +toll9 + +parking9 + +gasoline9 + +others9;
        $(".subtotal9").val(numberWithCommas(subtotal9));

        if ($(".toll10").val()) {
            var toll10 = $(".toll10").val().split(".").join("");
        } else {
            var toll10 = 0;
        }
        if ($(".parking10").val()) {
            var parking10 = $(".parking10").val().split(".").join("");
        } else {
            var parking10 = 0;
        }
        if ($(".gasoline10").val()) {
            var gasoline10 = $(".gasoline10").val().split(".").join("");
        } else {
            var gasoline10 = 0;
        }
        if ($(".others10").val()) {
            var others10 = $(".others10").val().split(".").join("");
        } else {
            var others10 = 0;
        }
        var subtotal10 = +toll10 + +parking10 + +gasoline10 + +others10;
        $(".subtotal10").val(numberWithCommas(subtotal10));

        if ($(".toll11").val()) {
            var toll11 = $(".toll11").val().split(".").join("");
        } else {
            var toll11 = 0;
        }
        if ($(".parking11").val()) {
            var parking11 = $(".parking11").val().split(".").join("");
        } else {
            var parking11 = 0;
        }
        if ($(".gasoline11").val()) {
            var gasoline11 = $(".gasoline11").val().split(".").join("");
        } else {
            var gasoline11 = 0;
        }
        if ($(".others11").val()) {
            var others11 = $(".others11").val().split(".").join("");
        } else {
            var others11 = 0;
        }
        var subtotal11 = +toll11 + +parking11 + +gasoline11 + +others11;
        $(".subtotal11").val(numberWithCommas(subtotal11));

        if ($(".toll12").val()) {
            var toll12 = $(".toll12").val().split(".").join("");
        } else {
            var toll12 = 0;
        }
        if ($(".parking12").val()) {
            var parking12 = $(".parking12").val().split(".").join("");
        } else {
            var parking12 = 0;
        }
        if ($(".gasoline12").val()) {
            var gasoline12 = $(".gasoline12").val().split(".").join("");
        } else {
            var gasoline12 = 0;
        }
        if ($(".others12").val()) {
            var others12 = $(".others12").val().split(".").join("");
        } else {
            var others12 = 0;
        }
        var subtotal12 = +toll12 + +parking12 + +gasoline12 + +others12;
        $(".subtotal12").val(numberWithCommas(subtotal12));

        if ($(".toll13").val()) {
            var toll13 = $(".toll13").val().split(".").join("");
        } else {
            var toll13 = 0;
        }
        if ($(".parking13").val()) {
            var parking13 = $(".parking13").val().split(".").join("");
        } else {
            var parking13 = 0;
        }
        if ($(".gasoline13").val()) {
            var gasoline13 = $(".gasoline13").val().split(".").join("");
        } else {
            var gasoline13 = 0;
        }
        if ($(".others13").val()) {
            var others13 = $(".others13").val().split(".").join("");
        } else {
            var others13 = 0;
        }
        var subtotal13 = +toll13 + +parking13 + +gasoline13 + +others13;
        $(".subtotal13").val(numberWithCommas(subtotal13));

        if ($(".toll14").val()) {
            var toll14 = $(".toll14").val().split(".").join("");
        } else {
            var toll14 = 0;
        }
        if ($(".parking14").val()) {
            var parking14 = $(".parking14").val().split(".").join("");
        } else {
            var parking14 = 0;
        }
        if ($(".gasoline14").val()) {
            var gasoline14 = $(".gasoline14").val().split(".").join("");
        } else {
            var gasoline14 = 0;
        }
        if ($(".others14").val()) {
            var others14 = $(".others14").val().split(".").join("");
        } else {
            var others14 = 0;
        }
        var subtotal14 = +toll14 + +parking14 + +gasoline14 + +others14;
        $(".subtotal14").val(numberWithCommas(subtotal14));

        if ($(".toll15").val()) {
            var toll15 = $(".toll15").val().split(".").join("");
        } else {
            var toll15 = 0;
        }
        if ($(".parking15").val()) {
            var parking15 = $(".parking15").val().split(".").join("");
        } else {
            var parking15 = 0;
        }
        if ($(".gasoline15").val()) {
            var gasoline15 = $(".gasoline15").val().split(".").join("");
        } else {
            var gasoline15 = 0;
        }
        if ($(".others15").val()) {
            var others15 = $(".others15").val().split(".").join("");
        } else {
            var others15 = 0;
        }
        var subtotal15 = +toll15 + +parking15 + +gasoline15 + +others15;
        $(".subtotal15").val(numberWithCommas(subtotal15));

        if ($(".toll16").val()) {
            var toll16 = $(".toll16").val().split(".").join("");
        } else {
            var toll16 = 0;
        }
        if ($(".parking16").val()) {
            var parking16 = $(".parking16").val().split(".").join("");
        } else {
            var parking16 = 0;
        }
        if ($(".gasoline16").val()) {
            var gasoline16 = $(".gasoline16").val().split(".").join("");
        } else {
            var gasoline16 = 0;
        }
        if ($(".others16").val()) {
            var others16 = $(".others16").val().split(".").join("");
        } else {
            var others16 = 0;
        }
        var subtotal16 = +toll16 + +parking16 + +gasoline16 + +others16;
        $(".subtotal16").val(numberWithCommas(subtotal16));

        if ($(".toll17").val()) {
            var toll17 = $(".toll17").val().split(".").join("");
        } else {
            var toll17 = 0;
        }
        if ($(".parking17").val()) {
            var parking17 = $(".parking17").val().split(".").join("");
        } else {
            var parking17 = 0;
        }
        if ($(".gasoline17").val()) {
            var gasoline17 = $(".gasoline17").val().split(".").join("");
        } else {
            var gasoline17 = 0;
        }
        if ($(".others17").val()) {
            var others17 = $(".others17").val().split(".").join("");
        } else {
            var others17 = 0;
        }
        var subtotal17 = +toll17 + +parking17 + +gasoline17 + +others17;
        $(".subtotal17").val(numberWithCommas(subtotal17));

        if ($(".toll18").val()) {
            var toll18 = $(".toll18").val().split(".").join("");
        } else {
            var toll18 = 0;
        }
        if ($(".parking18").val()) {
            var parking18 = $(".parking18").val().split(".").join("");
        } else {
            var parking18 = 0;
        }
        if ($(".gasoline18").val()) {
            var gasoline18 = $(".gasoline18").val().split(".").join("");
        } else {
            var gasoline18 = 0;
        }
        if ($(".others18").val()) {
            var others18 = $(".others18").val().split(".").join("");
        } else {
            var others18 = 0;
        }
        var subtotal18 = +toll18 + +parking18 + +gasoline18 + +others18;
        $(".subtotal18").val(numberWithCommas(subtotal18));

        if ($(".toll19").val()) {
            var toll19 = $(".toll19").val().split(".").join("");
        } else {
            var toll19 = 0;
        }
        if ($(".parking19").val()) {
            var parking19 = $(".parking19").val().split(".").join("");
        } else {
            var parking19 = 0;
        }
        if ($(".gasoline19").val()) {
            var gasoline19 = $(".gasoline19").val().split(".").join("");
        } else {
            var gasoline19 = 0;
        }
        if ($(".others19").val()) {
            var others19 = $(".others19").val().split(".").join("");
        } else {
            var others19 = 0;
        }
        var subtotal19 = +toll19 + +parking19 + +gasoline19 + +others19;
        $(".subtotal19").val(numberWithCommas(subtotal19));

        if ($(".toll20").val()) {
            var toll20 = $(".toll20").val().split(".").join("");
        } else {
            var toll20 = 0;
        }
        if ($(".parking20").val()) {
            var parking20 = $(".parking20").val().split(".").join("");
        } else {
            var parking20 = 0;
        }
        if ($(".gasoline20").val()) {
            var gasoline20 = $(".gasoline20").val().split(".").join("");
        } else {
            var gasoline20 = 0;
        }
        if ($(".others20").val()) {
            var others20 = $(".others20").val().split(".").join("");
        } else {
            var others20 = 0;
        }
        var subtotal20 = +toll20 + +parking20 + +gasoline20 + +others20;
        $(".subtotal20").val(numberWithCommas(subtotal20));

        if ($(".toll21").val()) {
            var toll21 = $(".toll21").val().split(".").join("");
        } else {
            var toll21 = 0;
        }
        if ($(".parking21").val()) {
            var parking21 = $(".parking21").val().split(".").join("");
        } else {
            var parking21 = 0;
        }
        if ($(".gasoline21").val()) {
            var gasoline21 = $(".gasoline21").val().split(".").join("");
        } else {
            var gasoline21 = 0;
        }
        if ($(".others21").val()) {
            var others21 = $(".others21").val().split(".").join("");
        } else {
            var others21 = 0;
        }
        var subtotal21 = +toll21 + +parking21 + +gasoline21 + +others21;
        $(".subtotal21").val(numberWithCommas(subtotal21));

        if ($(".toll22").val()) {
            var toll22 = $(".toll22").val().split(".").join("");
        } else {
            var toll22 = 0;
        }
        if ($(".parking22").val()) {
            var parking22 = $(".parking22").val().split(".").join("");
        } else {
            var parking22 = 0;
        }
        if ($(".gasoline22").val()) {
            var gasoline22 = $(".gasoline22").val().split(".").join("");
        } else {
            var gasoline22 = 0;
        }
        if ($(".others22").val()) {
            var others22 = $(".others22").val().split(".").join("");
        } else {
            var others22 = 0;
        }
        var subtotal22 = +toll22 + +parking22 + +gasoline22 + +others22;
        $(".subtotal22").val(numberWithCommas(subtotal22));

        if ($(".toll23").val()) {
            var toll23 = $(".toll23").val().split(".").join("");
        } else {
            var toll23 = 0;
        }
        if ($(".parking23").val()) {
            var parking23 = $(".parking23").val().split(".").join("");
        } else {
            var parking23 = 0;
        }
        if ($(".gasoline23").val()) {
            var gasoline23 = $(".gasoline23").val().split(".").join("");
        } else {
            var gasoline23 = 0;
        }
        if ($(".others23").val()) {
            var others23 = $(".others23").val().split(".").join("");
        } else {
            var others23 = 0;
        }
        var subtotal23 = +toll23 + +parking23 + +gasoline23 + +others23;
        $(".subtotal23").val(numberWithCommas(subtotal23));

        if ($(".toll24").val()) {
            var toll24 = $(".toll24").val().split(".").join("");
        } else {
            var toll24 = 0;
        }
        if ($(".parking24").val()) {
            var parking24 = $(".parking24").val().split(".").join("");
        } else {
            var parking24 = 0;
        }
        if ($(".gasoline24").val()) {
            var gasoline24 = $(".gasoline24").val().split(".").join("");
        } else {
            var gasoline24 = 0;
        }
        if ($(".others24").val()) {
            var others24 = $(".others24").val().split(".").join("");
        } else {
            var others24 = 0;
        }
        var subtotal24 = +toll24 + +parking24 + +gasoline24 + +others24;
        $(".subtotal24").val(numberWithCommas(subtotal24));

        if ($(".toll25").val()) {
            var toll25 = $(".toll25").val().split(".").join("");
        } else {
            var toll25 = 0;
        }
        if ($(".parking25").val()) {
            var parking25 = $(".parking25").val().split(".").join("");
        } else {
            var parking25 = 0;
        }
        if ($(".gasoline25").val()) {
            var gasoline25 = $(".gasoline25").val().split(".").join("");
        } else {
            var gasoline25 = 0;
        }
        if ($(".others25").val()) {
            var others25 = $(".others25").val().split(".").join("");
        } else {
            var others25 = 0;
        }
        var subtotal25 = +toll25 + +parking25 + +gasoline25 + +others25;
        $(".subtotal25").val(numberWithCommas(subtotal25));

        if ($(".toll26").val()) {
            var toll26 = $(".toll26").val().split(".").join("");
        } else {
            var toll26 = 0;
        }
        if ($(".parking26").val()) {
            var parking26 = $(".parking26").val().split(".").join("");
        } else {
            var parking26 = 0;
        }
        if ($(".gasoline26").val()) {
            var gasoline26 = $(".gasoline26").val().split(".").join("");
        } else {
            var gasoline26 = 0;
        }
        if ($(".others26").val()) {
            var others26 = $(".others26").val().split(".").join("");
        } else {
            var others26 = 0;
        }
        var subtotal26 = +toll26 + +parking26 + +gasoline26 + +others26;
        $(".subtotal26").val(numberWithCommas(subtotal26));

        if ($(".toll27").val()) {
            var toll27 = $(".toll27").val().split(".").join("");
        } else {
            var toll27 = 0;
        }
        if ($(".parking27").val()) {
            var parking27 = $(".parking27").val().split(".").join("");
        } else {
            var parking27 = 0;
        }
        if ($(".gasoline27").val()) {
            var gasoline27 = $(".gasoline27").val().split(".").join("");
        } else {
            var gasoline27 = 0;
        }
        if ($(".others27").val()) {
            var others27 = $(".others27").val().split(".").join("");
        } else {
            var others27 = 0;
        }
        var subtotal27 = +toll27 + +parking27 + +gasoline27 + +others27;
        $(".subtotal27").val(numberWithCommas(subtotal27));

        if ($(".toll28").val()) {
            var toll28 = $(".toll28").val().split(".").join("");
        } else {
            var toll28 = 0;
        }
        if ($(".parking28").val()) {
            var parking28 = $(".parking28").val().split(".").join("");
        } else {
            var parking28 = 0;
        }
        if ($(".gasoline28").val()) {
            var gasoline28 = $(".gasoline28").val().split(".").join("");
        } else {
            var gasoline28 = 0;
        }
        if ($(".others28").val()) {
            var others28 = $(".others28").val().split(".").join("");
        } else {
            var others28 = 0;
        }
        var subtotal28 = +toll28 + +parking28 + +gasoline28 + +others28;
        $(".subtotal28").val(numberWithCommas(subtotal28));

        if ($(".toll29").val()) {
            var toll29 = $(".toll29").val().split(".").join("");
        } else {
            var toll29 = 0;
        }
        if ($(".parking29").val()) {
            var parking29 = $(".parking29").val().split(".").join("");
        } else {
            var parking29 = 0;
        }
        if ($(".gasoline29").val()) {
            var gasoline29 = $(".gasoline29").val().split(".").join("");
        } else {
            var gasoline29 = 0;
        }
        if ($(".others29").val()) {
            var others29 = $(".others29").val().split(".").join("");
        } else {
            var others29 = 0;
        }
        var subtotal29 = +toll29 + +parking29 + +gasoline29 + +others29;
        $(".subtotal29").val(numberWithCommas(subtotal29));

        if ($(".toll30").val()) {
            var toll30 = $(".toll30").val().split(".").join("");
        } else {
            var toll30 = 0;
        }
        if ($(".parking30").val()) {
            var parking30 = $(".parking30").val().split(".").join("");
        } else {
            var parking30 = 0;
        }
        if ($(".gasoline30").val()) {
            var gasoline30 = $(".gasoline30").val().split(".").join("");
        } else {
            var gasoline30 = 0;
        }
        if ($(".others30").val()) {
            var others30 = $(".others30").val().split(".").join("");
        } else {
            var others30 = 0;
        }
        var subtotal30 = +toll30 + +parking30 + +gasoline30 + +others30;
        $(".subtotal30").val(numberWithCommas(subtotal30));

        if ($(".toll31").val()) {
            var toll31 = $(".toll31").val().split(".").join("");
        } else {
            var toll31 = 0;
        }
        if ($(".parking31").val()) {
            var parking31 = $(".parking31").val().split(".").join("");
        } else {
            var parking31 = 0;
        }
        if ($(".gasoline31").val()) {
            var gasoline31 = $(".gasoline31").val().split(".").join("");
        } else {
            var gasoline31 = 0;
        }
        if ($(".others31").val()) {
            var others31 = $(".others31").val().split(".").join("");
        } else {
            var others31 = 0;
        }
        var subtotal31 = +toll31 + +parking31 + +gasoline31 + +others31;
        $(".subtotal31").val(numberWithCommas(subtotal31));

        if ($(".toll32").val()) {
            var toll32 = $(".toll32").val().split(".").join("");
        } else {
            var toll32 = 0;
        }
        if ($(".parking32").val()) {
            var parking32 = $(".parking32").val().split(".").join("");
        } else {
            var parking32 = 0;
        }
        if ($(".gasoline32").val()) {
            var gasoline32 = $(".gasoline32").val().split(".").join("");
        } else {
            var gasoline32 = 0;
        }
        if ($(".others32").val()) {
            var others32 = $(".others32").val().split(".").join("");
        } else {
            var others32 = 0;
        }
        var subtotal32 = +toll32 + +parking32 + +gasoline32 + +others32;
        $(".subtotal32").val(numberWithCommas(subtotal32));

        if ($(".toll33").val()) {
            var toll33 = $(".toll33").val().split(".").join("");
        } else {
            var toll33 = 0;
        }
        if ($(".parking33").val()) {
            var parking33 = $(".parking33").val().split(".").join("");
        } else {
            var parking33 = 0;
        }
        if ($(".gasoline33").val()) {
            var gasoline33 = $(".gasoline33").val().split(".").join("");
        } else {
            var gasoline33 = 0;
        }
        if ($(".others33").val()) {
            var others33 = $(".others33").val().split(".").join("");
        } else {
            var others33 = 0;
        }
        var subtotal33 = +toll33 + +parking33 + +gasoline33 + +others33;
        $(".subtotal33").val(numberWithCommas(subtotal33));

        if ($(".toll34").val()) {
            var toll34 = $(".toll34").val().split(".").join("");
        } else {
            var toll34 = 0;
        }
        if ($(".parking34").val()) {
            var parking34 = $(".parking34").val().split(".").join("");
        } else {
            var parking34 = 0;
        }
        if ($(".gasoline34").val()) {
            var gasoline34 = $(".gasoline34").val().split(".").join("");
        } else {
            var gasoline34 = 0;
        }
        if ($(".others34").val()) {
            var others34 = $(".others34").val().split(".").join("");
        } else {
            var others34 = 0;
        }
        var subtotal34 = +toll34 + +parking34 + +gasoline34 + +others34;
        $(".subtotal34").val(numberWithCommas(subtotal34));

        if ($(".toll35").val()) {
            var toll35 = $(".toll35").val().split(".").join("");
        } else {
            var toll35 = 0;
        }
        if ($(".parking35").val()) {
            var parking35 = $(".parking35").val().split(".").join("");
        } else {
            var parking35 = 0;
        }
        if ($(".gasoline35").val()) {
            var gasoline35 = $(".gasoline35").val().split(".").join("");
        } else {
            var gasoline35 = 0;
        }
        if ($(".others35").val()) {
            var others35 = $(".others35").val().split(".").join("");
        } else {
            var others35 = 0;
        }
        var subtotal35 = +toll35 + +parking35 + +gasoline35 + +others35;
        $(".subtotal35").val(numberWithCommas(subtotal35));

        if ($(".toll36").val()) {
            var toll36 = $(".toll36").val().split(".").join("");
        } else {
            var toll36 = 0;
        }
        if ($(".parking36").val()) {
            var parking36 = $(".parking36").val().split(".").join("");
        } else {
            var parking36 = 0;
        }
        if ($(".gasoline36").val()) {
            var gasoline36 = $(".gasoline36").val().split(".").join("");
        } else {
            var gasoline36 = 0;
        }
        if ($(".others36").val()) {
            var others36 = $(".others36").val().split(".").join("");
        } else {
            var others36 = 0;
        }
        var subtotal36 = +toll36 + +parking36 + +gasoline36 + +others36;
        $(".subtotal36").val(numberWithCommas(subtotal36));

        if ($(".toll37").val()) {
            var toll37 = $(".toll37").val().split(".").join("");
        } else {
            var toll37 = 0;
        }
        if ($(".parking37").val()) {
            var parking37 = $(".parking37").val().split(".").join("");
        } else {
            var parking37 = 0;
        }
        if ($(".gasoline37").val()) {
            var gasoline37 = $(".gasoline37").val().split(".").join("");
        } else {
            var gasoline37 = 0;
        }
        if ($(".others37").val()) {
            var others37 = $(".others37").val().split(".").join("");
        } else {
            var others37 = 0;
        }
        var subtotal37 = +toll37 + +parking37 + +gasoline37 + +others37;
        $(".subtotal37").val(numberWithCommas(subtotal37));

        if ($(".toll38").val()) {
            var toll38 = $(".toll38").val().split(".").join("");
        } else {
            var toll38 = 0;
        }
        if ($(".parking38").val()) {
            var parking38 = $(".parking38").val().split(".").join("");
        } else {
            var parking38 = 0;
        }
        if ($(".gasoline38").val()) {
            var gasoline38 = $(".gasoline38").val().split(".").join("");
        } else {
            var gasoline38 = 0;
        }
        if ($(".others38").val()) {
            var others38 = $(".others38").val().split(".").join("");
        } else {
            var others38 = 0;
        }
        var subtotal38 = +toll38 + +parking38 + +gasoline38 + +others38;
        $(".subtotal38").val(numberWithCommas(subtotal38));

        if ($(".toll39").val()) {
            var toll39 = $(".toll39").val().split(".").join("");
        } else {
            var toll39 = 0;
        }
        if ($(".parking39").val()) {
            var parking39 = $(".parking39").val().split(".").join("");
        } else {
            var parking39 = 0;
        }
        if ($(".gasoline39").val()) {
            var gasoline39 = $(".gasoline39").val().split(".").join("");
        } else {
            var gasoline39 = 0;
        }
        if ($(".others39").val()) {
            var others39 = $(".others39").val().split(".").join("");
        } else {
            var others39 = 0;
        }
        var subtotal39 = +toll39 + +parking39 + +gasoline39 + +others39;
        $(".subtotal39").val(numberWithCommas(subtotal39));

        if ($(".toll40").val()) {
            var toll40 = $(".toll40").val().split(".").join("");
        } else {
            var toll40 = 0;
        }
        if ($(".parking40").val()) {
            var parking40 = $(".parking40").val().split(".").join("");
        } else {
            var parking40 = 0;
        }
        if ($(".gasoline40").val()) {
            var gasoline40 = $(".gasoline40").val().split(".").join("");
        } else {
            var gasoline40 = 0;
        }
        if ($(".others40").val()) {
            var others40 = $(".others40").val().split(".").join("");
        } else {
            var others40 = 0;
        }
        var subtotal40 = +toll40 + +parking40 + +gasoline40 + +others40;
        $(".subtotal40").val(numberWithCommas(subtotal40));

        var total = subtotal1 + subtotal2 + subtotal3 + subtotal4 + subtotal5 + subtotal6 + subtotal7 + subtotal8 + subtotal9 + subtotal10 + subtotal11 + subtotal12 + subtotal13 + subtotal14 + subtotal15 + subtotal16 + subtotal17 + subtotal18 + subtotal19 + subtotal20 + subtotal21 + subtotal22 + subtotal23 + subtotal24 + subtotal25 + subtotal26 + subtotal27 + subtotal28 + subtotal29 + subtotal30 + subtotal31 + subtotal32 + subtotal33 + subtotal34 + subtotal35 + subtotal36 + subtotal37 + subtotal38 + subtotal39 + subtotal40;
        $("#sum").val(numberWithCommas(total));
    }

    $(".change-price").change(function(){
        change_price();
    });
       
    var maxGroup = 40;
    var i = 1;
  
    // Fungsi untuk menambahkan baris baru dengan indeks yang benar
  $(".addMore").click(function () { 
      i++;
      $("#action_button").prop("disabled", true);
      $("#action_button_draft").prop("disabled", true);
      $(".warning-upload").show();

      $(".modal-body").animate(
          {
              scrollTop: $(".modal-body")[0].scrollHeight,
          },
          500
      );

      if ($('body').find('.fieldGroup').length < maxGroup) {    
           var fieldHTML = '<tr class="fieldGroup"><td>'+i+'</td><td><input type="hidden" name="id_detail[]"><input type="text" class="form-control amount-toll currency toll'+i+' change-price" name="toll[]" value="0" placeholder="Toll" required></td><td><input type="text" class="form-control amount-parking currency parking'+i+' change-price" name="parking[]" value="0" placeholder="Parking" required></td><td><input type="text" class="form-control amount-gasoline currency gasoline'+i+' change-price" name="gasoline[]" value="0" placeholder="Gasoline" required></td><td><input type="text" class="form-control amount-other currency others'+i+' change-price" name="others[]" value="0" placeholder="Other" required></td><td><input type="text" class="form-control amount-total currency subtotal'+i+' change-price" name="total[]" readonly placeholder="Total"></td><td><select name="payment_type[]" class="form-control" required><option value="" selected disabled>Select...</option><option value="Cash">Cash</option><option value="Fleet">Fleet</option></select></td><td class="file-proof"><button type="button" data-idx="'+i+'" class="btn btn-success btn-sm addFile"><i class="fa fa-upload"></i></button><button type="button" data-idx="'+i+'" class="btn btn-success btn-sm addCamera"><i class="fa fa-camera"></i></button><input type="file" accept="image/*" name="file[]"  style="display: none;" class="file-input file'+i+'"><input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;"></td><td><div id="preview_'+i+'"></div></td><td><input type="text" class="form-control" name="remark[]" placeholder="Remark"></td><td><button type="button" class="btn btn-danger remove-item">-</button></td></tr>';

               $('body').find('.fieldGroup:last').after(fieldHTML);
      $('.currency').mask("#.##0", {
          reverse: true
      });

      $(".change-price").change(function(){
                var toll1 = $(".toll1").val().split(".").join("");
                var parking1 = $(".parking1").val().split(".").join("");
                var gasoline1 = $(".gasoline1").val().split(".").join("");
                var others1 = $(".others1").val().split(".").join("");
                var subtotal1 = +toll1 + +parking1 + +gasoline1 + +others1;
                $(".subtotal1").val(numberWithCommas(subtotal1));
            
                if ($(".toll2").val()) {
                      var toll2 = $(".toll2").val().split(".").join("");
                  } else {
                      var toll2 = 0;
                  }
                  if ($(".parking2").val()) {
                      var parking2 = $(".parking2").val().split(".").join("");
                  } else {
                      var parking2 = 0;
                  }
                  if ($(".gasoline2").val()) {
                      var gasoline2 = $(".gasoline2").val().split(".").join("");
                  } else {
                      var gasoline2 = 0;
                  }
                  if ($(".others2").val()) {
                      var others2 = $(".others2").val().split(".").join("");
                  } else {
                      var others2 = 0;
                  }
                  var subtotal2 = +toll2 + +parking2 + +gasoline2 + +others2;
                  $(".subtotal2").val(numberWithCommas(subtotal2));

                  if ($(".toll3").val()) {
                      var toll3 = $(".toll3").val().split(".").join("");
                  } else {
                      var toll3 = 0;
                  }
                  if ($(".parking3").val()) {
                      var parking3 = $(".parking3").val().split(".").join("");
                  } else {
                      var parking3 = 0;
                  }
                  if ($(".gasoline3").val()) {
                      var gasoline3 = $(".gasoline3").val().split(".").join("");
                  } else {
                      var gasoline3 = 0;
                  }
                  if ($(".others3").val()) {
                      var others3 = $(".others3").val().split(".").join("");
                  } else {
                      var others3 = 0;
                  }
                  var subtotal3 = +toll3 + +parking3 + +gasoline3 + +others3;
                  $(".subtotal3").val(numberWithCommas(subtotal3));

                  if ($(".toll4").val()) {
                      var toll4 = $(".toll4").val().split(".").join("");
                  } else {
                      var toll4 = 0;
                  }
                  if ($(".parking4").val()) {
                      var parking4 = $(".parking4").val().split(".").join("");
                  } else {
                      var parking4 = 0;
                  }
                  if ($(".gasoline4").val()) {
                      var gasoline4 = $(".gasoline4").val().split(".").join("");
                  } else {
                      var gasoline4 = 0;
                  }
                  if ($(".others4").val()) {
                      var others4 = $(".others4").val().split(".").join("");
                  } else {
                      var others4 = 0;
                  }
                  var subtotal4 = +toll4 + +parking4 + +gasoline4 + +others4;
                  $(".subtotal4").val(numberWithCommas(subtotal4));

                  if ($(".toll5").val()) {
                      var toll5 = $(".toll5").val().split(".").join("");
                  } else {
                      var toll5 = 0;
                  }
                  if ($(".parking5").val()) {
                      var parking5 = $(".parking5").val().split(".").join("");
                  } else {
                      var parking5 = 0;
                  }
                  if ($(".gasoline5").val()) {
                      var gasoline5 = $(".gasoline5").val().split(".").join("");
                  } else {
                      var gasoline5 = 0;
                  }
                  if ($(".others5").val()) {
                      var others5 = $(".others5").val().split(".").join("");
                  } else {
                      var others5 = 0;
                  }
                  var subtotal5 = +toll5 + +parking5 + +gasoline5 + +others5;
                  $(".subtotal5").val(numberWithCommas(subtotal5));

                  if ($(".toll6").val()) {
                      var toll6 = $(".toll6").val().split(".").join("");
                  } else {
                      var toll6 = 0;
                  }
                  if ($(".parking6").val()) {
                      var parking6 = $(".parking6").val().split(".").join("");
                  } else {
                      var parking6 = 0;
                  }
                  if ($(".gasoline6").val()) {
                      var gasoline6 = $(".gasoline6").val().split(".").join("");
                  } else {
                      var gasoline6 = 0;
                  }
                  if ($(".others6").val()) {
                      var others6 = $(".others6").val().split(".").join("");
                  } else {
                      var others6 = 0;
                  }
                  var subtotal6 = +toll6 + +parking6 + +gasoline6 + +others6;
                  $(".subtotal6").val(numberWithCommas(subtotal6));

                  if ($(".toll7").val()) {
                      var toll7 = $(".toll7").val().split(".").join("");
                  } else {
                      var toll7 = 0;
                  }
                  if ($(".parking7").val()) {
                      var parking7 = $(".parking7").val().split(".").join("");
                  } else {
                      var parking7 = 0;
                  }
                  if ($(".gasoline7").val()) {
                      var gasoline7 = $(".gasoline7").val().split(".").join("");
                  } else {
                      var gasoline7 = 0;
                  }
                  if ($(".others7").val()) {
                      var others7 = $(".others7").val().split(".").join("");
                  } else {
                      var others7 = 0;
                  }
                  var subtotal7 = +toll7 + +parking7 + +gasoline7 + +others7;
                  $(".subtotal7").val(numberWithCommas(subtotal7));

                  if ($(".toll8").val()) {
                      var toll8 = $(".toll8").val().split(".").join("");
                  } else {
                      var toll8 = 0;
                  }
                  if ($(".parking8").val()) {
                      var parking8 = $(".parking8").val().split(".").join("");
                  } else {
                      var parking8 = 0;
                  }
                  if ($(".gasoline8").val()) {
                      var gasoline8 = $(".gasoline8").val().split(".").join("");
                  } else {
                      var gasoline8 = 0;
                  }
                  if ($(".others8").val()) {
                      var others8 = $(".others8").val().split(".").join("");
                  } else {
                      var others8 = 0;
                  }
                  var subtotal8 = +toll8 + +parking8 + +gasoline8 + +others8;
                  $(".subtotal8").val(numberWithCommas(subtotal8));

                  if ($(".toll9").val()) {
                      var toll9 = $(".toll9").val().split(".").join("");
                  } else {
                      var toll9 = 0;
                  }
                  if ($(".parking9").val()) {
                      var parking9 = $(".parking9").val().split(".").join("");
                  } else {
                      var parking9 = 0;
                  }
                  if ($(".gasoline9").val()) {
                      var gasoline9 = $(".gasoline9").val().split(".").join("");
                  } else {
                      var gasoline9 = 0;
                  }
                  if ($(".others9").val()) {
                      var others9 = $(".others9").val().split(".").join("");
                  } else {
                      var others9 = 0;
                  }
                  var subtotal9 = +toll9 + +parking9 + +gasoline9 + +others9;
                  $(".subtotal9").val(numberWithCommas(subtotal9));

                  if ($(".toll10").val()) {
                      var toll10 = $(".toll10").val().split(".").join("");
                  } else {
                      var toll10 = 0;
                  }
                  if ($(".parking10").val()) {
                      var parking10 = $(".parking10").val().split(".").join("");
                  } else {
                      var parking10 = 0;
                  }
                  if ($(".gasoline10").val()) {
                      var gasoline10 = $(".gasoline10").val().split(".").join("");
                  } else {
                      var gasoline10 = 0;
                  }
                  if ($(".others10").val()) {
                      var others10 = $(".others10").val().split(".").join("");
                  } else {
                      var others10 = 0;
                  }
                  var subtotal10 = +toll10 + +parking10 + +gasoline10 + +others10;
                  $(".subtotal10").val(numberWithCommas(subtotal10));

                  if ($(".toll11").val()) {
                      var toll11 = $(".toll11").val().split(".").join("");
                  } else {
                      var toll11 = 0;
                  }
                  if ($(".parking11").val()) {
                      var parking11 = $(".parking11").val().split(".").join("");
                  } else {
                      var parking11 = 0;
                  }
                  if ($(".gasoline11").val()) {
                      var gasoline11 = $(".gasoline11").val().split(".").join("");
                  } else {
                      var gasoline11 = 0;
                  }
                  if ($(".others11").val()) {
                      var others11 = $(".others11").val().split(".").join("");
                  } else {
                      var others11 = 0;
                  }
                  var subtotal11 = +toll11 + +parking11 + +gasoline11 + +others11;
                  $(".subtotal11").val(numberWithCommas(subtotal11));

                  if ($(".toll12").val()) {
                      var toll12 = $(".toll12").val().split(".").join("");
                  } else {
                      var toll12 = 0;
                  }
                  if ($(".parking12").val()) {
                      var parking12 = $(".parking12").val().split(".").join("");
                  } else {
                      var parking12 = 0;
                  }
                  if ($(".gasoline12").val()) {
                      var gasoline12 = $(".gasoline12").val().split(".").join("");
                  } else {
                      var gasoline12 = 0;
                  }
                  if ($(".others12").val()) {
                      var others12 = $(".others12").val().split(".").join("");
                  } else {
                      var others12 = 0;
                  }
                  var subtotal12 = +toll12 + +parking12 + +gasoline12 + +others12;
                  $(".subtotal12").val(numberWithCommas(subtotal12));

                  if ($(".toll13").val()) {
                      var toll13 = $(".toll13").val().split(".").join("");
                  } else {
                      var toll13 = 0;
                  }
                  if ($(".parking13").val()) {
                      var parking13 = $(".parking13").val().split(".").join("");
                  } else {
                      var parking13 = 0;
                  }
                  if ($(".gasoline13").val()) {
                      var gasoline13 = $(".gasoline13").val().split(".").join("");
                  } else {
                      var gasoline13 = 0;
                  }
                  if ($(".others13").val()) {
                      var others13 = $(".others13").val().split(".").join("");
                  } else {
                      var others13 = 0;
                  }
                  var subtotal13 = +toll13 + +parking13 + +gasoline13 + +others13;
                  $(".subtotal13").val(numberWithCommas(subtotal13));

                  if ($(".toll14").val()) {
                      var toll14 = $(".toll14").val().split(".").join("");
                  } else {
                      var toll14 = 0;
                  }
                  if ($(".parking14").val()) {
                      var parking14 = $(".parking14").val().split(".").join("");
                  } else {
                      var parking14 = 0;
                  }
                  if ($(".gasoline14").val()) {
                      var gasoline14 = $(".gasoline14").val().split(".").join("");
                  } else {
                      var gasoline14 = 0;
                  }
                  if ($(".others14").val()) {
                      var others14 = $(".others14").val().split(".").join("");
                  } else {
                      var others14 = 0;
                  }
                  var subtotal14 = +toll14 + +parking14 + +gasoline14 + +others14;
                  $(".subtotal14").val(numberWithCommas(subtotal14));

                  if ($(".toll15").val()) {
                      var toll15 = $(".toll15").val().split(".").join("");
                  } else {
                      var toll15 = 0;
                  }
                  if ($(".parking15").val()) {
                      var parking15 = $(".parking15").val().split(".").join("");
                  } else {
                      var parking15 = 0;
                  }
                  if ($(".gasoline15").val()) {
                      var gasoline15 = $(".gasoline15").val().split(".").join("");
                  } else {
                      var gasoline15 = 0;
                  }
                  if ($(".others15").val()) {
                      var others15 = $(".others15").val().split(".").join("");
                  } else {
                      var others15 = 0;
                  }
                  var subtotal15 = +toll15 + +parking15 + +gasoline15 + +others15;
                  $(".subtotal15").val(numberWithCommas(subtotal15));

                  if ($(".toll16").val()) {
                      var toll16 = $(".toll16").val().split(".").join("");
                  } else {
                      var toll16 = 0;
                  }
                  if ($(".parking16").val()) {
                      var parking16 = $(".parking16").val().split(".").join("");
                  } else {
                      var parking16 = 0;
                  }
                  if ($(".gasoline16").val()) {
                      var gasoline16 = $(".gasoline16").val().split(".").join("");
                  } else {
                      var gasoline16 = 0;
                  }
                  if ($(".others16").val()) {
                      var others16 = $(".others16").val().split(".").join("");
                  } else {
                      var others16 = 0;
                  }
                  var subtotal16 = +toll16 + +parking16 + +gasoline16 + +others16;
                  $(".subtotal16").val(numberWithCommas(subtotal16));

                  if ($(".toll17").val()) {
                      var toll17 = $(".toll17").val().split(".").join("");
                  } else {
                      var toll17 = 0;
                  }
                  if ($(".parking17").val()) {
                      var parking17 = $(".parking17").val().split(".").join("");
                  } else {
                      var parking17 = 0;
                  }
                  if ($(".gasoline17").val()) {
                      var gasoline17 = $(".gasoline17").val().split(".").join("");
                  } else {
                      var gasoline17 = 0;
                  }
                  if ($(".others17").val()) {
                      var others17 = $(".others17").val().split(".").join("");
                  } else {
                      var others17 = 0;
                  }
                  var subtotal17 = +toll17 + +parking17 + +gasoline17 + +others17;
                  $(".subtotal17").val(numberWithCommas(subtotal17));

                  if ($(".toll18").val()) {
                      var toll18 = $(".toll18").val().split(".").join("");
                  } else {
                      var toll18 = 0;
                  }
                  if ($(".parking18").val()) {
                      var parking18 = $(".parking18").val().split(".").join("");
                  } else {
                      var parking18 = 0;
                  }
                  if ($(".gasoline18").val()) {
                      var gasoline18 = $(".gasoline18").val().split(".").join("");
                  } else {
                      var gasoline18 = 0;
                  }
                  if ($(".others18").val()) {
                      var others18 = $(".others18").val().split(".").join("");
                  } else {
                      var others18 = 0;
                  }
                  var subtotal18 = +toll18 + +parking18 + +gasoline18 + +others18;
                  $(".subtotal18").val(numberWithCommas(subtotal18));

                  if ($(".toll19").val()) {
                      var toll19 = $(".toll19").val().split(".").join("");
                  } else {
                      var toll19 = 0;
                  }
                  if ($(".parking19").val()) {
                      var parking19 = $(".parking19").val().split(".").join("");
                  } else {
                      var parking19 = 0;
                  }
                  if ($(".gasoline19").val()) {
                      var gasoline19 = $(".gasoline19").val().split(".").join("");
                  } else {
                      var gasoline19 = 0;
                  }
                  if ($(".others19").val()) {
                      var others19 = $(".others19").val().split(".").join("");
                  } else {
                      var others19 = 0;
                  }
                  var subtotal19 = +toll19 + +parking19 + +gasoline19 + +others19;
                  $(".subtotal19").val(numberWithCommas(subtotal19));

                  if ($(".toll20").val()) {
                      var toll20 = $(".toll20").val().split(".").join("");
                  } else {
                      var toll20 = 0;
                  }
                  if ($(".parking20").val()) {
                      var parking20 = $(".parking20").val().split(".").join("");
                  } else {
                      var parking20 = 0;
                  }
                  if ($(".gasoline20").val()) {
                      var gasoline20 = $(".gasoline20").val().split(".").join("");
                  } else {
                      var gasoline20 = 0;
                  }
                  if ($(".others20").val()) {
                      var others20 = $(".others20").val().split(".").join("");
                  } else {
                      var others20 = 0;
                  }
                  var subtotal20 = +toll20 + +parking20 + +gasoline20 + +others20;
                  $(".subtotal20").val(numberWithCommas(subtotal20));

                  if ($(".toll21").val()) {
                      var toll21 = $(".toll21").val().split(".").join("");
                  } else {
                      var toll21 = 0;
                  }
                  if ($(".parking21").val()) {
                      var parking21 = $(".parking21").val().split(".").join("");
                  } else {
                      var parking21 = 0;
                  }
                  if ($(".gasoline21").val()) {
                      var gasoline21 = $(".gasoline21").val().split(".").join("");
                  } else {
                      var gasoline21 = 0;
                  }
                  if ($(".others21").val()) {
                      var others21 = $(".others21").val().split(".").join("");
                  } else {
                      var others21 = 0;
                  }
                  var subtotal21 = +toll21 + +parking21 + +gasoline21 + +others21;
                  $(".subtotal21").val(numberWithCommas(subtotal21));

                  if ($(".toll22").val()) {
                      var toll22 = $(".toll22").val().split(".").join("");
                  } else {
                      var toll22 = 0;
                  }
                  if ($(".parking22").val()) {
                      var parking22 = $(".parking22").val().split(".").join("");
                  } else {
                      var parking22 = 0;
                  }
                  if ($(".gasoline22").val()) {
                      var gasoline22 = $(".gasoline22").val().split(".").join("");
                  } else {
                      var gasoline22 = 0;
                  }
                  if ($(".others22").val()) {
                      var others22 = $(".others22").val().split(".").join("");
                  } else {
                      var others22 = 0;
                  }
                  var subtotal22 = +toll22 + +parking22 + +gasoline22 + +others22;
                  $(".subtotal22").val(numberWithCommas(subtotal22));

                  if ($(".toll23").val()) {
                      var toll23 = $(".toll23").val().split(".").join("");
                  } else {
                      var toll23 = 0;
                  }
                  if ($(".parking23").val()) {
                      var parking23 = $(".parking23").val().split(".").join("");
                  } else {
                      var parking23 = 0;
                  }
                  if ($(".gasoline23").val()) {
                      var gasoline23 = $(".gasoline23").val().split(".").join("");
                  } else {
                      var gasoline23 = 0;
                  }
                  if ($(".others23").val()) {
                      var others23 = $(".others23").val().split(".").join("");
                  } else {
                      var others23 = 0;
                  }
                  var subtotal23 = +toll23 + +parking23 + +gasoline23 + +others23;
                  $(".subtotal23").val(numberWithCommas(subtotal23));

                  if ($(".toll24").val()) {
                      var toll24 = $(".toll24").val().split(".").join("");
                  } else {
                      var toll24 = 0;
                  }
                  if ($(".parking24").val()) {
                      var parking24 = $(".parking24").val().split(".").join("");
                  } else {
                      var parking24 = 0;
                  }
                  if ($(".gasoline24").val()) {
                      var gasoline24 = $(".gasoline24").val().split(".").join("");
                  } else {
                      var gasoline24 = 0;
                  }
                  if ($(".others24").val()) {
                      var others24 = $(".others24").val().split(".").join("");
                  } else {
                      var others24 = 0;
                  }
                  var subtotal24 = +toll24 + +parking24 + +gasoline24 + +others24;
                  $(".subtotal24").val(numberWithCommas(subtotal24));

                  if ($(".toll25").val()) {
                      var toll25 = $(".toll25").val().split(".").join("");
                  } else {
                      var toll25 = 0;
                  }
                  if ($(".parking25").val()) {
                      var parking25 = $(".parking25").val().split(".").join("");
                  } else {
                      var parking25 = 0;
                  }
                  if ($(".gasoline25").val()) {
                      var gasoline25 = $(".gasoline25").val().split(".").join("");
                  } else {
                      var gasoline25 = 0;
                  }
                  if ($(".others25").val()) {
                      var others25 = $(".others25").val().split(".").join("");
                  } else {
                      var others25 = 0;
                  }
                  var subtotal25 = +toll25 + +parking25 + +gasoline25 + +others25;
                  $(".subtotal25").val(numberWithCommas(subtotal25));

                  if ($(".toll26").val()) {
                      var toll26 = $(".toll26").val().split(".").join("");
                  } else {
                      var toll26 = 0;
                  }
                  if ($(".parking26").val()) {
                      var parking26 = $(".parking26").val().split(".").join("");
                  } else {
                      var parking26 = 0;
                  }
                  if ($(".gasoline26").val()) {
                      var gasoline26 = $(".gasoline26").val().split(".").join("");
                  } else {
                      var gasoline26 = 0;
                  }
                  if ($(".others26").val()) {
                      var others26 = $(".others26").val().split(".").join("");
                  } else {
                      var others26 = 0;
                  }
                  var subtotal26 = +toll26 + +parking26 + +gasoline26 + +others26;
                  $(".subtotal26").val(numberWithCommas(subtotal26));

                  if ($(".toll27").val()) {
                      var toll27 = $(".toll27").val().split(".").join("");
                  } else {
                      var toll27 = 0;
                  }
                  if ($(".parking27").val()) {
                      var parking27 = $(".parking27").val().split(".").join("");
                  } else {
                      var parking27 = 0;
                  }
                  if ($(".gasoline27").val()) {
                      var gasoline27 = $(".gasoline27").val().split(".").join("");
                  } else {
                      var gasoline27 = 0;
                  }
                  if ($(".others27").val()) {
                      var others27 = $(".others27").val().split(".").join("");
                  } else {
                      var others27 = 0;
                  }
                  var subtotal27 = +toll27 + +parking27 + +gasoline27 + +others27;
                  $(".subtotal27").val(numberWithCommas(subtotal27));

                  if ($(".toll28").val()) {
                      var toll28 = $(".toll28").val().split(".").join("");
                  } else {
                      var toll28 = 0;
                  }
                  if ($(".parking28").val()) {
                      var parking28 = $(".parking28").val().split(".").join("");
                  } else {
                      var parking28 = 0;
                  }
                  if ($(".gasoline28").val()) {
                      var gasoline28 = $(".gasoline28").val().split(".").join("");
                  } else {
                      var gasoline28 = 0;
                  }
                  if ($(".others28").val()) {
                      var others28 = $(".others28").val().split(".").join("");
                  } else {
                      var others28 = 0;
                  }
                  var subtotal28 = +toll28 + +parking28 + +gasoline28 + +others28;
                  $(".subtotal28").val(numberWithCommas(subtotal28));

                  if ($(".toll29").val()) {
                      var toll29 = $(".toll29").val().split(".").join("");
                  } else {
                      var toll29 = 0;
                  }
                  if ($(".parking29").val()) {
                      var parking29 = $(".parking29").val().split(".").join("");
                  } else {
                      var parking29 = 0;
                  }
                  if ($(".gasoline29").val()) {
                      var gasoline29 = $(".gasoline29").val().split(".").join("");
                  } else {
                      var gasoline29 = 0;
                  }
                  if ($(".others29").val()) {
                      var others29 = $(".others29").val().split(".").join("");
                  } else {
                      var others29 = 0;
                  }
                  var subtotal29 = +toll29 + +parking29 + +gasoline29 + +others29;
                  $(".subtotal29").val(numberWithCommas(subtotal29));

                  if ($(".toll30").val()) {
                      var toll30 = $(".toll30").val().split(".").join("");
                  } else {
                      var toll30 = 0;
                  }
                  if ($(".parking30").val()) {
                      var parking30 = $(".parking30").val().split(".").join("");
                  } else {
                      var parking30 = 0;
                  }
                  if ($(".gasoline30").val()) {
                      var gasoline30 = $(".gasoline30").val().split(".").join("");
                  } else {
                      var gasoline30 = 0;
                  }
                  if ($(".others30").val()) {
                      var others30 = $(".others30").val().split(".").join("");
                  } else {
                      var others30 = 0;
                  }
                  var subtotal30 = +toll30 + +parking30 + +gasoline30 + +others30;
                  $(".subtotal30").val(numberWithCommas(subtotal30));

                  if ($(".toll31").val()) {
                      var toll31 = $(".toll31").val().split(".").join("");
                  } else {
                      var toll31 = 0;
                  }
                  if ($(".parking31").val()) {
                      var parking31 = $(".parking31").val().split(".").join("");
                  } else {
                      var parking31 = 0;
                  }
                  if ($(".gasoline31").val()) {
                      var gasoline31 = $(".gasoline31").val().split(".").join("");
                  } else {
                      var gasoline31 = 0;
                  }
                  if ($(".others31").val()) {
                      var others31 = $(".others31").val().split(".").join("");
                  } else {
                      var others31 = 0;
                  }
                  var subtotal31 = +toll31 + +parking31 + +gasoline31 + +others31;
                  $(".subtotal31").val(numberWithCommas(subtotal31));

                  if ($(".toll32").val()) {
                      var toll32 = $(".toll32").val().split(".").join("");
                  } else {
                      var toll32 = 0;
                  }
                  if ($(".parking32").val()) {
                      var parking32 = $(".parking32").val().split(".").join("");
                  } else {
                      var parking32 = 0;
                  }
                  if ($(".gasoline32").val()) {
                      var gasoline32 = $(".gasoline32").val().split(".").join("");
                  } else {
                      var gasoline32 = 0;
                  }
                  if ($(".others32").val()) {
                      var others32 = $(".others32").val().split(".").join("");
                  } else {
                      var others32 = 0;
                  }
                  var subtotal32 = +toll32 + +parking32 + +gasoline32 + +others32;
                  $(".subtotal32").val(numberWithCommas(subtotal32));

                  if ($(".toll33").val()) {
                      var toll33 = $(".toll33").val().split(".").join("");
                  } else {
                      var toll33 = 0;
                  }
                  if ($(".parking33").val()) {
                      var parking33 = $(".parking33").val().split(".").join("");
                  } else {
                      var parking33 = 0;
                  }
                  if ($(".gasoline33").val()) {
                      var gasoline33 = $(".gasoline33").val().split(".").join("");
                  } else {
                      var gasoline33 = 0;
                  }
                  if ($(".others33").val()) {
                      var others33 = $(".others33").val().split(".").join("");
                  } else {
                      var others33 = 0;
                  }
                  var subtotal33 = +toll33 + +parking33 + +gasoline33 + +others33;
                  $(".subtotal33").val(numberWithCommas(subtotal33));

                  if ($(".toll34").val()) {
                      var toll34 = $(".toll34").val().split(".").join("");
                  } else {
                      var toll34 = 0;
                  }
                  if ($(".parking34").val()) {
                      var parking34 = $(".parking34").val().split(".").join("");
                  } else {
                      var parking34 = 0;
                  }
                  if ($(".gasoline34").val()) {
                      var gasoline34 = $(".gasoline34").val().split(".").join("");
                  } else {
                      var gasoline34 = 0;
                  }
                  if ($(".others34").val()) {
                      var others34 = $(".others34").val().split(".").join("");
                  } else {
                      var others34 = 0;
                  }
                  var subtotal34 = +toll34 + +parking34 + +gasoline34 + +others34;
                  $(".subtotal34").val(numberWithCommas(subtotal34));

                  if ($(".toll35").val()) {
                      var toll35 = $(".toll35").val().split(".").join("");
                  } else {
                      var toll35 = 0;
                  }
                  if ($(".parking35").val()) {
                      var parking35 = $(".parking35").val().split(".").join("");
                  } else {
                      var parking35 = 0;
                  }
                  if ($(".gasoline35").val()) {
                      var gasoline35 = $(".gasoline35").val().split(".").join("");
                  } else {
                      var gasoline35 = 0;
                  }
                  if ($(".others35").val()) {
                      var others35 = $(".others35").val().split(".").join("");
                  } else {
                      var others35 = 0;
                  }
                  var subtotal35 = +toll35 + +parking35 + +gasoline35 + +others35;
                  $(".subtotal35").val(numberWithCommas(subtotal35));

                  if ($(".toll36").val()) {
                      var toll36 = $(".toll36").val().split(".").join("");
                  } else {
                      var toll36 = 0;
                  }
                  if ($(".parking36").val()) {
                      var parking36 = $(".parking36").val().split(".").join("");
                  } else {
                      var parking36 = 0;
                  }
                  if ($(".gasoline36").val()) {
                      var gasoline36 = $(".gasoline36").val().split(".").join("");
                  } else {
                      var gasoline36 = 0;
                  }
                  if ($(".others36").val()) {
                      var others36 = $(".others36").val().split(".").join("");
                  } else {
                      var others36 = 0;
                  }
                  var subtotal36 = +toll36 + +parking36 + +gasoline36 + +others36;
                  $(".subtotal36").val(numberWithCommas(subtotal36));

                  if ($(".toll37").val()) {
                      var toll37 = $(".toll37").val().split(".").join("");
                  } else {
                      var toll37 = 0;
                  }
                  if ($(".parking37").val()) {
                      var parking37 = $(".parking37").val().split(".").join("");
                  } else {
                      var parking37 = 0;
                  }
                  if ($(".gasoline37").val()) {
                      var gasoline37 = $(".gasoline37").val().split(".").join("");
                  } else {
                      var gasoline37 = 0;
                  }
                  if ($(".others37").val()) {
                      var others37 = $(".others37").val().split(".").join("");
                  } else {
                      var others37 = 0;
                  }
                  var subtotal37 = +toll37 + +parking37 + +gasoline37 + +others37;
                  $(".subtotal37").val(numberWithCommas(subtotal37));

                  if ($(".toll38").val()) {
                      var toll38 = $(".toll38").val().split(".").join("");
                  } else {
                      var toll38 = 0;
                  }
                  if ($(".parking38").val()) {
                      var parking38 = $(".parking38").val().split(".").join("");
                  } else {
                      var parking38 = 0;
                  }
                  if ($(".gasoline38").val()) {
                      var gasoline38 = $(".gasoline38").val().split(".").join("");
                  } else {
                      var gasoline38 = 0;
                  }
                  if ($(".others38").val()) {
                      var others38 = $(".others38").val().split(".").join("");
                  } else {
                      var others38 = 0;
                  }
                  var subtotal38 = +toll38 + +parking38 + +gasoline38 + +others38;
                  $(".subtotal38").val(numberWithCommas(subtotal38));

                  if ($(".toll39").val()) {
                      var toll39 = $(".toll39").val().split(".").join("");
                  } else {
                      var toll39 = 0;
                  }
                  if ($(".parking39").val()) {
                      var parking39 = $(".parking39").val().split(".").join("");
                  } else {
                      var parking39 = 0;
                  }
                  if ($(".gasoline39").val()) {
                      var gasoline39 = $(".gasoline39").val().split(".").join("");
                  } else {
                      var gasoline39 = 0;
                  }
                  if ($(".others39").val()) {
                      var others39 = $(".others39").val().split(".").join("");
                  } else {
                      var others39 = 0;
                  }
                  var subtotal39 = +toll39 + +parking39 + +gasoline39 + +others39;
                  $(".subtotal39").val(numberWithCommas(subtotal39));

                  if ($(".toll40").val()) {
                      var toll40 = $(".toll40").val().split(".").join("");
                  } else {
                      var toll40 = 0;
                  }
                  if ($(".parking40").val()) {
                      var parking40 = $(".parking40").val().split(".").join("");
                  } else {
                      var parking40 = 0;
                  }
                  if ($(".gasoline40").val()) {
                      var gasoline40 = $(".gasoline40").val().split(".").join("");
                  } else {
                      var gasoline40 = 0;
                  }
                  if ($(".others40").val()) {
                      var others40 = $(".others40").val().split(".").join("");
                  } else {
                      var others40 = 0;
                  }
                  var subtotal40 = +toll40 + +parking40 + +gasoline40 + +others40;
                  $(".subtotal40").val(numberWithCommas(subtotal40));

                  var total = subtotal1 + subtotal2 + subtotal3 + subtotal4 + subtotal5 + subtotal6 + subtotal7 + subtotal8 + subtotal9 + subtotal10 + subtotal11 + subtotal12 + subtotal13 + subtotal14 + subtotal15 + subtotal16 + subtotal17 + subtotal18 + subtotal19 + subtotal20 + subtotal21 + subtotal22 + subtotal23 + subtotal24 + subtotal25 + subtotal26 + subtotal27 + subtotal28 + subtotal29 + subtotal30 + subtotal31 + subtotal32 + subtotal33 + subtotal34 + subtotal35 + subtotal36 + subtotal37 + subtotal38 + subtotal39 + subtotal40;
                  $("#sum").val(numberWithCommas(total));
            });

        } else{

          alert('Maximum '+maxGroup+' groups are allowed.');

        }

});

$("body").on("click",".remove-item",function(){ 
  $(this).parents(".fieldGroup").remove();
});
  
  // Objek untuk menyimpan status upload di setiap row
  let uploadStatus = {};

  // Fungsi untuk menangani upload file
  $("body").on("click", ".addFile", function () {
      let btn = $(this);
      let row = btn.closest("tr");
      let idx = row.index();
      let fileInput = row.find(".file-input");

      fileInput.click();

      fileInput.off("change").on("change", function (event) {
        let file = event.target.files[0];

        if (file) {
          $("#action_button").prop("disabled", false);
          $("#action_button_draft").prop("disabled", false);
          $(".warning-upload").hide();

          let previewDiv = row.find("#preview_" + (idx + 1));
          previewDiv.empty();

          let fileType = file.type;

          if (fileType.startsWith("image/")) {
            // Preview gambar
            let reader = new FileReader();
            reader.onload = function (e) {
              previewDiv.append(
                $('<img>').attr('src', e.target.result).css({
                  maxWidth: '75px',
                  maxHeight: '75px',
                  border: '2px solid #28a745',
                  borderRadius: '5px',
                  marginTop: '5px'
                })
              );
            };
            reader.readAsDataURL(file);

          } else if (fileType === "application/pdf") {
            // Preview ikon PDF + link ke file
            let pdfIcon = 'https://cdn-icons-png.flaticon.com/512/337/337946.png'; // atau file lokal
            let fileURL = URL.createObjectURL(file);

            previewDiv.append(
              $('<a>').attr({
                href: fileURL,
                target: '_blank',
                title: 'Lihat PDF'
              }).append(
                $('<img>').attr({
                  src: pdfIcon,
                  alt: 'PDF File'
                }).css({
                  maxWidth: '50px',
                  maxHeight: '50px',
                  border: '2px solid #007bff',
                  borderRadius: '5px',
                  marginTop: '5px'
                })
              )
            );

          } else {
            previewDiv.append('<p style="color:red;">File tidak didukung</p>');
          }
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
                          let previewDiv = row.find("#preview_" + (idx + 1));
                          previewDiv.empty().append(
                              $('<img>').attr('src', imageURL).css({
                                  maxWidth: '75px',
                                  maxHeight: '75px',
                                  border: '2px solid #28a745',
                                  borderRadius: '5px',
                                  marginTop: '5px'
                              })
                          );

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



  $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:0});
  // $('#sum').maskMoney({ thousands:'.', decimal:',', precision:0});
  $('select[name="status"]').on('change', function(){
      var status = $(this).val();
      if(status) {
          $.ajax({
              url: 'reimbursement-user?status='+status+'&reimbursement_type=1',
              type:"GET",
              dataType:"json",
              beforeSend: function(){
              
              },
              success:function(data) {
                  $('select[name="user_id"]').empty();
                  $('select[name="user_id"]').append('<option value="">-Select Employee-</option>')
                  $.each(data, function(key, value){
                  $('select[name="user_id"]').append('<option value="'+ value.id +'">' + value.name + '</option>');
                  });


              },
          });
      } else {
          $('select[name="user_id"]').empty();
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
        user_id: null,
                payment_type: 'ALL',
          reimburses: [
            {
              id: null,
              toll: 0,
              parking: 0,
              gasoline: 0,
              other: 0,
              total: 0,
              remark: null,
              evidence: null
            }
          ],
          grandtotal: 0
      },
      
      mounted() {
        
        // this.loadData()
        $('#show-data').on('change', () => {
                    this.loadData(this.start, this.end, this.status, this.user_id, this.payment_type);
        });
        $(".number-format").change(function() {
          $(this).maskMoney({ thousands:'.', decimal:',', precision:0});
        })
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
                self.loadData(self.start,self.end,self.status, self.user_id, self.payment_type);
        $("input.daterange").on('apply.daterangepicker', function(ev, picker) {
          var startDate = picker.startDate.format('YYYY-MM-DD');
          var endDate = picker.endDate.format('YYYY-MM-DD');
          self.start = startDate
          self.end = endDate
          console.log("Selected date range: " + startDate + ' to ' + endDate);
                    self.loadData(startDate,endDate,self.status, self.user_id, self.payment_type);
        });
        this.initSelectForm()
       
      },
    
      methods : {
       
        searchDriver(){

        },
        reset(){
          this.status = null
          this.user_id = null
                    this.payment_type = 'ALL'
          var start = moment().startOf('month');
          var end = moment().endOf('month');
          this.start = start.format('YYYY-MM-DD');
          this.end = end.format('YYYY-MM-DD');
                    this.loadData(this.start,this.end,this.status, this.user_id, this.payment_type);

        },
        search(){
                    this.loadData(this.start,this.end,this.status, this.user_id, this.payment_type);
        },

        print(){

          var status = $('.status').val();
          console.log(status);
          if (status==null) {
            alert('Status cannot be empty');
            return false;
          }
          
          // Ambil semua nilai checkbox yang diceklis
          var selectedValues = [];
          $('.check-print:checked').each(function(){
              selectedValues.push($(this).val());
          });

          // Tampilkan hasil
          if(selectedValues.length > 0){
              var id = selectedValues.join(",");
              window.open("{{url('/')}}/reimbursement-driver-print?selected="+id+"&start="+this.start+"&end="+this.end+"&driver="+this.user_id+"&status="+this.status, "_blank")
          } else {
              
              // window.open("{{url('/')}}/reimbursement-driver-print?start="+this.start+"&end="+this.end+"&driver="+this.user_id+"&status="+this.status, "_blank")
              var user_id = "{{auth()->user()->id}}";
              window.open("{{url('/')}}/reimbursement-driver-print?start="+this.start+"&end="+this.end+"&driver="+user_id+"&status="+this.status, "_blank")
          }
          
        },
          
                loadData(start = null,end = null, status= null, driver= null, payment_type = 'ALL') {
          try {
            $('#myTable').dataTable().fnDestroy();
            
          } catch (error) {
            
          }
          
          const perPage = parseInt($('#show-data').val()) || 10;
          
          $('#myTable').dataTable({
            processing: false,
            serverSide: false,
            bPaginate: true,
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            bAutoWidth: false,
            pageLength: perPage,
            order: [],
            ajax: {
              url:'{{ url("reimbursement-driver") }}',
              data:{
                first:start,
                last:end,
                status:status,
                driver:driver,
                                payment_type:payment_type,
              }
            },
            columns: [

                      {
                        data: 'checkbox',
                        name: 'checkbox'
                      },
                      {
                        data: 'no_reimbursement',
                        name: 'no_reimbursement'
                      },
                      {
                        data: 'created_at',
                        name: 'created_at'
                      },
                      {
                        data: 'date',
                        name: 'date'
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
        
        addReimbursement() {
          this.reimburses.push({
              id: null,
              toll: 0,
              parking: 0,
              gasoline: 0,
              other: 0,
              total: 0,
              evidence: null
            })
            this.initSelectForm()
            $('input.form-control').focus(function() {
                $(this).select();
            });
            self = this

            this.$nextTick(() => {
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
  


</script>

@endpush
@endsection



