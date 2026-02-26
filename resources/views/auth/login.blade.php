<!DOCTYPE html>
<html lang="en">
<head>
	<title>Login Area | UUDP.APP</title>
  <meta name="description" content="Welcome Back! Explore all the features and find the easiest way for reimbusement & cash advances. ">
  <meta name="author" content="kutagara">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->
	<link rel="icon" type="image/png" href="access/images/icons/favicon.ico"/>
<!--===============================================================================================-->
	<!-- <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css"> -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<!-- <link href="https://netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="https://netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script> -->


<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="access/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="access/fonts/Linearicons-Free-v1.0.0/icon-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="access/vendor/animate/animate.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="access/vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="access/vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="access/vendor/select2/select2.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="access/vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="access/css/util.css">
	<link rel="stylesheet" type="text/css" href="access/css/main.css">
<!--===============================================================================================-->
</head>
<body>
 	<style>

		body{background: #eee url(https://subtlepatterns.com/patterns/sativa.png);}
html,body{
    position: relative;
    height: 100%;
}

.login-container{
    position: relative;
    width: 300px;
    margin: 80px auto;
    padding: 20px 40px 40px;
    text-align: center;
    background: #fff;
    border: 1px solid #ccc;
}

#output{
    position: absolute;
    width: 300px;
    top: -75px;
    left: 0;
    color: #fff;
}

#output.alert-success{
    background: rgb(25, 204, 25);
}

#output.alert-danger{
    background: rgb(228, 105, 105);
}


.login-container::before,.login-container::after{
    content: "";
    position: absolute;
    width: 100%;height: 100%;
    top: 3.5px;left: 0;
    background: #fff;
    z-index: -1;
    -webkit-transform: rotateZ(4deg);
    -moz-transform: rotateZ(4deg);
    -ms-transform: rotateZ(4deg);
    border: 1px solid #ccc;

}

.login-container::after{
    top: 5px;
    z-index: -2;
    -webkit-transform: rotateZ(-2deg);
     -moz-transform: rotateZ(-2deg);
      -ms-transform: rotateZ(-2deg);

}

.avatar{
    width: 100px;height: 100px;
    margin: 10px auto 30px;
    border-radius: 100%;
    border: 2px solid #aaa;
    background-size: cover;
}

.form-box input{
    width: 100%;
    padding: 10px;
    height:40px;
    border: 1px solid #ccc;;
    background: #fafafa;
    transition:0.2s ease-in-out;

}

.form-box input:focus{
    outline: 0;
    background: #eee;
}

.form-box input[type="text"]{
    border-radius: 5px 5px 0 0;
    text-transform: lowercase;
}

.form-box input[type="password"]{
    border-radius: 0 0 5px 5px;
    border-top: 0;
}

.form-box button.login{
    margin-top:15px;
    padding: 10px 20px;
}

.animated {
  -webkit-animation-duration: 1s;
  animation-duration: 1s;
  -webkit-animation-fill-mode: both;
  animation-fill-mode: both;
}

@-webkit-keyframes fadeInUp {
  0% {
    opacity: 0;
    -webkit-transform: translateY(20px);
    transform: translateY(20px);
  }

  100% {
    opacity: 1;
    -webkit-transform: translateY(0);
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  0% {
    opacity: 0;
    -webkit-transform: translateY(20px);
    -ms-transform: translateY(20px);
    transform: translateY(20px);
  }

  100% {
    opacity: 1;
    -webkit-transform: translateY(0);
    -ms-transform: translateY(0);
    transform: translateY(0);
  }
}

.fadeInUp {
  -webkit-animation-name: fadeInUp;
  animation-name: fadeInUp;
}


.password{
    position: relative;
}

.password input[type="password"]{
    padding-right: 30px;
}

.password .glyphicon,#password2 .glyphicon,

.password .fa,#password2 .fa  {
    display:none;
    right: -165px;
    position: absolute;
    top: 12px;
    cursor:pointer;
}
.btn-circle.btn-lg {
  width: 50px;
  height: 50px;
  
  padding: 0 !important;
  font-size: 18px;
  line-height: 1.33;
  border-radius: 25px;
}
.btn-circle {
  width: 30px;
    height: 30px;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    aspect-ratio: 1;
    object-fit: cover;
    padding: 0 !important;
    /* font-size: 13px; */
    line-height: 1.428571429;
    border-radius: 15px;
}

	 </style>
	<div class="limiter">
		<div class="container-login100">
      <div id="lanjut" class="p-3 p-md-5 box-login" style="width: 1000px;
			background-image: url('access/images/main-header.png');
			overflow: hidden;
			display: -webkit-box;
			display: -webkit-flex;
			display: -moz-box;
			display: -ms-flexbox;
			display: flex;
			flex-wrap: wrap;
			align-items: stretch;
			flex-direction: row-reverse;
      height: 500px; display: block;
      box-shadow: 0px 10px 15px -3px rgba(0,0,0,0.1);
      border-radius:1rem;">
          <div class="row" >
          <div class="col-12 col-md-8 col-lg-7 bl-left"  > 
              <div class="py-4"></div>
              <div class="p-3 text-white text-center text-md-left">
              <p class="fs-20 text-white">Welcome to</p>
              <h1>PT SUMITOMO FORESTRY INDONESIA
              </h1>
              </div>              
          </div>
          <div class="col-12 col-md-4 col-lg-5 bl-right text-center text-md-right d-flex align-items-md-end justify-content-center">
              <button type="button"   class="btn btn-success btn-sm w-100 shadow" style="max-width:240px">Next</button>
            </div>
          </div>
    </div>

    <div id="bg" style="width: 1000px;
    background: #fff;
    overflow: hidden;
    flex-wrap: wrap;
    border-radius:1rem;
    align-items: stretch;
    flex-direction: row-reverse;
    height: 600px; display: none;">
    <div class="row">
      <div class="col-md-6 order-md-last">
        <div class="p-3 p-md-5 d-flex justify-content-between" style="background-image: url('access/images/main-header.png'); background-size:cover; height:100%;"> 
              <h4 class="text-white my-3"> PT SUMITOMO FORESTRY INDONESIA</h4>
              <button type="button" id="login"  class="my-3 btn btn-success btn-circle"><i class="fa fa-times" aria-hidden="true"></i></button>
 

        </div>

      </div>
      <div class="col-md-6">
      <div class="p-3 p-md-5">
      <form id="myForm1"  method="POST" action="{{ route('login') }}">
            @csrf
          
          <h4 class="my-3">
          Welcome back,<br>
          <p>Log in to access our features</p>
          </h4>
          
          <br>
					<!-- <span class="login100-form-title p-b-34">
						Halaman Login UUDP
					</span> -->
          <div class="row">
						<div class="col-md-12">
							<label for="exampleInputEmail1">Username / NIP </label>
							<div class="form-group">
							<div class="validate-input m-b-20" data-validate="Type user name">
              <!-- <div class="wrap-input100 rs1-wrap-input100 validate-input m-b-20" data-validate="Type user name"> -->
								<input id="username" class="form-control"  @error('email') is-invalid @enderror" name="username" value="{{ old('email') }}" autocomplete="email" required placeholder="Enter your NIP or username">
              </div>
              @error('email')
              <span class="invalid-feedback" role="alert">
                  <strong>{{ $message }}</strong>
              </span>
          @enderror
						</div>
						</div>
						<div class="col-md-12">
							<label for="exampleInputEmail1">Password</label>
							<div class="password  validate-input m-b-20" data-validate="Type user name">
                <!-- <div class="password wrap-input100 rs1-wrap-input100 validate-input m-b-20" data-validate="Type user name"> -->
								<input class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" id="passwordfield"  type="password"  placeholder="Enter your password">
								<i class="fa fa-eye" aria-hidden="true"></i>
                @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
							</div>
					</div>
          <div class="col-12">
            <button class="btn btn-success btn-sm w-100" type="submit">
                Login
              </button>
          </div>

					<!-- <div class="container-login100-form-btn">
						<button class="login100-form-btn" type="submit">
							Sign in
						</button>
            
					</div> -->
</form>
      </div>
      </div>
    </div>
            <!-- <div class="login100-more text-left" style="background-image: url('access/images/main-header.png');">
              <br>
              <p style="font-size: 20px; color: white;">&nbsp;&nbsp;&nbsp;&nbsp;Pt Wildan J Saputra .tbk
                <button type="button" id="login" style="margin-left: 200px;" class="btn btn-success btn-circle"><i class="fa fa-times" aria-hidden="true"></i></button>

              </p>

            </div>

      <form id="myForm1" style="width: 50%;
      display: -webkit-box;
      display: -webkit-flex;
      display: -moz-box;
      display: -ms-flexbox;
      display: flex;
      flex-wrap: wrap;
      padding: 123px 65px 40px 65px; " method="POST" action="{{ route('login') }}">
            @csrf
            		<br><br>
					<span class="login100-form-title p-b-34">
						Halaman Login UUDP
					</span>
          <div class="row">
						<div class="col-md-12">
							<label for="exampleInputEmail1">NIK </label>
							<div class="form-group">
							<div class="validate-input m-b-20" data-validate="Type user name">
             
								<input id="username" class="form-control"  @error('email') is-invalid @enderror" name="username" value="{{ old('email') }}" autocomplete="email" required placeholder="Masukkan NIK Anda">
              </div>
              @error('email')
              <span class="invalid-feedback" role="alert">
                  <strong>{{ $message }}</strong>
              </span>
          @enderror
						</div>
						</div>
						<div class="col-md-12">
							<label for="exampleInputEmail1">Password</label>
							<div class="password  validate-input m-b-20" data-validate="Type user name">
               
								<input class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" id="passwordfield"  type="password"  placeholder="Masukkan Password Anda">
								<i class="fa fa-eye" aria-hidden="true"></i>
                @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
							</div>
					</div>

          <div class="col-12">
            <button class="btn btn-success btn-sm w-100" type="submit">
                Sign in
              </button>
          </div>

				 
</form> -->
			</div>
		</div>
	</div>



	<div id="dropDownSelect1"></div>

<!--===============================================================================================-->
	<script src="access/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="access/vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="access/vendor/bootstrap/js/popper.js"></script>
	<script src="access/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="access/vendor/select2/select2.min.js"></script>
	<script>
		$(".selection-2").select2({
			minimumResultsForSearch: 20,
			dropdownParent: $('#dropDownSelect1')
		});
	</script>
<!--===============================================================================================-->
	<script src="access/vendor/daterangepicker/moment.min.js"></script>
	<script src="access/vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="access/vendor/countdowntime/countdowntime.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>

<!--===============================================================================================-->
	<script src="access/js/main.js"></script>
<script>
	$('#lanjut').click(function(){

var xq = document.getElementById("lanjut");
xq.style.display = "none";
// var xs = document.getElementById("login");
// xs.style.display = "block";
var bg = document.getElementById("bg");
bg.style.display = "-webkit-box";
bg.style.display = "-webkit-flexock";
bg.style.display = "-moz-box";
bg.style.display = "-ms-flexbox";
bg.style.display = "flex";

var form = document.getElementById("myForm1");
form.style.display = "block";


})
$('#login').click(function(){

var xq = document.getElementById("lanjut");
xq.style.display = "block";

var bg = document.getElementById("bg");
bg.style.display = "none";



})
	$(document).ready(function(){

      let currForm1 = document.getElementById('myForm1');
        // Validate on submit:
        currForm1.addEventListener('submit', function(event) {
          if (currForm1.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
          }
          currForm1.classList.add('was-validated');
        }, false);
        // Validate on input:
        currForm1.querySelectorAll('#username').forEach(input => {
          input.addEventListener(('input'), () => {
            if (input.checkValidity()) {
              input.classList.remove('is-invalid')
              input.classList.add('is-valid');
            } else {
              input.classList.remove('is-valid')
              input.classList.add('is-invalid');
            }
            var is_valid = $('.form-control').length === $('.form-control.is-valid').length;
            $("#submitBtn").attr("disabled", !is_valid);
          });
        });
      });
</script>
<script>

$("#passwordfield").on("keyup",function(){
    if($(this).val())
        $(".fa.fa-eye").show();
    else
        $(".fa.fa-eye").hide();
    });
$(".fa.fa-eye").mousedown(function(){
                $("#passwordfield").attr('type','text');
            }).mouseup(function(){
            	$("#passwordfield").attr('type','password');
            }).mouseout(function(){
            	$("#passwordfield").attr('type','password');
            });
</script>
</body>
</html>
