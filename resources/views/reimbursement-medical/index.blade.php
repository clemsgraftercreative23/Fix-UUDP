@extends('template.app')

@section('content')

<div class="page-content" id="app">

<div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  <div class="col-md-4">
                  <div class="row"> 
                  <div class="col-sm-12">
                    <h2 class="card-title clr-green">Reimbursement Medical</h2>
                  </div>
                  {{-- <div class="col-sm-6 text-left text-sm-right">
                     <p> Total Reimbursement: </p>
                     <label class="card-title" id="totalpengajuan" style="font-size:15px; color:#62d49e;">Rp 0</label>
                  </div> --}}
                </div>
                  </div>
                  <div class="col-md-12">
                    <div class="row">
                      
                    <div class="col-md-2 mb-3">
                      <label for="">Status</label>
                      <select name="status" class="form-control select2"  @change="searchStatus" id="" v-model="status">
                        <option value="ALL">ALL</option>
                        <option value="1">APPROVED DIROPS</option>
                        <option value="2">APPROVED FINANCE</option>
                        <option value="3">APPROVED OWNER</option>
                        
                        <option value="5">SETTLED</option>
                        <option value="9">REJECT</option>
                        <option value="0">PENDING</option>
                      </select>
                    </div>
                    @if (auth()->user()->jabatan != "karyawan")
                        
                    <div class="col-md-3 mb-3">
                      <label for="">Employee</label>
                      <select name="user_id" @change="searchDriver" class="form-control select2" id="" v-model="user_id">
                        <option v-for="item in employees" :value="item.id">@{{item.name}}</option>
                        {{-- @foreach ($driver as $item)
                          <option value="{{$item->id}}">{{$item->name}}</option>
                        @endforeach --}}
                      </select>
                    </div>
                    @endif
                    <div class="col-md-3 mb-3">
                      <label for="">Period</label>
                      <input type="text" name="daterange" class="form-control daterange"/>
                    </div>
                    
                    <div class="col-md-1 mb-3">
                      <label for="" style="color: #fff">&nbsp;Action</label>
                      <button class="btn btn-primary d-block" @click="search()"><i class="fa fa-search"></i></button>
                    </div>
                    <div class="col-md-1 mb-3">
                      <label for="" style="color: #fff">&nbsp;Action</label>
                      <button class="btn btn-primary d-block" @click="print()"><i class="fa fa-print"></i></button>
                    </div>
                    <div class="col-md-2 col-sm-4 text-left text-sm-right mb-3 ">
                      <label for="" style="color: #fff">&nbsp;Action</label>
  
                         <button type="button" class="btn btn-primary btn-sm  w-100"   data-toggle="modal" data-target=".bd-example-modal-lg"><i class="fa fa-plus-circle" aria-hidden="true"></i> Create Inquiry</button>
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
                              <th>Inquiry No</th>
                              <th>Inquiry Date</th>
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
    <form method="post" id="sample_form" action="{{url('/')."/reimbursement-medical"}}" enctype="multipart/form-data">
    @csrf
      <div class="modal-dialog modal-xxl" style="max-width: 80% !important">
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
   <label for="exampleFormControlInput1">Employee ID</label>
   <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" value="{{Auth::user()->idKaryawan}}">
 </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
   <label for="exampleFormControlInput1">Check Up Date</label>
   <input type="date" class="form-control date-picker" name="date" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{date('Y-m-d')}}">
 </div>
                  </div>
                  
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Department</label>
                      <select name="reimbursement_department_id" id="" class="form-control">
                        @foreach (\App\Departemen::get() as $item)
                            <option value="{{$item->id}}">{{$item->nama_departemen}}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Status</label>
                      <select name="status_employee" id="" class="form-control">
                        <option value="1">Employee</option>
                        <option value="2">Wife/Husband</option>
                        <option value="3">Child</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Remark</label>
                      <input type="text" class="form-control" name="remark_parent" id="exampleFormControlInput1" style="border-radius: 10px;" value="">
                    </div>
                  </div>

                  <hr>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Patient Name</label>
                      <input type="text" class="form-control" name="patient_name" id="exampleFormControlInput1" style="border-radius: 10px;" value="">
                    </div>
                  </div>

                  
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Diagnose</label>
                      <input type="text" class="form-control" name="diagnose" id="exampleFormControlInput1" style="border-radius: 10px;" value="">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="">Attachment</label>
                      <br>
                      <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                        <i class="fa fa-upload"></i>
                      </button>
                      
                      <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" >
                        <i class="fa fa-camera"></i>
                      </button>
                      <input type="file" accept="image/*" :name="'file'"  style="display: none; " class="file-input">
                      <input type="file" accept="image/*" :name="'proof'" capture="camera" class="camera-input" style="display: none;">
                      <div id="preview_1"></div>
                    </div>
                  </div>

                  <hr>
               
                </div>
                <label class="modal-title clr-green" id="exampleModalCenterTitle">Medical Detail</label>
<div class="respon respon-big table-responsive">
                <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3
            align=center width="1400">
                  <thead>
                      <tr>
                          <th align="center" width="50">No.</th>
                          <td>Type</td>
                          <td>Name</td>
                          <td>Address</td>
                          <td>Contact Name</td>
                          <td>Phone</td>
                          <th align="center" >Action</th>
                      </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item,i) in details">
                      <td>@{{i + 1}}</td>
                          <td>
                            <select :name="'details['+i+'][type]'" id="" class="form-control" v-model="item.type">
                              <option value="HOSPITAL">HOSPITAL</option>
                              <option value="CLINIC">CLINIC</option>
                              <option value="PHARMACY">PHARMACY</option>
                            </select>
                          </td>
                          <td>
                            <input type="text" class="form-control" :name="'details['+i+'][name]'" v-model="item.name" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control" :name="'details['+i+'][address]'" v-model="item.address" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control" :name="'details['+i+'][pic]'" v-model="item.pic" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control" :name="'details['+i+'][phone]'" v-model="item.phone" placeholder="">
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
                <label class="modal-title clr-green" id="exampleModalCenterTitle">Expense Detail</label>
<div class="respon respon-big table-responsive">
                <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3
            align=center width="1400">
                  <thead>
                      <tr>
                          <th align="center" width="50">No.</th>
                          <td>Name</td>
                          <td>Amount</td>
                          <th align="center" >Action</th>
                      </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item,i) in expenses">
                      <td>@{{i + 1}}</td>                          
                          <td>
                            <input type="text" class="form-control" :name="'expenses['+i+'][name]'" v-model="item.name" placeholder="">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-input" :name="'expenses['+i+'][amount]'" v-model="item.amount" placeholder="">
                          </td>                         
                          <td>
                            <button v-if="i == 0" @click="addExpense" type="button" name="add" id="add" class="btn btn-success full-width">+</button>
                            <button v-if="i > 0" @click="removeExpense(i)" type="button" name="remove" id="remove" class="btn btn-danger full-width">-</button>
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
$(document).ready(function(){
    @if(Auth::user()->status_password != 1)
        $('#modalPassword').modal('show');
    @endif

    $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:0});
    // $('#sum').maskMoney({ thousands:'.', decimal:',', precision:0});



  });
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  new Vue({
      el: '#app',
      data: {
          details: [
            {
              id: null,
              name: null,
              address: null,
              pic: null,
              phone: null,
            }
          ],
          expenses: [
            {
              id: null,
              name: null,
              amount: null,
            }
          ],
          grandtotal: 0,
          start: null,
          employees: [],
          end: null,
          user_id: null,
          status: null,
      },
      mounted() {
        this.initSelectForm()
        
        var start = moment().startOf('month');
        this.start = start;
        this.end = end;
        var end = moment().endOf('month');
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
        $("input.daterange").on('apply.daterangepicker', function(ev, picker) {
          var startDate = picker.startDate.format('YYYY-MM-DD');
          var endDate = picker.endDate.format('YYYY-MM-DD');
          self.start = startDate
          self.end = endDate
          console.log("Selected date range: " + startDate + ' to ' + endDate);
        })
        this.loadData()
        $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0});
        $(".number-format").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.amount-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            this.expenses[index].amount = ($(event.target).val());
            self.changeAmount(0);
        });
      },
      methods: {
        searchStatus(){
          self = this
          // this.loadData(this.start,this.end,this.status, this.user_id);
          $.ajax({
            url: `{{url("/")}}/reimbursement-user?status=${self.status}&reimbursement_type=1`,
            methods: 'GET',
            success: function(e) {
              console.log(e)
              
              self.employees = e.data
            }
          })

        },
        searchDriver(){

        },
        search(){
          this.loadData(this.start,this.end,this.status, this.user_id);
        },
        changeAmount(i){
          subtotal = 0;
          this.expenses.forEach(element => {
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
        loadData() {
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
              url:'{{ url("reimbursement-medical") }}',
              // data:{first:first,last:last}
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
          item.total = ((item.toll) ? parseInt(item.toll) : 0) + ((item.parking) ? parseInt(item.parking) : 0) + ((item.gasoline) ? parseInt(item.gasoline) : 0) + ((item.other) ? parseInt(item.other) : 0) 
          this.grandtotal = 0
          self = this
          this.reimburses.forEach(element => {
            self.grandtotal += parseInt(element.total)            
          });
        },
        addReimbursement() {
          this.details.push({
              id: null,
              name: null,
              address: null,
              pic: null,
              phone: null,
            })
            this.initSelectForm()

            $('input.form-control').focus(function() {
                // Select all text inside the input field
                $(this).select();
            });

            self = this

            this.$nextTick(() => {
              self.initSelectForm();
            })
        },
        removeReimbursement(i) {
          this.details.splice(i,1)
          self = this
          
        },
        addExpense() {
          this.expenses.push({
              name: null,
              amount: null,
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
              $(".number-format").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-input').on('change', (event) => {
                  const index = $(event.target).closest('tr').index();
                  this.expenses[index].amount = ($(event.target).val());
                  self.changeAmount(0);
              });
            })
        },
        removeExpense(i) {
          this.expenses.splice(i,1)
          self = this
          subtotal = 0;
          this.expenses.forEach(element => {
            subtotal += parseInt(element.amount.replaceAll(".",""))            
          });
          self.grandtotal = subtotal.toLocaleString('de-DE')
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
