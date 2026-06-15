<!DOCTYPE html>
<html lang="en">
<head>
	<title>Login Area | UUDP.APP</title>
  <meta name="description" content="Welcome Back! Explore all the features and find the easiest way for reimbusement & cash advances. ">
  <meta name="author" content="kutagara">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="icon" type="image/png" href="access/images/icons/favicon.ico"/>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="access/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="access/fonts/Linearicons-Free-v1.0.0/icon-font.min.css">
	<link rel="stylesheet" type="text/css" href="access/vendor/animate/animate.css">
	<link rel="stylesheet" type="text/css" href="access/vendor/css-hamburgers/hamburgers.min.css">
	<link rel="stylesheet" type="text/css" href="access/vendor/animsition/css/animsition.min.css">
	<link rel="stylesheet" type="text/css" href="access/vendor/select2/select2.min.css">
	<link rel="stylesheet" type="text/css" href="access/vendor/daterangepicker/daterangepicker.css">
	<link rel="stylesheet" type="text/css" href="access/css/util.css">
	<link rel="stylesheet" type="text/css" href="access/css/main.css">
</head>
<body class="login-page-body">
 	<style>
		:root {
			--uudp-green: #28a745;
			--uudp-green-dark: #1e7e34;
			--uudp-navy: #1a2b3c;
		}
		.login-page-body {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
			background: #f5f8f6;
			min-height: 100%;
		}
		html, body {
			position: relative;
			height: 100%;
		}
		.limiter {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 1rem;
		}
		/* Splash (welcome) — uses prepared v2 artwork */
		#lanjut.splash-lanjut {
			width: 100%;
			max-width: 1200px;
			margin: 0 auto;
			background: #fff;
			border-radius: 1rem;
			box-shadow: 0 12px 40px -12px rgba(26, 43, 60, 0.15);
			overflow: hidden;
			cursor: pointer;
		}
		.splash-inner {
			position: relative;
			line-height: 0;
		}
		.splash-art {
			width: 100%;
			height: auto;
			display: block;
		}
		/* Optional hit area if artwork already shows “Next” — keeps tap/click obvious */
		.splash-hit-hint {
			position: absolute;
			bottom: 4%;
			right: 4%;
			left: auto;
			width: min(220px, 42%);
			height: min(56px, 9%);
			min-height: 44px;
			border-radius: 999px;
			background: transparent;
		}
		@media (max-width: 767px) {
			#lanjut.splash-lanjut {
				border-radius: 0.75rem;
			}
		}
		/* Form step — disembunyikan sampai splash diklik */
		#bg.login-form-panel {
			width: 100%;
			max-width: 1000px;
			margin: 0 auto;
			background: #fff;
			overflow: hidden;
			border-radius: 1rem;
			box-shadow: 0 12px 40px -12px rgba(26, 43, 60, 0.12);
			display: none;
			flex-wrap: wrap;
			align-items: stretch;
			flex-direction: row-reverse;
		}
		#bg .brand-side {
			background: linear-gradient(160deg, var(--uudp-navy) 0%, #0d3d2a 45%, var(--uudp-green) 100%);
			background-size: cover;
			min-height: 280px;
		}
		.password {
			position: relative;
		}
		.password .password-toggle {
			display: none;
			right: 12px;
			position: absolute;
			top: 50%;
			transform: translateY(-50%);
			cursor: pointer;
			color: #6c757d;
			line-height: 1;
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
			line-height: 1.428571429;
			border-radius: 15px;
		}
	 </style>
	<div class="limiter">
		<div class="container-login100 w-100 p-0" style="background: transparent;">
			<div id="lanjut" class="splash-lanjut"@if($errors->any()) style="display:none"@endif role="button" tabindex="0" aria-label="Lanjut ke halaman login">
				<div class="splash-inner">
					<img class="splash-art" src="{{ asset('assets/images/v2.png') }}" alt="Selamat datang di UUDP — PT Sumitomo Forestry Indonesia">
					<span class="splash-hit-hint" aria-hidden="true"></span>
				</div>
			</div>

			<div id="bg" class="login-form-panel"@if($errors->any()) style="display:flex"@endif>
				<div class="row no-gutters w-100 m-0">
					<div class="col-md-6 order-md-last p-0">
						<div class="p-3 p-md-4 d-flex justify-content-between align-items-start brand-side h-100">
							<div>
								<p class="small mb-1 text-white" style="opacity: 0.85;">Welcome to</p>
								<h4 class="text-white font-weight-bold mb-0" style="font-size: 1rem; letter-spacing: 0.02em;">PT SUMITOMO</h4>
								<h4 class="text-white font-weight-bold mb-0" style="font-size: 1rem; color: #a8e6c1;">FORESTRY INDONESIA</h4>
								<p class="small mt-3 mb-0 text-white" style="opacity: 0.75;">UUDP — Cash Advance App</p>
							</div>
							<button type="button" id="login" class="btn btn-light btn-circle flex-shrink-0" title="Kembali" aria-label="Kembali ke layar sambutan">
								<i class="fa fa-times text-success" aria-hidden="true"></i>
							</button>
						</div>
					</div>
					<div class="col-md-6">
						<div class="p-4 p-md-5">
							<form id="myForm1" method="POST" action="{{ route('login') }}" autocomplete="on">
								@csrf
								<h4 class="mb-1 font-weight-bold" style="color: var(--uudp-navy);">Welcome back</h4>
								<p class="text-muted small mb-4">Log in to access our features</p>
								<div class="row">
									<div class="col-md-12">
										<label for="username">Username / NIP</label>
										<div class="form-group">
											<div class="validate-input m-b-20" data-validate="Type user name">
												<input id="username" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" autocomplete="username" required placeholder="Enter your NIP or username">
											</div>
											@error('username')
												<span class="invalid-feedback d-block" role="alert">
													<strong>{{ $message }}</strong>
												</span>
											@enderror
										</div>
									</div>
									<div class="col-md-12">
										<label for="passwordfield">Password</label>
										<div class="password validate-input m-b-20" data-validate="Type password">
											<input class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" id="passwordfield" type="password" placeholder="Enter your password">
											<button type="button" class="password-toggle btn btn-link p-0 border-0" aria-label="Tampilkan password" tabindex="-1">
												<i class="fa fa-eye" aria-hidden="true"></i>
											</button>
											@error('password')
												<span class="invalid-feedback" role="alert">
													<strong>{{ $message }}</strong>
												</span>
											@enderror
										</div>
									</div>
									<div class="col-12 mt-2">
										<button class="btn btn-success w-100 py-2 font-weight-bold" style="background: var(--uudp-green); border-color: var(--uudp-green-dark);" type="submit">
											Login
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="dropDownSelect1"></div>

	<script src="access/vendor/jquery/jquery-3.2.1.min.js"></script>
	<script src="access/vendor/animsition/js/animsition.min.js"></script>
	<script src="access/vendor/bootstrap/js/popper.js"></script>
	<script src="access/vendor/bootstrap/js/bootstrap.min.js"></script>
	<script src="access/vendor/select2/select2.min.js"></script>
	<script>
		$(".selection-2").select2({
			minimumResultsForSearch: 20,
			dropdownParent: $('#dropDownSelect1')
		});
	</script>
	<script src="access/vendor/daterangepicker/moment.min.js"></script>
	<script src="access/vendor/daterangepicker/daterangepicker.js"></script>
	<script src="access/vendor/countdowntime/countdowntime.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
	<script src="access/js/main.js"></script>
<script>
	function showLoginForm() {
		document.getElementById("lanjut").style.display = "none";
		document.getElementById("bg").style.display = "flex";
		document.getElementById("username").focus();
	}
	function showSplash() {
		document.getElementById("lanjut").style.display = "block";
		document.getElementById("bg").style.display = "none";
	}
	$('#lanjut').on('click keypress', function(e) {
		if (e.type === 'keypress' && e.which !== 13 && e.which !== 32) return;
		e.preventDefault();
		showLoginForm();
	});
	$('#login').on('click', function(e) {
		e.stopPropagation();
		showSplash();
	});
	$(document).ready(function(){
		var currForm1 = document.getElementById('myForm1');
		currForm1.addEventListener('submit', function(event) {
			document.getElementById('passwordfield').setAttribute('type', 'password');
			if (currForm1.checkValidity() === false) {
				event.preventDefault();
				event.stopPropagation();
			}
			currForm1.classList.add('was-validated');
		}, false);
		currForm1.querySelectorAll('#username').forEach(function(input) {
			input.addEventListener('input', function() {
				if (input.checkValidity()) {
					input.classList.remove('is-invalid');
					input.classList.add('is-valid');
				} else {
					input.classList.remove('is-valid');
					input.classList.add('is-invalid');
				}
				var is_valid = $('.form-control').length === $('.form-control.is-valid').length;
				$("#submitBtn").attr("disabled", !is_valid);
			});
		});
	});
</script>
<script>
$("#passwordfield").on("keyup", function(){
	if ($(this).val()) {
		$(".password .password-toggle").show();
	} else {
		$(".password .password-toggle").hide();
		$("#passwordfield").attr("type", "password");
	}
});
$(".password .password-toggle").on("click", function(e){
	e.preventDefault();
	var input = $("#passwordfield");
	var show = input.attr("type") === "password";
	input.attr("type", show ? "text" : "password");
	$(this).attr("aria-label", show ? "Sembunyikan password" : "Tampilkan password");
});
</script>
</body>
</html>
