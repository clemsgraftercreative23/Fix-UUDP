

<div class="modal-body">
<span id="form_result"></span>
  <hr>
  Berikut adalah detail pencairan dana dari pengajuan nomor {{$pengajuan['0']->no_pengajuan}}: 
  <hr>
   <table class="table table-bordered">
        <thead>
            <tr>
                <th scope="col">Kode / Nama Proyek</th>
                <th scope="col">{{$proyek['0']->no_project}} / {{$proyek['0']->nama}}  </th>
            </tr>
            <tr>
                <th scope="col">Tanggal Settlement</th>
                <th scope="col">{{date("d-m-Y", strtotime($pencairan['0']->tgl_pencairan))}}  </th>
            </tr>
            <tr>
                <th scope="col">Metode Settlement</th>
                <th scope="col">{{$metode}}  </th>
            </tr>
            <tr>
                <th scope="col">Sumber</th>
                <th scope="col">{{$sumber}}  </th>
            </tr>
            <tr>
                <th scope="col">Penerima</th>
                <th scope="col">{{$pencairan['0']->penerima}}  </th>
            </tr>
            <tr>
                <th scope="col">Bank Penerima</th>
                <th scope="col">{{$pencairan['0']->bank}}  </th>
            </tr>
            <tr>
                <th scope="col">No Rekening</th>
                <th scope="col">{{$pencairan['0']->no_rek}}  </th>
            </tr>
            <tr>
                <th scope="col">Nominal Settlement</th>
                <th scope="col">{{$pencairan['0']->nominal}}  </th>
            </tr>
            <tr>
                <th scope="col">Keterangan</th>
                <th scope="col">{{$pencairan['0']->keterangan}}  </th>
            </tr>
            <tr>
                <th scope="col">Bukti Transfer</th>
                <th scope="col">
                  <a href="{{ URL::to('/') }}/images/file_bukti/{{$pencairan['0']->file_bukti}}" target="_blank">
                    <i class="fas fa-credit-card"></i> FILE BUKTI
                  </a>
                </th>
            </tr>
            <tr>
                <th scope="col">Pengirim (HR GA)</th>
                <th scope="col">{{$pengirim}} </th>
            </tr>
        </thead>
    </table>  
    <hr><br>
        <center><button type="button" class="btn btn-primary" data-dismiss="modal">Tutup</button></center>
    <br><br>  

</div>


