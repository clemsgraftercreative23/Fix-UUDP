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
                          background: url({{asset('assets/images/profile-bg-uudp.jpg')}}) no-repeat;
                          /* box-shadow: 0 4px 8px 0 rgb(0 0 0 / 95%), 9px 0px 20px 0 rgb(0 0 0); */
                          background-size: cover;
                          border-radius:10px;
                          z-index: -3;">
                          <br>
                          @foreach($karyawan as $g)
                          <form method="POST" action="{{url('karyawan/'.$g->id)}}">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col">
                                    <div class="profile-img" style=" position: relative; float: left; margin-left: 30px; margin-top: 0px;">
                                        <img src="{{asset('assets/images/avatars/profile-image-uudp.png')}}">
                                        
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
                                                    
                                                    @if(session()->has('success'))
                                                        <div class="alert alert-success">
                                                            {{ session()->get('success') }}
                                                        </div>
                                                    @endif
                                                    @if ($errors->any())
                                                        @foreach ($errors->all() as $error)
                                                            <div class="alert alert-danger">
                                                                {{ $error }}
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                    <table class="table cst-table">
                                                      <tr>
                                                        <td class="icon-row"> 
                                                          <span class="material-icons">account_circle</span><strong>Username</strong></td> <td><input type="text" value="{{$g->username}}" name="username" class="form-control">

                                                        </td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">account_box</span><strong>Name</strong></td> <td><input type="text" value="{{$g->name}}" name="name" class="form-control"></td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">account_box_outline</span><strong>Employee ID</strong></td> <td><input type="text" value="{{$g->idKaryawan}}" readonly class="form-control"></td>
                                                      </tr>
                                                      
                                                      <tr>
                                                        <td class="icon-row"> <span class="material-icons">chrome_reader_mode</span><strong>NIK</strong></td> <td><input type="text" value="{{$g->nik}}" name="nik" class="form-control"></td>
                                                        </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">alternate_email</span><strong>Email</strong></td> <td><input type="text" value="{{$g->email}}" name="email" class="form-control"></td>
                                                      </tr>

                                                      <tr>
                                                        <td class="icon-row"> <span class="material-icons">phone</span><strong>Handphone</strong></td> <td><input type="text" value="{{$g->phoneNumber}}" name="phoneNumber" class="form-control"></td>
                                                        </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">account_balance</span><strong>Bank Name</strong></td> <td><input type="text" value="{{$g->bankName}}" name="bankName" class="form-control"></td>
                                                      </tr>
                                                      <tr>
                                                      <td class="icon-row"> <span class="material-icons">account_balance_wallet</span><strong>Bank Number</strong></td> <td><input type="text" value="{{$g->bankAccount}}" name="bankAccount" class="form-control"></td>
                                                      </tr>
                                                      <tr>
                                                        <td class="icon-row"> <span class="material-icons">credit_card</span><strong>NPWP Number</strong></td> <td><input type="text" value="{{$g->npwpNo}}" name="npwpNo" class="form-control"></td>
                                                      </tr>
                                                      <td class="icon-row"> <span class="material-icons">account_balance</span><strong>Department</strong></td> <td>
                                                        <select name="departmentId" id="" class="form-control">
                                                          @foreach (\App\Departemen::get() as $item)
                                                              <option value="{{$item->id}}" @if($g->departmentId == $item->id) selected @endif>{{$item->nama_departemen}}</option>
                                                          @endforeach
                                                        </select>
                                                      </td>
                                                      </tr>
                                                      
                                                      <td class="icon-row"> <span class="material-icons">directions_car_filled</span><strong>Vehicle Number</strong></td> <td><input type="text" value="{{$g->vehicleNo}}" name="vehicleNo" class="form-control"></td>
                                                      </tr>
                                                    </table>
                                                </div>
                                               
                                                <div class="pb-3">
                                                <hr>
                                                </div>
                                                <h4  class="lead">Change Password</h4>
                                                <div class="post-body">
                                                  
                                                      <input type="hidden" name="id" value="{{$g->id}}">
                                                      <div class="row">
                                                          <div class="col">
                                                              <input type="password" name="password" class="form-control" placeholder="Enter new password" id="pass" >
                                                          </div>
                                                          <div class="col">
                                                              <input type="password" name="repassword" class="form-control" placeholder="Confirm password" id="pass2" >
                                                          </div>
                                                      </div>
                                                      <br>
                                                      <center>
                                                        <button type="submit" class="edit btn btn-primary btn-lg btn-block" name="action_button" id="">Save</button>
                                                      </center>
                                                </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                  </form>
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
                    url:"karyawan/update",
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
                  var html = '';

                  if(data.errors)
                  {
                    alert('Data gagal disimpan!');
                  }
                  if(data.success)
                  {
                    $('#sample_form')[0].reset();
                    alert('Changes saved successfully!');
                    location.reload();
                  }

                  },
                  complete: function(){
                    $('.loader').css("visibility", "hidden");
                  }
                  })
                });

                </script>

                @endpush
                @endsection
