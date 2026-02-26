@extends('template.app')

@section('content')
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

<div class="page-content">
                    <div class="main-wrapper">
                        <div class="profile-header" style="
                          top: 0;
                          left: 0;
                          content: '';
                          background: url({{asset('assets/images/profile-bg.jpg')}}) no-repeat;
                          box-shadow: 0 4px 8px 0 rgb(0 0 0 / 95%), 9px 0px 20px 0 rgb(0 0 0);
                          background-size: cover;
                          z-index: -3;">
                          @foreach($karyawan as $g)
                            <div class="row">
                                <div class="col">
                                    <div class="profile-img" style=" position: relative; float: left; margin-left: 30px; margin-top: 0px;">
                                        <img src="{{asset('assets/images/avatars/profile-image-1.png')}}">
                                    </div>
                                    <div class="profile-name">
                                        <h1>{{$g->name}}</h1>
                                    </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-content">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="post">
                                                <div class="post-header">
                                                    <div class="post-info p-0">
                                                         <h4 class="lead">Profile Detail</h4>
                                                    </div>

                                                </div>
                                                <div class="post-body"> 
                                                    
                                                    <table class="table cst-table">
                                                      <tr>
                                                        <td class="icon-row"> 
                                                          <span class="material-icons">account_circle</span><strong>NIP</strong></td> <td>{{$g->idKaryawan}}
                                                        </td>
                                                      </tr>
                                                      <tr>
                                                        <td class="icon-row"> 
                                                          <span class="material-icons">account_circle</span><strong>Username</strong></td> <td>{{$g->username}}
                                                        </td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">account_box</span><strong>Name</strong></td> <td>{{$g->name}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">fact_check</span><strong>Position</strong></td> <td>{{$g->jabatan}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">chrome_reader_mode</span><strong>NIK</strong></td> <td>{{$g->nik}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">alternate_email</span><strong>Email</strong></td> <td>{{$g->email}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">phone</span><strong>Handphone</strong></td> <td>{{$g->phoneNumber}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">date_range</span><strong>Join Date</strong></td> <td>{{$g->joinDate}}</td>
                                                      </tr>

                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">account_balance</span><strong>Bank Name</strong></td> <td>{{$g->bankName}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">account_balance_wallet</span><strong>Bank Number</strong></td> <td>{{$g->bankAccount}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">credit_card</span><strong>NPWP Number</strong></td> <td>{{$g->npwpNo}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">verified</span><strong>Empolyee Status</strong></td> <td>{{$g->employeeWorkStatus}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">account_balance</span><strong>Department</strong></td> <td>{{$g->nama_departemen}}</td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">directions_car_filled</span><strong>Vehicle No</strong></td> <td>{{$g->vehicleNo}}</td>
                                                      </tr>
                                                    </table>
                                                </div>
                                               
                                                <div class="pb-3">
                                                <hr>
                                                </div>
                                                <h4  class="lead">Change Password</h4>
                                                <div class="post-body">
                                                  <form action="{!!url('update-karyawan')!!}" method="POST">
                                                      @csrf
                                                      <input type="hidden" name="id" value="{{$g->id}}">
                                                      <div class="row">
                                                          <div class="col">
                                                              <input type="password" name="password" class="form-control" placeholder="Enter new password" id="pass">
                                                          </div>
                                                          <div class="col">
                                                              <input type="password" name="repassword" class="form-control" placeholder="Confirm password" id="pass2">
                                                          </div>
                                                      </div>
                                                      <br>
                                                      <div class="row">
                                                          <div class="col">
                                                              <select class="form-control" name="id_approval" id="id_approval" required>
                                                                  <option value="">-Select Approval-</option>
                                                                  @foreach($approval as $data)
                                                                  <option value="{{$data->id}}" @if($g->id_approval==$data->id) selected @endif>{{$data->name}}</option>
                                                                  @endforeach
                                                              </select>
                                                          </div>
                                                          <div class="col">
                                                              <select class="form-control" name="departmentId" id="departmentId" required>
                                                                  <option value="">-Select Departemen-</option>
                                                                  @foreach($departemen as $data)
                                                                  <option value="{{$data->id}}" @if($g->departmentId==$data->id) selected @endif>{{$data->nama_departemen}}</option>
                                                                  @endforeach
                                                              </select>
                                                          </div>
                                                          <div class="col">
                                                              <input type="text" name="vehicleNo" class="form-control" placeholder="Nomor Plat Kendaraan" id="vehicleNo" value="{{$g->vehicleNo}}">
                                                          </div>
                                                      </div>
                                                      <br>
                                                      <center>
                                                      <button type="submit" class="edit btn btn-primary btn-lg btn-block" name="action_button" id="">Save</button>
                                                      </center>
                                                  </form>
                                                </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                              @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @push('scripts')

                <script type="text/javascript">
                  $(document).ready(function(){
                      $('#pass2').focusout(function(){
                          var pass = $('#pass').val();
                          var pass2 = $('#pass2').val();
                          if(pass != pass2){
                              alert('The password combination you entered is not the same!');
                              location.reload();
                          }

                      });
                  });

                  $('#sample_form').on('submit', function(event){
                  event.preventDefault();
                  $("#action_button").prop("disabled", true);
                  $.ajax({
                    url:"../../update-karyawan",
                    method:"POST",
                    data: new FormData(this),
                    contentType: false,
                    cache:false,
                    processData: false,
                    dataType:"json",
                    beforeSend: function(){
                        $('.loader').css("visibility", "visible");
                        $("#action_button").prop("disabled", true);
                    },
                    success:function(data)
                    {
                      window.location.href = "../../";
                    },
                    complete: function(){
                        $('.loader').css("visibility", "hidden");
                    }
                  })
                });

                </script>

                @endpush
                @endsection
