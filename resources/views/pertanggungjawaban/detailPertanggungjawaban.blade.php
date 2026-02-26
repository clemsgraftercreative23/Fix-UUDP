

<div class="modal-body">
<span id="form_result"></span>
  <form id="sample_form">
  @csrf
      <input type="hidden" name="hidden_id" id="hidden_id">
      <input type="hidden" name="action" id="action" />
      <input type="hidden" name="id_pengajuan" id="id_pengajuan" value="{{$id_pengajuan}}" />
      <input type="hidden" name="id_listpengajuan" id="id_listpengajuan" value="{{$id_listpengajuan}}" />
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Nominal Inquiry</label>
            <input type="text" class="form-control"  value="{{$amount_pengajuan}}" readonly>
        </div>
        <div class="form-group col-md-4">
          <label>Keterangan</label>
            <input type="text" class="form-control"  value="{{$keterangan}}" readonly>
        </div>
        
      </div>
      <div class="form-group">
          <label>Deskripsi Umum Kegiatan</label>
          <textarea class="form-control" name="deskripsi" id="deskripsi" required></textarea>
      </div>

      
      <div id="limit-budget" class="increment">
        <div class="form-row control-group ">
            <div class="form-group col-md-4">
                <label>Induk Kegiatan</label>
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
              <input type="hidden" name="id_department[]" value="{{$id_pengajuan}}" />
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
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
                 url:"{{ route('pertanggungjawaban.store') }}",
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
                    $("#action_button").prop("disabled", false);

                 }
             });
        
      });

  });


</script>


