<style>
   .form-control{
        border-radius:5px;
    }
    .custom{
        height:2em;
        width:80%;
        border-radius: 5px;
    }
</style>

<div class="modal-body">
<span id="form_result"></span>
  <form id="sample_form" novalidate>
  @csrf
      <input type="hidden" name="hidden_id" id="hidden_id">
      <input type="hidden" name="action" id="action" />
      <input type="hidden" name="idmain" id="idmain" value="{{$id}}" />
      <input type="hidden" name="hidden_id" id="hidden_id" value="{{$project->id_project}}" />
      <div class="form-group">
          <label>Kode Projek </label>
          <input type="text" class="form-control" id="no_project" name="no_project"  placeholder="Masukkan nomor atau kode projek" value="{{$project->no_project}}" required >
      </div>
      <div class="form-group">
          <label>Nama Projek</label>
          <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama projek" value="{{$project->nama}}" required>
      </div>

      @if($cekBudget==0)
      <div id="limit-budget">
        <div class="form-row control-group increment">
            <input type="hidden" name="cekpost" value="add">
            <div class="form-group col-md-4">
                <label>Induk Kegiatan</label>
                <select class="custom-select form-control select-reset" name="id_kelompok[]" id="id_kelompok1" onclick="getDaftar('1')" required>
                  <option value="">--Pilih Induk Kegiatan--</option>
                  @foreach($kelompok as $row)
                      <option value="{{$row->id_kelompok}}">{{$row->nama}}</option>
                  @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Sub Kegiatan</label>
                 <select class="custom-select form-control" name="id_daftar[]" id="id_daftar1" required>
                  <option value="">--Pilih Sub Kegiatan--</option>
                  @foreach($daftar as $row)
                      <option value="{{$row->id_daftar}}">{{$row->nama}}</option>
                  @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Limit Budget</label>
                 <input type="text" class="form-control uang" name="limit[]">
            </div>
            <div class="form-group col-md-1">
                <label><br></label>
                <button type="button" class="btn btn-success" style="margin-top: 30px">+</button>
            </div>
        </div>

        <div class="new-input">
        
        </div>

      @else

      @foreach ($listBudget as $list)
      
      @if ($loop->first)

      <div id="limit-budget">
        <div class="form-row control-group increment">
            <input type="hidden" name="idbudget[]" value="{{$list->id}}">
            <input type="hidden" name="cekpost" value="edit">
            <div class="form-group col-md-4">
                <label>Induk Kegiatan</label>
                <select class="custom-select form-control" name="id_kelompok_edit[]" id="id_kelompok1" onclick="getDaftar('1')" required>
                  <option value="">--Pilih Induk Kegiatan--</option>
                  @foreach($kelompok as $row)
                      <option value="{{$row->id_kelompok}}" {{$list->id_kelompok == $row->id_kelompok ? "selected" : "" }}>{{$row->nama}}</option>
                  @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Sub Kegiatan</label>
                 <select class="custom-select form-control" name="id_daftar_edit[]" id="id_daftar1" required>
                  <option value="">--Pilih Sub Kegiatan--</option>
                  @foreach($daftar as $row)
                      <option value="{{$row->id_daftar}}" {{$list->id_daftar == $row->id_daftar ? "selected" : "" }}>{{$row->nama}}</option>
                  @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Limit Budget</label>
                 <input type="text" class="form-control uang" name="limit_edit[]" value="{{$list->limit}}">
            </div>
            <div class="form-group col-md-1">
                <label><br></label>
                <button type="button" class="btn btn-warning btn-clone" style="margin-top: 30px">+</button>
            </div>
        </div>
        @endif
        @endforeach

        <?php
            $num = 1; 
            $num_kelompok = 2;
            $num_daftar = 2;
            $num_fuction = 2;
            foreach(array_slice($listBudget, $num) as $key => $value) {
        ?>

        <div class="clone-copy hide">
          <div class="form-row control-group remove-form">
            <div class="form-group col-md-4">
                <select class="custom-select form-control" name="id_kelompok_edit[]" id="id_kelompok{{$num_kelompok++}}" onclick="getDaftar('{{$num_fuction++}}')" required>
                  <option value="">--Pilih Induk Kegiatan--</option>
                  @foreach($kelompok as $row)
                      <option value="{{$row->id_kelompok}}" {{$value->id_kelompok == $row->id_kelompok ? "selected" : "" }}>{{$row->nama}}</option>
                  @endforeach
                </select>

            </div>
            <div class="form-group col-md-4">
                <select class="custom-select form-control" name="id_daftar_edit[]" id="id_daftar{{$num_daftar++}}" required>
                  <option value="">--Pilih Sub Kegiatan--</option>
                  @foreach($daftar as $row)
                      <option value="{{$row->id_daftar}}" {{$value->id_daftar == $row->id_daftar ? "selected" : "" }}>{{$row->nama}}</option>
                  @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <input type="text" class="form-control uang" name="limit_edit[]" value="{{$value->limit}}">
            </div>
            <div class="form-group col-md-1">
                <label></label>
                <button type="button" class="btn btn-danger">-</button>
            </div>
        </div>
      </div>

      <?php } ?>

      </div>

      @endif

      <div class="new-edit">
        
      </div>

      <div class="form-group">
          <label>Deskripsi</label>
          <textarea class="form-control" name="keterangan" id="keterangan" placeholder="Masukkan Deskripsi Projek" required>{{$project->keterangan}}</textarea>
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
      var i = 1;
      var j = "{{count($listBudget)}}";
      $('#inc-hide').hide();
      $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});

      $(".btn-success").click(function(){ 
        // $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});
        i++;
        $(".new-input").append('<div class="clone hide"><div class="form-row control-group remove-form"><div class="form-group col-md-4"><select class="custom-select form-control" name="id_kelompok[]" id="id_kelompok'+i+'" onclick="getDaftar('+i+')" required><option value="">--Pilih Induk Kegiatan--</option>@foreach($kelompok as $row)<option value="{{$row->id_kelompok}}">{{$row->nama}}</option>@endforeach</select></div><div class="form-group col-md-4"><select class="custom-select form-control" name="id_daftar[]" id="id_daftar'+i+'" required><option value="">--Pilih Sub Kegiatan--</option>@foreach($daftar as $row)<option value="{{$row->id_daftar}}">{{$row->nama}}</option>@endforeach</select></div><div class="form-group col-md-3"><input type="text" class="form-control uang" name="limit[]"></div><div class="form-group col-md-1"><label></label><button type="button" class="btn btn-danger">-</button></div></div></div></div>');
        // $(".select-reset option:selected").prop("selected", false);
      });

      $(".btn-clone").click(function(){ 
        j++;
        // $( '.uang' ).mask('0.000.000.000.000.000', {reverse: true});
        $(".new-edit").append('<div class="clone-edit"><div class="form-row control-group remove-form"><div class="form-group col-md-4"><select class="custom-select form-control" name="id_kelompok_edit[]" id="id_kelompok'+j+'" onclick="getDaftar('+j+')" required><option value="">--Pilih Induk Kegiatan--</option>@foreach($kelompok as $row)<option value="{{$row->id_kelompok}}">{{$row->nama}}</option>@endforeach</select></div><div class="form-group col-md-4"><select class="custom-select form-control" name="id_daftar_edit[]" id="id_daftar'+j+'" required><option value="">--Pilih Sub Kegiatan--</option>@foreach($daftar as $row)<option value="{{$row->id_daftar}}">{{$row->nama}}</option>@endforeach</select></div><div class="form-group col-md-3"><input type="text" class="form-control uang" name="limit_edit[]"></div><div class="form-group col-md-1"><label></label><button type="button" class="btn btn-danger">-</button></div></div></div>');
      });

      $("body").on("click",".btn-danger",function(){ 
        $(this).parents(".remove-form").remove();
      });

      $('#sample_form').on('submit', function(event){
            event.preventDefault();
            $("#action_button").prop("disabled", true);
            
            $.ajax({
                 url:"{{ route('project.update') }}",
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

</script>

<script type="text/javascript">
    function getDaftar(id)
    {
          $('select[id="id_kelompok'+id+'"]').on('change', function(){
          var id_kelompok = $(this).val();
          console.log(id_kelompok);
          if(id_kelompok) {
          $.ajax({
          url: '/daftar_rencana/get/'+id_kelompok+'/',
          type:"GET",
          dataType:"json",
          success:function(data) {
              $('select[id="id_daftar'+id+'"]').empty();
              $('select[id="id_daftar'+id+'"]').append('<option value="">Pilih Sub Kegiatan</option>');
              $.each(data, function(key, value){
              $('select[id="id_daftar'+id+'"]').append('<option value="'+ key +'">' + value + '</option>');
              });
          },
          });
          } else {
          $('select[id="id_daftar'+id+'"]').empty();
          }
          });
    };
</script>

