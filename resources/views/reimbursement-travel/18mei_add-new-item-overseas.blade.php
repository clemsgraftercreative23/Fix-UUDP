@extends('template.app')
@section('content')

<?php 
function rupiah($angka){
	
	$hasil_rupiah = number_format($angka,0,',','.');
	return $hasil_rupiah;
 
}
?>

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
    .nav-tabs-container {
        width: 100%;
        overflow-x: auto;  /* Mengaktifkan scroll horizontal */
        overflow-y: hidden; /* Mencegah scroll vertikal */
        white-space: nowrap; /* Pastikan elemen tidak pindah ke baris baru */
        -webkit-overflow-scrolling: touch; /* Scroll lebih halus di mobile */
    }

    .nav-tabs {
        display: flex; /* Supaya elemen tetap dalam satu baris */
        flex-wrap: nowrap; /* Mencegah pindah ke baris berikutnya */
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .nav-item {
        flex-shrink: 0; /* Pastikan item tidak mengecil */
        margin-right: 15px; /* Beri sedikit jarak antar item */
    }

    .nav-link {
        background-color: #e8e8e8;
        display: block;
        padding: 10px 15px;
        white-space: nowrap; /* Pastikan teks tidak terpotong */
    }
    .button-container {
        display: flex;
        flex-wrap: nowrap; /* Pastikan tombol tetap dalam satu baris */
        justify-content: flex-end; /* Posisikan tombol ke kanan */
        overflow-x: auto; /* Scroll horizontal jika tidak cukup ruang */
        padding-bottom: 5px; /* Hindari tombol tertutup scrollbar */
        -webkit-overflow-scrolling: touch; /* Scroll lebih halus di mobile */
    }
    .btn {
        white-space: nowrap; /* Pastikan teks tidak turun ke bawah */
        flex-shrink: 0; /* Mencegah tombol mengecil */
    }
    

</style>

<div class="page-content" id="app">
    <div class="">
        <!--
        <form action="{{route('reimbursement-travel.store')}}" method="POST" enctype="multipart/form-data" style="overflow-y: auto;">
		-->
        <form action="{!!url('reimbursement-travel/save-item/'.Request::segment(3).'')!!}" method="POST" enctype="multipart/form-data" style="overflow-y: auto;">
            @csrf
            <div class="row">
              <div class="col-xl">
                  <div class="card">
                      <div class="card-body">
                          <h5 class="card-title">REIMBURSEMENT UUDP - TRAVEL {{strtoupper($travel_type)}}</h5>

                          <div class="row">
                              <input type="hidden" name="travel_type" value="{{$travel_type}}">
                              <div class="col-md-3">
                                  <label for="">Employee</label>
                                  <input type="text" class="form-control" readonly value="{{auth()->user()->name}}">
                              </div>
                              <div class="col-md-3">
                                  <label for="">Apply Date</label>
                                  <input type="text" class="form-control" name="remark" value="{{ date('d F Y', strtotime($data['0']->created_at)) }}" readonly>
                              </div> 
                              <div class="col-md-3">
                                  <label for="">Remark</label>
                                  <input type="text" class="form-control" name="remark" value="{{$data['0']->remark}}">
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
                          <div class="row">
                              <div class="col-md-12">
                                  <div class="row fieldGroup">
                                      <div class="col-md-3">
                                          <label for="">Currency</label>
                                          <input type="text" class="form-control" name="currency_rate[]" value="{{$travel_trip['0']->currency}}">
                                      </div>
                                      <div class="col-md-6">
                                          <label for="">Exchange Rate</label>
                                          <input type="text" class="form-control currency" name="rate[]" value="{{rupiah($travel_trip['0']->rate)}}">
                                      </div>
                                      <div class="col-md-3">
                                          <a class="btn btn-primary btn-sm addMore" style="color:white;margin-top:35px;cursor:pointer"><i class="fa fa-plus"></i></a>
                                      </div>
                                  </div>
                                  <br>
                                      @if($data['0']->travel_type!='Domestic')
                                          @foreach($travel_trip as $key => $row)
                                              @if($key > 0)
                                              <div class="row fieldGroup">
                                                  <div class="col-md-3">
                                                      <label for="">Currency</label>
                                                      <input type="text" class="form-control" name="currency_rate[]" value="{{$row->currency}}">
                                                  </div>
                                                  <div class="col-md-6">
                                                      <label for="">Exchange Rate</label>
                                                      <input type="text" class="form-control currency" name="rate[]" value="{{rupiah($row->rate)}}">
                                                  </div>
                                                  <div class="col-md-3">
                                                      <a class="btn btn-danger btn-sm remove-currency" style="color:white;margin-top:35px;cursor:pointer;background:#f05154"><i class="fa fa-trash"></i></a>
                                                  </div>
                                              </div>
                                              @endif
                                          @endforeach
                                      @endif
                              </div>                 
                          </div>
                          <br>
                          <hr>

                      </div>
                  </div>
              </div>
          </div>
            <br />
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
            <div class="row" v-for="(data,i) in reimburses">
                <div class="col-xl">
                    <div class="card">
                        <div class="card-body">
                            <div class="nav-tabs-container">
                                <ul class="nav nav-tabs">
                                    @foreach($data_item as $item)
                                    <li class="nav-item">
                                        <a class="nav-link" href="{!! url('reimbursement-travel/add-item/'.$data['0']->id.'/'.$item->id.'') !!}"><span class="item-1">{{$item->date}}</span></a>
                                    </li>
                                    @endforeach
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#reimburse-form"><span class="item-new">New Item</span></a>
                                    </li>
                                    <li class="nav-item">
                                    	<button type="submit" class="nav-link" name="save_item" id="action_button_item"><i class="fa fa-plus"></i> &nbsp;Add New Item</button>
                                    </li>
                                    <!-- <li class="nav-item">
                                        <a class="nav-link" href="{!! url('reimbursement-travel/add-item/'.$data['0']->id.'') !!}"><i class="fa fa-plus"></i> &nbsp;Add New Item</a>
                                    </li> -->
                                </ul>
                            </div><hr>
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="">Transaction Date</label>
                                    <input type="date" :name="'reimburse['+i+'][date]'" class="form-control" id="transaction_date" required />
                                </div>
                                <div class="col-md-3">
                                    <label for="">Purpose</label>
                                    <input type="text" :name="'reimburse['+i+'][purpose]'" class="form-control" required value="" />
                                </div>
                                <div class="col-md-3">
                                    <label for="">Trip Type</label>
                                    <select :name="'reimburse['+i+'][trip_type_id]'" id="" class="form-control" v-model="data.trip" @change="changeTrip(i)">
                                        <option value="" selected disabled>Pilih...</option>

                                        @foreach ($trip_types as $item)
                                        <option value="{{$item->id}}" data-allowance="{{$item->allowance}}">{{$item->name}} | {{$item->allowance}} USD</option>
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
                                    <input type="time" :name="'reimburse['+i+'][start_time]'" @change="changeTime(i)" v-model="data.start_time" class="form-control" value="" />
                                </div>

                                <div class="col-md-3">
                                    <label for="">Arrival</label>
                                    <input type="time" :name="'reimburse['+i+'][end_time]'" @change="changeTime(i)" v-model="data.end_time" class="form-control" value="" />
                                </div>

                                <div class="col-md-3">
                                    <label for="">Allowance</label>
                                    <input type="text" :name="'reimburse['+i+'][allowance]'" readonly class="form-control number-format" v-model="data.trip_allowance" value="" />
                                </div>

                                <div class="col-md-3">
                                    <label for="">Travel Time</label>
                                    <input type="text" :name="'reimburse['+i+'][travel_time]'" readonly class="form-control" v-model="data.travel_time" value="" />
                                </div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col-xl">
                                    <div class="table-responsive">
                                        <table class="table full-width" style="width: 100%;">
                                            <thead style="width: 100%;">
                                                <tr>
                                                    <th width="200">Cost Type</th>
                                                    <th>Destination</th>
                                                    <th>Currency</th>
                                                    <th>Amount</th>
                                                    <th>IDR Rate</th>
                                                    <th>Pph23</th>
                                                    <th>Payment</th>
                                                    <th width="200">Evidence</th>
                                                    <th>Preview</th>
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
                                                        <input type="text" class="form-control" :name="'reimburse['+i+'][detail]['+a+'][destination]'" />
                                                    </td>
                                                    <td>
                                                        <select :name="'reimburse['+i+'][detail]['+a+'][currency]'" class="form-control" id="" v-model="dt.currency">
                                                            <option v-for="dt in rates" :value="dt.code">@{{dt.code}}</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control amount-input" @change="calculateTotal(i,a)" :name="'reimburse['+i+'][detail]['+a+'][amount]'" v-model="dt.amount" />
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control number-format" readonly :name="'reimburse['+i+'][detail]['+a+'][idr_rate]'" v-model="dt.idr_rate" />
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control number-format" readonly :name="'reimburse['+i+'][detail]['+a+'][tax]'" v-model="dt.tax" />
                                                    </td>
                                                    <td>
                                                        <select :name="'reimburse['+i+'][detail]['+a+'][payment_type]'" id="" class="form-control" v-model="dt.payment_type" required>
                                                            <option value="" selected disabled>Select...</option>
                                                            <option value="BDC">BDC</option>
                                                            <option value="Cash">Cash</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <button type="button" :data-idx="a" class="btn btn-success btn-sm addFile">
                                                            <i class="fa fa-upload"></i>
                                                        </button>

                                                        <button type="button" :data-idx="a" class="btn btn-success btn-sm addCamera">
                                                            <i class="fa fa-camera"></i>
                                                        </button>
                                                        <input type="file" accept="image/*" :name="'reimburse['+i+'][detail]['+a+'][file]'" style="display: none;" class="file-input" />
                                                        <input type="file" accept="image/*" :name="'reimburse['+i+'][detail]['+a+'][proof]'" capture="camera" class="camera-input" style="display: none;" />
                                                    </td>
                                                    <td>
                                                        <div :id="'preview_' + a"></div>
                                                        <!-- Pastikan ID Preview sesuai dengan index a -->
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
                            <hr />
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="">Total</label>
                                    <input type="text" :name="'reimburse['+i+'][total]'" v-model="data.total" readonly class="form-control" value="" />
                                </div>
                                <div class="col-md-9">
                                    <span style="color: #62d49e; float: right;" class="warning-upload">
                                        <br />
                                        The button is disabled until a file is uploaded.<br />
                                        <br />
                                        <br />
                                    </span>
                                </div>
                            </div>

                            <div class="button-container">
                                <a class="btn btn-secondary text-right" href="{{route('reimbursement-travel.index')}}"><i class="fa fa-save"></i> BACK</a>&nbsp;
                                <button class="btn btn-primary" type="submit" id="action_button" name="save">SUBMIT</button>&nbsp;
                                <button class="btn btn-warning" type="submit" id="action_button_draft" name="save_draft">DRAFT</button>
                            </div>
                            <br />
                            <br />
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- End Modal -->

<!-- Modal -->
<div class="modal fade" id="modalPhoto" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Upload Gambar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="material-icons">close</i>
                </button>
            </div>
            <div class="modal-body">
                <video id="videoElement" autoplay style="width: 100%;"></video>
                <!-- <canvas id="canvas"></canvas> -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button id="captureButton" class="btn btn-success">Capture Image</button>
                </div>
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
    $("#action_button_draft").prop("disabled", true);
    $("#action_button_item").prop("disabled", true);
    $(".warning-upload").show();

    $("#transaction_date").on("change", function () {
        let selectedDate = $(this).val(); // Ambil nilai dari input date
        if (selectedDate) {
            $(".item-new").text("" + selectedDate); // Ubah teks sesuai tanggal
        } else {
            $(".item-new").text("New Item"); // Kembali ke default jika kosong
        }
    });

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
            $(".addFile").on('click', function() {
              let idx = $(this).attr("data-idx"); // Ambil data-idx
              let fileInput = $(this).parent().find(".file-input");

              fileInput.click();

              fileInput.off("change").on("change", function(event) {
                var file = event.target.files[0];

                if (file) {
                  let fileType = file.type;
                  let previewDiv = $("#preview_" + idx);
                  previewDiv.html(""); // Bersihkan preview sebelumnya

                  if (fileType.startsWith("image/")) {
                    // Preview gambar
                    var reader = new FileReader();
                    reader.onload = function(e) {
                      var img = $('<img>').attr('src', e.target.result).css({
                        maxWidth: '100%',
                        maxHeight: '200px',
                        border: '2px solid #28a745',
                        borderRadius: '5px',
                        marginTop: '5px'
                      });
                      previewDiv.append(img);
                    };
                    reader.readAsDataURL(file);
                  } else if (fileType === "application/pdf") {
                    // Preview PDF (ikon + link ke file PDF)
                    var fileURL = URL.createObjectURL(file);
                    var pdfIcon = 'https://cdn-icons-png.flaticon.com/512/337/337946.png'; // Ganti dengan lokal jika perlu
                    var link = $('<a>').attr({
                      href: fileURL,
                      target: '_blank',
                      title: 'Klik untuk lihat PDF'
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
                    );
                    previewDiv.append(link);
                  } else {
                    // File tidak didukung
                    previewDiv.append('<p style="color:red;">File tidak didukung</p>');
                  }

                  // Aktifkan tombol aksi
                  $(".warning-upload").hide();
                  $("#action_button").prop("disabled", false);
                  $("#action_button_draft").prop("disabled", false);
                  $("#action_button_item").prop("disabled", false);
                }
              });
            });



            $(".addCamera").on('click', function() {
                let idx = $(this).attr("data-idx"); // Ambil data-idx
                let fileInput = $(this).parent().find(".file-input")[0];

                $("#modalPhoto").modal("show");
                const videoElement = $("#videoElement")[0];

                if (navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
                        .then(function(stream) {
                            videoElement.srcObject = stream;

                            $("#captureButton").off("click").on("click", function() {
                                const canvas = document.createElement("canvas");
                                const context = canvas.getContext("2d");

                                canvas.width = videoElement.videoWidth;
                                canvas.height = videoElement.videoHeight;
                                context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

                                canvas.toBlob(function(blob) {
                                    const file = new File([blob], "capture.png", { type: "image/png" });

                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(file);
                                    fileInput.files = dataTransfer.files;

                                    $("#preview_" + idx).html(""); // Bersihkan preview sebelumnya
                                    var img = $('<img>').attr('src', URL.createObjectURL(blob)).css({ maxWidth: '100%', maxHeight: '200px' });
                                    $("#preview_" + idx).append(img); // Tambahkan ke preview yang benar
                                }, "image/png");

                                stream.getTracks().forEach(track => track.stop());
                                $("#modalPhoto").modal("hide");
                                $(".warning-upload").hide();
                                $("#action_button").prop("disabled", false);
                                $("#action_button_draft").prop("disabled", false);
                                $("#action_button_item").prop("disabled", false);
                            });
                        })
                        .catch(err => console.error("Error accessing webcam: " + err));
                }
            });

        },
        // changeTrip(i) {
        //     id = this.reimburses[i].trip
        //     self = this
        //     this.reimburses[i].trip_allowance = self.trip_types.filter(a => a.id == id)[0].allowance.toLocaleString('de-DE')
        //     this.calculateTotal(i,0)
        // },
        changeTrip(i) {
		    const id = this.reimburses[i].trip;
		    const self = this;

		    // Dapatkan nilai allowance
		    const allowance = self.trip_types.find(a => a.id == id)?.allowance || 0;

		    // Ambil semua elemen currency dan rate
		    const currencyInputs = document.querySelectorAll('[name="currency_rate[]"]');
		    const rateInputs = document.querySelectorAll('[name="rate[]"]');

		    let rate = 1; // default

		    // Cari rate untuk USD
		    currencyInputs.forEach((currencyInput, index) => {
		        if (currencyInput.value.trim().toUpperCase() === 'USD') {
		            const rateValue = rateInputs[index].value.replace(/\./g, ''); // Hapus titik
		            rate = parseFloat(rateValue) || 1;
		        }
		    });

		    // Hitung trip_allowance berdasarkan allowance * rate
		    const totalAllowance = allowance * rate;

		    // Simpan hasil ke reimburses[i].trip_allowance
		    this.reimburses[i].trip_allowance = totalAllowance.toLocaleString('de-DE');

		    // Hitung total
		    this.calculateTotal(i, 0);
		},


        changeTime(i) {

            // Get the input values
            data = this.reimburses[i]
            let time1 = data.start_time;
            let time2 = data.end_time;
            let date1 = new Date('1970-01-01T' + time1 + 'Z');
            let date2 = new Date('1970-01-01T' + time2 + 'Z');
            let timeDifference = Math.abs(date2 - date1);
            let hoursDifference = Math.floor(timeDifference / 1000 / 60 / 60);
            let minutesDifference = Math.floor((timeDifference / 1000 / 60) % 60);
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
            $("#action_button_draft").prop("disabled", true);
            $("#action_button_item").prop("disabled", true);
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
                });

            } catch (error) {
                
            }
   
            
        },        
        removeDetail(i,a) {
            
            this.reimburses[i].details.splice(a,1)
            this.calculateTotal(i,0)
        },
        changeCost(i,a) {
            id = this.reimburses[i].details[a].cost_type
            self = this
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
