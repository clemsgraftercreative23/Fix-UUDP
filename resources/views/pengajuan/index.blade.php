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
                    <h2 class="card-title clr-green"    >Inquiry UUDP</h2>
                  </div>
                  <div class="col-sm-6 text-left text-sm-right">
                     <p> Total Inquiry: </p>
                     <label class="card-title" id="totalpengajuan" style="font-size:15px; color:#62d49e;">Rp.10.000</label>
                  </div>
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
                
                

                  <table id="myTable" class="display" style="width:1080px"  >
                      <thead>
                          <tr>
                              <th>Inquiry No</th>
                              <th>Inquiry Date</th>
                              <th>Project ID</th>
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
    <form method="post" id="sample_form"  enctype="multipart/form-data">
    @csrf
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header border-bottom"  >
              <div class="d-flex justify-content-between w-100">
                    <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle"  >Create Inquiry UUDP</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
                </div>
                

                  

              </div>

              <div class="modal-body py-3">
              <div class="row my-3"> 
                  <div class="col-md-6">
                    <div class="form-group row">
                        <label for="inputEmail3" class="col-sm-2 col-form-label">Tgl</label>
                        <div class="col-sm-10">
                          <input type="email" class="form-control" id="inputEmail3"  value="<?php echo date('d-m-Y'); ?>" readonly placeholder="12/12/2020">
                        </div>
                      </div>
                    </div>

                  <div class="col-md-6">
                    <div class="form-group row">
                        <label for="inputEmail3" class="col-sm-5 col-form-label">Nomor Inquiry</label>
                        <div class="col-sm-7">
                          <input type="text" name="no_pengajuan" id="no_pengajuan" class="form-control" readonly>
                        </div>
                      </div>
                  </div>
                
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
   <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" value="{{Auth::user()->nik}}">
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
   <!-- <input type="email" class="form-control" id="exampleFormControlInput1" style="border-radius: 10px;" placeholder="name@example.com"> 
<style media="screen">
.select2-selection {
  height: auto!important;
  padding: 12px 25px;
  border-radius: 4px!important;
  box-shadow: none;
  border: 2px solid #e8e8e8!important;
}
</style>-->
   <select class=" form-control" onchange="searchkelompok('1')" id="id_project"    name="id_project">
     <option value="">Pilih Nomor Project</option>
     @foreach($project as $g)
     <option  value="{{$g->id}}">{{$g->no_project}} - {{$g->nama}}</option>
     @endforeach
   </select>
 </div>
 <div class="form-group">
<label for="exampleFormControlInput1">Nama Project</label>
<input type="hidden" name="kd_list" id="kd_list" value="">
<input type="email" class="form-control" id="nama_project" style="border-radius: 10px;" readonly placeholder="" name="nama_project">
</div>
<div class="form-group">
<label for="exampleFormControlInput1">Keterangan Project</label>
<input type="email" class="form-control" id="keterangan_project" style="border-radius: 10px;" readonly placeholder="" name="keterangan_project">
</div>
                  </div>
                </div>
                <label class="modal-title clr-green" id="exampleModalCenterTitle"  >Inquiry</label>
<div class="respon respon-big table-responsive">
                <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3
            align=center width="1400">
                  <thead>
                      <tr>
                          <th align="center" width="1%">No.</th>
                          <th align="center" width="22%">Kelompok Rencana Kegiatan</th>
                          <th align="center" width="22%">Daftar Rencana Kegiatan</th>
                          <th align="center" width="22%">Keterangan Alokasi Inquiry</th>
                          <th align="center" width="15%">Limit Budget</th>
                          <th align="center" width="15%">Nominal Inquiry</th>
                          <th align="center"></th>

                      </tr>
                  </thead>
                  <tbody>
                                    <tr>
                                      <td>1</td>
                                         <td>
                                           <input type="hidden" id="kd_unik_1" name="kd_unik" value=""/>
                                           <select style="border-radius: 10px; h" placeholder="Enter your Name"  onclick="searchdaftar('1')" id="id_kelompok_1" class="form-control name_list cst-select" name="id_kelompok[]">
                                             <option value="">Induk Kegiatan</option>
                                             @foreach($kelompok as $d)
                                             <option  value="{{$d->id}}">{{$d->nama}}</option>
                                             @endforeach
                                           </select>
                                         </td>
                                          <td>
                                            <select style="border-radius: 10px;  " placeholder="Enter your Name" onclick="searchbudget('1')" id="id_daftar_1" class="form-control name_list cst-select" name="id_daftar[]">
                                              <option value="">Sub Kegiatan</option>
                                            </select>
                                          </td>

                                           <td>
                                             <input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;  " placeholder="Isi keterangan" class="form-control name_list cst-select" />
                                           </td>
                                           <td>
                                             <input type="text" id="id_budget_1" name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" />
                                           </td>
                                           <td>
                                             <input type="text" id="nominal_pengajuan" onclick="nominal_hitung()"  name="nominal_pengajuan[]" style="border-radius: 10px;" placeholder="Nominal" required autofocus class="form-control name_list nominal_pengajuan" />
                                           </td>
                                           <td>
                                             <button type="button" name="add" id="add" class="btn btn-success">+</button>
                                           </td>
                                    </tr>
                                  </tbody>
                               </table>

</div>
<br>
                <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Nominal</label>
                <div class="form-group">
                <label for="exampleFormControlInput1">Total Inquiry</label>
                <input type="text" class="form-control" id="sum" style="border-radius: 10px;" name="total_pengajuan" placeholder="">
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
$(document).ready(function(){

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
         if(g == 1){
           var ga =  document.getElementById("nominal_pengajuan").value;

         }else {
           var ga =  document.getElementById("nominal_pengajuan_"+g).value;
         }

         if(ga != ''){

           i++;
           o++;
           let rs = Math.random().toString(36).substring(7);

           $('#dynamic_field').append('<tr id="row'+o+'"><td>'+o+'</td><td><input type="hidden" id="kd_unik_'+i+'" name="kd_unik" value="'+rs+'"/><select style="border-radius: 10px;" placeholder="Enter your Name" class="form-control name_list" onclick="searchdaftar('+i+')"  id="id_kelompok_'+i+'" name="id_kelompok[]"><option value="">Rencana Kegiatan</option></select></td><td><select style="border-radius: 10px;" placeholder="Enter your Name" class="form-control name_list" id="id_daftar_'+i+'" onclick="searchbudget('+i+')" name="id_daftar[]"><option value="">Rencana Kegiatan</option></select></td><td><input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;" placeholder="Isi keterangan" class="form-control name_list" /></td><td><input type="text" id="id_budget_'+i+'" name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" name="nominal_pengajuan[]" id="nominal_pengajuan_'+i+'" required autofocus style="border-radius: 10px;" placeholder="Nominal" class="form-control name_list nominal_pengajuan" onclick="nominal_hitung()"  /></td><td><button type="button" name="remove" id="'+o+'" class="btn btn-danger btn_remove">X</button></td></tr>');
           searchkelompokappen(i);
           $(".nominal_pengajuan").on('keyup',function() {

          // $(this).keyup(function(){
            calculateSum();
          // });
          });

         }


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
          url:'{{ url("pengajuan") }}',
          data:{first:first,last:last}
         },
         columns: [

                  {
                    data: 'no_pengajuan',
                    name: 'no_pengajuan'
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
                    data: 'nama',
                    name: 'nama'
                  },
                  {
                    data: 'total',
                    name: 'total'
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
     if(id_project) {
         $.ajax({
             url: '/pengajuan/project/'+id_project,
             type:"GET",
             dataType:"json",
             // beforeSend: function(){
             //     $('.loader').css("visibility", "visible");
             // },

             success:function(data) {


                 $.each(data, function(key){

                   document.getElementById("nama_project").value =   data.pro[0].nama ;
                    document.getElementById("keterangan_project").value =  data.pro[0].keterangan ;
                    document.getElementById("no_pengajuan").value =  'UUDP.'+data.pro[0].no_project+'.000'+data.max ;


                 });
             },

         });
     } else {
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

  $('#sample_form').on('submit', function(event){
      event.preventDefault();
      $("#action_button_add").prop("disabled", true);

          $.ajax({
          url:"{{ url('pengajuan/add') }}",
          method:"POST",
          data: new FormData(this),
          contentType: false,
          cache:false,
          processData: false,
          dataType:"json",
           success:function(data) {

                    $('#sample_form')[0].reset();
                    $('#myTable').DataTable().ajax.reload();
                    $("#action_button_add").prop("disabled", false);

                    $('#formModal').modal('hide');
                },

        });

    });
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
      error:function(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.length > 0) {
          alert(xhr.responseJSON.errors[0]);
        }
      }

    });

    }

    if($('#action').val() == "finance_supervisor")
    {
      $.ajax({
      url:"{{ url('pengajuan/approvefinancesupervisor') }}",
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
      error:function(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.length > 0) {
          alert(xhr.responseJSON.errors[0]);
        }
      }

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
</script>
<script type="text/javascript">
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

                 $.each(data, function(key, value){

                     $('select[id="id_kelompok_'+id+'"]').append('<option value="'+ key +'">' + value + '</option>');

                 });
             },

         });
     } else {
         $('select[id="id_kelompok_'+id+'"]').empty();
     }

 // });
 };

</script>
<script type="text/javascript">
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
  $(document).on('click', '.prosesfs', function(){
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
      x1.style.display = "none";
      $('#action').val("finance_supervisor");

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
