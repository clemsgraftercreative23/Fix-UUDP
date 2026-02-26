
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
<div class="modal-body">
<span id="form_result"></span>
  <hr>
  <table border="0">
        <thead>
            <tr>
                <td width="500px"><span style="color:#66da90;"><h4>Buat Pertanggungjawaban</h4></span></td>
                <td width="60px">Tanggal : </td>
                <td width="240px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{date('d-m-Y', strtotime($pengajuan['0']->created_at))}}"></td>
                <td width="60px">Nomor : </td>
                <td width="240px"><input type="text" class="form-control custom" readonly style="background-color: #ffffff;" value="{{$pengajuan['0']->no_pengajuan}}"></td>
            </tr>
        </thead>
    </table> 
    <hr>
    <table style="border-collapse:collapse;">
        <thead>
            <tr>
                <td width="1100px" class="dotted">
                    <span style="color:#66da90;">
                        <br><h4>&nbsp;&nbsp;&nbsp;Total Inquiry : <br> &nbsp;&nbsp;&nbsp;Rp. {{number_format($pengajuan['0']->nominal_pengajuan,2,',','.')}}</h4><br>
                    </span>
                </td>
            </tr>
        </thead>
    </table> 
    <hr><span style="color:#66da90;"><h5>Rincian Kegiatan</h5></span><hr>
     <table class="table table-bordered">
            <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Induk Kegiatan</th>
                <th width="28%">Sub Kegiatan</th>
                <th width="20%">Nominal Inquiry</th>
                <th width="37%">Keterangan</th>
            </tr>
            </thead>
            <?php $no=1;?>
            @foreach($list_pengajuan as $row)
            <tr>
                <td width="1px" rowspan="3">{{$no++}}</td>
                <td width="100px"><strong>{{$row->nama_kelompok}}</strong></td>
                <td width="200px"><strong>{{$row->nama_daftar}}</strong></td>
                <td width="200px"><strong>{{$row->nominal_pengajuan}}</strong></td>
                <td width="200px"><strong>{{$row->keterangan}}</strong></td>
            </tr>
            <tr>
                <td width="100px"><span style="color:red; background-color:yellow"><i><u>*) Isi Pertanggungjawaban</u></i></span></td>
                <td width="200px">
                    <select class="form-control" name="departmentName[]">
                       <option value="">--Pilih Departemen--</option>
                        @foreach($departemen as $r)
                            <option value="{{$r->nama_departemen}}">{{$r->nama_departemen}}</option>
                        @endforeach
                    </select>
                </td>
                <td width="200px">
                    <input type="hidden" class="form-control" name="accountNo[]" value="{{$row->noWithIndent}}">
                    <input type="text" class="form-control" name="amount[]"  placeholder="Nominal Realisasi">
                    <input type="hidden" class="form-control" name="amountType[]" value="DEBIT">
                    <input type="hidden" class="form-control" name="subsidiaryType[]" value="EMPLOYEE">
                    <input type="hidden" class="form-control" name="employeeNo[]" value="{{$user->username}}">
                    <input type="hidden" class="form-control" name="projectNo[]" value="{{$project->no_project}}">
                </td>
                <td width="200px"><input type="file" class="form-control" name="image[]" ></td>
            </tr>
            <tr>
                <td>Deskripsi Kegiatan</td>
                <td colspan="3"><textarea class="form-control"></textarea></td>
            </tr>
            @endforeach
    </table>
  <p>
  <a class="btn btn-primary" data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
    Klik jika ada pengembalian
  </a>
</p>
<div class="collapse" id="collapseExample">
  <div class="card card-body">
    <table class="table table-bordered">
        <thead>
            <tr>
                <td>Jenis</td>
                <td scope="col">
                    <select class="form-control" name="metode">
                        <option value="">--Pilih Jenis--</option>
                        <option value="sisa">Sisa</option>
                        <option value="kurang">Kurang</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Metode Pengembalian</td>
                <td scope="col">
                    <select class="form-control" name="metode">
                       <option value="">--Pilih Metode Settlement--</option>
                        @foreach($kasbank as $row)
                            <option value="{{$row->kode_perkiraan}}">{{$row->nama}}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
            <tr>
                <td>Sumber</td>
                <td scope="col">
                    <select class="form-control" name="sumber">
                        <option value="">--Pilih Sumber--</option>
                    </select>
                </td>
            </tr>
             <tr>
                <td>Nominal</td>
                <td scope="col"><input type="text" class="form-control uang" name=""></td>
            </tr>
        </thead>
    </table> 
  </div>
</div>
<hr><span style="color:#66da90;"><h5>Deskripsi</h5></span><hr>
<table class="table table-bordered">
    <thead>
        <tr>
            <td>Tanggal Pertanggungjawaban</td>
            <td scope="col">
                <?=date('d-m-Y')?>
            </td>
        </tr>
        <tr>
            <td>
                Judul Pertanggungjawaban<br>
                <small><i>(Masukkan judul singkat untuk laporan ini)</i></small>
            </td>
            <td scope="col"><input type="text" class="form-control uang" name=""></td>
        </tr>
    </thead>
</table> 

<hr><br>
    <center>
        <button type="button" class="btn btn-primary" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" data-dismiss="modal">Simpan</button>
    </center>
<br><br>  

</div>

<script type="text/javascript">
    
    $('select[name="metode"]').on('change', function(){
        var id = $(this).val();
        if(id) {
            $.ajax({
                url: '../getMetode/'+id,
                type:"GET",
                dataType:"json",
                beforeSend: function(){
                    $('.loader').css("visibility", "visible");
                },

                success:function(data) {

                    $('select[name="sumber"]').empty();

                    $.each(data, function(key, value){

                        $('select[name="sumber"]').append('<option value="'+ key +'">' + value + '</option>');

                    });
                },
                complete: function(){
                    $('.loader').css("visibility", "hidden");
                }
            });
        } else {
            $('select[name="sumber"]').empty();
        }

    });
</script>


