<div class="modal fade bd-example-modal-xl" tabindex="-1" id="formModaledit"  role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
  <form method="post" id="sample_edit"  enctype="multipart/form-data">
  @csrf
                                            <div class="modal-dialog modal-xl">
                                                <div class="modal-content">
                                                  <div class="modal-header"  >
<input type="hidden" name="id_pengajuan" id="id_pengajuan" value="">
<input type="hidden" name="action" id="action" />
<input type="hidden" name="maxcheck" id="maxcheck" value="">
                                                    

                                                    

                                                  </div>
 

                                                  <div class="modal-body">
                                                  <div class="row">
                                                      <div class="col-md-12 mb-3 d-flex justify-content-between">
                                                        <h2 class="modal-title clr-green" id="lbltipAddedComment"  ></h2>
                                                        <button type="button" class="close" onClick="window.location.reload();" data-dismiss="modal" aria-label="Close">
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
                                                            <label for="inputEmail3" class="col-sm-5 col-form-label">Nomor 1Pengajuan</label>
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
                                       <input type="hidden" name="max" id="max" value="100">
                                       </div>
                                                      </div>
                                                    </div>
                                                    <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Inquiry</label>
<div class="respon respon-big table-responsive">
                                                    <table   cellpadding=3 cellspacing=3
                                                align=center width=100%>
                                                      <thead>
                                                          <tr>
                                                              <th align="center" width="3%">No.</th>
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
<hr>

                                    <div id="termin">
                                      <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Termin Settlement</label>
                                      <div class="respon respon-100 table-responsive">
                                      <table class=""    
                                  align=center width=100%>
                                        <thead>
                                            <tr>
                                                <!-- <th align="center" width="5%">No.</th> -->
                                                <th align="center" >Presentase Termin</th>
                                                <th align="center"  >Tanggal</th>
                                                <th align="center"><button type="button" name="add" id="adds" class="btn btn-success">+</button></th>

                                            </tr>
                                        </thead>
                                        <tbody id="dynamic_termin">

                                        </tbody>
                                     </table>
                                      </div>

                                    </div>
                                                  </div>
                                                  <div   class="modal-footer" >
                                                    <div id="foo" >
                                                      <button type="button" class="btn btn-danger" onClick="window.location.reload();" data-dismiss="modal">Kembali</button>
                                                      <button   class="btn btn-primary" type="submit">Update</button>

                                                    </div>
                                                    <div id="foot">
                                                      <button type="button" class="btn btn-danger" onClick="window.location.reload();" data-dismiss="modal">Kembali</button>
                                                    </div>
                                                    <div id="footer">
                                                      <!-- <button class="btn btn-primary"   type="submit">PUSH SISA PENGAJUAN</button> -->
                                                    </div>
                                                  </div>

                                              </div>

                                                </div>
                                            </div>
                                        </div>
