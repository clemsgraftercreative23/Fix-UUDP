<div class="page-container">
    <div class="page-header" style="background: white;">
        <div class="show-search p-3">
            <form>
                <input type="text" name="search" id="nav-search" placeholder="Search...">
            </form>
        </div>
        <nav class="navbar navbar-expand">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="d-flex justify-content-beetwen w-100">
                <ul class="navbar-nav d-flex align-items-center">
                    <li class="nav-item small-screens-sidebar-link">
                        <a href="#" class="nav-link" style="background-color: #fff;"><i class="material-icons-outlined">menu</i></a>
                    </li>
                    <li class="nav-item">
                        <img src="../../access/images/logo.png" alt="" class="head-logo">
                    </li>
                </ul>
            </div>

            <div class="d-flex justify-content-end w-100">
                <ul class="navbar-nav d-flex align-items-center">
                    <!-- <li class="nav-item small-screens-sidebar-link">
                        <a href="#" class="nav-link"><i class="material-icons-outlined">menu</i></a>
                    </li>
                    <li class="nav-item">
                        <img src="../../access/images/logo.png" alt="" class="head-logo">
                    </li> -->
                    <li class="nav-item d-none d-md-flex align-items-center m-0">
                        <a href="#" class="nav-link d-flex align-items-center" style="background-color: #fff;">
                            <i class="material-icons-outlined">access_alarms</i>
                            <label class="m-0">&nbsp; {{ date('d-m-Y / h:i:s') }} WIB</label>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-flex align-items-center m-0">
                        <a href="#" class="nav-link d-flex align-items-center" style="background-color: #fff;">
                            <label class="m-0">&nbsp; | </label>
                        </a>
                    </li>
                    <li class="nav-item nav-profile dropdown">
                        <a class="nav-link" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: #fff;">
                            <span>Accurate Status: </span>
                            <span class="text-{{ $accurate['status'] ? 'success' : 'danger' }}">{{ $accurate['status'] ? 'Online' : 'Offline' }}</span>
                        </a>
                    </li>
                </ul>
                <div class="d-flex justify-content-end nav-icon">
                    <div class="collapse navbar-collapse" id="navbarNav"></div>
                    <div class="navbar-search d-none d-sm-flex">
                        <!-- <form>
                            <div class="form-group">
                                <input type="text" name="search" id="nav-search" placeholder="Search...">
                            </div>
                        </form> -->
                    </div>
                    <!-- <a href="#" class="search-btn d-flex d-sm-none">
                        <span class="material-icons-outlined">search</span>
                    </a>
                    <a href="#"><span class="material-icons-outlined">notifications</span></a> -->
                </div>
            </div>
        </nav>
    </div>
    <br><br>

    <?php $segment1 = Request::segment(1); ?>
    @if(Auth::user()->jabatan == 'superadmin' || Auth::user()->jabatan == 'karyawan' || Auth::user()->jabatan == 'Direktur Operasional' || Auth::user()->jabatan == 'Finance' || Auth::user()->jabatan == 'Owner')
        <div class="nav-foot d-sm-none">
            <a href="{{ url('home') }}" class="nf-link">
                <span class="material-icons">home</span>
                <label>Home</label>
            </a>
            <a href="{{ url('reimbursement-driver') }}" class="nf-link">
                <span class="material-icons">directions_car</span>
                <label>Driver</label>
            </a>
            <a href="{{ url('reimbursement-travel') }}" class="nf-link">
                <span class="material-icons">card_travel</span>
                <label>Travel</label>
            </a>
            <a href="{{ url('reimbursement-entertaiment') }}" class="nf-link">
                <span class="material-icons">assistant</span>
                <label>Entertainment</label>
            </a>
            <a href="{{ url('profile') }}" class="nf-link">
                <span class="material-icons">account_box</span>
                <label>Profile</label>
            </a>
        </div>
    @endif
