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
  
  @media (max-width: 768px) {
      /* MOBILE ONLY */
      .input-style {
        width: 150px !important;
      }
      .select-style {
        width: 80px !important;
      }
  }
</style>

<div class="page-content" id="app">

@php
  $showApprovalTab = in_array(auth()->user()->jabatan, ['Owner', 'Finance', 'Direktur Operasional', 'superadmin'], true) || (int) $check_approval > 0;
@endphp
@if($showApprovalTab)
<div class="clearfix">
     <a href="{!!url('reimbursement-entertaiment')!!}" class="btn btn-success float-left" style="width: 48%;">My Inquiry</a>
     <a href="{!!url('reimbursement-entertaiment-approval')!!}" class="btn btn-info float-right" style="width: 48%;">Approval</a>
</div>
@endif

<!-- @if($showApprovalTab)
<div class="alert alert-info mt-2 mb-0" role="alert">
  <strong>Cara approve:</strong> Di halaman ini tidak ada tombol approve per baris. Buka tab <strong>Approval</strong> untuk setujui banyak klaim sekaligus, atau klik <strong>nomor klaim</strong> lalu gunakan tombol <strong>Approve</strong> di halaman detail (sesuai peran: Head Department → HR GA → Finance).
</div>
@else
<div class="alert alert-light border mt-2 mb-0" role="alert">
  Pengajuan Anda bisa dilihat di tabel di bawah. Untuk menyetujui klaim orang lain, akun Anda harus memiliki peran verifikator (Head Department / HR GA / Finance) atau akses Approval.
</div>
@endif -->

<br>

<div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                    <div class="col-12">
                      <p class="card-title clr-green" >Dashboard</p>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="row"> 
                            <div class="col-sm-6">
                                <h2 class="card-title clr-green">Reimbursement Entertainment @if($check_approval > 0) (My Inquiry) @endif</h2>
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
                       <!--  @if (auth()->user()->jabatan != "karyawan")
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
                                <button class="btn btn-primary d-block" @click="search()" style="margin-top:32px"><i class="fa fa-search"></i></button>
                                <button class="btn btn-primary d-block" @click="reset()" style="margin-top:32px"><i class="fas fa-sync-alt fa-fw"></i></button>
                                <button class="btn btn-primary d-block" @click="print()" style="margin-top:32px"><i class="fa fa-print"></i></button>
                                <button type="button" class="btn btn-primary btn-sm w-100 create-data" data-toggle="modal" data-target=".bd-example-modal-lg" style="margin-top:32px">
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
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>


                      </tbody>

                  </table>
              </div>
          </div>
      </div>
  </div>
  <div class="modal fade bd-example-modal-lg" id="formModal" style="overflow-y: auto">
    <form method="post" id="sample_form" action="{{url('/')."/reimbursement-entertaiment"}}" enctype="multipart/form-data">
    @csrf
      <!--<div class="modal-dialog modal-xxl" style="max-width: 80% !important">-->
      <div class="modal-dialog modal-xxl" style="max-width: 100%;margin: 19;top: 19;bottom: 19;left: 19;right: 19;display: flex;">
          <div class="modal-content">
              <div class="modal-header border-bottom"  >
              <div class="d-flex justify-content-between w-100">
                    <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle">Create Reimbursement Entertainment</h2>
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
                          <th align="center">Action</th>
                      </tr>
                  </thead>
                  <tbody>
                    <tr class="fieldGroup">
                      
                          <td>
                            <input type="text" class="form-control input-style" name="empty_zone[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="attendance[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="position[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="place[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="guest[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="guest_position[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="company[]" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control input-style" name="type[]" placeholder="">
                          </td>
                          <td>
                              <select name="payment_type[]" class="form-control select-style" required>
                                  <option value="" selected disabled>Select...</option>
                                  <option value="BDC">BDC</option>
                                  <option value="Cash">Cash</option>
                              </select>
                          </td>
                          <td>
                            <input type="text" class="form-control amount-input currency amount1 change-amount input-style" name="amount[]"  placeholder="">
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
                            <input type="text" class="form-control input-style" name="remark[]" placeholder="Remark">
                          </td>
                          <td>
                            <button  type="button" name="add" id="add" class="btn btn-success full-width addMore">+</button>
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

    $("#checkAll").click(function(){
        $('input:checkbox').not(this).prop('checked', this.checked);
    });

    $("#action_button").prop("disabled", true);
    $("#action_button_draft").prop("disabled", true);
    $(".warning-upload").show();
    
    $(".change-amount").change(function(){
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

    $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:0});
    // $('#sum').maskMoney({ thousands:'.', decimal:',', precision:0});
    
    $('select[name="status"]').on('change', function(){
        var status = $(this).val();
        if(status) {
            $.ajax({
                url: 'reimbursement-user?status='+status+'&reimbursement_type=3',
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
    
    
    var maxGroup = 10;
       var i = 1;
       
       $(".addMore").click(function(){
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
            if($('body').find('.fieldGroup').length < maxGroup){
             
              var fieldHTML = '<tr class="fieldGroup"><td><input type="text" class="form-control" name="empty_zone[]" placeholder=""></td><td><input type="text" class="form-control" name="attendance[]" placeholder=""></td><td><input type="text" class="form-control" name="position[]" placeholder=""></td><td><input type="text" class="form-control" name="place[]" placeholder=""></td><td><input type="text" class="form-control" name="guest[]" placeholder=""></td><td><input type="text" class="form-control" name="guest_position[]" placeholder=""></td><td><input type="text" class="form-control" name="company[]" placeholder=""></td><td><input type="text" class="form-control" name="type[]" placeholder=""></td><td><select name="payment_type[]" class="form-control" required><option value="" selected disabled>Select...</option><option value="BDC">BDC</option><option value="Cash">Cash</option></select></td><td><input type="text" class="form-control amount-input currency amount'+i+' change-amount" name="amount[]"  placeholder=""></td><td class="file-proof"><button type="button" data-idx="'+i+'" class="btn btn-success btn-sm addFile"><i class="fa fa-upload"></i></button><button type="button" data-idx="'+i+'" class="btn btn-success btn-sm addCamera"><i class="fa fa-camera"></i></button><input type="file" accept="image/*" name="file[]"  style="display: none;" class="file-input file'+i+'"><input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;"></td><td><div id="preview_'+i+'"></div></td><td><input type="text" class="form-control" name="remark[]" placeholder="Remark"></td><td><button  type="button" name="add" id="add" class="btn btn-danger full-width remove-item">-</button></td></tr>';
              
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
  
});
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  new Vue({
      el: '#app',
      data: {
          reimburses: [
            {
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
            }
          ],
          grandtotal: 0,
          start: null,
          end: null,
          user_id: null,
          status: null,
      },
      
      mounted() {
        
        // this.loadData()
        $('#show-data').on('change', () => {
          this.loadData(this.start, this.end, this.status, this.user_id);
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
        self.loadData(self.start,self.end,self.status, self.user_id);
        $("input.daterange").on('apply.daterangepicker', function(ev, picker) {
          var startDate = picker.startDate.format('YYYY-MM-DD');
          var endDate = picker.endDate.format('YYYY-MM-DD');
          self.start = startDate
          self.end = endDate
          console.log("Selected date range: " + startDate + ' to ' + endDate);
          self.loadData(startDate,endDate,self.status, self.user_id);
      });
        this.initSelectForm()
       
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
              window.open("{{url('/')}}/reimbursement-entertaiment-print?selected="+id+"&start="+this.start+"&end="+this.end+"&driver="+this.user_id+"&status="+this.status, "_blank")

          } else {

              var user_id = "{{auth()->user()->id}}";
              window.open("{{url('/')}}/reimbursement-entertaiment-print?start="+this.start+"&end="+this.end+"&driver="+user_id+"&status="+this.status, "_blank")
          }
          
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
          
        },
        loadData(start = null,end = null, status= null, driver= null) {
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
                        data: 'status_label',
                        name: 'status_label'
                      },
                      {
                        data: 'action',
                        name: 'action'
                      },

                ],
            });
        },
        calculate(el,item) {
          item.total = ((item.toll) ? parseInt(item.toll) : 0) + ((item.parking) ? parseInt(item.parking) : 0) + ((item.gasoline) ? parseInt(item.gasoline) : 0) + ((item.other) ? parseInt(item.other) : 0) 
          this.grandtotal = 0
          self = this
          this.reimburses.forEach(element => {
            self.grandtotal += parseInt(element.total)            
          });
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

            $('input.form-control').focus(function() {
                // Select all text inside the input field
                $(this).select();
            });

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
