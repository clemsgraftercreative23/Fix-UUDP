<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Welcome! Explore all the features and find the easiest way for reimbusement & cash advances. ">
        <meta name="author" content="kutagara">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="shortcut icon" href="access/images/icons/favicon.ico" />
        <!-- Title -->
        <title>UUDP.APP</title>
        <!-- Styles -->
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700,900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">
        <link href="{{asset('assets/plugins/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
        <link href="{{asset('assets/plugins/font-awesome/css/all.min.css')}}" rel="stylesheet">
        <link href="{{asset('assets/plugins/DataTables/datatables.min.css')}}" rel="stylesheet">
        <link href="{{asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
        <!-- Theme Styles -->
        <link href="{{asset('assets/css/connect.min.css?newcache9')}}" rel="stylesheet">
        <link href="{{asset('assets/css/dark_theme.css')}}" rel="stylesheet">
        <link href="{{asset('assets/css/custom.css?newcache18')}}" rel="stylesheet">
        <script src="{{asset('assets/plugins/jquery/jquery-3.4.1.min.js')}}"></script>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

        <style type="text/css">
            .swal-footer {
            text-align: center;
            }
          
            /* Perbaiki tampilan select di iOS & tetap munculkan ikon panah */

            select.form-control {
              -webkit-appearance: none;
              -moz-appearance: none;
              appearance: none;
              padding-right: 2.5rem; /* kasih ruang untuk panah */
              background-image: url("data:image/svg+xml,%3Csvg width='14' height='10' viewBox='0 0 14 10' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l6 6 6-6' stroke='%23666' stroke-width='2' fill='none' fill-rule='evenodd'/%3E%3C/svg%3E");
              background-repeat: no-repeat;
              background-position: right 0.75rem center;
              background-size: 1rem;
            }

            /* Optional: fallback untuk Safari yang suka ngeyel */
            select.form-control:invalid {
              color: #999;
            }


        </style>
        <!-- <link href="https://www.jqueryscript.net/css/jquerysctipttop.css" rel="stylesheet" type="text/css"> -->
        <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.min.css"/> -->


        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        {{-- <div class='loader'>
            <div class='spinner-grow text-primary' role='status'>
                <span class='sr-only'>Loading...</span>
            </div>
        </div> --}}
        <div class="full-loading">
            <center>
                <h5 class="text-white"><i class="fa fa-spinner fa-spin"></i> DALAM PROSES.. MOHON MENUNGGU</h5>
            </center>
        </div>
        <div class="connect-container align-content-stretch d-flex flex-wrap">
