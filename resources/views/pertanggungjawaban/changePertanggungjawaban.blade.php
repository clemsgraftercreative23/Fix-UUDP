

<div class="modal-body">
<span id="form_result"></span>
  <form id="sample_form">
  @csrf
      <input type="hidden" name="hidden_id" id="hidden_id">
      <input type="hidden" name="action" id="action" />
      <input type="hidden" name="id_pengajuan" id="id_pengajuan" value="{{$id_pengajuan}}" />
      <input type="hidden" name="id_listpengajuan" id="id_listpengajuan" value="{{$id_listpengajuan}}" />
      <input type="hidden" name="id_pertanggungjawaban" id="id_pertanggungjawaban" value="{{$pertanggungjawaban['0']->id}}" />
    
      <div class="form-group">
          <label>Deskripsi Kegiatan</label>
          <textarea class="form-control" name="deskripsi" id="deskripsi" required>{{$pertanggungjawaban['0']->deskripsi}}</textarea>
      </div>
      

      <div class="form-group">
          <label>Daftar Pertanggungjawaban yang sudah ditambahkan</label>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                  <td>Nominal Realisasi</td>
                  <td>File Bukti</td>
                  <td>Aksi</td>
              </tr>
            </thead>
            <tbody>
              @foreach ($detail as $item)
                  <tr>
                    <td>{{number_format($item->nominal_realisasi,0,',','.')}}</td>
                    <td><a href="#" class="btn btn-sm btn-info">File</a></td>
                    <td>
                        <button type="button" onclick="" class="btn btn-danger btn-danger">-</button>
                    </td>
                  </tr>
              @endforeach
            </tbody>
          </table>
          {{ csrf_field() }}
      </div>

     

      <hr>
      <div id="limit-budget" class="increment">
        <div class="form-row control-group ">
            <div class="form-group col-md-4">
                <label>Departemen</label>
                 <input type="text" name="departemen[]" id="departemen" class="form-control"  value="{{$nama_induk}}" readonly>
            </div>
            <div class="form-group col-md-4">
                <label>File Bukti</label>
                <input type="file" name="images[]" class="form-control" required>
            </div>
            <div class="form-group col-md-3">
                <label>Nominal Realisasi</label>
                 <input type="text" name="nominal_realisasi[]" id="nominal_realisasi" class="form-control uang" required>
            </div>
            <div >
              <input type="hidden" name="id_peng[]" value="{{$id_pengajuan}}" />
              <input type="hidden" name="id_listpeng[]" value="{{$id_listpengajuan}}" />
              <input type="hidden" name="noWithIndent[]" value="{{$noWithIndent}}">
            </div>
            <div class="form-group col-md-1">
                <label><br></label>
                <button type="button" class="btn btn-success">+</button>
            </div>
        </div>

        <div class="clone hide" id="inc-hide">
          <div class="form-row control-group remove-form">
            <div class="form-group col-md-4">
                <input type="text" name="departemen[]" id="departemen" class="form-control"  value="{{$nama_induk}}" readonly>
            </div>
            <div class="form-group col-md-4">
                <input type="file" name="images[]" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <input type="text" name="nominal_realisasi[]" class="form-control uang">
            </div>
            <div >
              <input type="hidden" name="id_peng[]" value="{{$id_pengajuan}}" />
              <input type="hidden" name="id_listpeng[]" value="{{$id_listpengajuan}}" />
              <input type="hidden" name="noWithIndent[]" value="{{$noWithIndent}}">
            </div>
            <div class="form-group col-md-1">
                <label></label>
                <button type="button" class="btn btn-danger" style="margin-top: -15px">-</button>
            </div>
        </div>
        </div>
      </div>
      <div class="modal-footer">
          <a href="javascript:history.go(0)" class="btn btn-secondary">Kembali</a>
          <button type="submit" class="btn btn-primary" name="action_button" id="action_button">Simpan</button>
      </div>

      

</form>
</div>

 

<script type="text/javascript">
  $(document).on('click', function(e) {
    if ( e.target.id != 'ok' ) {
        $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});
    }
  });

  deleteDetail(id) {
    // $.ajax({
    //   url: '/pertanggungjawaban/detail/'+id,
    //   method: 'DELETE',
    //   data: {
    //     _token: "{{csrf_token()}}"
    //   },
    //   success: function() {
    //     window.reload();
    //   }
    // })
  }

  $(document).ready(function(){
      $('#inc-hide').hide();
      $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});

      $(".btn-success").click(function(){ 
        // $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});
        var html = $(".clone").last().html();
        $(".increment").append(html);
        // $(".select-reset option:selected").prop("selected", false);
      });

      $(".btn-clone").click(function(){ 
        // $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});
        var html = $(".clone-edit").html();
        $(".clone-copy").last().append(html);
      });

      $("body").on("click",".btn-danger",function(){ 
        $(this).parents(".remove-form").remove();
      });


      
      $('#sample_form').on('submit', function(event){
            event.preventDefault();
            $("#action_button").prop("disabled", true);
            
            $.ajax({
                 url:"../../pertanggungjawaban/change",
                 method:"POST",
                 data:new FormData(this),
                 contentType: false,
                 cache: false,
                 processData: false,
                 dataType:"json",
                 beforeSend: function(){
                 $('.loader').css("visibility", "visible");
                 $("#action_button").prop("disabled", true);
                 },
                 success:function(data)
                 {
                 var html = '';
                 if(data.errors)
                 {
                 alert('Data gagal disimpan!');
                 }
                 if(data.success)
                 {
                 alert('Data berhasil disimpan!');
                 location.reload();
                 }
                 $('#form_result').html(html);
                 $('#formModal').modal('hide');
                 
                 },
                 complete: function(){
                 $('.loader').css("visibility", "hidden");
                 }
             });
        
      });

  });

  $(document).ready(function(){

   fetch_data();

   function fetch_data()
   {
    var id = {{$pertanggungjawaban['0']->id}};
    console.log(id);
    $.ajax({
     url:"../../pertanggungjawaban/fetchData/"+id,
     dataType:"json",
     success:function(data)
     {
        var html = '';
        for(var count=0; count < data.length; count++)
        {

        var bilangan = data[count].nominal_realisasi;
        var number_string = bilangan.toString(),
        sisa  = number_string.length % 3,
        rupiah  = number_string.substr(0, sisa),
        ribuan  = number_string.substr(sisa).match(/\d{3}/g);
        if (ribuan) {
        separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
        }       
       html +='<tr>';
       html +='<td  class="column_name" data-column_name="nama_departemen" data-id="'+data[count].id+'">'+data[count].nama_departemen+'</td>';
       html +='<td  class="column_name" data-column_name="nominal_realisasi" data-id="'+data[count].id+'">'+rupiah+'</td>';
       html += '<td><a href="{{ URL::to('/') }}/images/pertanggungjawaban/'+data[count].images+'" class="btn btn-success btn-xs" target="_blank"><i class="fa fa-file"></i></a></td>';
       html += '<td><button type="button" class="btn btn-danger btn-xs delete" id="'+data[count].id+'"><i class="fa fa-trash"></i></button></td></tr>';
      }
      $('tbody').html(html);
     }
    });
   }

   var _token = $('input[name="_token"]').val();
   $(document).on('click', '.delete', function(){
      var id = $(this).attr("id");
      console.log(id);
      if(confirm("Apakah Anda yakin ingin menghapus data ini?"))
      {
       $.ajax({
        url:"{{ route('pertanggungjawaban.deleteData') }}",
        method:"POST",
        data:{id:id, _token:_token},
        success:function(data)
        {
         $('#message').html(data);
         fetch_data();
        }
       });
      }
   });

   


  });

</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.4.0/bootbox.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.4.0/bootbox.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.4.0/bootbox.min.js.map" type="text/javascript"></script>


