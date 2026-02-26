@extends('template.app')
@section('content')

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
<script src="https://cdn.rawgit.com/igorescobar/jQuery-Mask-Plugin/1ef022ab/dist/jquery.mask.min.js"></script>

<div class="page-content">

  <div class="row">
    <div class="col-xl">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">KETERANGAN PERTANGGUNGJAWABAN</h5><hr>
                <p>Untuk saat ini, karyawan yang bersangkutan belum melaporkan pertanggungjawaban.</p><hr>
            </div>
        </div>
    </div>
</div>




  
@push('scripts')

@endpush
@endsection
