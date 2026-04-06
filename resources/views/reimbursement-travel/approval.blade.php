@extends('template.app')

@section('content')

<div class="page-content" id="app">

<div class="clearfix">
   <a href="{!!url('reimbursement-travel')!!}" class="btn btn-success float-left" style="width: 48%;">My Inquiry</a>
   <a href="{!!url('reimbursement-travel-approval')!!}" class="btn btn-info float-right" style="width: 48%;">Approval</a>
</div>

<br>

<div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  <div class="col-md-4">
                  <div class="row"> 
                  <div class="col-sm-12">
                    <h2 class="card-title clr-green">Reimbursement Travel (Approval)</h2>
                  </div>
                  {{-- <div class="col-sm-6 text-left text-sm-right">
                     <p> Total Reimbursement: </p>
                     <label class="card-title" id="totalpengajuan" style="font-size:15px; color:#62d49e;">Rp 0</label>
                  </div> --}}
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
                          <select 
                            name="status" 
                            class="form-control select2 status"  
                            @change="searchStatus" 
                            v-model="status"
                          >
                            <option value="1">APPROVED HEAD DEPT</option>
                            <option value="2">APPROVED HR GA</option>
                            <option value="3">APPROVED FINANCE</option>
                            <option value="5">SETTLED</option>
                            <option value="9">REJECT</option>
                            <option value="0">PENDING</option>
                          </select>
                        </div>
                        
                        @if (auth()->user()->jabatan != "karyawan")
                          <div class="col-md-2 mb-3">
                            <label for="user_id">Employee</label>
                            <select 
                              name="user_id" 
                              class="form-control select2 employee" 
                              @change="searchDriver" 
                              v-model="user_id"
                            >
                              <option v-for="item in employees" :value="item.id">
                                @{{ item.name }}
                              </option>
                              {{-- @foreach ($driver as $item)
                                <option value="{{$item->id}}">{{$item->name}}</option>
                              @endforeach --}}
                            </select>
                          </div>
                        @endif
                        
                        <div class="col-md-3 mb-3">
                          <label for="daterange">Period</label>
                          <input 
                            type="text" 
                            name="daterange" 
                            class="form-control daterange"
                          />
                        </div>
                        
                        <div class="col-md-5 mb-3">
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <button class="btn btn-primary d-block" @click="search()" style="margin-top:32px" title="Search"><i class="fa fa-search"></i></button>
                                <button class="btn btn-primary d-block" @click="reset()" style="margin-top:32px" title="Reset"><i class="fa fa-fas fa-sync-alt fa-fw"></i></button>
                                @if(auth()->user()->jabatan!='karyawan')
                                <button class="btn btn-primary btn-approve" @click="approve()" style="margin-top:32px" title="Approve"><i class="fa fa-check"></i></button>
                                @endif
                                <button class="btn btn-primary d-block" @click="print()" style="margin-top:32px" title="Print"><i class="fa fa-print"></i></button>
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
                              <th>Type</th>
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
  <div class="modal fade bd-example-modal-lg" id="formModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <form method="post" id="sample_form" action="{{url('/')."/reimbursement-driver"}}" enctype="multipart/form-data">
    @csrf
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header border-bottom"  >
              <div class="d-flex justify-content-between w-100">
                    <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle">Buat Reimbursement UUDP</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
                </div>
              </div>

              <div class="modal-body py-3">
              <div class="row my-3"> 
                
                  <div class="col-md-4">
                    <div class="form-group">
   <label for="exampleFormControlInput1">Nama Lengkap</label>
   <input type="hidden" name="id_user" value="{{Auth::user()->id}}">
   <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" placeholder="Nama Lengkap" value="{{Auth::user()->name}}">
 </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
   <label for="exampleFormControlInput1">NIK Karyawan</label>
   <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" value="{{Auth::user()->idKaryawan}}">
 </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
   <label for="exampleFormControlInput1">Tanggal</label>
   <input type="date" class="form-control date-picker" name="date" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{date('Y-m-d')}}">
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
                      <input type="text" class="form-control date-picker" name="remark_parent" id="exampleFormControlInput1" style="border-radius: 10px;" value="">
                    </div>
                  </div>

                  <hr>
               
                </div>
                <label class="modal-title clr-green" id="exampleModalCenterTitle">Rincian Reimbursement</label>
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
                          <th align="center" width="100px">Bukti</th>
                          <th align="center" >Remark</th>
                          <th align="center" >Action</th>

                      </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item,i) in reimburses">
                      <td>@{{i + 1}}</td>
                          <td>
                            <input type="text" class="form-control" @change="calculate(this, item)" name="toll[]" v-model="item.toll" placeholder="Toll">
                          </td>
                          <td>
                            <input type="text" class="form-control" @change="calculate(this, item)" name="parking[]" v-model="item.parking" placeholder="Parking">
                          </td>
                          <td>
                            <input type="text" class="form-control" @change="calculate(this, item)" name="gasoline[]" v-model="item.gasoline" placeholder="Gasoline">
                          </td>
                          <td>
                            <input type="text" class="form-control" @change="calculate(this, item)" name="other[]" v-model="item.other" placeholder="Other">
                          </td>
                          <td>
                            <input type="text" class="form-control" @change="calculate(this, item)" name="total[]" readonly v-model="item.total" placeholder="Total">
                          </td>
                          
                          <td class="file-proof">
                            <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                              <i class="fa fa-upload"></i>
                            </button>
                            
                            <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" >
                              <i class="fa fa-camera"></i>
                            </button>
                            <input type="file" accept="image/*" name="file[]"  style="display: none; " class="file-input">
                            <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                            <div id="preview_1"></div>
                          </td>
                          
                          <td>
                            <input type="text" class="form-control" name="remark[]" v-model="item.remark" placeholder="Remark">
                          </td>
                          <td>
                            <button v-if="i == 0" @click="addReimbursement" type="button" name="add" id="add" class="btn btn-success full-width">+</button>
                            <button v-if="i > 0" @click="removeReimbursement(i)" type="button" name="remove" id="remove" class="btn btn-danger full-width">-</button>
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
              <div class="modal-footer">
                  <button type="button" class="btn btn-danger" data-dismiss="modal">BATALKAN</button>
                  <button   class="btn btn-primary" type="submit">AJUKAN SEKARANG</button>
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

<div id="loadingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.7); color: #fff; padding: 20px; border-radius: 10px; font-size: 16px;">
    Processing... Please wait.
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
            <canvas id="canvas"></canvas>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button id="captureButton" class="btn btn-success">Capture Image</button>
            </div>
      </div>
  </div>
  </div>

<!-- End Modal -->

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js" charset="utf-8"></script>
<script type="text/javascript">
(function () {
    window.syncBulkApproveVisibility = function () {
        var status = $('select[name="status"]').val();
        var show = false;
        @if(Auth::user()->jabatan=='Direktur Operasional')
          show = (status == '9' || status == '0');
        @elseif(Auth::user()->jabatan=='Finance')
          show = (status == '1');
        @elseif(Auth::user()->jabatan=='Owner')
          show = (status == '2');
        @elseif(Auth::user()->jabatan=='superadmin')
          show = (status == '0' || status == '1' || status == '2');
        @endif
        if (show) { $('.btn-approve').show(); } else { $('.btn-approve').hide(); }
    };
})();
$(document).ready(function(){
    @if(Auth::user()->status_password != 1)
        $('#modalPassword').modal('show');
    @endif

    $("#checkAll").click(function(){
        $('input:checkbox').not(this).prop('checked', this.checked);
    });

    $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:0});
    // $('#sum').maskMoney({ thousands:'.', decimal:',', precision:0});

    $('select[name="status"]').on('change', function(){
        var status = $(this).val();
        window.syncBulkApproveVisibility();
        if(status) {
            $.ajax({
                url: 'reimbursement-user?status='+status+'&reimbursement_type=2',
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

    window.syncBulkApproveVisibility();

  });
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  new Vue({
      el: '#app',
      data: {
        start: null,
        end: null,
        status: null,
        user_id: null,
        employees: [],
          reimburses: [
            {
              id: null,
              toll: null,
              parking: null,
              gasoline: null,
              other: null,
              total: null,
              remark: null,
              evidence: null
            }
          ],
          grandtotal: 0
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
        this.$nextTick(function () {
          if (typeof window.syncBulkApproveVisibility === 'function') {
            window.syncBulkApproveVisibility();
          }
        });
       
      },
      methods : {
          searchStatus(){
          let self = this;
          this.$nextTick(() => {
            if (typeof window.syncBulkApproveVisibility === 'function') {
              window.syncBulkApproveVisibility();
            }
          });
          $.ajax({
            url: 'reimbursement-user?status='+self.status+'&reimbursement_type=2',
            methods: 'GET',
            success: function(e) {
              console.log(e)
              
              self.employees = e.data
            }
          })

        },
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
          if (status==null) {
            alert('Status cannot be empty');
            return false;
          }

          var employee = $('.employee').val();
          if (employee=="" || employee==null) {
            alert('Employee cannot be empty');
            return false;
          }

          // Ambil semua nilai checkbox yang diceklis
          var selectedValues = [];
          $('.check-print:checked').each(function(){
              selectedValues.push($(this).val());
          });

          if(selectedValues.length > 0){
              var id = selectedValues.join(",");
              window.open("{{url('/')}}/reimbursement-travel-print?selected="+id+"&start="+this.start+"&end="+this.end+"&driver="+this.user_id+"&status="+this.status, "_blank")

          } else {

              var user_id = $('.employee').val();
              window.open("{{url('/')}}/reimbursement-travel-print?start="+this.start+"&end="+this.end+"&driver="+user_id+"&status="+this.status, "_blank")
          }

        },
        approve(){

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

          var id = selectedValues.join(",");
          if (id==0) {
            alert('Please check the data first !');
            return false;
          }

          $('#loadingIndicator').show();

          $.ajax({
            url: '{{url("/")}}/reimbursement-travel/approve_multiple/'+id+'',
            methods: 'GET',
            success: function(e) {
              alert('Status updated & WA notification sent');
              location.reload();
              
            },
            complete: function() {
                $('#loadingIndicator').hide();
            }
          })
          
        },
        initSelectForm() {
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
        loadData(first = null,end = null,status = null, user = null) {
    
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
              url:'{{ url("reimbursement-travel-approval") }}',
              data:{
                first:first,
                last:end,
                status:status,
                user_id:user,
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
                          data: 'travel_type',
                          name: 'travel_type',
                          render: function(data) {
                              return data === 'Domestic' 
                                  ? 'Local' 
                                  : data === 'Overseas' 
                                      ? 'Expatriate' 
                                      : data;
                          }
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
              toll: null,
              parking: null,
              gasoline: null,
              other: null,
              total: null,
              evidence: null
            })
            this.initSelectForm()
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
