@extends('template.app')

@section('content')

<div class="page-content">

<div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  <div class="col-md-5">
                  <div class="row"> 
                  <div class="col-sm-6">
                    <h2 class="card-title clr-green">Reimbursement UUDP</h2>
                  </div>
                  {{-- <div class="col-sm-6 text-left text-sm-right">
                     <p> Total Reimbursement: </p>
                     <label class="card-title" id="totalpengajuan" style="font-size:15px; color:#62d49e;">Rp 0</label>
                  </div> --}}
                </div>
                  </div>
                  <div class="col-md-7">
                  <div class="row">
                  <div class="col-sm-4 mb-3">
                    <div class="dropdown ">
                      <select class="btn-outline-success btn-sm btn-block dropdown-toggle" style="border-radius: 7px; border: 1px solid #5fd0a5;   padding: 7px 15px; font-size: 12px; height: 37px;" id="first" name="first">
                        <option value="">First Month</option>
                        <option value="01">Januari</option>
                        <option value="02">Februari</option>
                        <option value="03">Maret</option>
                        <option value="04">April</option>
                        <option value="05">Mei</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">Agustus</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                      </select>

                    </div>
                  </div>
                  <div class="col-sm-4 mb-3 ">
                    <div class="dropdown ">
                      <select class="btn-outline-success btn-sm btn-block dropdown-toggle" style="border-radius: 7px; border: 1px solid #5fd0a5; color: #5fd0a5; padding: 7px 15px; font-size: 12px; height: 37px;" id="last" name="last">
                        <option value="">Last Month</option>
                        <option value="01">Januari</option>
                        <option value="02">Februari</option>
                        <option value="03">Maret</option>
                        <option value="04">April</option>
                        <option value="05">Mei</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">Agustus</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-6 col-sm-4 text-left text-sm-right mb-3 ">
                       <button type="button" class="btn btn-primary btn-sm  w-100" onclick="create()"  data-toggle="modal" data-target=".bd-example-modal-lg"><i class="fa fa-plus-circle" aria-hidden="true"></i> Create Inquiry</button>
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
                              <th>Nama Project</th>
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
    <form method="post" id="sample_form" action="{{url('/')."/reimbursement"}}" enctype="multipart/form-data">
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
   <label for="exampleFormControlInput1">Jabatan</label>
   <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" value="{{Auth::user()->jabatan}}">
 </div>
                  </div>

                  <hr>
                  <div class="col-md-12">
                    <label class="modal-title clr-green" id="exampleModalCenterTitle" >Pilih Project</label>

                    <div class="form-group">
   <label for="exampleFormControlInput1">No Project</label>
   <select class=" form-control" onchange="searchkelompok('1')" id="id_project"    name="id_project">
     <option value="" selected disabled>Pilih Nomor Project</option>
     @foreach($project as $g)
     <option  value="{{$g->id}}">{{$g->no_project}} - {{$g->nama}}</option>
     @endforeach
     <option value="OTHER">LAINNYA</option>
   </select>
 </div>

<div class="form-group">
<label for="exampleFormControlInput1">Keterangan Project</label>
<input type="text" class="form-control" id="keterangan_project" style="border-radius: 10px;" placeholder="" name="keterangan_project">
</div>
                  </div>
                </div>
                <label class="modal-title clr-green" id="exampleModalCenterTitle">Rincian Reimbursement</label>
<div class="respon respon-big table-responsive">
                <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3
            align=center width="1400">
                  <thead>
                      <tr>
                          <th align="center" width="50">No.</th>
                          <th align="center" >Keterangan Alokasi Inquiry</th>
                          <th align="center" >Inquiry Note</th>
                          <th align="center" width="15%">Nominal Reimbursement</th>
                          <th align="center" >Bukti Transaksi</th>
                          <th align="center" width="100"></th>

                      </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>1</td>
                          <td>
                            <input type="hidden" id="kd_unik_1" name="kd_unik" value=""/>
                            <select style="border-radius: 10px; display:none !important;" placeholder="Enter your Name"  onclick="searchdaftar()" id="id_kelompok_1" class="form-control name_list " name="id_kelompok[]">
                              <option value="">Induk Kegiatan</option>
                            </select>
                            <input type="text" class="form-control" name="plain_kelompok[]" placeholder="Alokasi Kegiatan" id="plain_kelompok_1">
                          </td>
                          <td>
                            <input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;  " placeholder="Isi keterangan" class="form-control name_list " />
                          </td>
                          <td>
                            <input type="text" id="nominal_pengajuan" onclick=""  name="nominal_pengajuan[]" style="border-radius: 10px;" placeholder="Nominal" required autofocus class="form-control name_list nominal_pengajuan" />
                          </td>
                          <td class="file-proof">
                            <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                              Upload
                            </button>
                            
                            <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" >
                              Select Camera
                            </button>
                            <input type="file" accept="image/*" name="file[]"  style="display: none; " class="file-input">
                            <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                            <div id="preview_1"></div>
                          </td>
                          <td>
                            <button type="button" name="add" id="add" class="btn btn-success full-width">+</button>
                          </td>
                    </tr>
                  </tbody>
                </table>

</div>
<br>
                <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Nominal</label>
                <div class="form-group">
                <label for="exampleFormControlInput1">Total Inquiry</label>
                <input type="text" class="form-control" id="sum" style="border-radius: 10px;" name="total_pengajuan" readonly placeholder="">
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
@include('pengajuan.op')

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
<script>

  var data_kelompok = [];

  function initSelectForm() {
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
  }

  $(document).ready(function() {
   
});
$(document).ready(function(){
 
  initSelectForm()

       var i=1;
       var o = 1;
       var p = 1;
       $('#add').click(function(){

         var myInputElements = document.querySelectorAll(".nominal_pengajuan");
         var j = []
         var s;
         for (s = 0; s < myInputElements.length; s++) {
             j.push(1);
         }
         var g = j.length;
        //  if(g == 1){
        //    var ga =  document.getElementById("nominal_pengajuan").value;

        //  }else {
        //    var ga =  document.getElementById("nominal_pengajuan_"+g).value;
        //  }

        //  if(ga != ''){

            i++;
           o++;
           let rs = Math.random().toString(36).substring(7);

           $('#dynamic_field').append(`
            <tr class="list-item">
                <td>`+i+`</td>
                    <td>
                      <input type="hidden" id="kd_unik_1" name="kd_unik" value=""/>
                      <select style="border-radius: 10px; `+($("#id_project").val() == null ? 'display:none !important;' : '')+`" placeholder="Enter your Name" class="form-control name_list " id="id_kelompok_`+i+`" name="id_kelompok[]">
                        <option value="">Pilih</option>
                       
                      </select>
                      <input type="text" class="form-control" name="plain_kelompok[]" placeholder="Alokasi Kegiatan" id="plain_kelompok_`+i+`">
                    </td>
                    <td>
                      <input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;  " placeholder="Isi keterangan" class="form-control name_list " />
                    </td>
                    <td>
                      <input type="text" id="nominal_pengajuan" onclick="" name="nominal_pengajuan[]" style="border-radius: 10px;" placeholder="Nominal" required autofocus class="form-control name_list nominal_pengajuan" />
                    </td>
                    <td class="file-proof">
                      <button type="button" data-idx="`+i+`" class="btn btn-success btn-sm addFile">
                        Upload
                      </button>
                      
                      <button type="button"  data-idx="`+i+`" class="btn btn-success btn-sm addCamera" >
                        Select Camera
                      </button>
                      <div id='#preview_`+i+`'></div>
                      <input type="file" accept="image/*" name="file[]"  style="display: none; " class="file-input">
                      <input type="file" accept="image/*" name="proof[]" capture="environment" class="camera-input" style="display: none;">
                    </td>
                    <td>
                      <button type="button" name="delete" class="btn btn-danger btn-delete ">x</button>
                    </td>
              </tr>
           `);
          var id_project = $("#id_project").val();
          if(id_project && id_project != "OTHER") {
              $("#id_kelompok_"+i).show()
              $("#plain_kelompok_"+i).hide()
              
              $.each(data_kelompok, function(key, value){
                $("select#id_kelompok_"+i).append(`<option value="`+key+`">`+value+`</option>'`)
              });
              $("select#id_kelompok_"+i).append(`<option value="OTHER">LAINNYA</option>'`)
              $('select[id="id_kelompok_'+i+'"]').on('change',function() {
                console.log($("#plain_kelompok_"+i+""))
                    if($(this).val() == "OTHER") {
                      $("#plain_kelompok_"+i).show()
                    } else {
                      $("#plain_kelompok_"+i).hide()
                    }
                 });
          } else {
              $("#id_kelompok_"+i).hide()
              $("#plain_kelompok_"+i).show()
          }
           $(".nominal_pengajuan").on('keyup',function() {
              $(this).maskMoney({ thousands:'.', decimal:',', precision:0});

            // $(this).keyup(function(){
              calculateSum();
            // });
            });
            initSelectForm()
            $(".btn-delete").on('click',function() {
              $(this).closest(".list-item").remove()
            });


        //  }

       });
       $(document).on('click', '.btn_remove', function(){

           deletebudget(i);

          o--;
            var button_id = $(this).attr("id");
            var nominal_hapus = $('#nominal_pengajuan_'+button_id+'').val();
            var nominal_awal = $('#sum').val();

    var clean = nominal_hapus.replace(/\D/g, '');
    // var a =  parseFloat(clean);
    var cleans = nominal_awal.replace(/\D/g, '');
    // var b =  parseFloat(cleans);

            var hitung_akhir = cleans - clean;
            console.log(hitung_akhir);
            document.getElementById("sum").value =  hitung_akhir;
            $('#sum').maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

            $('#row'+button_id+'').remove();
       });




  load_data();

  function load_data(first = '',last = '' ) {
    // function load_data() {
      $('#myTable').dataTable({

        processing: false,
        serverSide: false,
        "bPaginate": true,
        "bLengthChange": false,
        "bFilter": false,
        "bInfo": false,
        "bAutoWidth": false,
        "pageLength": 5,
        "order": [],
         ajax: {
          url:'{{ url("reimbursement") }}',
          data:{first:first,last:last}
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
  }

totalpengajuan();
  function totalpengajuan(first = '',last = ''){

    $.ajax({
        url: '/pengajuan/totalpengajuan/',
        type:"GET",
        dataType:"json",
        data:{first:first,last:last},

        success:function(data) {
document.getElementById('totalpengajuan').innerHTML = 'Rp. '+data[0];

        },

    });
  }
  //
  $('#first').click(function(){
    var first = $('#first').val();
    var last = $('#last').val();
    $('#myTable').dataTable().fnDestroy();
    load_data(first,last);
    totalpengajuan(first,last);
   });
  $('#last').click(function(){
    var first = $('#first').val();
    var last = $('#last').val();
    $('#myTable').dataTable().fnDestroy();
    load_data(first,last);
    totalpengajuan(first,last);

  });

  });


</script>

<script type="text/javascript">
$(document).ready(function(){
  $('select[name="id_project"]').on('change', function(){
     var id_project = $(this).val();
     if(id_project && id_project != "OTHER") {
        $("#id_kelompok_1").show()
        $("#plain_kelompok_1").hide()
         $.ajax({
             url: '/pengajuan/project/'+id_project,
             type:"GET",
             dataType:"json",
             // beforeSend: function(){
             //     $('.loader').css("visibility", "visible");
             // },

             success:function(data) {


                 $.each(data, function(key){

                  //  document.getElementById("nama_project").value =   data.pro[0].nama ;
                    document.getElementById("keterangan_project").value =  data.pro[0].keterangan ;
                    // document.getElementById("no_pengajuan").value =  'UUDP.'+data.pro[0].no_project+'.000'+data.max ;


                 });
             },

         });
     } else {
        document.getElementById("keterangan_project").value = "" ;
        $("#id_kelompok_1").hide()
        $("#plain_kelompok_1").show()
     }

 });
 });


</script>
<script>





    // var tanpa_rupiah = document.getElementById('nominal_pengajuan');
  //   var base = document.querySelector('#nominal_pengajuan');
  //   var selector = '.nominal_pengajuan';
	// selector.addEventListener('keyup', function(e)
	// {
  //   tanpa_rupiah.value = formatRupiah(this.value);
	// });


    function nominal_hitung_edit(){
      $(".nominal_pengajuan_edit").each(function() {
        $(this).keyup(function(){

          calculateSumedit();
        });
      });


    }


	function calculateSum() {
		var sum = 0;
		$(".nominal_pengajuan").each(function() {


        var rupiah = this.value;
        var clean = rupiah.replace(/\D/g, '');
        sum += parseFloat(clean);

		});
    document.getElementById("sum").value =  sum;
    
    $('#sum').maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');


	}
  function calculateSumedit() {

		var sum = 0;
		$(".nominal_pengajuan_edit").each(function() {

			// if(!isNaN(this.value) && this.value.length!=0) {
			// 	sum += parseFloat(this.value);
			// }

      var rupiah = this.value;
  var clean = rupiah.replace(/\D/g, '');
  sum += parseFloat(clean);

		});
    document.getElementById("sum_edit").value =  sum ;
    $('#sum_edit').maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');


	}


</script>
<script type="text/javascript">

  // $('#sample_form').on('submit', function(event){
  //     event.preventDefault();
  //     $("#action_button_add").prop("disabled", true);

  //         $.ajax({
  //         url:"{{ url('pengajuan/add') }}",
  //         method:"POST",
  //         data: new FormData(this),
  //         contentType: false,
  //         cache:false,
  //         processData: false,
  //         dataType:"json",
  //          success:function(data) {

  //                   $('#sample_form')[0].reset();
  //                   $('#myTable').DataTable().ajax.reload();
  //                   $("#action_button_add").prop("disabled", false);

  //                   $('#formModal').modal('hide');
  //               },

  //       });

  //   });
</script>
<script type="text/javascript">

  $('#sample_edit').on('submit', function(event){
    event.preventDefault();
    if($('#action').val() == 'edit')
    {
              $.ajax({
              url:"{{ url('pengajuan/update') }}",
              method:"POST",
              data: new FormData(this),
              contentType: false,
              cache:false,
              processData: false,
              dataType:"json",
               success:function(data) {

                        $('#sample_edit')[0].reset();
                        $('#myTable').DataTable().ajax.reload();

                        $('#formModaledit').modal('hide');
                    },

            });

    }

    if($('#action').val() == "proses")
    {
      $.ajax({
      url:"{{ url('pengajuan/approvefinace') }}",
      method:"POST",
      data: new FormData(this),
      contentType: false,
      cache:false,
      processData: false,
      dataType:"json",
       success:function(data) {

                $('#sample_edit')[0].reset();
                $('#myTable').DataTable().ajax.reload();

                $('#formModaledit').modal('hide');
            },

    });

    }

    if($('#action').val() == "owner")
    {
      $.ajax({
      url:"{{ url('pengajuan/update') }}",
      method:"POST",
      data: new FormData(this),
      contentType: false,
      cache:false,
      processData: false,
      dataType:"json",
       success:function(data) {

                $('#sample_edit')[0].reset();
                $('#myTable').DataTable().ajax.reload();

                $('#formModaledit').modal('hide');
            },

    });

      $.ajax({
      url:"{{ url('pengajuan/approveowner') }}",
      method:"POST",
      data: new FormData(this),
      contentType: false,
      cache:false,
      processData: false,
      dataType:"json",
       success:function(data) {

                $('#sample_edit')[0].reset();
                $('#myTable').DataTable().ajax.reload();

                $('#formModaledit').modal('hide');
            },

    });

    }

    });

function searchkelompok(id)
 {
// $('select[id="id_project"]').on('change', function(){
     var id_project =  document.getElementById("id_project").value;
     if(id_project) {
         $.ajax({
             url: '/pengajuan/searchkelompok/'+id_project,
             type:"GET",
             dataType:"json",


             success:function(data) {

                 $('select[id="id_kelompok_'+id+'"]').empty();
                 $('select[id="id_kelompok_'+id+'"]').append('<option value="">Pilih</option>');
                  data_kelompok = data;
                 $.each(data, function(key, value){

                     $('select[id="id_kelompok_'+id+'"]').append('<option value="'+ key +'">' + value + '</option>');

                 });
                 $('select[id="id_kelompok_'+id+'"]').append('<option value="OTHER">LAINNYA</option>');

                 $('select[id="id_kelompok_'+id+'"]').on('change',function() {
                    if($(this).val() == "OTHER") {
                      $("#plain_kelompok_1").show()
                    } else {
                      $("#plain_kelompok_1").hide()
                    }
                 });

             },

         });
     } else {
         $('select[id="id_kelompok_'+id+'"]').empty();
     }

 // });
 };



function deletebudget(id)
 {
   // var xd = id;

   xd = i+1;
   // console.log(xd);
   var daftar = document.getElementById("id_daftar_"+xd).value;
   var kelompok = document.getElementById("id_kelompok_"+xd).value;
   var project = document.getElementById("id_project").value;
   var unik = document.getElementById("kd_unik_"+xd).value;
   var list = document.getElementById("kd_list").value;

   $.ajax({
      url: '/pengajuan/delete/'+project+'/'+kelompok+'/'+daftar+'/'+list+'/'+unik,
      type:"GET",
      dataType:"json",
      success:function(data) {

      },

   });

 }
</script>
<script type="text/javascript">
function searchkelompokappen(id)
 {
   var id_project = document.getElementById("id_project").value;
     if(id_project) {
         $.ajax({
             url: '/pengajuan/searchkelompok/'+id_project,
             type:"GET",
             dataType:"json",


             success:function(data) {

                 $('select[id="id_kelompok_'+id+'"]').empty();
                 $('select[id="id_kelompok_'+id+'"]').append('<option value="">Pilih</option>');

                 $.each(data, function(key, value){

                     $('select[id="id_kelompok_'+id+'"]').append('<option value="'+ key +'">' + value + '</option>');

                 });
             },

         });
     } else {
         $('select[id="id_kelompok_'+id+'"]').empty();
     }

     var tanpa_rupiah = document.getElementById('nominal_pengajuan_'+id);
     // tanpa_rupiah.maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

  tanpa_rupiah.addEventListener('keyup', function(e)
  {
     tanpa_rupiah.value = formatRupiah(this.value);
  });

     var x = id-1;
     var daftar = document.getElementById("id_daftar_"+x).value;
     var kelompok = document.getElementById("id_kelompok_"+x).value;
     var bud = document.getElementById("id_budget_"+x).value;
     var unik = document.getElementById("kd_unik_"+x).value;
     // var project = document.getElementById("id_project").value;
     var list = document.getElementById("kd_list").value;
     if(x == 1){
       var nomi = document.getElementById("nominal_pengajuan").value;
       document.getElementById("nominal_pengajuan").setAttribute('readonly', true);
     }else {
       document.getElementById("nominal_pengajuan_"+x).setAttribute('readonly', true);
       var nomi = document.getElementById("nominal_pengajuan_"+x).value;
     }

     document.getElementById("id_budget_"+x).setAttribute('readonly', true);
     document.getElementById("id_kelompok_"+x).setAttribute('readonly', true);
     document.getElementById("id_daftar_"+x).setAttribute('readonly', true);

     var nominal = nomi.replace(/\D/g, '');
     var budget = bud.replace(/\D/g, '');



     $.ajax({
         url: '/pengajuan/tmps/'+id_project+'/'+kelompok+'/'+daftar+'/'+list+'/'+nominal+'/'+budget+'/'+unik,
         type:"GET",
         dataType:"json",


         success:function(data) {


         },

     });


 };

</script>

<script type="text/javascript">
function searchdaftar(id)
{
$('select[id="id_kelompok_'+id+'"]').on('change', function(){
     var id_kelompok = $(this).val();
     var id_proyek = document.getElementById("id_project").value;

     if(id_kelompok) {
         $.ajax({
             url: '/abc/'+id_kelompok+'/'+id_proyek,
             type:"GET",
             dataType:"json",


             success:function(data) {

                 $('select[id="id_daftar_'+id+'"]').empty();
                 $('select[id="id_daftar_'+id+'"]').append('<option value="">Pilih</option>');

                 $.each(data, function(key, value){

                     $('select[id="id_daftar_'+id+'"]').append('<option value="'+ key +'">' + value + '</option>');

                 });
             },

         });
     } else {
         $('select[id="id_daftar_'+id+'"]').empty();
     }

 });
 };

</script>
<script type="text/javascript">
function searchbudget(id)
 {
   // console.log(id);
$('select[id="id_daftar_'+id+'"]').on('change', function(){
     var id_daftar = $(this).val();
     var id_kelompok = document.getElementById("id_kelompok_"+id).value;
     var id_proyek = document.getElementById("id_project").value;
     var kd_list = document.getElementById("kd_list").value;

         $.ajax({
             url: '/pengajuan/searchbudget/'+id_proyek+'/'+id_kelompok+'/'+id_daftar+'/'+kd_list,
             type:"GET",
             dataType:"json",


             success:function(data) {

                 $.each(data, function(key, value){
                  var bud = formatRupiah(value);
                     document.getElementById("id_budget_"+id).value =  bud;


                 });
             },

         });

 });
 };

</script>
<script type="text/javascript">
$(document).ready(function(){
  
  $(".nominal_pengajuan").on('keyup',function() {

  // $(this).keyup(function(){
    calculateSum();
  // });
  });
  $(document).on('click', '.edit', function(){
      var id = $(this).attr('id');
       document.getElementById('lbltipAddedComment').innerHTML = 'Proses Inquiry UUDP';
      $('#edit_form').html('');
      $.ajax({
      url: '/pengajuan/edit/'+id,
      dataType:"json",
      success:function(html){
      $('#no_pengajuan_edit').val(html.data[0].no_pengajuan);
      $('#id_project_edit').val(html.data[0].no_project);
      $('#nama_project_edit').val(html.data[0].nama_project);
      $('#keterangan_project_edit').val(html.data[0].keterangan_project);
      $('#sum_edit').val(html.data[0].nominal_pengajuan);
      $('#id_pengajuan').val(html.data[0].id);
      $('#tgl').val(html.data[0].created_at);
      $('#nama_lengkap').val(html.data[0].name_user);
      $('#nik').val(html.data[0].nik);
      $('#jabatan').val(html.data[0].jabatan);
      $('#action').val("edit");
      $('#sum_edit').maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

      var x1 = document.getElementById("tipe_pencairan");
      x1.style.display = "none";
      }
    })

    $.ajax({
    url: '/pengajuan/detail/'+id,
    dataType:"json",
   success:function(data) {
     $('#dynamic_fields').html('');

     for (var i = 0; i < data.cek; i++) {
       var z = i+1;
       $('#dynamic_fields').append('<tr id="row'+i+'"><td>'+z+'</td><td><input type="hidden" name="id_list[]" id="id_list_'+i+'" value="'+data.hasil[i].id+'"><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_kelompok+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_daftar+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;" placeholder="Isi keterangan" value="'+data.hasil[i].keterangan+'" readonly class="form-control name_list" /></td><td><input type="text" id="limit_'+i+'" value="'+data.hasil[i].limit+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" value="'+data.hasil[i].nominal_pengajuan+'" name="nominal_pengajuan[]" id="nominal_pengajuan_'+i+'" style="border-radius: 10px;"  placeholder="Nominal" class="form-control name_list nominal_pengajuan_edit" onclick="nominal_hitung_edit()"   /></td></tr>');
       $("#limit_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');
       $("#nominal_pengajuan_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

     }


    }
  })

  });
})
</script>
<script type="text/javascript">
$(document).ready(function(){
  $(document).on('click', '.proses', function(){
      var id = $(this).attr('id');

      $('#edit_form').html('');
      $('#lbltipAddedComment').html('');
      $.ajax({
      url: '/pengajuan/edit/'+id,
      dataType:"json",
      success:function(html){
        document.getElementById('lbltipAddedComment').innerHTML = 'APPROVED Inquiry UUDP';
      $('#no_pengajuan_edit').val(html.data[0].no_pengajuan);
      $('#id_project_edit').val(html.data[0].no_project);
      $('#nama_project_edit').val(html.data[0].nama_project);
      $('#keterangan_project_edit').val(html.data[0].keterangan_project);
      $('#sum_edit').val(html.data[0].nominal_pengajuan);
      $('#id_pengajuan').val(html.data[0].id);
      $('#tgl').val(html.data[0].created_at);
      $('#nama_lengkap').val(html.data[0].name_user);
      $('#nik').val(html.data[0].nik);
      $('#jabatan').val(html.data[0].jabatan);
      $('#action').val("proses");
      $('#sum_edit').maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');
      var x1 = document.getElementById("tipe_pencairan");
      x1.style.display = "none";
      }
    })

    $.ajax({
    url: '/pengajuan/detail/'+id,
    dataType:"json",
   success:function(data) {
     $('#dynamic_fields').html('');

     for (var i = 0; i < data.cek; i++) {
       var z = i+1;
       $('#dynamic_fields').append('<tr id="row'+i+'"><td>'+z+'</td><td><input type="hidden" name="id_list[]" id="id_list_'+i+'" value="'+data.hasil[i].id+'"><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_kelompok+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_daftar+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;" placeholder="Isi keterangan" value="'+data.hasil[i].keterangan+'" readonly class="form-control name_list" /></td><td><input type="text" id="limit_'+i+'" value="'+data.hasil[i].limit+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" value="'+data.hasil[i].nominal_pengajuan+'" name="nominal_pengajuan[]" id="nominal_pengajuan_'+i+'" style="border-radius: 10px;"  placeholder="Nominal" class="form-control name_list nominal_pengajuan_edit" onclick="nominal_hitung_edit()" readonly  /></td></tr>');
       $("#limit_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');
       $("#nominal_pengajuan_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

     }


    }
  })

  });
})
</script>
<script type="text/javascript">
$(document).ready(function(){
  $(document).on('click', '.prosesowner', function(){
      var id = $(this).attr('id');
      $('#edit_form').html('');
      $('#lbltipAddedComment').html('');
      document.getElementById('lbltipAddedComment').innerHTML = 'Approved Inquiry UUDP';

      $.ajax({
      url: '/pengajuan/edit/'+id,
      dataType:"json",
      success:function(html){
      $('#no_pengajuan_edit').val(html.data[0].no_pengajuan);
      $('#id_project_edit').val(html.data[0].no_project);
      $('#nama_project_edit').val(html.data[0].nama_project);
      $('#keterangan_project_edit').val(html.data[0].keterangan_project);
      $('#sum_edit').val(html.data[0].nominal_pengajuan);
      $('#id_pengajuan').val(html.data[0].id);
      $('#tgl').val(html.data[0].created_at);
      $('#nama_lengkap').val(html.data[0].name_user);
      $('#nik').val(html.data[0].nik);
      $('#jabatan').val(html.data[0].jabatan);
      $('#sum_edit').maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

      var x1 = document.getElementById("tipe_pencairan");
      x1.style.display = "block";
      $('#action').val("owner");

      }
    })

    $.ajax({
    url: '/pengajuan/detail/'+id,
    dataType:"json",
   success:function(data) {
     $('#dynamic_fields').html('');

     for (var i = 0; i < data.cek; i++) {
       var z = i+1;
       $('#dynamic_fields').append('<tr id="row'+i+'"><td>'+z+'</td><td><input type="hidden" name="id_list[]" id="id_list_'+i+'" value="'+data.hasil[i].id+'"><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_kelompok+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_daftar+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;" placeholder="Isi keterangan" value="'+data.hasil[i].keterangan+'" readonly class="form-control name_list" /></td><td><input type="text" id="limit_'+i+'" value="'+data.hasil[i].limit+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" value="'+data.hasil[i].nominal_pengajuan+'" name="nominal_pengajuan[]" id="nominal_pengajuan_'+i+'" style="border-radius: 10px;"  placeholder="Nominal" class="form-control name_list nominal_pengajuan_edit" onclick="nominal_hitung_edit()"   /></td></tr>');
       $("#limit_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');
       $("#nominal_pengajuan_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

     }


    }
  })

  });
})
var xss1 = document.getElementById("termin");
xss1.style.display = "none";


$('#cek_termin').on('change',function(){
      var fileName = $(this).val();
    // if (fileName == 'Full Payment'){
    //   var xq = document.getElementById("termin");
    //   xq.style.display = "none";

    // }else{
      var xq = document.getElementById("termin");
      xq.style.display = "block";
    // }
})

var i=1;
$('#adds').click(function(){
     i++;
     $('#dynamic_termin').append('<tr id="row'+i+'"><td>'+i+'</td><td><div class="input-group"><input type="number" name="nominal_termin[]" class="form-control name_termin" style="border-radius: 5px;" id="percent"  onKeyPress="if(this.value.length==3) return false;"   placeholder="100" aria-describedby="inputGroupPrepend" ><div class="input-group-prepend"><span class="input-group-text" id="inputGroupPrepend" style="border-right: 5px;">%</span></div></div></td><td><input type="date" name="date[]" class="form-control date_termin" style="border-radius: 10px;" value=""></td><td><button type="button" name="remove" id="'+i+'" class="btn btn-danger btn_removes">X</button></td></tr>');
});

$(document).on('click', '.btn_removes', function(){

    i--;
     var button_id = $(this).attr("id");

     $('#row'+button_id+'').remove();
});


</script>
<script type="text/javascript">
function create(){
  let r = Math.random().toString(36).substring(7);
  document.getElementById('kd_list').value = r ;
  document.getElementById('kd_unik_1').value = r ;

}
function formatRupiah(angka, prefix)
	{
		var number_string = angka.replace(/[^,\d]/g, '').toString(),
			split	= number_string.split(','),
			sisa 	= split[0].length % 3,
			rupiah 	= split[0].substr(0, sisa),
			ribuan 	= split[0].substr(sisa).match(/\d{3}/gi);

		if (ribuan) {
			separator = sisa ? '.' : '';
			rupiah += separator + ribuan.join('.');
		}

		rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
		return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
	}

  $(document).ready(function() {
    $("#id_project").select2({
    allowClear:true,
    placeholder: 'Pilih Nomor Project'
  });
});
</script>

@endpush
@endsection
