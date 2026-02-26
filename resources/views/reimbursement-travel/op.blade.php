<div class="modal fade bd-example-modal-xl" tabindex="-1" id="formModaledit"  role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
  <form method="post" id="sample_edit"  enctype="multipart/form-data">
  @csrf
                                            <div class="modal-dialog modal-xl">
                                                <div class="modal-content">
                                                  <div class="modal-header"  >
<input type="hidden" name="id_pengajuan" id="id_pengajuan" value="">
<input type="hidden" name="action" id="action" />

                                                   

                                                    

                                                  </div>

                                                  <div class="modal-body">
                                                  <div class="row">
                                                      <div class="col-md-12 mb-3 d-flex justify-content-between">
                                                        <h2 class="modal-title clr-green" id="lbltipAddedComment" ></h2>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                          <i class="material-icons">close</i>
                                                      </button>
                                                      </div>

                                                      <div class="col-md-6">
                                                        <div class="form-group row">
                                                            <label for="inputEmail3" class="col-sm-2 col-form-label">Tgl</label>
                                                            <div class="col-sm-10">
                                                              <input type="text" class="form-control" id="tgl"  readonly placeholder="12/12/2020">
                                                            </div>
                                                          </div>
                                                        </div>

                                                      <div class="col-md-6">
                                                        <div class="form-group row">
                                                            <label for="inputEmail3" class="col-sm-5 col-form-label">Nomor Inquiry</label>
                                                            <div class="col-sm-7">
                                                              <input type="text" name="no_pengajuan" id="no_pengajuan_edit" readonly class="form-control"   placeholder="UUDP">
                                                            </div>
                                                          </div>
                                                      </div>
                                                    </div>
                                                    <div class="row">
                                                      <div class="col-md-4">
                                                        <div class="form-group">
                                       <label for="exampleFormControlInput1">Nama Lengkap</label>
                                       <input type="hidden" name="id_user" value="{{Auth::user()->id}}">
                                       <input type="email" class="form-control" id="nama_lengkap" readonly style="border-radius: 10px;" placeholder="Nama Lengkap" >
                                       </div>
                                                      </div>
                                                      <div class="col-md-4">
                                                        <div class="form-group">
                                       <label for="exampleFormControlInput1">NIK Karyawan</label>
                                       <input type="email" class="form-control" id="nik" readonly style="border-radius: 10px;" >
                                       </div>
                                                      </div>
                                                      <div class="col-md-4">
                                                        <div class="form-group">
                                       <label for="exampleFormControlInput1">Jabatan</label>
                                       <input type="email" class="form-control" id="jabatan" readonly style="border-radius: 10px;" >
                                       </div>
                                                      </div>
                                                      <div class="col-md-12">
                                                        <hr>
                                                        <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Pilih Project</label>

                                                        <div class="form-group">
                                       <label for="exampleFormControlInput1">No Project</label>
                                       <input type="text" id="id_project_edit" readonly style="border-radius: 10px;" name="id_project" class="form-control">
                                       </div>
                                       <div class="form-group">
                                       <label for="exampleFormControlInput1">Nama Project</label>
                                       <input type="email" class="form-control" id="nama_project_edit" style="border-radius: 10px;" readonly placeholder="" name="nama_project">
                                       </div>
                                       <div class="form-group">
                                       <label for="exampleFormControlInput1">Keterangan Project</label>
                                       <input type="email" class="form-control" id="keterangan_project_edit" style="border-radius: 10px;" readonly placeholder="" name="keterangan_project">
                                       </div>
                                                      </div>
                                                    </div>
                                                    <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Inquiry</label>

                                                 <div class="respon table-responsive">
                                                 <table   cellpadding=3 cellspacing=3
                                                align=center width=100%>
                                                      <thead>
                                                          <tr>
                                                              <th align="center" width="5%">No.</th>
                                                              <th align="center" width="22%">Kelompok Rencana Kegiatan</th>
                                                              <th align="center" width="22%">Daftar Rencana Kegiatan</th>
                                                              <th align="center" width="22%">Keterangan Alokasi Inquiry</th>
                                                              <th align="center" width="15%">Limit Budget</th>
                                                              <th align="center" width="15%">Nominal Inquiry</th>
                                                              <th align="center"></th>

                                                          </tr>
                                                      </thead>
                                                      <tbody id="dynamic_fields">

                                                                      </tbody>
                                                                   </table>
                                                 </div>
                                       <br>
                                                    <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Nominal</label>
                                                    <div class="form-group">
                                                    <label for="exampleFormControlInput1">Total Inquiry</label>
                                                    <input type="text" class="form-control" readonly id="sum_edit" style="border-radius: 10px;" name="total_pengajuan" placeholder="">
                                                    </div>
                                                    <div id="tipe_pencairan" style="display:none">


                                                    <div class="form-group">
                                    <label for="exampleFormControlInput1">Tipe Settlement</label>
                                    <!-- <input type="email" class="form-control" id="exampleFormControlInput1" style="border-radius: 10px;" placeholder="name@example.com"> -->
                                    <select class="form-control" id="cek_termin"  style="border-radius: 10px;" name="">
                                     <option value="">Pilih</option>
                                     <option value="Full Payment">Full Payment</option>
                                     <option value="Termin">Termin</option>
                                    </select>
                                    </div>

                                    </div>

                                    <div id="termin">
                                      <table   cellpadding=3 cellspacing=3
                                  align=center width=100%>
                                        <thead>
                                            <tr>
                                                <th align="center" width="5%">No.</th>
                                                <th align="center" width="15%">Presentase Termin</th>
                                                <th align="center" width="15%">Tanggal</th>
                                                <th align="center"></th>

                                            </tr>
                                        </thead>
                                        <tbody id="dynamic_termin">
                                          <tr>
                                            <td>1</td>
                                            <td>
                                              <!-- <input type="text" name="nominal_termin[]" style="border-radius: 10px;" placeholder="Presentase Termin" class="form-control name_termin" /> -->
                                              <div class="input-group">

                                                        <input type="number" name="nominal_termin[]" class="form-control name_termin" style="border-radius: 5px;" id="percent"  onKeyPress="if(this.value.length==3) return false;" value="100"   placeholder="100" aria-describedby="inputGroupPrepend">
     <div class="input-group-prepend">
                                                            <span class="input-group-text" id="inputGroupPrepend" style="border-right: 5px;">%</span>
                                                        </div>

                                                    </div>
                                            </td>
                                            <td>
                                              <input type="date" name="date[]" class="form-control date_termin" style="border-radius: 10px;" value="">
                                            </td>
                                            <td>
                                              <button type="button" name="add" id="adds" class="btn btn-success">+</button>
                                            </td>
                                                        </tbody>
                                                     </table>

                                    </div>
                                                  </div>
                                                  <div class="modal-footer">
                                                      <!-- <button type="button" class="btn btn-danger" data-dismiss="modal">BATALKAN</button> -->
                                                      <button   class="btn btn-primary" type="submit">APPROVED</button>
                                                  </div>
                                              </div>

                                                </div>
                                            </div>
                                        </div>
