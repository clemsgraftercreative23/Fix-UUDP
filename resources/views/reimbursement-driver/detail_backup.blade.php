@extends('template.app')
@section('content')

<style type="text/css">
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
<script src="https://cdn.rawgit.com/igorescobar/jQuery-Mask-Plugin/1ef022ab/dist/jquery.mask.min.js"></script>

<div class="page-content" id="app">

  <div class="row">
    <div class="col-xl">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">RINCIAN REIMBURSEMENT DRIVER</h5><hr>
                <p>Berikut merupakan data reimbursement yang diajukan oleh <b>{{$data->user->name}}</b>.</p><hr>
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
                <hr>
                <table border="0">
                      <thead>
                          <tr>
                              <td width="400px"><span style="color:#66da90;"><h4>Rincian Reimbursement</h4></span></td>
                              <td width="60px">Tanggal : </td>
                              <td width="240px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{$data->date}}"></td>
                              <td width="60px">Nomor : </td>
                              <td width="340px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{$data->no_reimbursement}}"></td>
                              <td width="60px">Total : </td>
                              <td width="340px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{number_format($data->nominal_pengajuan,0,',','.')}}"></td>
                          </tr>
                      </thead>
                  </table> 
                  <hr>
                  <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="inputEmail4">Mengetahui Head Department</label>
                            <input type="text" class="form-control" value="{{strtoupper($data->mengetahui_op)}}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="inputPassword4">Mengetahui HR GA</label>
                            <input type="text" class="form-control" value="{{strtoupper($data->mengetahui_finance)}}" readonly >
                        </div>
                        <div class="form-group col-md-4">
                            <label for="inputPassword4">Menyetujui Finance</label>
                            <input type="text" class="form-control" value="{{strtoupper($data->mengetahui_owner)}}" readonly >
                        </div>
                        <div class="form-group col-md-4">
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

                  <hr><span style="color:#66da90;"><h5>Rincian Reimbursement</h5></span><hr>
                  <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Toll</th>
                            <th>Parking</th>
                            <th>Gasoline</th>
                            <th>Other</th>
                            <th>Total</th>
                            <th>Remark</th>
                            <th>Attachment</th>
                        </tr>
                        </thead>
                        <?php $no=1;?>
                        @foreach($data->drivers as $row)
                        <tr>
                            <td width="1px">{{$no++}}</td>
                            <td width="200px"><span>{{number_format($row->toll,0,',','.')}}</span></td>
                            <td width="200px"><span>{{number_format($row->parking,0,',','.')}}</span></td>
                            <td width="200px"><span>{{number_format($row->gasoline,0,',','.')}}</span></td>
                            <td width="200px"><span>{{number_format($row->others,0,',','.')}}</span></td>
                            <td width="200px"><span>{{number_format($row->subtotal,0,',','.')}}</span></td>
                            <td width="200px"><span>{{$row->remark}}</span></td>
                            <td width="200px"><a href="{{ URL::to('/') }}/images/file_bukti/{{$row->evidence}}" target="_blank"><i class="fa fa-file"></i></a></td>

                        </tr>
                        @endforeach
                  </table>
                  
                    @if ($data->status == 5)
                    <hr><span style="color:#66da90;"><h5>Rincian Settlement</h5></span><hr>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Metode</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->metode_data->nama}}">
                            </div>
                        </div>
                        {{-- <div class="col-md-2">
                            <div class="form-group">
                                <label>Nama Rekening</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->sumber_data->nama_list}}">
                            </div>
                        </div> --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Nama Rekening</label>
                                <input readonly type="text" class="form-control" name="penerima" value="{{$data->penerima}}">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Nomor Rekening</label>
                                <input readonly type="text" class="form-control" name="no_rek" value="{{$data->no_rek}}">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Bank</label>
                                <input readonly type="text" class="form-control" name="bank" value="{{$data->bank}}">
                            </div>
                        </div>
                    </div>
                    @endif 

                  
                        <hr><br>
                        <center>
                            @if (auth()->user()->jabatan == 'karyawan')                                
                                <a href="{!!url('reimbursement-driver')!!}" class="btn btn-secondary">Kembali </a>&nbsp;&nbsp;
                            @endif
                            
                            
                            @if (auth()->user()->jabatan == 'Direktur Operasional') 
                                    <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                        @csrf
                                        @if ($data->status != 9)
                                            <a href="{!!url('reimbursement-driver')!!}" class="btn btn-secondary">Kembali</a>&nbsp;&nbsp;
                                        @endif
                                        @if($data->status == 0)
                                            @if($data->id_user != auth()->user()->id)
                                            <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                            @endif
                                        @endif
                                    </form>
                                
                            @endif

                            @if ($data->status == 9 && auth()->user()->id == $data->id_user) 
                                <!--<button type="button" class="btn btn-primary click-edit"  data-toggle="modal" data-target=".bd-example-modal-lg">Edit</button>-->
                                <button type="button" class="btn btn-primary click-edit"  data-toggle="modal" id="{{Request::segment(2)}}">Edit</button>
                                
                            @endif
                            
                            @if (auth()->user()->jabatan == 'Finance')                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <a href="{!!url('reimbursement-driver')!!}" class="btn btn-secondary">Kembali</a>&nbsp;&nbsp;
                                    @if($data->status == 1)
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                    @endif
                                </form>
                            @endif
                            
                            @if ($data->status == 2 && auth()->user()->jabatan == 'Owner')                                
                                <form action="{{url('/').'/reimbursement/approve/'.$data->id}}" method="POST">
                                    @csrf
                                    <a href="{!!url('reimbursement-driver')!!}" class="btn btn-secondary">Kembali</a>&nbsp;&nbsp;
                                    <button type="submit" class="btn btn-primary" name="finish_button" id="finish_button">Approve</button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReject" name="reject_button" id="reject_button">Reject</button>
                                </form>
                            @endif
                        </center>

            </div>
        </div>
    </div>
</div>
<div class="modal fade bd-example-modal-lg" id="formModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <form method="post" id="sample_form" action="{{url('/')."/reimbursement-driver/".$data->id}}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="deletedId" :model="deletedId">
      <div class="modal-dialog modal-xl">
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
   <input type="date" class="form-control date-picker" name="date" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{$data->date}}">
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
                      <input type="text" class="form-control date-picker" name="remark_parent" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{$data->remark}}" required>
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
                            <input type="hidden" name="id_detail[]" v-model="item.id">
                            <input type="text" class="form-control amount-toll click-form" @keyup="calculate(i, item)"  name="toll[]" v-model="item.toll" placeholder="Toll">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-parking click-form" @keyup="calculate(i, item)"  name="parking[]" v-model="item.parking" placeholder="Parking">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-gasoline click-form" @keyup="calculate(i, item)"  name="gasoline[]" v-model="item.gasoline" placeholder="Gasoline">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-other click-form" @keyup="calculate(i, item)"  name="others[]" v-model="item.others" placeholder="Other">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-total click-form" @keyup="calculate(i, item)"  name="total[]" readonly v-model="item.subtotal" placeholder="Total">
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
                            <input type="text" class="form-control" required name="remark[]" v-model="item.remark" placeholder="Remark">
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



<!-- Modal Edit-->
<div class="modal fade" id="formModaEdit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Insert Pertanggungjawaban</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <iframe src="https://sfi.uudp.app/get-reimbursement-driver/1"></iframe>  
        </div>
    </div>
</div>
<!-- End Modal Edit-->

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
                    <button class="btn btn-secondary" type="button" data-dismiss="modal" aria-label="Close">Back</button>
                    <button class="btn btn-primary" type="submit">Save</button>
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

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  
   $(document).ready(function(){
  
       $(".click-edit").click(function(){
            var id = $(this).attr('id');
            $('#formModaEdit').modal('show');
       });
        
    });

  new Vue({
      el: '#app',
      data: {
        start: null,
        end: null,
        employees: [],
        status: null,
        deletedId: [],
        user_id: null,
          reimburses: @json($data->drivers),
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
        
        $("input.daterange").on('apply.daterangepicker', function(ev, picker) {
          var startDate = picker.startDate.format('YYYY-MM-DD');
          var endDate = picker.endDate.format('YYYY-MM-DD');
          self.start = startDate
          self.end = endDate
          console.log("Selected date range: " + startDate + ' to ' + endDate);
      });
        this.initSelectForm()
        $(".number-format").maskMoney({ thousands:'.', decimal:',', precision:0});
        
        $(".amount-toll").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.amount-toll').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            this.reimburses[index].toll = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".amount-gasoline").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.amount-gasoline').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            this.reimburses[index].gasoline = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".amount-parking").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.amount-parking').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            this.reimburses[index].parking = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".amount-other").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.amount-other').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            this.reimburses[index].others = ($(event.target).val());
            self.changeAmount(0);
        });

      },
      methods : {
        searchStatus(){
          self = this
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
        reset(){
          this.status = null
          this.user_id = null
          var start = moment().startOf('month');
          var end = moment().endOf('month');
          this.start = start.format('YYYY-MM-DD');
          this.end = end.format('YYYY-MM-DD');

        },
        search(){
        },

        print(){
          window.open("{{url('/')}}/reimbursement-driver-print?start="+this.start+"&end="+this.end+"&driver="+this.user_id+"&status="+this.status, "_blank")
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
          })
        },
        
        calculate(el,item) {
          // this.reimburses[el].subtotal = ((item.toll) ? parseInt(item.toll.toString().replaceAll('.', '')) : 0) + ((item.parking) ? parseInt(item.parking.toString().replaceAll('.', '')) : 0) + ((item.gasoline) ? parseInt(item.gasoline.toString().replaceAll('.', '')) : 0) + ((item.other) ? parseInt(item.other.toString().replaceAll('.', '')) : 0) 
          // this.grandtotal = 0
          // self = this
          // this.reimburses.forEach(element => {
          //   self.grandtotal += parseInt(element.subtotal)            
          // });
          // $(".number-format").maskMoney({ thousands:'.', decimal:',', precision:0});
        },
        changeAmount(i) {
          subtotal = 0;
          self = this
          this.reimburses.forEach(element => {
            idx = self.reimburses.indexOf(element)
            item = self.reimburses[idx]
            self.reimburses[idx].subtotal = ((item.toll) ? parseInt(item.toll.toString().replaceAll('.', '')) : 0) + ((item.parking) ? parseInt(item.parking.toString().replaceAll('.', '')) : 0) + ((item.gasoline) ? parseInt(item.gasoline.toString().replaceAll('.', '')) : 0) + ((item.others) ? parseInt(item.others.toString().replaceAll('.', '')) : 0)
            self.reimburses[idx].subtotal = self.reimburses[idx].subtotal.toLocaleString('de-DE')
            subtotal += parseInt(self.reimburses[idx].subtotal.toString().replaceAll(".",""))

          });
          
          this.grandtotal = subtotal.toLocaleString('de-DE')
        },
        addReimbursement() {
          this.reimburses.push({
              id: null,
              toll: 0,
              parking: 0,
              gasoline: 0,
              others: 0,
              subtotal: 0,
              evidence: null
            })
            this.initSelectForm()
            $('input.form-control').focus(function() {
                $(this).select();
            });
            self = this

            this.$nextTick(() => {

              self.initSelectForm();

              $(".amount-toll").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-toll').on('change', (event) => {
                  const index = $(event.target).closest('tr').index();
                  this.reimburses[index].toll = ($(event.target).val());
                  self.changeAmount(index);
              });

              $(".amount-gasoline").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-gasoline').on('change', (event) => {
                  const index = $(event.target).closest('tr').index();
                  this.reimburses[index].gasoline = ($(event.target).val());
                  self.changeAmount(index);
              });

              $(".amount-parking").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-parking').on('change', (event) => {
                  const index = $(event.target).closest('tr').index();
                  this.reimburses[index].parking = ($(event.target).val());
                  self.changeAmount(index);
              });

              $(".amount-other").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-other').on('change', (event) => {
                  const index = $(event.target).closest('tr').index();
                  this.reimburses[index].others = ($(event.target).val());
                  self.changeAmount(index);
              });


            })
        },
        removeReimbursement(i) {
          if(this.reimburses[i].id != null){
            this.deletedId.push(this.reimburses[i].id)
          } else {
            this.deletedId.push("-")
          }
          this.reimburses.splice(i,1)
          self = this
          this.reimburses.forEach(element => {
            self.grandtotal += parseInt(element.subtotal)            
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
