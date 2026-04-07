@extends('template.app')

@section('content')
<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-12"><p class="card-title clr-green">Dashboard</p></div>
                  <div class="col-md-7">
                      <div class="row"> 
                        <div class="col-sm-4">
                          <h2 class="card-title clr-green" id="total">Settlement UUDP</h2>
                        </div>
                        <div class="col-sm-4 text-left text-sm-right">
                          <p> Total Settlement: </p>
                          <label class="card-title" id="totalpencairan" style="font-size:15px; color:#62d49e;">10.000</label>
                        </div>
                        <div class="col-sm-4 text-left text-sm-right">
                          <p> Sisa Settlement: </p>
                          <label id="sisapencairan"  class="card-title" style="font-size:15px; color:#62d49e;">Rp.10.000</label>
                        </div>
                      </div>
                  </div>
                  <div class="col-md-5">
                      <div class="row">
                      <div class="col-sm-6 mb-3">
                    <div class="dropdown">
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
                  <div class="col-sm-6 mb-3">
                    <div class="dropdown">
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
                      </div>
                  </div> 
                
                  
                </div>

                  <table id="myTable" class="display" style="width:1200px">
                      <thead>
                          <tr>
                              <th>Inquiry No</th>
                              <th>Inquiry Date</th>
                              <th>Project ID</th>
                              <th>Mengetahui</th>
                              <th>Menyetujui</th>
                              <th>Total Inquiry</th>
                              <th>Nominal Inquiry</th>
                          </tr>
                      </thead>
                      <tbody>


                      </tbody>

                  </table>
              </div>
          </div>
      </div>
  </div>
  @include('pencairan.push')
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
<script>
$(document).ready(function(){
  <?php if (Auth::user()->status_password != 1) { ?>
        $('#modalPassword').modal('show');
  <?php } ?>

  load_data();

  function load_data(first = '',last = '' ) {
    // function load_data() {
      $('#myTable').dataTable({

        processing: false,
        serverSide: false,
        "order": [],
        "bPaginate": true,
        "bLengthChange": false,
        "bFilter": false,
        "bInfo": false,
        "bAutoWidth": false,
        "pageLength": 5,
         ajax: {
          url:'{{ url("pencairan") }}',
          data:{first:first,last:last}
         },
         columns: [

                  {
                    data: 'no_pengajuan',
                    name: 'no_pengajuan',
                    render: function(data, type, row, meta) {
                    if (type === 'display') {
                        data = '<a href="insertPencairan/' + data + '">' + data + '</a>';
                    }
                    return data;
                    }
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
                    data: 'mengetahui',
                    name: 'mengetahui'
                  },
                  {
                    data: 'menyetujui',
                    name: 'menyetujui'
                  },
                  {
                    data: 'totalpengajuan',
                    name: 'totalpengajuan'
                  },
                  {
                    data: 'action',
                    name: 'action'
                  },

            ],
    });
  }

  totalpencairan();
    function totalpencairan(first = '',last = ''){

      $.ajax({
          url: '/pencairan/totalpencairan/',
          type:"GET",
          dataType:"json",
          data:{first:first,last:last},

          success:function(data) {
  document.getElementById('totalpencairan').innerHTML = 'Rp. '+data[0];

          },

      });
    }

    sisapencairan();
      function sisapencairan(first = '',last = ''){

        $.ajax({
            url: '/pencairan/sisapencairan/',
            type:"GET",
            dataType:"json",
            data:{first:first,last:last},

            success:function(data) {
    document.getElementById('sisapencairan').innerHTML = 'Rp. '+data[0];

            },

        });
      }
    //
    $('#first').on('change', function(){
      var first = $('#first').val();
      var last = $('#last').val();
      $('#myTable').dataTable().fnDestroy();
      load_data(first,last);
      totalpencairan(first,last);
      sisapencairan(first,last);

     });
    $('#last').on('change', function(){
      var first = $('#first').val();
      var last = $('#last').val();
      $('#myTable').dataTable().fnDestroy();
      load_data(first,last);
      totalpencairan(first,last);
      sisapencairan(first,last);


    });


    });

</script>
<script type="text/javascript">
$(document).ready(function(){
  $(document).on('click', '.owner', function(){
      var id = $(this).attr('id');
      $('#edit_form').html('');
      $('#lbltipAddedComment').html('');
      document.getElementById('lbltipAddedComment').innerHTML = 'Detail Settlement UUDP';

      $.ajax({
      url: '/pengajuan/edit/'+id,
      dataType:"json",
      success:function(html){
        var x1 = document.getElementById("foo");
        x1.style.display = "block";
        var x2 = document.getElementById("foot");
        x2.style.display = "none";
        var x3 = document.getElementById("footer");
        x3.style.display = "none";

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
      $('#action').val("owner");
      $('#sum_edit').maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');


      }
    });



    $.ajax({
    url: '/pengajuan/detail/'+id,
    dataType:"json",
   success:function(data) {
     $('#dynamic_fields').html('');

     for (var i = 0; i < data.cek; i++) {
       var z = i+1;

       $('#dynamic_fields').append('<tr id="row'+i+'"><td>'+z+'</td><td><input type="hidden" name="id_list[]" id="id_list_'+i+'" value="'+data.hasil[i].id+'"><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_kelompok+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_daftar+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;" placeholder="Isi keterangan" value="'+data.hasil[i].keterangan+'" readonly class="form-control name_list" /></td><td><input type="text" id="limit_'+i+'" value="'+data.hasil[i].limit+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" readonly value="'+data.hasil[i].nominal_pengajuan+'" name="nominal_pengajuan[]" id="nominal_pengajuan_'+i+'" style="border-radius: 10px;"  placeholder="Nominal" class="form-control name_list nominal_pengajuan_edit" onclick="nominal_hitung_edit()"   /></td></tr>');
       $("#limit_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');
       $("#nominal_pengajuan_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

     }


    }
  })

  $.ajax({
  url: '/pencairan/cektermin/'+id,
  dataType:"json",
 success:function(data) {
   $('#max').val(data.cek);
   $('#dynamic_termin').html('');
   $('#maxcheck').val(data.cek);

   if(data.sum == 100){
   var x1 = document.getElementById("adds");
   x1.style.display = "none";
 }

   for (var i = 0; i < data.cek; i++) {
     var z = i+1;
     var status = data.hasil[i].status;
     var val = Math.floor(1000 + Math.random() * 9000);
     if(status == 1){
       $('#dynamic_termin').append('<tr id="row'+val+'"><td><div class="input-group"><input type="hidden" name="id_termins[]" value="'+data.hasil[i].id+'"><input type="hidden" name="status[]" id="id_status_'+data.hasil[i].id+'" value="'+data.hasil[i].status+'"><input type="number" name="nominal_termin[]" class="form-control name_termin" style="border-radius: 5px;"  id="percent_'+i+'" readonly value="'+data.hasil[i].nominal+'"  onKeyPress="if(this.value.length==3) return false;"   placeholder="100" aria-describedby="inputGroupPrepend"><div class="input-group-prepend"><span class="input-group-text" id="inputGroupPrepend" style="border-right: 5px;">%</span></div></div></td><td><input type="date" readonly name="date[]" id="date_'+i+'" value="'+data.hasil[i].date+'"class="form-control date_termin" style="border-radius: 10px;" value=""></td></tr>');

     }else {
       $('#dynamic_termin').append('<tr id="row'+val+'"><td><input type="hidden" name="id_termins[]"  value="'+data.hasil[i].id+'"><div class="input-group"><input type="hidden" name="status[]" id="id_status_'+data.hasil[i].id+'" value="'+data.hasil[i].status+'"><input type="number" name="nominal_termin[]" class="form-control name_termin" style="border-radius: 5px;"  id="percent_'+i+'" value="'+data.hasil[i].nominal+'"  onKeyPress="if(this.value.length==3) return false;"   placeholder="100" aria-describedby="inputGroupPrepend"><div class="input-group-prepend"><span class="input-group-text" id="inputGroupPrepend" style="border-right: 5px;">%</span></div></div></td><td><input type="date" name="date[]" id="date_'+i+'" value="'+data.hasil[i].date+'"class="form-control date_termin" style="border-radius: 10px;" value=""></td><td><button type="button" name="remove" id="'+z+'" onclick="hapus('+data.hasil[i].id+','+val+')" class="btn btn-danger btn_remove">X</button></td></tr>');

     }

   }


  }
})

  });


  $(document).on('click', '.proses', function(){
      var id = $(this).attr('id');
      $('#edit_form').html('');
      $('#lbltipAddedComment').html('');

      document.getElementById('lbltipAddedComment').innerHTML = 'Detail Settlement UUDP';

      $.ajax({
      url: '/pengajuan/edit/'+id,
      dataType:"json",
      success:function(html){
        var x1 = document.getElementById("foo");
        x1.style.display = "none";
        var x2 = document.getElementById("foot");
        x2.style.display = "block";
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


      }
    })

        $.ajax({
        url: '/pengajuan/detail/'+id,
        dataType:"json",
       success:function(data) {
         $('#dynamic_fields').html('');

         for (var i = 0; i < data.cek; i++) {
           var z = i+1;

           $('#dynamic_fields').append('<tr id="row'+i+'"><td>'+z+'</td><td><input type="hidden" name="id_list[]" id="id_list_'+i+'" value="'+data.hasil[i].id+'"><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_kelompok+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" id="id_budget_'+i+'" value="'+data.hasil[i].nama_daftar+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget" class="form-control name_list limit_budget" /></td><td><input type="text" name="keterangan_pengajuan[]" style="border-radius: 10px;" placeholder="Isi keterangan" value="'+data.hasil[i].keterangan+'" readonly class="form-control name_list" /></td><td><input type="text" id="limit_'+i+'" value="'+data.hasil[i].limit+'" readonly name="id_budget[]" style="border-radius: 10px;" placeholder="Limit Budget"  class="form-control name_list limit_budget" /></td><td><input type="text" readonly value="'+data.hasil[i].nominal_pengajuan+'" name="nominal_pengajuan[]" id="nominal_pengajuan_'+i+'" style="border-radius: 10px;"  placeholder="Nominal" class="form-control name_list nominal_pengajuan_edit" onclick="nominal_hitung_edit()"   /></td></tr>');
           $("#limit_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');
           $("#nominal_pengajuan_"+i).maskMoney({ thousands:'.', decimal:',', precision:0, affixesStay: true}).trigger('mask.maskMoney');

         }


        }
      })

      $.ajax({
      url: '/pencairan/cektermin/'+id,
      dataType:"json",
     success:function(data) {
       $('#max').val(data.cek);
       $('#dynamic_termin').html('');
       $('#maxcheck').val(data.cek);

       var x1 = document.getElementById("adds");
       x1.style.display = "none";
       if(data.sum == 100){
         var x1 = document.getElementById("footer");
         x1.style.display = "none";
       }else {
         var x1 = document.getElementById("footer");
         x1.style.display = "block";
       }
       for (var i = 0; i < data.cek; i++) {
         var z = i+1;
         var status = data.hasil[i].status;
         var val = Math.floor(1000 + Math.random() * 9000);
         if(status == 1){
           $('#dynamic_termin').append('<tr id="row'+val+'"><td><div class="input-group"><input type="hidden" name="id_termins[]" value="'+data.hasil[i].id+'"><input type="hidden" name="status[]" id="id_status_'+data.hasil[i].id+'" value="'+data.hasil[i].status+'"><input type="number" name="nominal_termin[]" class="form-control name_termin" style="border-radius: 5px;"  id="percent_'+i+'" readonly value="'+data.hasil[i].nominal+'"  onKeyPress="if(this.value.length==3) return false;"   placeholder="100" aria-describedby="inputGroupPrepend"><div class="input-group-prepend"><span class="input-group-text" id="inputGroupPrepend" style="border-right: 5px;">%</span></div></div></td><td><input type="date" readonly name="date[]" id="date_'+i+'" value="'+data.hasil[i].date+'"class="form-control date_termin" style="border-radius: 10px;" value=""></td></tr>');

         }else {
           $('#dynamic_termin').append('<tr id="row'+val+'"><td><input type="hidden" name="id_termins[]" value="'+data.hasil[i].id+'"><div class="input-group"><input type="hidden" name="status[]" id="id_status_'+data.hasil[i].id+'" value="'+data.hasil[i].status+'"><input type="number" name="nominal_termin[]" readonly class="form-control name_termin" style="border-radius: 5px;"  id="percent_'+i+'" value="'+data.hasil[i].nominal+'"  onKeyPress="if(this.value.length==3) return false;"   placeholder="100" aria-describedby="inputGroupPrepend"><div class="input-group-prepend"><span class="input-group-text" id="inputGroupPrepend" style="border-right: 5px;">%</span></div></div></td><td><input type="date" name="date[]" id="date_'+i+'" readonly value="'+data.hasil[i].date+'"class="form-control date_termin" style="border-radius: 10px;" value=""></td><td></td></tr>');

         }

       }


      }
    })
    });
})

var i = 0;
// var i = document.getElementById("maxcheck").value;
// console.log(i);
$('#adds').click(function(){
    i++;

     var id_pengajuan = $('#id_pengajuan').val();

     $('#dynamic_termin').append('<tr id="row'+i+'"><td><input type="hidden" name="status[]" id="id_status_'+i+'" value="0"><input type="hidden" name="id_termins[]" id="id_termin_'+i+'" value=""><div class="input-group"><input type="number" name="nominal_termin[]" class="form-control name_termin" style="border-radius: 5px;" id="percent"  onKeyPress="if(this.value.length==3) return false;"   placeholder="100" aria-describedby="inputGroupPrepend" ><div class="input-group-prepend"><span class="input-group-text" id="inputGroupPrepend" style="border-right: 5px;">%</span></div></div></td><td><input type="date" name="date[]" class="form-control date_termin" style="border-radius: 10px;" value=""></td><td><button type="button" name="remove" id="'+i+'" class="btn btn-danger btn_removes">X</button></td></tr>');
addtermin(id_pengajuan, i);
});

$(document).on('click', '.btn_removes', function(){

    var k = i;
    var id_termin = $('#id_termin_'+k).val();
    i--;
    var button_id = $(this).attr("id");
    $('#row'+button_id+'').remove();
     $.ajax({
     url: '/pencairan/deletetermin/'+id_termin,
     dataType:"json",
    success:function(data) {

    }

   });
});

function addtermin(s, h){
  $.ajax({
  url: '/pencairan/addtermin/'+s,
  dataType:"json",
 success:function(data) {
$('#id_termin_'+h).val(data);

   // $('#row'+a+'').remove();

 }

});
}


</script>

<script type="text/javascript">
function hapus(a, b){
  console.log(b);
  $.ajax({
  url: '/pencairan/deletetermin/'+a,
  dataType:"json",
 success:function(data) {

   $('#row'+b+'').remove();

 }

});
}
  $('#sample_edit').on('submit', function(event){
    event.preventDefault();

    if($('#action').val() == "proses")
    {
      $.ajax({
      url:"{{ url('pencairan/push') }}",
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
      url:"{{ url('pencairan/termin') }}",
      method:"POST",
      data: new FormData(this),
      contentType: false,
      cache:false,
      processData: false,
      dataType:"json",
       success:function(data) {

                $('#sample_edit')[0].reset();
                $('#myTable').DataTable().ajax.reload();
                location.reload();
                $('#formModaledit').modal('hide');
            },

    });


    }

    });
</script>

@endpush
@endsection
