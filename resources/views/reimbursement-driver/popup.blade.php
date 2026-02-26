  <!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
       

        <!-- Styles -->
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700,900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">
        <link href="https://sfi.uudp.app/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://sfi.uudp.app/assets/plugins/font-awesome/css/all.min.css" rel="stylesheet">
        <link href="https://sfi.uudp.app/assets/plugins/DataTables/datatables.min.css" rel="stylesheet">
        <link href="https://sfi.uudp.app/assets/plugins/select2/css/select2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
        <!-- Theme Styles -->
        <link href="https://sfi.uudp.app/assets/css/connect.min.css?newcache9" rel="stylesheet">
        <link href="https://sfi.uudp.app/assets/css/dark_theme.css" rel="stylesheet">
        <link href="https://sfi.uudp.app/assets/css/custom.css?newcache18" rel="stylesheet">
        <script src="https://sfi.uudp.app/assets/plugins/jquery/jquery-3.4.1.min.js"></script>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        
    </head>

<div class="page-content" id="app">
    <div class="row">

    <form method="post" id="sample_form">
    @csrf
    <input type="hidden" name="deletedId" :model="deletedId">
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header border-bottom"  >
              <div class="d-flex justify-content-between w-100">
                    <h2 class="modal-title maintitle clr-green mb-0" id="exampleModalCenterTitle">Edit Reimbursement Driver</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="material-icons">close</i>
                  </button>
                </div>
              </div>

              <div class="modal-body py-3">
              <div class="row my-3"> 
                
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
   <input type="email" class="form-control" id="exampleFormControlInput1" readonly style="border-radius: 10px;" value="{{Auth::user()->idKaryawan}}">
 </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
   <label for="exampleFormControlInput1">Tanggal</label>
   <input type="date" class="form-control date-picker" name="date" id="exampleFormControlInput1" style="border-radius: 10px;" value="">
 </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Department</label>
                      <select name="reimbursement_department_id" id="" class="form-control">
                        @foreach ($department as $item)
                            <option value="{{$item->id}}">{{$item->nama_departemen}}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="exampleFormControlInput1">Remark</label>
                      <input type="text" class="form-control date-picker" name="remark_parent" id="exampleFormControlInput1" style="border-radius: 10px;" value="{{$data['0']->remark}}" required>
                    </div>
                  </div>

                  <hr>
               
                </div>
                <label class="modal-title clr-green" id="exampleModalCenterTitle">Rincian Reimbursement</label>
<div class="respon respon-big table-responsive">
                <table  id="dynamic_field" class="" cellpadding=3 cellspacing=3
            align=center width="1400">
                  <thead>
                      <tr>
                          <th align="center" width="50">No.</th>
                          <th align="center" >Toll</th>
                          <th align="center" >Parking</th>
                          <th align="center" width="15%">Gasoline</th>
                          <th align="center" >Other</th>
                          <th align="center" >Total</th>
                          <th align="center" width="100px">Bukti</th>
                          <th align="center" >Remark</th>
                          <th align="center" >Action</th>

                      </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item,i) in reimburses">
                      <td>@{{i + 1}}</td>
                          <td>
                            <input type="hidden" name="id_detail[]" v-model="item.id">
                            <input type="text" class="form-control amount-toll click-form" @keyup="calculate(i, item)"  name="toll[]" v-model="item.toll" placeholder="Toll">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-parking click-form" @keyup="calculate(i, item)"  name="parking[]" v-model="item.parking" placeholder="Parking">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-gasoline click-form" @keyup="calculate(i, item)"  name="gasoline[]" v-model="item.gasoline" placeholder="Gasoline">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-other click-form" @keyup="calculate(i, item)"  name="others[]" v-model="item.others" placeholder="Other">
                          </td>
                          <td>
                            <input type="text" class="form-control amount-total click-form" @keyup="calculate(i, item)"  name="total[]" readonly v-model="item.subtotal" placeholder="Total">
                          </td>
                          
                          <td class="file-proof">
                            <button type="button" data-idx="1" class="btn btn-success btn-sm addFile">
                              <i class="fa fa-upload"></i>
                            </button>
                            
                            <button type="button" data-idx="1" class="btn btn-success btn-sm addCamera" >
                              <i class="fa fa-camera"></i>
                            </button>
                            <input type="file" accept="image/*" name="file[]"  style="display: none; " class="file-input">
                            <input type="file" accept="image/*" name="proof[]" capture="camera" class="camera-input" style="display: none;">
                            <div id="preview_1"></div>
                          </td>
                          
                          <td>
                            <input type="text" class="form-control" required name="remark[]" v-model="item.remark" placeholder="Remark">
                          </td>
                          <td>
                            <button v-if="i == 0" @click="addReimbursement" type="button" name="add" id="add" class="btn btn-success full-width">+</button>
                            <button v-if="i > 0" @click="removeReimbursement(i)" type="button" name="remove" id="remove" class="btn btn-danger full-width">-</button>
                          </td>
                    </tr>
                  </tbody>
                </table>

</div>
<br>
                <label class="modal-title" id="exampleModalCenterTitle" style="color:green; font-size:10px;">Nominal</label>
                <div class="form-group">
                <label for="exampleFormControlInput1">Total Inquiry</label>
                <input type="text" v-model="grandtotal" class="form-control number-format" id="sum" style="border-radius: 10px;" name="total_pengajuan" readonly placeholder="">
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
</div>
