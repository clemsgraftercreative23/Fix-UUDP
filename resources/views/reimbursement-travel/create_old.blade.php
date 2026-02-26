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
</style>

<div class="page-content" id="app">
<div class="">
    <form action="{{route('reimbursement-travel.store')}}" method="POST" enctype="multipart/form-data" style="overflow-y: auto">
        @csrf 
        <div class="row">
            <div class="col-xl">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">REIMBURSEMENT UUDP - TRAVEL ( Domestic )</h5>
                        {{-- <p>Here’s a quick example to demonstrate Bootstrap’s form styles. </p> --}}
                        <input type="hidden" name="travel_type" value="Domestic">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="">Employee</label>
                                <input type="text" class="form-control" readonly value="{{auth()->user()->name}}">
                            </div>
                            <div class="col-md-3">
                                <label for="">Apply Date</label>
                                <input type="text" class="form-control" readonly value="{{date('d F Y')}}">
                            </div>
                            <div class="col-md-3">
                                <label for="">Remark</label>
                                <input type="text" class="form-control" name="remark" value="" required>
                            </div>   
                            <div class="col-md-3">
                            <div class="form-group">
                                <label for="exampleFormControlInput1">Department</label>
                                <select name="reimbursement_department_id" id="" class="form-control">
                                    @foreach (\App\Departemen::get() as $item)
                                        <option value="{{$item->id}}" @if(auth()->user()->departmentId == $item->id) selected @endif>{{$item->nama_departemen}}</option>
                                    @endforeach
                                </select>
                                </div>
                            </div>                  
                        </div>
                        <hr>
                        <div v-for="(dt,i) in rates" class="row">
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="">Currency</label>
                                        <input type="text" class="form-control" :name="'rates['+i+'][code]'" v-model="dt.code">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="">Exchange Rate</label>
                                        <input type="text" class="form-control" :name="'rates['+i+'][rate]'" v-model="dt.rate">
                                    </div>
                                </div>
                            </div>                 
                        </div>
                        <hr>
                        <!--<div class="row">-->
                        <!--    <div class="col-md-12">-->
                        <!--        <div class="row fieldGroup">-->
                        <!--            <div class="col-md-3">-->
                        <!--                <label for="">Currency</label>-->
                        <!--                <input type="text" class="form-control" name="currency_rate[]" value="" required>-->
                        <!--            </div>-->
                        <!--            <div class="col-md-6">-->
                        <!--                <label for="">Exchange Rate</label>-->
                        <!--                <input type="text" class="form-control currency" name="rate[]" required>-->
                        <!--            </div>-->
                        <!--            <div class="col-md-3">-->
                        <!--                <a class="btn btn-primary btn-sm addMore" @click="addRate" style="color:white;margin-top:35px;cursor:pointer"><i class="fa fa-plus"></i></a>-->
                        <!--            </div>-->
                        <!--        </div>-->
                        <!--        <br>-->
                        <!--    </div>                 -->
                        <!--</div>-->
                        <br>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary text-right" @click="addRate"><i class="fa fa-plus"></i> Add Rate</button>

                            </div>
                        </div>
                       
                    </div>
                </div>
            </div>
        </div>
        <!--<div class="row">-->
        <!--    <div class="col-xl text-right">-->
        <!--        <button type="button" class="btn btn-primary text-right" @click="addTravel"><i class="fa fa-plus"></i> Add New</button>-->
        <!--    </div>-->
        <!--</div>-->
        <br>
        <div class="row" v-for="(data,i) in reimburses">
            <div class="col-xl">
                <div class="card">
                    <div class="card-body">
                        
                        <!--<div class="row">-->
                        <!--    <div class="col-xl text-right">-->
                        <!--        <button class="btn btn-danger text-right" @click="removeTravel(i)"><i class="fa fa-trash"></i> Remove</button>-->
                        <!--    </div>-->
                        <!--</div>-->
                        {{-- <p>Here’s a quick example to demonstrate Bootstrap’s form styles. </p> --}}
                        <div class="row">
                            <div class="col-md-3">
                                <label for="">Transaction Date</label>
                                <input type="date" :name="'reimburse['+i+'][date]'" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label for="">Purpose</label>
                                <input type="text" :name="'reimburse['+i+'][purpose]'" class="form-control" required value="">
                            </div>
                            <div class="col-md-3">
                                <label for="">Trip Type</label>
                                <select :name="'reimburse['+i+'][trip_type_id]'" id="" class="form-control" v-model="data.trip" @change="changeTrip(i)">
                                    <option value="" selected disabled>Pilih...</option>
    
                                    @foreach ($trip_types as $item)
                                        <option value="{{$item->id}}" data-allowance="{{$item->allowance}}">{{$item->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="">Hotel </label>
                                <select :name="'reimburse['+i+'][hotel_condition_id]'" id="" class="form-control">
                                    <option value="" selected disabled>Pilih...</option>
                                    @foreach ($hotel_conditions as $item)
                                        <option value="{{$item->id}}" data-allowance="{{$item->allowance}}">{{$item->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="">Start</label>
                                <input type="time" :name="'reimburse['+i+'][start_time]'" @change="changeTime(i)" v-model="data.start_time" class="form-control" value="">
                            </div>  
                            
                            <div class="col-md-3">
                                <label for="">Arrival</label>
                                <input type="time" :name="'reimburse['+i+'][end_time]'" @change="changeTime(i)" v-model="data.end_time" class="form-control" value="">
                            </div>    
                            
                            <div class="col-md-3">
                                <label for="">Allowance</label>
                                <input type="text" :name="'reimburse['+i+'][allowance]'" readonly class="form-control number-format" v-model="data.trip_allowance" value="">
                            </div>    
                            
                            <div class="col-md-3">
                                <label for="">Travel Time</label>
                                <input type="text" :name="'reimburse['+i+'][travel_time]'" readonly class="form-control" v-model="data.travel_time" value="">
                            </div>                     
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xl">
                                <div class="table-responsive">
                                    <table class="table full-width" style="width: 100%">
                                        <thead style="width: 100%">
                                            <tr>
                                                <th width="200">Cost Type</th>
                                                <th>Destination</th>
                                                <th>Currency</th>
                                                <th>Amount</th>
                                                <th>IDR Rate</th>
                                                <th>Pph23</th>
                                                <th>Payment</th>
                                                <th width="200">File</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(dt,a) in data.details">
                                                <td>
                                                    <select :name="'reimburse['+i+'][detail]['+a+'][cost_type_id]'" id="" class="form-control" v-model="dt.cost_type" @change="changeCost(i,a)">
                                                        <option value="" selected disabled>Pilih...</option>
                                                        @foreach ($types as $item)
                                                            <option value="{{$item->id}}" data-type="{{$item->type}}">{{$item->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" :name="'reimburse['+i+'][detail]['+a+'][destination]'">
                                                </td>
                                                <td>
                                                    <select  :name="'reimburse['+i+'][detail]['+a+'][currency]'" class="form-control" id="" v-model="dt.currency">
                                                        <option v-for="dt in rates" :value="dt.code">@{{dt.code}}</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control amount-input" @change="calculateTotal(i,a)" :name="'reimburse['+i+'][detail]['+a+'][amount]'" v-model="dt.amount">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control number-format" readonly :name="'reimburse['+i+'][detail]['+a+'][idr_rate]'" v-model="dt.idr_rate">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control number-format" readonly :name="'reimburse['+i+'][detail]['+a+'][tax]'" v-model="dt.tax">
                                                </td>
                                                <td>
                                                    <select :name="'reimburse['+i+'][detail]['+a+'][payment_type]'" id="" class="form-control" v-model="dt.payment_type"required>
                                                        <option value="" selected disabled>Select...</option>
                                                        <option value="BDC">BDC</option>
                                                        <option value="Cash">Cash</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                                                        <i class="fa fa-upload"></i>
                                                      </button>
                                                      
                                                      <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" >
                                                        <i class="fa fa-camera"></i>
                                                      </button>
                                                      <input type="file" accept="image/*" :name="'reimburse['+i+'][detail]['+a+'][file]'"  style="display: none; " class="file-input">
                                                      <input type="file" accept="image/*" :name="'reimburse['+i+'][detail]['+a+'][proof]'" capture="camera" class="camera-input" style="display: none;">
                                                      <div id="preview_1"></div>
                                                </td>
                                                <td>
                                                    <button type="button" v-if="a == 0" @click="addDetail(i)" class="btn btn-success">+</button>
                                                    <button type="button" v-if="a > 0" @click="removeDetail(i,a)" class="btn btn-danger">-</button>
                                                </td>                                                                         
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="">Total</label>
                                <input type="text" :name="'reimburse['+i+'][total]'" v-model="data.total" readonly class="form-control" value="">
                            </div>  
                            <div class="col-md-9">
                                <span style="color:#62d49e;float:right;" class="warning-upload"><br>The button is disabled until a file is uploaded.<br><br><br></span>
                            </div>
                                       
                        </div>

                         
                        <div class="col-xl text-right">
                            <a class="btn btn-secondary text-right" href="{{route('reimbursement-travel.index')}}"><i class="fa fa-save"></i> BACK</a>&nbsp;&nbsp;&nbsp;
                            <button class="btn btn-primary" type="submit" id="action_button" name="save">SUBMIT</button>
                            <button class="btn btn-warning" type="submit" id="action_button_draft" name="save_draft">DRAFT</button>
                        </div>
                        <br><br>
                       
                    </div>
                </div>

            </div>

        </div>
        
    </form>

</div>
</div>

<!-- End Modal -->

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
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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

    $('.nominal_pengajuan').maskMoney({ thousands:'.', decimal:',', precision:0});   
    
    var maxGroup = 10;
    var i = 1;
    
    $("#action_button").prop("disabled", true);
    $(".warning-upload").show();

  });
</script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
  
  new Vue({
      el: '#app',
      data: {
        usd_rate: 0,
        idr_rate: 0,
        jpy_rate: 0,
        reimburses: [
            {
                trip: null,
                trip_allowance: null,
                travel_time: null,
                start_time: null,
                end_time: null,
                details: [
                    {
                        cost_type: null,
                        destination: null,
                        currency: null,
                        amount: null,
                        tax: null,
                        idr_rate: null,
                        code: null,
                    }
                ],
                total: 0
            },
        ],
        rates: [
            {
                code: 'IDR',
                rate: 1
            }
        ],
        types : @json($types),
        trip_types : @json($trip_types),
        grandtotal: 0
      },
      mounted() {
        this.initSelectForm()
        self = this
        $(".idr-rate-input").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.idr-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.idr_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".usd-rate-input").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.usd-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.usd_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".jpy-rate-input").maskMoney({ thousands:'.', decimal:',', precision:0});
        $('.jpy-rate-input').on('change', (event) => {
            const index = $(event.target).closest('tr').index();
            self.jpy_rate = ($(event.target).val());
            self.changeAmount(0);
        });

        $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0});
            $('.amount-input').on('change', (event) => {
            self.reimburses[self.reimburses.length - 1].details[0].amount = ($(event.target).val());
            self.changeAmount(0);
            self.calculateTotal(0,0)
        });
        // $('.number-format').maskMoney({ thousands:'.', decimal:',', precision:0});
      
      },
      methods : {
        changeAmount(i) {

        },
        getRate(currency, amt) {
            self = this;
            try {
                rate = self.rates.filter(a => a.code == currency)[0].rate
            } catch (error) {
                if(currency == "IDR")
                    rate = 1
            }
            return parseInt(amt.replaceAll(".","")) * parseInt(`${rate}`.replaceAll(".",""));
             
        },
        initSelectForm() {
          $(".addFile").on('click',function(){
            $(this).parent().find(".file-input").click();
            $(this).parent().find(".file-input").change(function(event) {
                var file = event.target.files[0];
                
                if (file) {
                    var reader = new FileReader();
                    $("#action_button").prop("disabled", false);
                    $(".warning-upload").hide();
                    
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
            // $(".addCamera").on('click',function(){
            // idx = $(this).data('idx')
            // fileInput = $(this).parent().find(".file-input")[0]; 
            // $("#modalPhoto").modal('show')
            // const videoElement = $('#videoElement')[0];
            // const canvas = $('#canvas')[0];
            // const context = canvas.getContext('2d');

            // // Access the webcam
            // if (navigator.mediaDevices.getUserMedia) {
            //     navigator.mediaDevices.getUserMedia({ video: {
            //         facingMode: { ideal: "environment" }
            //     } })
            //         .then(function(stream) {
            //             videoElement.srcObject = stream;
            //             $('#captureButton').on('click', function() {
            //                 canvas.width = videoElement.videoWidth * 1;
            //                 canvas.height = videoElement.videoHeight * 1;
            //                 context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            //                 canvas.toBlob(function(blob) {
            //                     const file = new File([blob], "capture.png", { type: "image/png" });

            //                     // Display the captured image in the preview div
            //                     const dataURL = URL.createObjectURL(file);
                            
            //                     // Create a DataTransfer to add the file to the input element
            //                     const dataTransfer = new DataTransfer();
            //                     dataTransfer.items.add(file);
            //                     fileInput.files = dataTransfer.files;
            //                     console.log(fileInput)
            //                 }, 'image/png'); 
                            
            //                 stream.getTracks().forEach(function(track) {
            //                     return track.stop();
            //                 });
            //                 $("#modalPhoto").modal('hide')
            //                 $("#action_button").prop("disabled", false);
            //                 $(".warning-upload").hide();

            //             });
            //         })
            //         .catch(function(err) {
            //             console.error("Error accessing webcam: " + err);
            //         });
            // }
            
            // })

            $(".addCamera").on('click',function(){
                idx = $(this).data("idx");
                fileInput = $(this).parent().find(".file-input")[0];
                $("#modalPhoto").modal("show");
                const videoElement = $("#videoElement")[0];

                // Akses webcam
                if (navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices
                        .getUserMedia({
                            video: {
                                facingMode: { ideal: "environment" },
                            },
                        })
                        .then(function (stream) {
                            videoElement.srcObject = stream;

                            // Tombol Capture
                            $("#captureButton").off("click").on("click", function () {
                                const canvas = document.createElement("canvas");
                                const context = canvas.getContext("2d");

                                canvas.width = videoElement.videoWidth;
                                canvas.height = videoElement.videoHeight;

                                context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                                canvas.toBlob(
                                    function (blob) {
                                        const file = new File([blob], "capture.png", { type: "image/png" });

                                        // Tambahkan file ke input
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(file);
                                        fileInput.files = dataTransfer.files;
                                        console.log(fileInput);
                                    },
                                    "image/png"
                                );

                                // Matikan stream kamera
                                stream.getTracks().forEach(function (track) {
                                    track.stop();
                                });

                                $("#modalPhoto").modal("hide");
                                $("#action_button").prop("disabled", false);
                                $(".warning-upload").hide();
                            });
                        })
                        .catch(function (err) {
                            console.error("Error accessing webcam: " + err);
                        });
                }
            })
        },
        changeTrip(i) {
            id = this.reimburses[i].trip
            self = this
            // alert(self.trip_types.filter(a => a.id == id)[0].allowance)
            this.reimburses[i].trip_allowance = self.trip_types.filter(a => a.id == id)[0].allowance.toLocaleString('de-DE')
            this.calculateTotal(i,0)
        },
        changeTime(i) {

            // Get the input values
            data = this.reimburses[i]
            let time1 = data.start_time;
            let time2 = data.end_time;

            // Parse the input values to Date objects (using a dummy date)
            let date1 = new Date('1970-01-01T' + time1 + 'Z');
            let date2 = new Date('1970-01-01T' + time2 + 'Z');

            // Calculate the difference in milliseconds
            let timeDifference = Math.abs(date2 - date1);

            // Convert the difference to hours and minutes
            let hoursDifference = Math.floor(timeDifference / 1000 / 60 / 60);
            let minutesDifference = Math.floor((timeDifference / 1000 / 60) % 60);

            // Display the difference
            let differenceMessage = `Time difference: ${hoursDifference} hours and ${minutesDifference} minutes.`;
            this.reimburses[i].travel_time = `${hoursDifference} Hours and ${minutesDifference} minutes.`;
        },
        addRate() {
            this.rates.push({
                code: null,
                rate: null
            });
            
            
        },
        addTravel() {
            this.reimburses.push({
                trip_allowance: null,
                travel_time: null,
                details: [
                    {
                        cost_type: null,
                        destination: null,
                        currency: null,
                        amount: null,
                        tax: null,
                        code: null,
                        idr_rate: null,
                    }
                ],
                total: 0
            });
            self = this
            this.$nextTick(() => {
              self.initSelectForm();

              $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-input').on('change', (event) => {
                self.reimburses[self.reimburses.length - 1].details[0].amount = ($(event.target).val());
                self.changeAmount(0);
                self.calculateTotal(self.reimburses.length - 1,0)
              });
            })

        },
        removeTravel(i) {
            this.reimburses.splice(i, 1)
            this.calculateTotal(i,0)
        },
        addDetail(i) {
            $("#action_button").prop("disabled", true);
            $(".warning-upload").show();
            this.reimburses[i].details.push({
                cost_type: null,
                destination: null,
                currency: null,
                amount: null,
                tax: null,
                code: null,
            });
            self = this
            this.$nextTick(() => {
              self.initSelectForm();
              $(".amount-input").maskMoney({ thousands:'.', decimal:',', precision:0});
              $('.amount-input').on('change', (event) => {
                const index = $(event.target).closest('tr').index();
                this.reimburses[i].details[index].amount = ($(event.target).val());
                self.changeAmount(0);
                self.calculateTotal(i,index)

              });
            })
        },
        calculateTotal(i,a) {
            subtotal = 0
            self = this
            try {
                currency = this.reimburses[i].details[a].currency
                amount = this.reimburses[i].details[a].amount
                id = this.reimburses[i].details[a].cost_type
                
                allowance = self.reimburses[i].trip_allowance
                this.reimburses[i].total = allowance.toLocaleString('de-DE')

                tax = self.types.filter(a => a.id == id)[0].tax
                this.reimburses[i].details[a].idr_rate = this.getRate(currency, amount).toLocaleString("de-DE")
                this.reimburses[i].details[a].tax = (this.getRate(currency, amount) * tax / 100).toLocaleString('de-DE')
                this.reimburses[i].details.forEach(element => {
                    subtotal += parseInt(element.idr_rate.replaceAll(".",""))
                    
                    allowance = self.reimburses[i].trip_allowance.replaceAll(".","")
                    
                    total = +subtotal + +allowance
                    
                    this.reimburses[i].total = total.toLocaleString('de-DE')
                    
                    // console.log('allowance'+allowance)
                    // console.log('subtotal'+subtotal)
                    
                });

            } catch (error) {
                
            }
   
            // allowance_currency = self.trip_types.filter(a => a.id == self.reimburses[i].trip)[0].currency

            // allowance = self.getRate(allowance_currency,self.reimburses[i].trip_allowance.replaceAll(".",""))

            // subtotal = +allowance 
            // this.reimburses[i].total = subtotal.toLocaleString('de-DE')
            
        },        
        removeDetail(i,a) {
            
            this.reimburses[i].details.splice(a,1)
            this.calculateTotal(i,0)
        },
        changeCost(i,a) {
            id = this.reimburses[i].details[a].cost_type
            self = this
            // alert(self.trip_types.filter(a => a.id == id)[0].allowance)
            this.reimburses[i].details[a].code = self.types.filter(a => a.id == id)[0].type
            this.calculateTotal(i,a)
        }
      },
      watch: {
       
      },
  });

</script>

@endpush
@endsection
