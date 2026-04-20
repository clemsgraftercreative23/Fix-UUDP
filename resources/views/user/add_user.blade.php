@extends('template.app')

@section('content')

<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                <div class="col-12"><p class="card-title clr-green" >Dashboard</p></div>
                  <div class="col-md-6">
                  <h2 class="card-title clr-green">Tambah User Aplikasi</h2>
                  </div>
                </div>

                <form id="sample_form">
                  @csrf
                      <input type="hidden" name="action" id="action" />
                      <input type="hidden" name="hidden_id" id="hidden_id" />
                      <div class="form-group">
                          <label>NIP</label>
                          <!--  -->
                          <select class="form-control nip" name="nip" required>
                            <option value="">--Pilih Karyawan--</option>
                            @foreach($user as $row)
                                <option value="{{$row->username}}">{{$row->username}} - {{$row->name}}</option>
                            @endforeach
                          </select>
                      </div>
                      <div class="form-group">
                          <label>Nama Lengkap</label>
                          <input type="text" class="form-control" id="name"  placeholder="" readonly>
                      </div>
                      <div class="form-group">
                          <label>Domisili</label>
                          <input type="text" class="form-control" id="domisiliType"  placeholder="" readonly>
                      </div>
                      <div class="form-group">
                          <label>Status Karyawan</label>
                          <input type="text" class="form-control" id="employeeWorkStatus"  placeholder="" readonly>
                      </div>
                      <div class="form-group">
                          <label>Tanggal Bergabung</label>
                          <input type="text" class="form-control" id="joinDate"  placeholder="" readonly>
                      </div>
                      <div class="form-group">
                          <label>Departemen</label>
                          <select class="form-control" name="departmentId" id="departmentId" required>
                              <option value="-">--Piih Departemen--</option>
                              @foreach($departemen as $data)
                              <option value="{{$data->id}}">{{$data->nama_departemen}}</option>
                              @endforeach
                          </select>
                      </div>
                      <div class="form-group">
                          <label>Jabatan</label>
                          <select class="form-control" name="jabatan" required>
                              <option value="-">--Piih Jabatan--</option>
                              <option value="superadmin">Admin</option>
                              <option value="Direktur Operasional">Head Department</option>
                              <option value="Finance">HR GA</option>
                              <option value="Finance Supervisor">Finance Supervisor</option>
                              <option value="Owner">Finance</option>
                          </select>
                      </div>
                      </div>
                      <center>
                      <div class="modal-footer">
                          <a href="{!!url('user_aplikasi')!!}" class="btn btn-secondary" data-dismiss="modal">Batal</a>
                          <button type="submit" class="btn btn-primary" name="action_button" id="action_button">Simpan</button>
                      </div>
                      </center>
                </form>

              </div>
          </div>
      </div>
  </div>

  
  
@push('scripts')

<script type="text/javascript">
    $(".nip").change(function() {
        var id = $(this).val();
        $.ajax({
            url: '/fillEmployee/' + $(this).val(),
            type: 'get',
           data: {id : 'id'},
           success: function(html) {
              $('#name').val(html.data.name);
              $('#domisiliType').val(html.data.domisiliType);
              $('#employeeWorkStatus').val(html.data.employeeWorkStatus);
              $('#joinDate').val(html.data.joinDate);
              $('#hidden_id').val(html.data.id);
           },
          error: function(jqXHR, textStatus, errorThrown) {}
       });
    });

    $('#sample_form').on('submit', function(event){
            event.preventDefault();
            $("#action_button").prop("disabled", true);
            
            $.ajax({
                 url:"{{ route('user_aplikasi.update') }}",
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
                 window.location.href = "user_aplikasi";
                 }
                 $('#form_result').html(html);
                 $('#formModal').modal('hide');
                 
                 },
                 complete: function(){
                 $('.loader').css("visibility", "hidden");
                 }
             });
        
      });
</script>

@endpush
@endsection
